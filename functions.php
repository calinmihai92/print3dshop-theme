<?php
/**
 * Print3D Shop — funcții temă.
 */

defined( 'ABSPATH' ) || exit;

define( 'P3D_VER', wp_get_theme()->get( 'Version' ) );
define( 'P3D_URI', get_template_directory_uri() );

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/data.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'woocommerce' );
	register_nav_menus( array( 'primary' => 'Meniu principal' ) );
} );

/**
 * Stiluri și scripturi proprii; scoatem ce lasă în urmă tema veche.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'p3d-fonts', P3D_URI . '/assets/css/fonts.css', array(), P3D_VER );
	wp_enqueue_style( 'p3d-main', P3D_URI . '/assets/css/main.css', array( 'p3d-fonts' ), P3D_VER );
	wp_enqueue_script( 'p3d-main', P3D_URI . '/assets/js/main.js', array(), P3D_VER, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	global $wp_styles, $wp_scripts;
	$drop = array( 'trx_addons', 'trx-addons', 'revslider', 'rs-plugin', 'tp-tools', 'tinvwl', 'whatsapp', 'wacc' );
	foreach ( array( $wp_styles, $wp_scripts ) as $reg ) {
		if ( ! $reg ) {
			continue;
		}
		foreach ( (array) $reg->queue as $handle ) {
			foreach ( $drop as $needle ) {
				if ( false !== stripos( $handle, $needle ) ) {
					wp_dequeue_style( $handle );
					wp_dequeue_script( $handle );
				}
			}
		}
	}
}, 999 );

add_action( 'wp_head', function () {
	$fonts = array( 'lora-latin-500-normal', 'source-sans-3-latin-400-normal' );
	foreach ( $fonts as $f ) {
		printf( '<link rel="preload" href="%s/assets/fonts/%s.woff2" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( P3D_URI ), esc_attr( $f ) );
	}
	echo '<meta name="theme-color" content="#f7f5f2">' . "\n";
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s/assets/img/favicon.svg" type="image/svg+xml">' . "\n", esc_url( P3D_URI ) );
	}
}, 2 );

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Tipul „Servicii” (cpt_services) era creat de pluginul temei vechi.
 * Îl înregistrăm noi, cu același nume și aceeași adresă (/services/…), ca URL-urile să rămână.
 */
add_action( 'init', function () {
	if ( post_type_exists( 'cpt_services' ) ) {
		return;
	}
	register_post_type( 'cpt_services', array(
		'label'        => 'Servicii',
		'public'       => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-admin-tools',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'rewrite'      => array( 'slug' => 'services', 'with_front' => false ),
		'has_archive'  => false,
	) );
}, 20 );

/**
 * Gravura laser e scoasă momentan: trimitem temporar (302) spre pagina de servicii.
 */
add_action( 'template_redirect', function () {
	if ( is_singular( 'cpt_services' ) && in_array( get_post_field( 'post_name', get_queried_object_id() ), p3d_hidden_services(), true ) ) {
		wp_safe_redirect( home_url( '/our-services/' ), 302 );
		exit;
	}
} );

/**
 * FAQ ca date structurate pe paginile de servicii și pe prima pagină.
 */
add_action( 'wp_head', function () {
	$faq = null;
	if ( is_front_page() ) {
		$faq = p3d_home_faq();
	} elseif ( is_singular( 'cpt_services' ) ) {
		$s   = p3d_service( get_post_field( 'post_name', get_queried_object_id() ) );
		$faq = $s['faq'] ?? null;
	}
	if ( ! $faq ) {
		return;
	}
	$data = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $q ) {
			return array(
				'@type'          => 'Question',
				'name'           => $q[0],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ),
			);
		}, $faq ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 30 );

/**
 * Formularul de contact existent (Contact Form 7), dacă există.
 */
function p3d_contact_form() {
	if ( ! shortcode_exists( 'contact-form-7' ) ) {
		return '';
	}
	$forms = get_posts( array( 'post_type' => 'wpcf7_contact_form', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'ASC' ) );
	if ( ! $forms ) {
		return '';
	}
	return do_shortcode( '[contact-form-7 id="' . (int) $forms[0]->ID . '"]' );
}

/**
 * Lungimea rezumatelor din blog.
 */
add_filter( 'excerpt_length', function () { return 24; } );
add_filter( 'excerpt_more', function () { return '…'; } );
