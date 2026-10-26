<?php
/**
 * Filamente: „Comandă rapidă” deasupra listei – câte un card pe tip de filament (brand + material + refill/rolă),
 * cu bulinele culorilor, poza, prețul și stocul culorii alese, și buton „Adaugă în coș” (AJAX).
 * Respectă filtrele din pagină (brand, material, culoare, variantă).
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

function p3d_quick_lines() {
	$cur = get_queried_object();
	if ( ! $cur || empty( $cur->term_id ) ) {
		return array();
	}
	$meta = array( array( 'key' => '_dkw_id', 'compare' => 'EXISTS' ) );
	foreach ( array( 'material' => '_dkw_material', 'varianta' => '_dkw_variant' ) as $param => $key ) {
		if ( ! empty( $_GET[ $param ] ) ) { // phpcs:ignore
			$meta[] = array( 'key' => $key, 'value' => sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ); // phpcs:ignore
		}
	}
	$q = new WP_Query( array(
		'post_type'              => 'product',
		'post_status'            => current_user_can( 'manage_woocommerce' ) ? array( 'publish', 'draft', 'private' ) : 'publish',
		'posts_per_page'         => 1000,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'tax_query'              => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cur->term_id, 'include_children' => true ) ), // phpcs:ignore
		'meta_query'             => $meta, // phpcs:ignore
		'orderby'                => 'title',
		'order'                  => 'ASC',
	) );
	if ( ! $q->posts ) {
		return array();
	}
	update_meta_cache( 'post', $q->posts );
	$fam    = function_exists( 'p3d_dkw_color_families' ) ? p3d_dkw_color_families() : array();
	$want   = isset( $_GET['culoare'] ) ? sanitize_text_field( wp_unslash( $_GET['culoare'] ) ) : ''; // phpcs:ignore
	$lines  = array();
	foreach ( $q->posts as $id ) {
		$grp     = (string) get_post_meta( $id, '_p3d_grup', true );
		$variant = (string) get_post_meta( $id, '_dkw_variant', true );
		$key     = $grp . '|' . $variant;
		$in      = 'instock' === get_post_meta( $id, '_stock_status', true );
		$family  = (string) get_post_meta( $id, '_dkw_color_family', true );
		$hex     = (string) get_post_meta( $id, '_p3d_culoare_hex', true );
		if ( '' === $hex && isset( $fam[ $family ] ) && 0 === strpos( $fam[ $family ], '#' ) ) {
			$hex = $fam[ $family ];
		}
		if ( ! isset( $lines[ $key ] ) ) {
			$cats  = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) );
			$brand = '';
			foreach ( $cats as $c ) {
				if ( 0 === strpos( $c, 'Filament ' ) ) {
					$brand = substr( $c, 9 );
				}
			}
			$lines[ $key ] = array(
				'brand'    => $brand,
				'title'    => trim( get_post_meta( $id, '_dkw_material', true ) . ( 'Refill' === $variant ? ' Refill' : ( '' !== $variant ? ' – rolă întreagă' : '' ) ) ),
				'items'    => array(),
				'in'       => 0,
				'has_want' => false,
			);
		}
		$price = (float) get_post_meta( $id, '_price', true );
		$lines[ $key ]['items'][] = array(
			'id'    => $id,
			'name'  => (string) get_post_meta( $id, '_p3d_culoare', true ),
			'hex'   => $hex ? $hex : '#cccccc',
			'in'    => $in,
			'qty'   => (int) get_post_meta( $id, '_stock', true ),
			'price' => $price,
			'ph'    => wc_price( $price ),
			'img'   => (string) wp_get_attachment_image_url( (int) get_post_thumbnail_id( $id ), 'woocommerce_thumbnail' ),
			'url'   => get_permalink( $id ),
			'fam'   => $family,
		);
		if ( $in ) {
			$lines[ $key ]['in']++;
			if ( '' !== $want && $family === $want ) {
				$lines[ $key ]['has_want'] = true;
			}
		}
	}
	return array_values( array_filter( $lines, function ( $l ) use ( $want ) {
		return $l['in'] > 0 && ( '' === $want || $l['has_want'] );
	} ) );
}

add_action( 'woocommerce_before_shop_loop', function () {
	if ( ! function_exists( 'p3d_dkw_is_fil_archive' ) || ! p3d_dkw_is_fil_archive() || is_paged() ) {
		return;
	}
	$lines = p3d_quick_lines();
	if ( ! $lines ) {
		return;
	}
	$want = isset( $_GET['culoare'] ) ? sanitize_text_field( wp_unslash( $_GET['culoare'] ) ) : ''; // phpcs:ignore
	echo '<section class="p3d-quick"><div class="p3d-quick-head"><h2>Comandă rapidă</h2><p>Alege tipul de filament și culoarea, apoi adaugă în coș. ' . count( $lines ) . ' tipuri.</p></div>';
	foreach ( $lines as $l ) {
		// culorile în stoc primele, apoi cele epuizate
		usort( $l['items'], function ( $a, $b ) { return ( $b['in'] <=> $a['in'] ) ?: strcasecmp( $a['name'], $b['name'] ); } );
		$sel = 0;
		foreach ( $l['items'] as $i => $it ) {
			if ( $it['in'] && ( '' === $want || $it['fam'] === $want ) ) {
				$sel = $i;
				break;
			}
		}
		$prices = wp_list_pluck( array_filter( $l['items'], function ( $x ) { return $x['in']; } ), 'price' );
		$pmin   = min( $prices );
		$pmax   = max( $prices );
		$first  = $l['items'][ $sel ];
		echo '<article class="p3d-qline" data-items="' . esc_attr( wp_json_encode( $l['items'] ) ) . '">';
		echo '<a class="p3d-qimg" href="' . esc_url( $first['url'] ) . '"><img src="' . esc_url( $first['img'] ) . '" alt="" loading="lazy"></a>';
		echo '<div class="p3d-qbody">';
		$logo = 'assets/img/brands/' . sanitize_title( $l['brand'] ) . '.png';
		if ( '' !== $l['brand'] && file_exists( get_template_directory() . '/' . $logo ) ) {
			echo '<img class="p3d-qlogo" src="' . esc_url( get_template_directory_uri() . '/' . $logo ) . '" alt="' . esc_attr( $l['brand'] ) . '" loading="lazy">';
		}
		echo '<span class="p3d-qbrand">Filament ' . esc_html( $l['brand'] ) . '</span>';
		echo '<h3 class="p3d-qtitle">' . esc_html( $l['title'] ) . '</h3>';
		echo '<div class="p3d-qprice"><strong>' . ( $pmin === $pmax ? wc_price( $pmin ) : 'de la ' . wc_price( $pmin ) ) . '</strong> <span>' . (int) $l['in'] . '/' . count( $l['items'] ) . ' culori în stoc</span></div>'; // phpcs:ignore
		echo '<div class="p3d-qsw">';
		foreach ( $l['items'] as $i => $it ) {
			$bg = 0 === strpos( $it['hex'], '#' ) ? $it['hex'] : '#ccc';
			printf(
				'<button type="button" class="p3d-qdot%s%s" data-i="%d" title="%s" aria-label="%s"%s><span style="background:%s"></span></button>',
				$i === $sel ? ' is-on' : '',
				$it['in'] ? '' : ' is-out',
				(int) $i,
				esc_attr( $it['name'] . ( $it['in'] ? '' : ' – stoc epuizat' ) ),
				esc_attr( $it['name'] ),
				$it['in'] ? '' : ' aria-disabled="true"',
				esc_attr( $bg )
			);
		}
		echo '</div>';
		echo '<div class="p3d-qfoot"><div><a class="p3d-qname" href="' . esc_url( $first['url'] ) . '">' . esc_html( $first['name'] ) . '</a><div class="p3d-qmeta"><strong class="p3d-qp">' . $first['ph'] . '</strong> <span class="p3d-qst">' . ( $first['in'] ? 'În stoc' : 'Stoc epuizat' ) . '</span></div></div>'; // phpcs:ignore
		echo '<button type="button" class="button p3d-qadd"' . ( $first['in'] ? '' : ' disabled' ) . '>' . p3d_icon( 'cart', 16 ) . ' <span>Adaugă în coș</span></button></div>'; // phpcs:ignore
		echo '</div></article>';
	}
	echo '<div class="p3d-quick-all"><h2>Toate filamentele</h2></div></section>';
	?>
	<script>
	(function () {
		var ajax = (window.wc_add_to_cart_params && wc_add_to_cart_params.wc_ajax_url) ? wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart') : '/?wc-ajax=add_to_cart';
		document.querySelectorAll('.p3d-qline').forEach(function (card) {
			var items = JSON.parse(card.dataset.items), cur = items[+card.querySelector('.p3d-qdot.is-on').dataset.i];
			var img = card.querySelector('.p3d-qimg img'), links = card.querySelectorAll('.p3d-qimg, .p3d-qname');
			var name = card.querySelector('.p3d-qname'), price = card.querySelector('.p3d-qp'), st = card.querySelector('.p3d-qst'), btn = card.querySelector('.p3d-qadd');
			card.querySelector('.p3d-qsw').addEventListener('click', function (e) {
				var d = e.target.closest('.p3d-qdot'); if (!d) return;
				card.querySelectorAll('.p3d-qdot').forEach(function (x) { x.classList.toggle('is-on', x === d); });
				cur = items[+d.dataset.i];
				if (cur.img) img.src = cur.img;
				links.forEach(function (a) { a.href = cur.url; });
				name.textContent = cur.name; price.innerHTML = cur.ph;
				st.textContent = cur.in ? 'În stoc' : 'Stoc epuizat'; st.classList.toggle('is-out', !cur.in);
				btn.disabled = !cur.in; btn.querySelector('span').textContent = 'Adaugă în coș'; btn.classList.remove('is-added');
			});
			btn.addEventListener('click', function () {
				if (!cur.in) return;
				btn.disabled = true; btn.querySelector('span').textContent = 'Se adaugă…';
				var body = new URLSearchParams(); body.append('product_id', cur.id); body.append('quantity', 1);
				fetch(ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
					btn.disabled = false;
					if (res && res.error) { window.location = cur.url; return; }
					btn.classList.add('is-added'); btn.querySelector('span').textContent = 'Adăugat ✓';
					document.querySelectorAll('.nav-cart-dot').forEach(function (x) { x.hidden = false; });
					if (window.jQuery && res && res.fragments) { jQuery(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, jQuery(btn)]); }
				}).catch(function () { window.location = cur.url; });
			});
		});
	})();
	</script>
	<?php
}, 6 );
