<?php
/**
 * Contul meu (WooCommerce): meniu în română, panou cu scurtături, cont nou cu email + parolă.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$out = array(
		'dashboard'       => 'Panou',
		'orders'          => 'Comenzile mele',
		'edit-address'    => 'Adrese',
		'edit-account'    => 'Detalii cont',
		'customer-logout' => 'Ieșire',
	);
	foreach ( $items as $k => $v ) {
		if ( ! isset( $out[ $k ] ) && ! in_array( $k, array( 'downloads', 'payment-methods', 'wishlist' ), true ) ) {
			$out = array_slice( $out, 0, -1, true ) + array( $k => $v ) + array_slice( $out, -1, 1, true );
		}
	}
	return $out;
}, 20 );

// Setări: cont nou din pagina „Contul meu” și la finalizarea comenzii; utilizatorul alege parola.
add_action( 'init', function () {
	$want = array(
		'woocommerce_enable_myaccount_registration'  => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_registration_generate_username' => 'yes',
		'woocommerce_registration_generate_password' => 'no',
	);
	foreach ( $want as $k => $v ) {
		if ( get_option( $k ) !== $v ) {
			update_option( $k, $v );
		}
	}
} );

// Bulina de pe coș: WooCommerce pune cookie-ul woocommerce_items_in_cart când coșul are produse.
add_action( 'wp_footer', function () {
	echo "<script>if(/(^|; )woocommerce_items_in_cart=1/.test(document.cookie)){document.querySelectorAll('.nav-cart-dot').forEach(function(d){d.hidden=false;});}</script>";
}, 99 );

/* ---------- Livrare gratuită: cât mai lipsește (coș și finalizare comandă) ---------- */

function p3d_free_shipping_min() {
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		foreach ( $z['shipping_methods'] as $m ) {
			if ( 'free_shipping' === $m->id && 'yes' === $m->enabled && in_array( $m->requires, array( 'min_amount', 'either' ), true ) ) {
				return (float) $m->min_amount;
			}
		}
	}
	return 0;
}

function p3d_free_shipping_notice() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$min = function_exists( 'p3d_fanbox_free_min' ) ? p3d_fanbox_free_min() : 250;
	$tot = array( 'own' => 0, 'fil' => 0 );
	foreach ( WC()->cart->get_cart() as $item ) {
		$type          = function_exists( 'p3d_is_fil_product' ) && p3d_is_fil_product( $item['product_id'] ) ? 'fil' : 'own';
		$tot[ $type ] += (float) $item['line_total'] + (float) $item['line_tax'];
	}
	$labels = array( 'own' => 'prin curier', 'fil' => 'la FANbox pentru filamente' );
	foreach ( $tot as $type => $total ) {
		if ( $total <= 0 ) {
			continue;
		}
		if ( $total >= $min ) {
			echo '<div class="p3d-freeship is-ok">' . p3d_icon( 'truck', 18 ) . ' <span>Ai livrare <strong>gratuită ' . esc_html( $labels[ $type ] ) . '</strong>.</span></div>'; // phpcs:ignore
			continue;
		}
		$pct = max( 4, min( 100, round( $total / $min * 100 ) ) );
		$what = 'fil' === $type ? 'filamente' : 'produse';
		echo '<div class="p3d-freeship">' . p3d_icon( 'truck', 18 ) . ' <span>Mai adaugă ' . esc_html( $what ) . ' de <strong>' . wc_price( $min - $total ) . '</strong> și ai livrare gratuită ' . esc_html( $labels[ $type ] ) . ' (peste ' . wc_price( $min ) . ').</span><i style="--p:' . (int) $pct . '%"></i></div>'; // phpcs:ignore
	}
}
add_action( 'woocommerce_before_cart_table', 'p3d_free_shipping_notice' );
add_action( 'woocommerce_before_checkout_form', 'p3d_free_shipping_notice', 5 );
