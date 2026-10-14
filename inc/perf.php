<?php
/**
 * Viteză: scoatem fișierele de care paginile temei nu au nevoie (Elementor, Contact Form 7,
 * stilurile WooCommerce în afara magazinului) și mutăm jQuery la finalul paginii, unde se poate.
 */

defined( 'ABSPATH' ) || exit;

/** Pagina curentă e construită cu Elementor (doar paginile vechi afișate prin page.php)? */
function p3d_page_uses_elementor() {
	if ( ! is_singular( 'page' ) || is_front_page() ) {
		return false;
	}
	$id = get_queried_object_id();
	if ( 'builder' !== get_post_meta( $id, '_elementor_edit_mode', true ) ) {
		return false;
	}
	// Paginile cu șablon propriu în temă nu afișează conținutul Elementor.
	$slug = get_post_field( 'post_name', $id );
	return ! in_array( $slug, array( 'contacts', 'despre-noi', 'our-services', 'preturi' ), true );
}

function p3d_is_shop_page() {
	return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() ) {
		return;
	}
	global $wp_styles, $wp_scripts;

	// Contact Form 7: formularele vechi nu mai apar pe site.
	wp_dequeue_style( 'contact-form-7' );
	wp_dequeue_script( 'contact-form-7' );
	wp_dequeue_script( 'swv' );

	// Elementor: doar pe paginile vechi construite cu el.
	if ( ! p3d_page_uses_elementor() ) {
		foreach ( array( $wp_styles, $wp_scripts ) as $reg ) {
			foreach ( (array) $reg->queue as $h ) {
				if ( preg_match( '/^(elementor|e-animation|widget-|jquery-numerator|jquery-ui-core$)/', $h ) ) {
					wp_dequeue_style( $h );
					wp_dequeue_script( $h );
				}
			}
		}
	}

	// WooCommerce: stilurile și scripturile de coș doar în magazin.
	if ( ! p3d_is_shop_page() ) {
		foreach ( array( 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general', 'wc-blocks-style' ) as $h ) {
			wp_dequeue_style( $h );
		}
		foreach ( array( 'wc-add-to-cart', 'woocommerce', 'wc-jquery-blockui', 'jquery-blockui', 'wc-js-cookie', 'js-cookie', 'wc-cart-fragments' ) as $h ) {
			wp_dequeue_script( $h );
		}
	}

	// jQuery și ce depinde de el: la finalul paginii, ca să nu blocheze afișarea.
	if ( ! p3d_is_shop_page() && ! p3d_page_uses_elementor() ) {
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate', 'wpgdprc-front-js' ) as $h ) {
			if ( isset( $wp_scripts->registered[ $h ] ) ) {
				$wp_scripts->add_data( $h, 'group', 1 );
			}
		}
	}
}, 1000 );

// Fonturile (0,8 KB) direct în pagină, fără o cerere separată care blochează afișarea.
add_action( 'wp_head', function () {
	$file = get_template_directory() . '/assets/css/fonts.css';
	if ( ! is_readable( $file ) ) {
		return;
	}
	$css = (string) file_get_contents( $file ); // phpcs:ignore
	$css = str_replace( 'url(../fonts/', 'url(' . P3D_URI . '/assets/fonts/', $css );
	echo '<style id="p3d-fonts-inline">' . $css . '</style>' . "\n"; // phpcs:ignore
}, 6 );

// Fișierele vechi cerute pe http (conținut mixt) → https.
add_filter( 'style_loader_src', 'p3d_force_https_src' );
add_filter( 'script_loader_src', 'p3d_force_https_src' );
function p3d_force_https_src( $src ) {
	if ( is_ssl() && is_string( $src ) && 0 === strpos( $src, 'http://' . wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
		$src = 'https://' . substr( $src, 7 );
	}
	return $src;
}
