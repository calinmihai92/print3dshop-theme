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
	return ! empty( $chosen[0] ) && p3d_is_fanbox_rate( $chosen[0] );
}

function p3d_cart_goods_total() {
	return WC()->cart ? (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total() : 0;
}

// FANbox gratuit peste prag.
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	if ( p3d_cart_goods_total() < p3d_fanbox_free_min() ) {
		return $rates;
	}
	foreach ( $rates as $id => $rate ) {
		if ( p3d_is_fanbox_rate( $id ) ) {
			$rate->set_cost( 0 );
			$rate->set_taxes( array() );
			$rate->set_label( preg_replace( '/\s*[–-]\s*gratuit.*$/u', '', $rate->get_label() ) . ' – gratuit' );
		}
	}
	return $rates;
}, 90 );

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
			var v = $('input[name^="shipping_method"]:checked').val() || $('input[name^="shipping_method"]').val() || '';
			var label = $('input[name^="shipping_method"][value="' + v + '"]').closest('li').text() || $('.woocommerce-shipping-totals td').text();
			var on = /fanbox/i.test(label);
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
