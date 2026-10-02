<?php
/**
 * Produse personalizate: câmpuri pe pagina de produs (nume, culori, opțiuni),
 * transmise în coș și în comandă.
 *
 * Configurarea se face pe produs, în meta `_p3d_campuri` (JSON), ex.:
 * [
 *   {"key":"nume","type":"text","label":"Nume","max":10,"req":1,"help":"…"},
 *   {"key":"culoare1","type":"color","label":"Culoare literă"},
 *   {"key":"unghi","type":"select","label":"Unghi nume","options":["Drept","Înclinat 30°"]}
 * ]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Paleta de bază (nume => cod). „Altă culoare” se adaugă automat. */
function p3d_palette() {
	return array(
		'Alb'        => '#f4f2ee',
		'Negru'      => '#17181b',
		'Roșu'       => '#d62828',
		'Albastru'   => '#1f6fd1',
		'Bleu'       => '#6ec1ef',
		'Verde'      => '#2fa84f',
		'Mentă'      => '#8fdcc2',
		'Galben'     => '#f5c518',
		'Portocaliu' => '#f26a1b',
		'Roz'        => '#f48fb1',
		'Mov'        => '#8e44ad',
		'Auriu'      => '#c9a23a',
	);
}

function p3d_fields( $product ) {
	if ( ! $product ) {
		return array();
	}
	$raw = (string) $product->get_meta( '_p3d_campuri' );
	if ( '' === $raw ) {
		return array();
	}
	$fields = json_decode( $raw, true );
	return is_array( $fields ) ? array_values( array_filter( $fields, function ( $f ) {
		return ! empty( $f['key'] ) && ! empty( $f['type'] ) && ! empty( $f['label'] );
	} ) ) : array();
}

add_action( 'init', function () {
	register_post_meta( 'product', '_p3d_campuri', array(
		'type'          => 'string',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => function () { return current_user_can( 'edit_products' ); },
	) );
} );

/* ---------- Afișare pe pagina de produs ---------- */

add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	$fields = p3d_fields( $product );
	if ( ! $fields ) {
		return;
	}
	echo '<div class="p3d-pers">';
	foreach ( $fields as $f ) {
		$name = 'p3d_f[' . sanitize_key( $f['key'] ) . ']';
		$id   = 'p3d_f_' . sanitize_key( $f['key'] );
		$req  = ! empty( $f['req'] );
		$lab  = esc_html( $f['label'] ) . ( $req ? ' <abbr title="obligatoriu">*</abbr>' : '' );

		if ( 'text' === $f['type'] ) {
			$max = isset( $f['max'] ) ? (int) $f['max'] : 20;
			printf(
				'<div class="p3d-field"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%3$s" maxlength="%4$d" placeholder="%5$s"%6$s autocomplete="off">%7$s</div>',
				esc_attr( $id ),
				$lab, // phpcs:ignore
				esc_attr( $name ),
				$max,
				esc_attr( $f['placeholder'] ?? '' ),
				$req ? ' required' : '',
				! empty( $f['help'] ) ? '<small>' . esc_html( $f['help'] ) . '</small>' : ''
			);
		} elseif ( 'select' === $f['type'] ) {
			echo '<div class="p3d-field"><span class="p3d-field-label">' . $lab . '</span><div class="p3d-chips">'; // phpcs:ignore
			foreach ( (array) ( $f['options'] ?? array() ) as $i => $opt ) {
				printf(
					'<label class="p3d-chip"><input type="radio" name="%s" value="%s"%s><span>%s</span></label>',
					esc_attr( $name ),
					esc_attr( $opt ),
					0 === $i ? ' checked' : '',
					esc_html( $opt )
				);
			}
			echo '</div></div>';
		} elseif ( 'color' === $f['type'] ) {
			echo '<div class="p3d-field p3d-colorfield"><span class="p3d-field-label">' . $lab . ' <abbr title="obligatoriu">*</abbr>: <strong class="p3d-color-current">—</strong></span><div class="p3d-color-swatches">'; // phpcs:ignore
			foreach ( p3d_palette() as $cname => $hex ) {
				printf(
					'<label class="p3d-cs" title="%1$s"><input type="radio" name="%2$s" value="%1$s" required><span style="background:%3$s"></span></label>',
					esc_attr( $cname ),
					esc_attr( $name ),
					esc_attr( $hex )
				);
			}
			printf(
				'<label class="p3d-cs p3d-cs-other" title="Altă culoare"><input type="radio" name="%s" value="__alta" required><span></span></label>',
				esc_attr( $name )
			);
			printf(
				'</div><input type="text" class="p3d-color-other" name="%s" maxlength="40" placeholder="Scrie culoarea dorită (ex. gri deschis, turcoaz)" hidden></div>',
				esc_attr( 'p3d_alta[' . sanitize_key( $f['key'] ) . ']' )
			);
		}
	}
	echo '</div>';
	?>
	<script>
	document.querySelectorAll('.p3d-colorfield').forEach(function (box) {
		var cur = box.querySelector('.p3d-color-current'), other = box.querySelector('.p3d-color-other');
		box.addEventListener('change', function (e) {
			if (e.target.type !== 'radio') return;
			var isOther = e.target.value === '__alta';
			cur.textContent = isOther ? 'Altă culoare' : e.target.value;
			other.hidden = !isOther; other.required = isOther;
			if (isOther) other.focus();
		});
	});
	</script>
	<?php
}, 5 );

