<?php
/**
 * Magazin (WooCommerce): termen de livrare, culori legate între produse,
 * culoare personalizată la comandă, notă producător pentru produsele de la furnizori.
 *
 * Câmpuri pe produs (Produs → Date produs → General → „Print3D Shop”):
 *  _p3d_livrare        text, ex. „1–2 zile lucrătoare”
 *  _p3d_furnizor       yes = produs de la furnizor („În stoc furnizor” + nota producătorului)
 *  _p3d_producator_url link spre site-ul producătorului (pentru notă)
 *  _p3d_grup           cheie comună pentru produsele-variante (ex. suport-capsule-20)
 *  _p3d_culoare        numele culorii (ex. Negru)
 *  _p3d_culoare_hex    culoarea pentru bulina de selecție (ex. #17181b)
 *  _p3d_culoare_custom yes = clientul scrie culoarea dorită
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}, 11 );

/* ---------- Câmpuri în administrare ---------- */

function p3d_shop_fields() {
	return array(
		'_p3d_livrare'        => array( 'text', 'Termen de livrare', 'ex. 1–2 zile lucrătoare' ),
		'_p3d_furnizor'       => array( 'checkbox', 'Produs de la furnizor', 'Afișează „În stoc furnizor” și nota producătorului' ),
		'_p3d_producator_url' => array( 'text', 'Site-ul producătorului', 'https://…' ),
		'_p3d_grup'           => array( 'text', 'Grup culori', 'aceeași cheie pe toate culorile aceluiași produs' ),
		'_p3d_culoare'        => array( 'text', 'Culoare', 'ex. Negru' ),
		'_p3d_culoare_hex'    => array( 'text', 'Cod culoare', 'ex. #17181b' ),
		'_p3d_culoare_custom' => array( 'checkbox', 'Culoare la alegere', 'Clientul scrie culoarea dorită înainte de „Adaugă în coș”' ),
	);
}

add_action( 'woocommerce_product_options_general_product_data', function () {
	echo '<div class="options_group"><p class="form-field"><strong>Print3D Shop</strong></p>';
	foreach ( p3d_shop_fields() as $key => $f ) {
		if ( 'checkbox' === $f[0] ) {
			woocommerce_wp_checkbox( array( 'id' => $key, 'label' => $f[1], 'description' => $f[2] ) );
		} else {
			woocommerce_wp_text_input( array( 'id' => $key, 'label' => $f[1], 'placeholder' => $f[2], 'desc_tip' => true, 'description' => $f[2] ) );
		}
	}
	echo '</div>';
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	foreach ( p3d_shop_fields() as $key => $f ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifică nonce-ul formularului de produs.
		if ( 'checkbox' === $f[0] ) {
			$product->update_meta_data( $key, isset( $_POST[ $key ] ) ? 'yes' : 'no' );
		} elseif ( isset( $_POST[ $key ] ) ) {
			$val = '_p3d_producator_url' === $key ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			$product->update_meta_data( $key, $val );
		}
		// phpcs:enable
	}
} );

// Câmpurile sunt editabile și prin API-ul REST (meta_data), deci le înregistrăm ca meta de produs.
add_action( 'init', function () {
	foreach ( array_keys( p3d_shop_fields() ) as $key ) {
		register_post_meta( 'product', $key, array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () { return current_user_can( 'edit_products' ); },
		) );
	}
} );

function p3d_meta( $product, $key ) {
	return $product ? (string) $product->get_meta( $key ) : '';
}

/* ---------- Disponibilitate și termen de livrare ---------- */

function p3d_availability_html( $product ) {
	if ( ! $product || ! $product->is_in_stock() ) {
		return '<p class="p3d-avail is-out">' . p3d_icon( 'clock', 16 ) . ' Momentan indisponibil</p>'; // phpcs:ignore
	}
	$furnizor = 'yes' === p3d_meta( $product, '_p3d_furnizor' );
	$livrare  = p3d_meta( $product, '_p3d_livrare' );
	$label    = $furnizor ? 'În stoc furnizor' : 'În stoc';
	$qty      = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
	if ( $furnizor && $qty > 0 ) {
		$label .= ' (' . $qty . ' buc.)';
	}
	$out  = '<p class="p3d-avail">' . p3d_icon( 'check', 16 ) . ' <strong>' . esc_html( $label ) . '</strong>'; // phpcs:ignore
	if ( $livrare ) {
		$out .= ' <span class="p3d-avail-sep">·</span> ' . p3d_icon( 'truck', 16 ) . ' Livrare în ' . esc_html( $livrare ); // phpcs:ignore
	}
	return $out . '</p>';
}

// Înlocuim mesajul standard de stoc cu al nostru.
add_filter( 'woocommerce_get_stock_html', function ( $html, $product ) {
	return is_product() ? '' : $html;
}, 10, 2 );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	echo p3d_availability_html( $product ); // phpcs:ignore
}, 15 );

/* ---------- Culori: bulinele cu legături spre celelalte produse din grup ---------- */

