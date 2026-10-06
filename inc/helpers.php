<?php
/**
 * Helpers: date de contact, iconițe, logo.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date de contact — editabile din Aspect → Personalizează → Date de contact.
 */
function p3d_opt( $key ) {
	$defaults = array(
		'phone'    => '0785 813 441',
		'whatsapp' => '40785813441',
		'email'    => 'office@print3dshop.ro',
		'address'  => 'Șoseaua Iancului nr. 53, București',
		'maps'     => 'https://www.google.com/maps/search/?api=1&query=%C8%98oseaua+Iancului+53+Bucure%C8%99ti',
		'hours'    => 'Luni–Vineri: 09:00–18:00 · Sâmbătă–Duminică: închis',
	);
	$val = get_theme_mod( 'p3d_' . $key, '' );
	return '' !== $val ? $val : ( $defaults[ $key ] ?? '' );
}

function p3d_tel() {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', p3d_opt( 'phone' ) );
}

function p3d_wa( $text = 'Bună ziua! Vă scriu de pe print3dshop.ro.' ) {
	$num = preg_replace( '/[^0-9]/', '', p3d_opt( 'whatsapp' ) );
	return 'https://wa.me/' . $num . '?text=' . rawurlencode( $text );
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'p3d_contact', array( 'title' => 'Date de contact', 'priority' => 30 ) );
	$fields = array(
		'phone'    => 'Telefon afișat (ex. 0785 813 441)',
		'whatsapp' => 'WhatsApp (format internațional, doar cifre, ex. 40785813441)',
		'email'    => 'Email',
		'address'  => 'Adresă',
		'maps'     => 'Link Google Maps',
		'hours'    => 'Program (ex. Luni–Vineri: 09:00–18:00 · Sâmbătă–Duminică: închis)',
	);
	foreach ( $fields as $k => $label ) {
		$wp->add_setting( 'p3d_' . $k, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( 'p3d_' . $k, array( 'label' => $label, 'section' => 'p3d_contact', 'type' => 'text' ) );
	}
} );

/**
 * Iconițe SVG (contur), aceeași familie ca pe 123ai.
 */
function p3d_icon( $name, $size = 20 ) {
	static $paths = array(
		'arrow'    => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
		'chevron'  => '<path d="M6 9l6 6 6-6"/>',
		'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'whatsapp' => '<path d="M3.5 20.5l1.3-4A8.5 8.5 0 1 1 8 19.6z"/><path d="M9 9.5c.3 2.2 2.3 4.3 4.5 4.8l1.2-1.2 2 .9c-.3 1.3-1.4 2-2.7 1.8-3.4-.5-6-3.2-6.4-6.5-.1-1.2.6-2.3 1.8-2.6l.9 2z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'pin'      => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'wrench'   => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
		'nozzle'   => '<rect x="7" y="3" width="10" height="6" rx="1.5"/><path d="M9 9h6l-2 4h-2z"/><path d="M5 18h14M7 21h10"/>',
		'layers'   => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
		'cube'     => '<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
		'pen'      => '<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
		'search'   => '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>',
		'scan'     => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M12 7.5l4 2.2v4.6l-4 2.2-4-2.2V9.7z"/><path d="M8 9.7l4 2.2 4-2.2M12 11.9v4.6"/>',
		'shield'   => '<path d="M12 3l7.5 3v5.5c0 4.5-3.2 8.3-7.5 9.5-4.3-1.2-7.5-5-7.5-9.5V6z"/><path d="M8.8 12l2.2 2.2 4.2-4.4"/>',
		'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
		'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'chat'     => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/>',
		'upload'   => '<path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
		'palette'  => '<path d="M12 3a9 9 0 1 0 0 18c1.2 0 1.8-.8 1.8-1.7 0-1.1-.9-1.5-.9-2.6 0-.9.7-1.6 1.6-1.6H17a4 4 0 0 0 4-4C21 6.6 17 3 12 3z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="14.5" cy="7.5" r="1"/>',
		'truck'    => '<path d="M3 6h11v10H3z"/><path d="M14 9.5h4l3 3.5v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
		'cart'     => '<path d="M3 4h2.5l2.2 11h10.6l2-8H6.4"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
		'thermo'   => '<path d="M10 14V5a2 2 0 1 1 4 0v9a4 4 0 1 1-4 0z"/><path d="M12 10v6"/>',
		'zap'      => '<path d="M13 3L5 13.5h6L10 21l8-10.5h-6z"/>',
		'alert'    => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/>',
		'cycle'    => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v4h-4"/>',
		'doc'      => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
		'users'    => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5a3 3 0 0 1 0 6"/><path d="M18 14.3c1.8.8 3 2.6 3 5.7"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
	);
	$d = $paths[ $name ] ?? '';
	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $size,
		$d
	);
}

/**
 * Semnul logo-ului (varianta 2 — duza, simplificată pentru mărimi mici).
 */
function p3d_logo_mark( $class = 'brand-mark', $on_dark = false ) {
	$ink = $on_dark ? '#ffffff' : '#17181b';
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 120 120" fill="none" aria-hidden="true" focusable="false">'
		. '<rect x="34" y="8" width="52" height="28" rx="6" fill="' . $ink . '"/>'
		. '<path d="M46 36H74L65 58H55Z" fill="' . $ink . '"/>'
		. '<circle cx="60" cy="70" r="4" fill="#f26a1b"/>'
		. '<rect x="35" y="84" width="50" height="8" rx="4" fill="#f26a1b"/>'
		. '<rect x="27" y="95" width="66" height="8" rx="4" fill="#f26a1b"/>'
		. '<rect x="19" y="106" width="82" height="8" rx="4" fill="#f26a1b"/>'
		. '</svg>';
}

function p3d_logo() {
	return p3d_logo_mark() . '<span class="brand-word">print3d<span>shop</span></span>';
}

/**
 * Serviciul activ în pagina curentă (pentru meniu).
 */
function p3d_is( $slug ) {
	if ( is_singular( 'cpt_services' ) ) {
		return get_post_field( 'post_name', get_queried_object_id() ) === $slug;
	}
	return is_page( $slug );
}

/**
 * Lista de prețuri (rânduri din p3d_prices()).
 */
function p3d_price_rows( $rows ) {
	echo '<ul class="price-list">';
	foreach ( $rows as $r ) {
		echo '<li><span class="price-name">' . esc_html( $r[0] );
		if ( ! empty( $r[1] ) ) {
			echo '<small>' . esc_html( $r[1] ) . '</small>';
		}
		$free = in_array( $r[2], array( 'Gratuit', 'Ofertă personalizată' ), true );
		echo '</span><strong class="price-val' . ( $free ? ' is-text' : '' ) . '">' . esc_html( $r[2] ) . '</strong></li>';
	}
	echo '</ul>';
}

function p3d_prices_url( $anchor = '' ) {
	return home_url( '/preturi/' ) . ( $anchor ? '#' . $anchor : '' );
}
