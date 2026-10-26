<?php
/**
 * Livrare la FANbox (locker FAN Courier), ca pe dkw.ro:
 *  - metoda de livrare „FANbox” (flat rate cu „FANbox” în titlu); gratuită la comenzi peste 250 lei;
 *  - clientul scrie FANbox-ul ales (cu link spre lista de FANbox-uri);
 *  - la FANbox nu se poate plăti ramburs;
 *  - FANbox-ul apare în comandă, în emailuri, în contul clientului și la furnizor (dkw).
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

const P3D_FANBOX_LIST = 'https://www.fanbox.ro/ro#locations';

function p3d_fanbox_free_min() {
	return (float) apply_filters( 'p3d_fanbox_free_min', 250 );
}

/** Rata de livrare (ex. „flat_rate:4”) e FANbox? */
function p3d_is_fanbox_rate( $rate_id ) {
	static $cache = array();
	if ( isset( $cache[ $rate_id ] ) ) {
		return $cache[ $rate_id ];
	}
	$parts = explode( ':', (string) $rate_id );
	$ok    = false;
	if ( isset( $parts[1] ) ) {
		$m  = WC_Shipping_Zones::get_shipping_method( (int) $parts[1] );
		$ok = $m && false !== stripos( (string) $m->get_title(), 'fanbox' );
	}
	return $cache[ $rate_id ] = $ok;
}

function p3d_fanbox_chosen() {
	$chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	foreach ( $chosen as $c ) {
		if ( $c && p3d_is_fanbox_rate( $c ) ) {
			return true;
		}
	}
	return false;
}

/** Metoda e pentru filamente (livrate de furnizor)? După titlu: „FANbox” sau „FAN Courier”. */
function p3d_is_fil_method_rate( $rate_id ) {
	$parts = explode( ':', (string) $rate_id );
	if ( ! isset( $parts[1] ) ) {
		return false;
	}
	$m = WC_Shipping_Zones::get_shipping_method( (int) $parts[1] );
	return $m && preg_match( '/fanbox|fan\s*courier/i', (string) $m->get_title() );
}

function p3d_is_fil_product( $product_id ) {
	return '' !== (string) get_post_meta( $product_id, '_dkw_id', true );
}

function p3d_cart_goods_total() {
	return WC()->cart ? (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total() : 0;
}

function p3d_package_total( $package ) {
	$t = 0;
	foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
		$t += (float) ( $item['line_total'] ?? 0 ) + (float) ( $item['line_tax'] ?? 0 );
	}
	return $t;
}

// Coșul se împarte în două colete: produsele noastre (Sameday / ridicare) și filamentele (furnizor: FANbox / FAN Courier).
add_filter( 'woocommerce_cart_shipping_packages', function ( $packages ) {
	if ( empty( $packages[0]['contents'] ) ) {
		return $packages;
	}
	$base = $packages[0];
	$own  = array();
	$fil  = array();
	foreach ( $base['contents'] as $key => $item ) {
		if ( p3d_is_fil_product( $item['product_id'] ) ) {
			$fil[ $key ] = $item;
		} else {
			$own[ $key ] = $item;
		}
	}
	$out = array();
	foreach ( array( 'own' => $own, 'fil' => $fil ) as $type => $items ) {
		if ( ! $items ) {
			continue;
		}
		$p                  = $base;
		$p['contents']      = $items;
		$p['contents_cost'] = array_sum( wp_list_pluck( $items, 'line_total' ) );
		$p['p3d_type']      = $type;
		$out[]              = $p;
	}
	return $out;
} );

add_filter( 'woocommerce_shipping_package_name', function ( $name, $i, $package ) {
	if ( empty( $package['p3d_type'] ) || count( WC()->shipping()->get_packages() ) < 2 ) {
		return 'fil' === ( $package['p3d_type'] ?? '' ) ? 'Livrare filamente' : $name;
	}
	return 'fil' === $package['p3d_type'] ? 'Livrare filamente (de la furnizor)' : 'Livrare celelalte produse';
}, 10, 3 );