function p3d_group_products( $product ) {
	$grup = p3d_meta( $product, '_p3d_grup' );
	if ( '' === $grup ) {
		return array();
	}
	return wc_get_products( array(
		'status'     => 'publish',
		'limit'      => 20,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
		'meta_key'   => '_p3d_grup', // phpcs:ignore
		'meta_value' => $grup, // phpcs:ignore
	) );
}

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$items = p3d_group_products( $product );
	if ( count( $items ) < 2 ) {
		return;
	}
	$current = p3d_meta( $product, '_p3d_culoare' );
	echo '<div class="p3d-colors"><span class="p3d-colors-label">Culoare: <strong>' . esc_html( $current ) . '</strong></span><div class="p3d-swatches">';
	foreach ( $items as $p ) {
		$name   = p3d_meta( $p, '_p3d_culoare' );
		$hex    = p3d_meta( $p, '_p3d_culoare_hex' );
		$custom = 'yes' === p3d_meta( $p, '_p3d_culoare_custom' );
		$style  = $custom ? 'background:conic-gradient(#f26a1b,#f5c518,#1f9d55,#2b7bd6,#8e44ad,#e23b3b,#f26a1b)' : 'background:' . ( $hex ? $hex : '#ccc' );
		printf(
			'<a href="%s" class="p3d-swatch%s" title="%s" aria-label="%s"%s><span style="%s"></span></a>',
			esc_url( $p->get_permalink() ),
			$p->get_id() === $product->get_id() ? ' is-active' : '',
			esc_attr( $name ),
			esc_attr( $name ),
			$p->get_id() === $product->get_id() ? ' aria-current="true"' : '',
			esc_attr( $style )
		);
	}
	echo '</div></div>';
}, 25 );

/* ---------- Culoare personalizată ---------- */

add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( 'yes' !== p3d_meta( $product, '_p3d_culoare_custom' ) ) {
		return;
	}
	echo '<p class="p3d-custom-color"><label for="p3d_culoare">Culoarea dorită <abbr title="obligatoriu">*</abbr></label>';
	echo '<input type="text" id="p3d_culoare" name="p3d_culoare" maxlength="60" placeholder="ex. galben, mov, gri deschis…" required></p>';
} );

add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id ) {
	$product = wc_get_product( $product_id );
	if ( 'yes' === p3d_meta( $product, '_p3d_culoare_custom' ) && empty( $_POST['p3d_culoare'] ) ) { // phpcs:ignore
		wc_add_notice( 'Scrie culoarea dorită pentru suport.', 'error' );
		return false;
	}
	return $ok;
}, 10, 2 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data ) {
	if ( ! empty( $_POST['p3d_culoare'] ) ) { // phpcs:ignore
		$data['p3d_culoare'] = sanitize_text_field( wp_unslash( $_POST['p3d_culoare'] ) ); // phpcs:ignore
	}
	return $data;
} );

add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	if ( ! empty( $cart_item['p3d_culoare'] ) ) {
		$items[] = array( 'name' => 'Culoare', 'value' => $cart_item['p3d_culoare'] );
	}
	return $items;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( ! empty( $values['p3d_culoare'] ) ) {
		$item->add_meta_data( 'Culoare', $values['p3d_culoare'] );
	}
}, 10, 3 );

/* ---------- Nota producătorului (doar produsele de la furnizori) ---------- */

add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'product' ) || ! in_the_loop() ) {
		return $content;
	}
	$product = wc_get_product( get_the_ID() );
	if ( 'yes' !== p3d_meta( $product, '_p3d_furnizor' ) ) {
		return $content;
	}
	$url  = p3d_meta( $product, '_p3d_producator_url' );
	$link = $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow"><strong>site-ul producătorului</strong></a>' : '<strong>site-ul producătorului</strong>';
	return $content . '<div class="p3d-note"><strong>NOTĂ</strong><p>Informațiile prezentate sunt furnizate de producător, dar pot fi schimbate de acesta fără ca noi să avem posibilitatea să le actualizăm în timp real.<br>Pentru mai multe caracteristici și informații actualizate, consultați ' . $link . '.</p></div>';
}, 20 );

/* ---------- Ajustări de afișare ---------- */

// Fără bara laterală și fără titlul dublu al paginii de magazin.
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_filter( 'woocommerce_show_page_title', '__return_false' );

add_filter( 'loop_shop_columns', function () { return 3; } );
add_filter( 'loop_shop_per_page', function () { return 24; } );

add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 3;
	$args['columns']        = 3;
	return $args;
} );

// Produsele din același grup de culori nu mai apar și la „Produse similare”.
add_filter( 'woocommerce_related_products', function ( $ids, $product_id ) {
	$product = wc_get_product( $product_id );
	$grup    = p3d_meta( $product, '_p3d_grup' );
	if ( '' === $grup ) {
		return $ids;
	}
	return array_values( array_filter( $ids, function ( $id ) use ( $grup ) {
		return get_post_meta( $id, '_p3d_grup', true ) !== $grup;
	} ) );
}, 10, 2 );

// În listă, sub preț: termenul de livrare.
add_action( 'woocommerce_after_shop_loop_item_title', function () {
	global $product;
	$livrare = p3d_meta( $product, '_p3d_livrare' );
	if ( $livrare && $product->is_in_stock() ) {
		echo '<span class="p3d-loop-ship">' . p3d_icon( 'truck', 14 ) . ' Livrare în ' . esc_html( $livrare ) . '</span>'; // phpcs:ignore
	}
}, 15 );

// Produsul cu culoare la alegere nu se poate pune direct în coș din listă: trimitem la pagina lui.
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( 'yes' !== p3d_meta( $product, '_p3d_culoare_custom' ) ) {
		return $html;
	}
	return sprintf( '<a href="%s" class="button">Alege culoarea</a>', esc_url( $product->get_permalink() ) );
}, 10, 2 );

add_filter( 'woocommerce_product_supports', function ( $supports, $feature, $product ) {
	if ( 'ajax_add_to_cart' === $feature && 'yes' === p3d_meta( $product, '_p3d_culoare_custom' ) ) {
		return false;
	}
	return $supports;
}, 10, 3 );
