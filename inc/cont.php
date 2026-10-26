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
		if ( ! isset( $out[ $k ] ) && 'downloads' !== $k && 'payment-methods' !== $k ) {
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