// Fiecare colet vede doar metodele lui; FANbox gratuit peste prag; Sameday gratuit peste prag doar pe coletul nostru.
add_filter( 'woocommerce_package_rates', function ( $rates, $package ) {
	$type  = $package['p3d_type'] ?? 'own';
	$total = p3d_package_total( $package );
	foreach ( $rates as $id => $rate ) {
		$fil = p3d_is_fil_method_rate( $id );
		if ( ( 'fil' === $type ) !== (bool) $fil ) {
			unset( $rates[ $id ] );
			continue;
		}
		if ( 'own' === $type && 'free_shipping' === $rate->get_method_id() && $total < p3d_fanbox_free_min() ) {
			unset( $rates[ $id ] );
			continue;
		}
		if ( p3d_is_fanbox_rate( $id ) && $total >= p3d_fanbox_free_min() ) {
			$rate->set_cost( 0 );
			$rate->set_taxes( array() );
			$rate->set_label( preg_replace( '/\s*[–-]\s*gratuit.*$/u', '', $rate->get_label() ) . ' – gratuit' );
		}
	}
	return $rates;
}, 90, 2 );

// La FANbox nu se poate plăti ramburs.
add_filter( 'woocommerce_available_payment_gateways', function ( $gws ) {
	if ( ! is_admin() && isset( $gws['cod'] ) && p3d_fanbox_chosen() ) {
		unset( $gws['cod'] );
	}
	return $gws;
} );

// Câmpul „FANbox ales” (apare doar când e aleasă livrarea la FANbox).
add_action( 'woocommerce_after_checkout_billing_form', function ( $checkout ) {
	echo '<div class="p3d-fanbox-field" hidden>';
	woocommerce_form_field( 'p3d_fanbox', array(
		'type'        => 'text',
		'label'       => 'FANbox ales',
		'placeholder' => 'ex. FANbox Kaufland Pantelimon, Șos. Iancului 53',
		'required'    => true,
		'description' => '<a href="' . esc_url( P3D_FANBOX_LIST ) . '" target="_blank" rel="noopener">Consultă lista de FANbox-uri</a>. Scrie numele sau adresa FANbox-ului. La FANbox plata se face online, cu cardul.',
	), $checkout->get_value( 'p3d_fanbox' ) );
	echo '</div>';
	?>
	<script>
	jQuery(function ($) {
		function sync() {
			var on = false;
			$('input[name^="shipping_method"]').each(function () {
				if ((this.type === 'radio' && !this.checked)) return;
				var label = $(this).closest('li').text() || $(this).closest('td').text();
				if (/fanbox/i.test(label)) on = true;
			});
			$('.p3d-fanbox-field').prop('hidden', !on);
		}
		$(document.body).on('updated_checkout change', 'input[name^="shipping_method"]', sync).on('updated_checkout', sync);
		sync();
	});
	</script>
	<?php
} );

add_action( 'woocommerce_checkout_process', function () {
	$methods = isset( $_POST['shipping_method'] ) ? (array) wp_unslash( $_POST['shipping_method'] ) : array(); // phpcs:ignore
	foreach ( $methods as $m ) {
		if ( p3d_is_fanbox_rate( $m ) && '' === trim( (string) ( $_POST['p3d_fanbox'] ?? '' ) ) ) { // phpcs:ignore
			wc_add_notice( 'Scrie FANbox-ul în care vrei să primești coletul (vezi lista de FANbox-uri).', 'error' );
		}
	}
} );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	$methods = isset( $_POST['shipping_method'] ) ? (array) wp_unslash( $_POST['shipping_method'] ) : array(); // phpcs:ignore
	foreach ( $methods as $m ) {
		if ( p3d_is_fanbox_rate( $m ) && ! empty( $_POST['p3d_fanbox'] ) ) { // phpcs:ignore
			$order->update_meta_data( '_p3d_fanbox', sanitize_text_field( wp_unslash( $_POST['p3d_fanbox'] ) ) ); // phpcs:ignore
		}
	}
} );

function p3d_fanbox_of( $order ) {
	return $order ? (string) $order->get_meta( '_p3d_fanbox' ) : '';
}

// Administrare, emailuri, pagina de mulțumire / contul clientului.
add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
	$f = p3d_fanbox_of( $order );
	if ( $f ) {
		echo '<p><strong>FANbox:</strong> ' . esc_html( $f ) . '</p>';
	}
} );

add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin, $order ) {
	$f = p3d_fanbox_of( $order );
	if ( $f ) {
		$fields['p3d_fanbox'] = array( 'label' => 'FANbox', 'value' => $f );
	}
	return $fields;
}, 10, 3 );

add_action( 'woocommerce_order_details_after_customer_details', function ( $order ) {
	$f = p3d_fanbox_of( $order );
	if ( $f ) {
		echo '<p class="p3d-order-fanbox"><strong>Livrare la FANbox:</strong> ' . esc_html( $f ) . '</p>';
	}
} );
