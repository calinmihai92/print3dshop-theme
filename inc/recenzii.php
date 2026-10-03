<?php
/**
 * Recenzii produse:
 *  - doar clienții care au cumpărat produsul (setarea WooCommerce „doar proprietari verificați”);
 *  - stele obligatorii + fotografie obligatorie (JPG/PNG/WEBP, max. 5 MB);
 *  - pe produsele din același grup de culori (_p3d_grup) se văd recenziile tuturor culorilor,
 *    fiecare cu mențiunea „Recenzie lăsată la produsul …”.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/* ---------- Câmpul de fotografie în formularul de recenzie ---------- */

add_filter( 'woocommerce_product_review_comment_form_args', function ( $args ) {
	$args['comment_field'] .= '<p class="comment-form-p3d-photo"><label for="p3d_review_photo">Fotografie cu produsul&nbsp;<span class="required">*</span></label>'
		. '<input type="file" id="p3d_review_photo" name="p3d_review_photo" accept="image/jpeg,image/png,image/webp" required>'
		. '<small>Adaugă o fotografie cu produsul primit (JPG, PNG sau WEBP, maximum 5 MB).</small></p>';
	return $args;
} );

// Formularul de comentarii trebuie să poată trimite fișiere.
add_action( 'comment_form_top', function () {
	if ( ! is_product() ) {
		return;
	}
	echo '<script>document.currentScript.closest("form").setAttribute("enctype","multipart/form-data");</script>';
} );

function p3d_review_photo_error() {
	if ( empty( $_FILES['p3d_review_photo'] ) || ! empty( $_FILES['p3d_review_photo']['error'] ) || empty( $_FILES['p3d_review_photo']['tmp_name'] ) ) { // phpcs:ignore
		return 'Adaugă o fotografie cu produsul. Recenziile fără fotografie nu pot fi publicate.';
	}
	$f = $_FILES['p3d_review_photo']; // phpcs:ignore
	if ( (int) $f['size'] > 5 * MB_IN_BYTES ) {
		return 'Fotografia este prea mare (maximum 5 MB).';
	}
	$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array(
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
	) );
	if ( empty( $check['type'] ) || ! @getimagesize( $f['tmp_name'] ) ) { // phpcs:ignore
		return 'Fișierul trebuie să fie o imagine JPG, PNG sau WEBP.';
	}
	return '';
}

// Verificare înainte de salvare: doar pentru recenziile noi la produse (nu și pentru răspunsurile din administrare).
add_filter( 'preprocess_comment', function ( $data ) {
	if ( is_admin() || 'product' !== get_post_type( $data['comment_post_ID'] ?? 0 ) || ! empty( $data['comment_parent'] ) ) {
		return $data;
	}
	$err = p3d_review_photo_error();
	if ( $err ) {
		wp_die( esc_html( $err ), 'Recenzie incompletă', array( 'response' => 400, 'back_link' => true ) );
	}
	return $data;
} );

// Salvăm fotografia în Media și o legăm de recenzie.
add_action( 'comment_post', function ( $comment_id, $approved, $data ) {
	if ( 'product' !== get_post_type( $data['comment_post_ID'] ?? 0 ) || ! empty( $data['comment_parent'] ) || empty( $_FILES['p3d_review_photo']['tmp_name'] ) ) { // phpcs:ignore
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( 'p3d_review_photo', (int) $data['comment_post_ID'], array( 'post_title' => 'Fotografie recenzie #' . $comment_id ) );
	if ( ! is_wp_error( $att ) ) {
		update_comment_meta( $comment_id, 'p3d_review_photo', (int) $att );
	}
}, 10, 3 );

// Afișăm fotografia sub textul recenziei.
add_action( 'woocommerce_review_after_comment_text', function ( $comment ) {
	$att = (int) get_comment_meta( $comment->comment_ID, 'p3d_review_photo', true );
	if ( ! $att ) {
		return;
	}
	printf(
		'<a class="p3d-review-photo" href="%s" target="_blank" rel="noopener">%s</a>',
		esc_url( wp_get_attachment_image_url( $att, 'large' ) ),
		wp_get_attachment_image( $att, 'medium', false, array( 'loading' => 'lazy', 'alt' => 'Fotografie de la client' ) )
	);
} );

// În administrare (Comentarii) se vede și fotografia.
add_filter( 'comment_text', function ( $text, $comment = null ) {
	if ( ! is_admin() || ! $comment ) {
		return $text;
	}
	$att = (int) get_comment_meta( $comment->comment_ID, 'p3d_review_photo', true );
	return $att ? $text . '<p>' . wp_get_attachment_image( $att, 'thumbnail' ) . '</p>' : $text;
}, 10, 2 );

/* ---------- Recenziile tuturor culorilor pe fiecare produs din grup ---------- */

function p3d_group_ids( $product_id ) {
	$grup = (string) get_post_meta( $product_id, '_p3d_grup', true );
	if ( '' === $grup ) {
		return array( (int) $product_id );
	}
	$ids = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => 50,
		'fields'      => 'ids',
		'meta_key'    => '_p3d_grup', // phpcs:ignore
		'meta_value'  => $grup, // phpcs:ignore
	) );
	return $ids ? array_map( 'intval', $ids ) : array( (int) $product_id );
}

add_filter( 'comments_template_query_args', function ( $args ) {
	if ( ! is_product() || empty( $args['post_id'] ) ) {
		return $args;
	}
	$ids = p3d_group_ids( $args['post_id'] );
	if ( count( $ids ) > 1 ) {
		unset( $args['post_id'] );
		$args['post__in'] = $ids;
	}
	return $args;
} );

add_action( 'woocommerce_review_before_comment_text', function ( $comment ) {
	if ( ! is_product() || (int) $comment->comment_post_ID === (int) get_queried_object_id() ) {
		return;
	}
	printf(
		'<p class="p3d-review-from">Recenzie lăsată la produsul <a href="%s">%s</a></p>',
		esc_url( get_permalink( $comment->comment_post_ID ) ),
		esc_html( get_the_title( $comment->comment_post_ID ) )
	);
} );

// Numărul și media recenziilor din tab și de sub titlu țin cont de tot grupul.
function p3d_group_rating( $product ) {
	static $cache = array();
	$id = $product->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$ids = p3d_group_ids( $id );
	if ( count( $ids ) < 2 ) {
		return $cache[ $id ] = null;
	}
	$count = 0;
	$sum   = 0;
	foreach ( get_comments( array( 'post__in' => $ids, 'status' => 'approve', 'type' => 'review', 'parent' => 0 ) ) as $c ) {
		$r = (int) get_comment_meta( $c->comment_ID, 'rating', true );
		if ( $r ) {
			$sum += $r;
			$count++;
		}
	}
	return $cache[ $id ] = array( $count, $count ? round( $sum / $count, 2 ) : 0 );
}

add_filter( 'woocommerce_product_get_review_count', function ( $count, $product ) {
	if ( ! is_product() ) {
		return $count;
	}
	$g = p3d_group_rating( $product );
	return $g ? $g[0] : $count;
}, 10, 2 );

add_filter( 'woocommerce_product_get_average_rating', function ( $avg, $product ) {
	if ( ! is_product() ) {
		return $avg;
	}
	$g = p3d_group_rating( $product );
	return $g ? $g[1] : $avg;
}, 10, 2 );