/* ---------- Validare, coș, comandă ---------- */

function p3d_posted_fields( $product ) {
	$fields = p3d_fields( $product );
	$in     = isset( $_POST['p3d_f'] ) ? (array) wp_unslash( $_POST['p3d_f'] ) : array(); // phpcs:ignore
	$alta   = isset( $_POST['p3d_alta'] ) ? (array) wp_unslash( $_POST['p3d_alta'] ) : array(); // phpcs:ignore
	$out    = array();
	$errors = array();
	foreach ( $fields as $f ) {
		$k   = sanitize_key( $f['key'] );
		$val = isset( $in[ $k ] ) ? trim( sanitize_text_field( $in[ $k ] ) ) : '';
		if ( 'color' === $f['type'] ) {
			if ( '__alta' === $val ) {
				$custom = isset( $alta[ $k ] ) ? trim( sanitize_text_field( $alta[ $k ] ) ) : '';
				$val    = '' !== $custom ? 'Altă culoare: ' . $custom : '';
			} elseif ( '' !== $val && ! array_key_exists( $val, p3d_palette() ) ) {
				$val = '';
			}
			if ( '' === $val ) {
				$errors[] = 'Alege: ' . $f['label'] . '.';
				continue;
			}
		} elseif ( 'select' === $f['type'] ) {
			if ( ! in_array( $val, (array) ( $f['options'] ?? array() ), true ) ) {
				$val = (string) ( $f['options'][0] ?? '' );
			}
		} else {
			$max = isset( $f['max'] ) ? (int) $f['max'] : 20;
			if ( function_exists( 'mb_strlen' ) ? mb_strlen( $val ) > $max : strlen( $val ) > $max ) {
				$errors[] = $f['label'] . ': maximum ' . $max . ' caractere.';
				continue;
			}
			if ( ! empty( $f['req'] ) && '' === $val ) {
				$errors[] = 'Completează: ' . $f['label'] . '.';
				continue;
			}
		}
		if ( '' !== $val ) {
			$out[ $f['label'] ] = $val;
		}
	}
	return array( $out, $errors );
}

add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! p3d_fields( $product ) ) {
		return $ok;
	}
	list( , $errors ) = p3d_posted_fields( $product );
	foreach ( $errors as $e ) {
		wc_add_notice( $e, 'error' );
	}
	return $errors ? false : $ok;
}, 10, 2 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! p3d_fields( $product ) ) {
		return $data;
	}
	list( $vals ) = p3d_posted_fields( $product );
	if ( $vals ) {
		$data['p3d_pers'] = $vals;
		$data['p3d_uid']  = md5( wp_json_encode( $vals ) ); // același produs cu nume diferite = rânduri diferite în coș
	}
	return $data;
}, 10, 2 );

add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	foreach ( (array) ( $cart_item['p3d_pers'] ?? array() ) as $label => $val ) {
		$items[] = array( 'name' => $label, 'value' => $val );
	}
	return $items;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	foreach ( (array) ( $values['p3d_pers'] ?? array() ) as $label => $val ) {
		$item->add_meta_data( $label, $val );
	}
}, 10, 3 );

/* ---------- În listă: produsele personalizate trimit la pagina lor ---------- */

add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( ! p3d_fields( $product ) ) {
		return $html;
	}
	return sprintf( '<a href="%s" class="button">Personalizează</a>', esc_url( $product->get_permalink() ) );
}, 20, 2 );

add_filter( 'woocommerce_product_supports', function ( $supports, $feature, $product ) {
	if ( 'ajax_add_to_cart' === $feature && p3d_fields( $product ) ) {
		return false;
	}
	return $supports;
}, 20, 3 );
