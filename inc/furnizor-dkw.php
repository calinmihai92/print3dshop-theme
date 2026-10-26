<?php
/**
 * Furnizor filamente dkw.ro: sincronizare automată (produse, prețuri, stoc) din API-ul public
 * https://dkw.ro/api/products în WooCommerce, cu adaos pe tranșe de preț.
 *
 *  - Produse → Filamente dkw.ro: setări, „Sincronizează acum”, ultimul rezultat.
 *  - Rulează automat la fiecare oră (WP-Cron), doar dacă e activată.
 *  - Produsele noi se creează câte un lot pe rulare (cu poze); prețul și stocul se actualizează la toate.
 *  - Produsele dispărute din API sau inactive trec pe „stoc epuizat”; nu se șterge nimic.
 *  - Toate primesc „În stoc furnizor”, termenul de livrare și nota producătorului (vezi inc/shop.php).
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

const P3D_DKW_OPT  = 'p3d_dkw_settings';
const P3D_DKW_LOG  = 'p3d_dkw_log';
const P3D_DKW_HOOK = 'p3d_dkw_sync';

function p3d_dkw_defaults() {
	return array(
		'enabled'  => 0,
		'api'      => 'https://dkw.ro/api/products',
		'base'     => 'https://dkw.ro',
		// „prag:procent” pe rânduri; ultima linie cu prag 0 = peste ultimul prag.
		'tiers'    => "60:15\n120:12\n0:10",
		'livrare'  => '2–4 zile lucrătoare',
		'include'  => 'filamente-',
		'exclude'  => 'filamente-accesorii',
		'batch'    => 25,
		'status'   => 'draft',
		'order'    => 100,
	);
}

function p3d_dkw_opts() {
	return wp_parse_args( (array) get_option( P3D_DKW_OPT, array() ), p3d_dkw_defaults() );
}

/** Adaosul (%) pentru un preț al furnizorului. */
function p3d_dkw_markup( $price, $opts = null ) {
	$opts  = $opts ? $opts : p3d_dkw_opts();
	$tiers = array();
	foreach ( preg_split( '/\r?\n/', (string) $opts['tiers'] ) as $line ) {
		if ( preg_match( '/^\s*(\d+(?:[.,]\d+)?)\s*:\s*(\d+(?:[.,]\d+)?)\s*$/', $line, $m ) ) {
			$tiers[] = array( (float) str_replace( ',', '.', $m[1] ), (float) str_replace( ',', '.', $m[2] ) );
		}
	}
	$rest = 0;
	foreach ( $tiers as $t ) {
		if ( 0.0 === $t[0] ) {
			$rest = $t[1];
		}
	}
	usort( $tiers, function ( $a, $b ) { return $a[0] <=> $b[0]; } );
	foreach ( $tiers as $t ) {
		if ( $t[0] > 0 && $price <= $t[0] ) {
			return $t[1];
		}
	}
	return $rest;
}

/** Prețul nostru: prețul furnizorului + adaos, rotunjit în sus la leu întreg. */
function p3d_dkw_price( $price, $opts = null ) {
	$price = (float) $price;
	return $price > 0 ? (string) ceil( $price * ( 1 + p3d_dkw_markup( $price, $opts ) / 100 ) ) : '';
}

function p3d_dkw_brand( $item ) {
	$name = isset( $item['category']['nameRo'] ) ? (string) $item['category']['nameRo'] : '';
	$name = trim( preg_replace( '/^filamente\s*/i', '', $name ) );
	return '' !== $name ? $name : 'dkw';
}

function p3d_dkw_maker_url( $brand ) {
	$map = array(
		'bambu'   => 'https://bambulab.com',
		'sunlu'   => 'https://www.sunlu.com',
		'eryone'  => 'https://eryone3d.com',
		'elegoo'  => 'https://www.elegoo.com',
		'anycubic'=> 'https://www.anycubic.com',
	);
	$b = strtolower( $brand );
	foreach ( $map as $k => $url ) {
		if ( false !== strpos( $b, $k ) ) {
			return $url;
		}
	}
	return '';
}

/** Codul culorii (aproximativ) după numele din titlu, pentru bulinele de culoare. */
function p3d_dkw_hex( $color ) {
	$c   = strtolower( remove_accents( $color ) );
	$map = array(
		'black' => '#17181b', 'negru' => '#17181b', 'white' => '#f4f2ee', 'alb' => '#f4f2ee', 'ivory' => '#f3ecd8', 'beige' => '#d9c7a3', 'bej' => '#d9c7a3',
		'red' => '#d62828', 'rosu' => '#d62828', 'blue' => '#1f6fd1', 'albastru' => '#1f6fd1', 'cyan' => '#22b8cf', 'turquoise' => '#2ec4b6', 'turcoaz' => '#2ec4b6',
		'green' => '#2fa84f', 'verde' => '#2fa84f', 'mint' => '#8fdcc2', 'yellow' => '#f5c518', 'galben' => '#f5c518', 'orange' => '#f26a1b', 'portocaliu' => '#f26a1b',
		'pink' => '#f48fb1', 'roz' => '#f48fb1', 'purple' => '#8e44ad', 'violet' => '#8e44ad', 'mov' => '#8e44ad', 'gold' => '#c9a23a', 'auriu' => '#c9a23a',
		'silver' => '#b8bcc2', 'argintiu' => '#b8bcc2', 'grey' => '#8a8f98', 'gray' => '#8a8f98', 'gri' => '#8a8f98', 'brown' => '#7a4b2a', 'maro' => '#7a4b2a',
		'wood' => '#a47148', 'walnut' => '#5c3a21', 'wallnut' => '#5c3a21', 'transparent' => '#e8eef2', 'clear' => '#e8eef2', 'natural' => '#efe6d2',
	);
	foreach ( $map as $k => $hex ) {
		if ( preg_match( '/\b' . preg_quote( $k, '/' ) . '\b/', $c ) ) {
			return $hex;
		}
	}
	return '';
}

/* ---------- Citire API ---------- */

function p3d_dkw_fetch_all( $opts, &$error ) {
	$items = array();
	$page  = 1;
	do {
		$res = wp_remote_get( add_query_arg( array( 'page' => $page, 'limit' => 100 ), $opts['api'] ), array( 'timeout' => 25, 'headers' => array( 'Accept' => 'application/json' ) ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			$error = is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $res );
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) || ! isset( $data['products'] ) ) {
			$error = 'Răspuns neașteptat de la API.';
			return null;
		}
		$items = array_merge( $items, (array) $data['products'] );
		$pages = isset( $data['pagination']['totalPages'] ) ? (int) $data['pagination']['totalPages'] : 1;
		$page++;
	} while ( $page <= $pages && $page <= 50 );
	return $items;
}

function p3d_dkw_included( $item, $opts ) {
	$slug = isset( $item['category']['slug'] ) ? (string) $item['category']['slug'] : '';
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $opts['exclude'] ) ) ) as $ex ) {
		if ( $slug === $ex ) {
			return false;
		}
	}
	$inc = array_filter( array_map( 'trim', explode( ',', (string) $opts['include'] ) ) );
	if ( ! $inc ) {
		return true;
	}
	foreach ( $inc as $pre ) {
		if ( 0 === strpos( $slug, $pre ) ) {
			return true;
		}
	}
	return false;
}

/* ---------- Categorii WooCommerce ---------- */

function p3d_dkw_term( $name, $parent = 0 ) {
	$slug = sanitize_title( $parent ? 'filament-' . $name : $name );
	$t    = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $parent ? 'Filament ' . $name : $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

/* ---------- Creare / actualizare ---------- */

function p3d_dkw_existing() {
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_dkw_id'" ); // phpcs:ignore
	$map  = array();
	foreach ( $rows as $r ) {
		$map[ $r->meta_value ] = (int) $r->post_id;
	}
	return $map;
}

function p3d_dkw_split( $title ) {
	$parts = preg_split( '/\s+[-–]\s+/u', trim( (string) $title ), 2 );
	return array( trim( $parts[0] ), isset( $parts[1] ) ? trim( $parts[1] ) : '' );
}

/** Detaliile afișate în descrierea scurtă: brand, material, culoare, cod culoare, variantă. */
function p3d_dkw_specs( $item ) {
	$brand               = p3d_dkw_brand( $item );
	list( $mat, $color ) = p3d_dkw_split( $item['titleRo'] ?? '' );
	$material            = trim( (string) ( $item['material'] ?? '' ) );
	$material            = '' !== $material ? $material : $mat;
	if ( $material === strtolower( $material ) ) {
		$material = strtoupper( $material );
	}
	$code                = '';
	if ( preg_match( '/\(([^)]+)\)/', $color, $m ) ) {
		$code  = trim( $m[1] );
		$color = trim( str_replace( $m[0], '', $color ) );
	}
	$title = strtolower( (string) ( $item['titleRo'] ?? '' ) );
	$color = trim( preg_replace( '/\b(refill|spool)\b/i', '', $color ) );
	$color = '' !== $color ? mb_strtoupper( mb_substr( $color, 0, 1 ) ) . mb_substr( $color, 1 ) : '';
	$rows  = array( 'Brand' => $brand, 'Material' => $material );
	if ( '' !== $color ) {
		$rows['Culoare'] = $color;
	}
	if ( '' !== $code ) {
		$rows['Cod culoare'] = $code;
	}
	if ( false !== strpos( $title, 'refill' ) ) {
		$rows['Variantă'] = 'Refill (fără bobină)';
	} elseif ( false !== strpos( $title, 'spool' ) ) {
		$rows['Variantă'] = 'Cu bobină';
	}
	return $rows;
}

function p3d_dkw_specs_html( $rows ) {
	$out = '<ul class="p3d-short-specs">';
	foreach ( $rows as $k => $v ) {
		$out .= '<li><span>' . esc_html( $k ) . ':</span> <strong>' . esc_html( $v ) . '</strong></li>';
	}
	return $out . '</ul>';
}

function p3d_dkw_apply( WC_Product $p, $item, $opts, $is_new ) {
	$stock  = max( 0, (int) ( $item['stock'] ?? 0 ) );
	$active = ! empty( $item['isActive'] );
	$price  = p3d_dkw_price( $item['priceRon'] ?? 0, $opts );
	if ( '' !== $price && $p->get_regular_price() !== $price ) {
		$p->set_regular_price( $price );
	}
	$p->set_manage_stock( true );
	$p->set_stock_quantity( $active ? $stock : 0 );
	$p->set_stock_status( $active && $stock > 0 ? 'instock' : 'outofstock' );
	$p->set_backorders( 'no' );
	$p->update_meta_data( '_dkw_price', (string) ( $item['priceRon'] ?? '' ) );
	$p->update_meta_data( '_dkw_synced', time() );
	$p->update_meta_data( '_p3d_livrare', $opts['livrare'] );
	$p->update_meta_data( '_p3d_furnizor', 'yes' );
	$specs = p3d_dkw_specs( $item );
	$p->set_short_description( p3d_dkw_specs_html( $specs ) );
	$p->update_meta_data( '_dkw_material', $specs['Material'] );
	if ( isset( $specs['Culoare'] ) ) {
		$p->update_meta_data( '_p3d_culoare', $specs['Culoare'] );
	}
	if ( isset( $specs['Cod culoare'] ) && preg_match( '/^#[0-9a-f]{6}$/i', $specs['Cod culoare'] ) ) {
		$p->update_meta_data( '_p3d_culoare_hex', $specs['Cod culoare'] );
	}
	if ( $is_new ) {
		$brand              = p3d_dkw_brand( $item );
		list( $mat, $color ) = p3d_dkw_split( $item['titleRo'] ?? '' );
		$title              = 'Filament ' . $brand . ' ' . $mat . ( '' !== $color ? ' – ' . $color : '' );
		$p->set_name( $title );
		$p->set_slug( sanitize_title( 'filament-' . ( $item['slug'] ?? $title ) ) );
		$p->set_status( $opts['status'] );
		$p->set_menu_order( (int) $opts['order'] );
		$p->set_catalog_visibility( 'visible' );
		$p->set_sku( 'DKW-' . strtoupper( substr( sanitize_title( $item['slug'] ?? $item['id'] ), 0, 40 ) ) );
		$desc  = '<p>Filament ' . esc_html( $brand ) . ' ' . esc_html( $mat ) . ( '' !== $color ? ', culoarea ' . esc_html( $color ) : '' ) . ', pentru imprimante 3D FDM.</p>';
		$orig  = trim( wp_strip_all_tags( (string) ( $item['descRo'] ?? '' ) ) );
		if ( '' !== $orig && $orig !== trim( (string) ( $item['titleRo'] ?? '' ) ) ) {
			$desc .= wpautop( esc_html( $orig ) );
		}
		$desc .= '<p>Produsul este livrat de partenerul nostru, distribuitor de filamente din România. Stocul se actualizează automat.</p>';
		$p->set_description( $desc );
		$parent = p3d_dkw_term( 'Filamente' );
		$child  = $parent ? p3d_dkw_term( $brand, $parent ) : 0;
		$p->set_category_ids( array_values( array_filter( array( $parent, $child ) ) ) );
		$p->update_meta_data( '_dkw_id', (string) $item['id'] );
		$p->update_meta_data( '_p3d_producator_url', p3d_dkw_maker_url( $brand ) );
		$p->update_meta_data( '_p3d_grup', 'dkw-' . sanitize_title( $brand . '-' . $mat ) );
		if ( '' !== $color ) {
			$p->update_meta_data( '_p3d_culoare_hex', p3d_dkw_hex( $color ) );
		}
	}
	return $p;
}

function p3d_dkw_images( $product_id, $item, $opts ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$imgs = (array) ( $item['images'] ?? array() );
	usort( $imgs, function ( $a, $b ) {
		return ( empty( $b['isPrimary'] ) <=> empty( $a['isPrimary'] ) ) ?: ( (int) ( $a['sortOrder'] ?? 0 ) <=> (int) ( $b['sortOrder'] ?? 0 ) );
	} );
	$ids = array();
	foreach ( array_slice( $imgs, 0, 4 ) as $img ) {
		$url = (string) ( $img['url'] ?? '' );
		if ( '' === $url ) {
			continue;
		}
		// API-ul dă „/products/{id}/{fișier}”; pozele se servesc din „/api/images/{id}/{fișier}”.
		if ( 0 === strpos( $url, '/products/' ) ) {
			$url = '/api/images/' . substr( $url, strlen( '/products/' ) );
		}
		if ( 0 === strpos( $url, '/' ) ) {
			$url = rtrim( $opts['base'], '/' ) . $url;
		}
		$id = media_sideload_image( $url, $product_id, get_the_title( $product_id ), 'id' );
		if ( ! is_wp_error( $id ) ) {
			$ids[] = (int) $id;
			update_post_meta( $id, '_wp_attachment_image_alt', get_the_title( $product_id ) );
		}
	}
	if ( $ids ) {
		set_post_thumbnail( $product_id, $ids[0] );
		update_post_meta( $product_id, '_product_image_gallery', implode( ',', array_slice( $ids, 1 ) ) );
	}
	return count( $ids );
}

/** O rulare: actualizează tot ce există și creează un lot de produse noi. */
function p3d_dkw_run( $manual = false ) {
	$opts = p3d_dkw_opts();
	if ( ! $manual && empty( $opts['enabled'] ) ) {
		return;
	}
	if ( get_transient( 'p3d_dkw_lock' ) ) {
		return;
	}
	set_transient( 'p3d_dkw_lock', 1, 10 * MINUTE_IN_SECONDS );
	@set_time_limit( 300 ); // phpcs:ignore
	$log   = array( 'time' => time(), 'manual' => $manual, 'created' => 0, 'updated' => 0, 'out' => 0, 'pending' => 0, 'total' => 0, 'error' => '' );
	$error = '';
	$items = p3d_dkw_fetch_all( $opts, $error );
	if ( null === $items ) {
		$log['error'] = $error;
		update_option( P3D_DKW_LOG, $log, false );
		delete_transient( 'p3d_dkw_lock' );
		return $log;
	}
	$existing = p3d_dkw_existing();
	$seen     = array();
	$new_left = max( 1, (int) $opts['batch'] );
	foreach ( $items as $item ) {
		if ( empty( $item['id'] ) || ! p3d_dkw_included( $item, $opts ) ) {
			continue;
		}
		$log['total']++;
		$id          = (string) $item['id'];
		$seen[ $id ] = true;
		if ( isset( $existing[ $id ] ) ) {
			$p = wc_get_product( $existing[ $id ] );
			if ( $p ) {
				p3d_dkw_apply( $p, $item, $opts, false )->save();
				$log['updated']++;
				// Produse create fără poză (ex. descărcare eșuată): reîncercăm, în limita lotului.
				if ( ! $p->get_image_id() && $new_left > 0 ) {
					p3d_dkw_images( $p->get_id(), $item, $opts );
					$new_left--;
				}
			}
			continue;
		}
		if ( empty( $item['isActive'] ) ) {
			continue;
		}
		if ( $new_left <= 0 ) {
			$log['pending']++;
			continue;
		}
		try {
			$p   = p3d_dkw_apply( new WC_Product_Simple(), $item, $opts, true );
			$pid = $p->save();
			p3d_dkw_images( $pid, $item, $opts );
			$log['created']++;
			$new_left--;
		} catch ( Exception $e ) {
			$log['error'] = $e->getMessage();
		}
	}
	// Produse care nu mai apar la furnizor: stoc epuizat.
	foreach ( $existing as $dkw_id => $pid ) {
		if ( ! isset( $seen[ $dkw_id ] ) ) {
			$p = wc_get_product( $pid );
			if ( $p && $p->is_in_stock() ) {
				$p->set_stock_quantity( 0 );
				$p->set_stock_status( 'outofstock' );
				$p->save();
				$log['out']++;
			}
		}
	}
	update_option( P3D_DKW_LOG, $log, false );
	delete_transient( 'p3d_dkw_lock' );
	return $log;
}

add_action( P3D_DKW_HOOK, function () { p3d_dkw_run( false ); } );

add_action( 'init', function () {
	$on = ! empty( p3d_dkw_opts()['enabled'] );
	$at = wp_next_scheduled( P3D_DKW_HOOK );
	if ( $on && ! $at ) {
		wp_schedule_event( time() + 120, 'hourly', P3D_DKW_HOOK );
	} elseif ( ! $on && $at ) {
		wp_clear_scheduled_hook( P3D_DKW_HOOK );
	}
} );

/* ---------- Pagina din administrare ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=product', 'Filamente dkw.ro', 'Filamente dkw.ro', 'manage_woocommerce', 'p3d-dkw', 'p3d_dkw_page' );
} );

add_action( 'admin_post_p3d_dkw_save', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'p3d_dkw' ) ) {
		wp_die( 'Nu ai voie.' );
	}
	$in   = wp_unslash( $_POST ); // phpcs:ignore
	$opts = p3d_dkw_opts();
	$opts['enabled'] = empty( $in['enabled'] ) ? 0 : 1;
	$opts['tiers']   = sanitize_textarea_field( $in['tiers'] ?? $opts['tiers'] );
	$opts['livrare'] = sanitize_text_field( $in['livrare'] ?? $opts['livrare'] );
	$opts['include'] = sanitize_text_field( $in['include'] ?? $opts['include'] );
	$opts['exclude'] = sanitize_text_field( $in['exclude'] ?? $opts['exclude'] );
	$opts['batch']   = max( 1, min( 100, (int) ( $in['batch'] ?? 25 ) ) );
	$opts['status']  = in_array( $in['status'] ?? '', array( 'publish', 'draft' ), true ) ? $in['status'] : 'publish';
	update_option( P3D_DKW_OPT, $opts, false );
	$msg = 'saved';
	if ( ! empty( $in['run'] ) ) {
		p3d_dkw_run( true );
		$msg = 'synced';
	}
	if ( ! empty( $in['reprice'] ) ) {
		$n = 0;
		foreach ( p3d_dkw_existing() as $pid ) {
			$p = wc_get_product( $pid );
			$b = $p ? (float) $p->get_meta( '_dkw_price' ) : 0;
			if ( $p && $b > 0 ) {
				$p->set_regular_price( p3d_dkw_price( $b, $opts ) );
				$p->save();
				$n++;
			}
		}
		$msg = 'repriced-' . $n;
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=p3d-dkw&m=' . $msg ) );
	exit;
} );

function p3d_dkw_page() {
	$o   = p3d_dkw_opts();
	$log = (array) get_option( P3D_DKW_LOG, array() );
	$cnt = count( p3d_dkw_existing() );
	$m   = isset( $_GET['m'] ) ? sanitize_key( $_GET['m'] ) : ''; // phpcs:ignore
	echo '<div class="wrap"><h1>Filamente dkw.ro</h1>';
	if ( $m ) {
		echo '<div class="notice notice-success"><p>' . esc_html( 'saved' === $m ? 'Setările au fost salvate.' : ( 'synced' === $m ? 'Sincronizare rulată.' : 'Prețuri recalculate: ' . str_replace( 'repriced-', '', $m ) . ' produse.' ) ) . '</p></div>';
	}
	echo '<p>Produsele, prețurile și stocurile se iau automat din <a href="https://dkw.ro" target="_blank" rel="noopener">dkw.ro</a>, la fiecare oră. Prețul de vânzare = prețul dkw.ro + adaos, rotunjit în sus la leu.</p>';
	echo '<p><strong>Produse importate:</strong> ' . (int) $cnt . '</p>';
	if ( $log ) {
		printf(
			'<p><strong>Ultima sincronizare:</strong> %s%s — %d produse la furnizor (după filtre), %d create, %d actualizate, %d trecute pe stoc epuizat, %d în așteptare (se creează la următoarele rulări).%s</p>',
			esc_html( wp_date( 'j M Y, H:i', (int) $log['time'] ) ),
			! empty( $log['manual'] ) ? ' (manual)' : '',
			(int) $log['total'], (int) $log['created'], (int) $log['updated'], (int) $log['out'], (int) $log['pending'],
			$log['error'] ? ' <span style="color:#b32d2e">Eroare: ' . esc_html( $log['error'] ) . '</span>' : ''
		);
	}
	$next = wp_next_scheduled( P3D_DKW_HOOK );
	echo '<p><strong>Următoarea rulare automată:</strong> ' . ( $next ? esc_html( wp_date( 'j M Y, H:i', $next ) ) : 'oprită' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="p3d_dkw_save">';
	wp_nonce_field( 'p3d_dkw' );
	echo '<table class="form-table">';
	echo '<tr><th>Sincronizare automată</th><td><label><input type="checkbox" name="enabled" value="1"' . checked( ! empty( $o['enabled'] ), true, false ) . '> Pornită (la fiecare oră)</label></td></tr>';
	echo '<tr><th>Adaos pe tranșe</th><td><textarea name="tiers" rows="4" cols="20">' . esc_textarea( $o['tiers'] ) . '</textarea><p class="description">Un rând pe tranșă: <code>prag:procent</code>. Ex. <code>60:15</code> = până la 60 lei, +15%. Rândul <code>0:10</code> = peste ultimul prag, +10%.</p></td></tr>';
	echo '<tr><th>Termen de livrare</th><td><input type="text" name="livrare" value="' . esc_attr( $o['livrare'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Categorii incluse</th><td><input type="text" name="include" value="' . esc_attr( $o['include'] ) . '" class="regular-text"><p class="description">Începutul slug-ului categoriei de pe dkw.ro (separate prin virgulă).</p></td></tr>';
	echo '<tr><th>Categorii excluse</th><td><input type="text" name="exclude" value="' . esc_attr( $o['exclude'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Produse noi pe rulare</th><td><input type="number" name="batch" min="1" max="100" value="' . (int) $o['batch'] . '"><p class="description">Pozele se descarcă la creare, deci produsele noi intră pe loturi.</p></td></tr>';
	echo '<tr><th>Produsele noi apar</th><td><select name="status"><option value="publish"' . selected( $o['status'], 'publish', false ) . '>Publicate</option><option value="draft"' . selected( $o['status'], 'draft', false ) . '>Ciornă (le public eu)</option></select></td></tr>';
	echo '</table><p>';
	submit_button( 'Salvează', 'primary', 'save', false );
	echo ' ';
	submit_button( 'Salvează și sincronizează acum', 'secondary', 'run', false );
	echo ' ';
	submit_button( 'Recalculează prețurile (după adaos)', 'secondary', 'reprice', false );
	echo '</p></form>';
	echo '<h2>Exemple de prețuri</h2><table class="widefat" style="max-width:420px"><thead><tr><th>Preț dkw.ro</th><th>Adaos</th><th>Prețul nostru</th></tr></thead><tbody>';
	foreach ( array( 40, 55, 75, 90, 140 ) as $x ) {
		printf( '<tr><td>%d lei</td><td>%s%%</td><td>%s lei</td></tr>', $x, esc_html( (string) p3d_dkw_markup( $x, $o ) ), esc_html( p3d_dkw_price( $x, $o ) ) );
	}
	echo '</tbody></table></div>';
}

/* =====================================================================
 * Comenzi: trimitere automată la dkw.ro (dropshipping) + AWB înapoi
 * =====================================================================
 *  - Când o comandă trece în „În procesare” (la ramburs: imediat după plasare), produsele dkw
 *    din ea se trimit furnizorului: prin webhook (JSON semnat HMAC) și/sau pe email.
 *  - Furnizorul trimite înapoi AWB-ul la /wp-json/p3d/v1/dkw-awb (același secret):
 *    comanda primește nota cu AWB-ul (trimisă și clientului pe email) și trece în „Finalizată”.
 */

function p3d_dkw_order_opts() {
	return wp_parse_args( (array) get_option( 'p3d_dkw_orders', array() ), array(
		'mode'    => 'none', // none | webhook | email | both
		'url'     => '',
		'secret'  => '',
		'email'   => '',
		'sender'  => 'Print3dShop.ro',
		'complete'=> 1,
	) );
}

function p3d_dkw_order_items( WC_Order $order ) {
	$items = array();
	foreach ( $order->get_items() as $item ) {
		$pid    = $item->get_product_id();
		$dkw_id = (string) get_post_meta( $pid, '_dkw_id', true );
		if ( '' === $dkw_id ) {
			continue;
		}
		$items[] = array(
			'dkw_id'      => $dkw_id,
			'sku'         => (string) get_post_meta( $pid, '_sku', true ),
			'name'        => $item->get_name(),
			'qty'         => (int) $item->get_quantity(),
			'dkw_price'   => (float) get_post_meta( $pid, '_dkw_price', true ),
		);
	}
	return $items;
}

function p3d_dkw_payload( WC_Order $order, $items ) {
	$all_dkw = count( $items ) === count( $order->get_items() );
	$ship    = $order->has_shipping_address();
	$g       = function ( $f ) use ( $order, $ship ) {
		$m = ( $ship ? 'get_shipping_' : 'get_billing_' ) . $f;
		return (string) $order->$m();
	};
	$states  = WC()->countries->get_states( 'RO' );
	$county  = $g( 'state' );
	return array(
		'source'    => 'print3dshop.ro',
		'order'     => (string) $order->get_order_number(),
		'order_id'  => $order->get_id(),
		'created'   => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
		'sender'    => p3d_dkw_order_opts()['sender'],
		'customer'  => array(
			'name'     => trim( $g( 'first_name' ) . ' ' . $g( 'last_name' ) ),
			'phone'    => (string) $order->get_billing_phone(),
			'email'    => (string) $order->get_billing_email(),
			'address'  => trim( $g( 'address_1' ) . ' ' . $g( 'address_2' ) ),
			'city'     => $g( 'city' ),
			'county'   => isset( $states[ $county ] ) ? $states[ $county ] : $county,
			'postcode' => $g( 'postcode' ),
			'country'  => $g( 'country' ) ?: 'RO',
		),
		'payment'   => array(
			'method'       => $order->get_payment_method(),
			'cod'          => 'cod' === $order->get_payment_method(),
			// Suma de încasat la livrare: doar dacă toată comanda e de la dkw (altfel se stabilește manual).
			'cod_amount'   => ( 'cod' === $order->get_payment_method() && $all_dkw ) ? (float) $order->get_total() : null,
		),
		'shipping'  => array( 'method' => $order->get_shipping_method(), 'pickup' => false !== stripos( $order->get_shipping_method(), 'ridicare' ) ),
		'note'      => (string) $order->get_customer_note(),
		'items'     => $items,
		'complete'  => $all_dkw,
	);
}

function p3d_dkw_sign( $body, $secret ) {
	return hash_hmac( 'sha256', $body, (string) $secret );
}

function p3d_dkw_forward( $order_id, $attempt = 1 ) {
	$order = wc_get_order( $order_id );
	$o     = p3d_dkw_order_opts();
	if ( ! $order || 'none' === $o['mode'] || $order->get_meta( '_dkw_forwarded' ) ) {
		return;
	}
	$items = p3d_dkw_order_items( $order );
	if ( ! $items ) {
		return;
	}
	$payload = p3d_dkw_payload( $order, $items );
	$ok      = false;
	if ( in_array( $o['mode'], array( 'webhook', 'both' ), true ) && $o['url'] ) {
		$body = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		$res  = wp_remote_post( $o['url'], array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json', 'X-P3D-Signature' => p3d_dkw_sign( $body, $o['secret'] ) ),
			'body'    => $body,
		) );
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		if ( $code >= 200 && $code < 300 ) {
			$ok   = true;
			$resp = json_decode( wp_remote_retrieve_body( $res ), true );
			$ref  = is_array( $resp ) && ! empty( $resp['id'] ) ? ' (comanda la dkw: ' . sanitize_text_field( $resp['id'] ) . ')' : '';
			$order->add_order_note( 'Comanda a fost trimisă automat la dkw.ro' . $ref . '.' );
		} else {
			$err = is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . $code;
			$order->add_order_note( 'Trimiterea la dkw.ro a eșuat (' . $err . '), încercarea ' . $attempt . '.' );
			if ( $attempt < 4 ) {
				wp_schedule_single_event( time() + 15 * MINUTE_IN_SECONDS * $attempt, 'p3d_dkw_forward_retry', array( $order_id, $attempt + 1 ) );
			} else {
				wp_mail( get_option( 'admin_email' ), 'Comanda #' . $order->get_order_number() . ' nu a ajuns la dkw.ro', 'Trimiterea automată a eșuat de 4 ori. Verifică comanda: ' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) );
			}
		}
	}
	if ( in_array( $o['mode'], array( 'email', 'both' ), true ) && is_email( $o['email'] ) ) {
		$c    = $payload['customer'];
		$rows = '';
		foreach ( $items as $i ) {
			$rows .= '<tr><td>' . esc_html( $i['sku'] ) . '</td><td>' . esc_html( $i['name'] ) . '</td><td>' . (int) $i['qty'] . '</td></tr>';
		}
		$cod  = null !== $payload['payment']['cod_amount'] ? number_format( $payload['payment']['cod_amount'], 2, ',', '.' ) . ' lei' : ( $payload['payment']['cod'] ? 'de stabilit (comanda conține și alte produse)' : 'nu (plătită)' );
		$html = '<h2>Comandă nouă de la print3dshop.ro – #' . esc_html( $payload['order'] ) . '</h2>'
			. '<p><strong>Livrare către:</strong><br>' . esc_html( $c['name'] ) . '<br>' . esc_html( $c['phone'] ) . ' · ' . esc_html( $c['email'] ) . '<br>' . esc_html( $c['address'] ) . ', ' . esc_html( $c['city'] ) . ', ' . esc_html( $c['county'] ) . ' ' . esc_html( $c['postcode'] ) . '</p>'
			. '<p><strong>Ramburs de încasat:</strong> ' . esc_html( $cod ) . '</p>'
			. ( $payload['note'] ? '<p><strong>Notă client:</strong> ' . esc_html( $payload['note'] ) . '</p>' : '' )
			. '<table border="1" cellpadding="6" cellspacing="0"><tr><th>Cod</th><th>Produs</th><th>Buc.</th></tr>' . $rows . '</table>'
			. '<p>Expeditor pe colet: ' . esc_html( $payload['sender'] ) . '. Vă rugăm să trimiteți AWB-ul ca răspuns la acest email.</p>';
		if ( wp_mail( $o['email'], 'Comandă print3dshop.ro #' . $payload['order'], $html, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . get_option( 'admin_email' ) ) ) ) {
			$ok = true;
			$order->add_order_note( 'Comanda a fost trimisă pe email la ' . $o['email'] . '.' );
		}
	}
	if ( $ok ) {
		$order->update_meta_data( '_dkw_forwarded', time() );
		$order->save();
	}
	if ( ! $payload['complete'] ) {
		$order->add_order_note( 'Atenție: comanda conține și produse proprii; doar produsele dkw au fost trimise furnizorului. Rambursul se stabilește manual.' );
	}
}

add_action( 'woocommerce_order_status_processing', 'p3d_dkw_forward', 20, 1 );
add_action( 'p3d_dkw_forward_retry', 'p3d_dkw_forward', 10, 2 );

// AWB de la furnizor.
add_action( 'rest_api_init', function () {
	register_rest_route( 'p3d/v1', '/dkw-awb', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$o    = p3d_dkw_order_opts();
			$body = $req->get_body();
			$sig  = (string) $req->get_header( 'x_p3d_signature' );
			if ( ! $o['secret'] || ! hash_equals( p3d_dkw_sign( $body, $o['secret'] ), $sig ) ) {
				return new WP_Error( 'forbidden', 'Semnătură invalidă.', array( 'status' => 403 ) );
			}
			$d     = json_decode( $body, true );
			$order = wc_get_order( (int) ( $d['order_id'] ?? 0 ) );
			if ( ! $order || empty( $d['awb'] ) ) {
				return new WP_Error( 'bad_request', 'Comandă sau AWB lipsă.', array( 'status' => 400 ) );
			}
			$awb     = sanitize_text_field( $d['awb'] );
			$courier = sanitize_text_field( $d['courier'] ?? '' );
			$url     = esc_url_raw( $d['tracking_url'] ?? '' );
			$order->update_meta_data( '_dkw_awb', $awb );
			$order->update_meta_data( '_dkw_courier', $courier );
			$order->update_meta_data( '_dkw_tracking_url', $url );
			$order->save();
			$msg = 'Comanda ta a fost predată curierului' . ( $courier ? ' ' . $courier : '' ) . '. AWB: ' . $awb . ( $url ? ' – urmărire: ' . $url : '' );
			$order->add_order_note( $msg, true ); // notă către client (primește email)
			if ( $o['complete'] && p3d_dkw_order_items( $order ) && count( p3d_dkw_order_items( $order ) ) === count( $order->get_items() ) ) {
				$order->update_status( 'completed', 'AWB primit de la dkw.ro.' );
			}
			return array( 'ok' => true );
		},
	) );
} );

add_action( 'admin_post_p3d_dkw_orders', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'p3d_dkw_orders' ) ) {
		wp_die( 'Nu ai voie.' );
	}
	$in = wp_unslash( $_POST ); // phpcs:ignore
	$o  = p3d_dkw_order_opts();
	$o['mode']     = in_array( $in['mode'] ?? '', array( 'none', 'webhook', 'email', 'both' ), true ) ? $in['mode'] : 'none';
	$o['url']      = esc_url_raw( $in['url'] ?? '' );
	$o['email']    = sanitize_email( $in['email'] ?? '' );
	$o['sender']   = sanitize_text_field( $in['sender'] ?? 'Print3dShop.ro' );
	$o['complete'] = empty( $in['complete'] ) ? 0 : 1;
	if ( ! $o['secret'] || ! empty( $in['newsecret'] ) ) {
		$o['secret'] = wp_generate_password( 40, false );
	}
	update_option( 'p3d_dkw_orders', $o, false );
	wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=p3d-dkw&m=saved' ) );
	exit;
} );

add_action( 'admin_footer-product_page_p3d-dkw', function () {
	$o = p3d_dkw_order_opts();
	echo '<div class="wrap"><h2>Comenzi către dkw.ro (dropshipping)</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="p3d_dkw_orders">';
	wp_nonce_field( 'p3d_dkw_orders' );
	echo '<table class="form-table">';
	echo '<tr><th>Trimitere comenzi</th><td><select name="mode">';
	foreach ( array( 'none' => 'Oprită', 'webhook' => 'Automat, prin API (webhook)', 'email' => 'Pe email', 'both' => 'API + email' ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $o['mode'], $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select><p class="description">Se trimit doar produsele dkw, când comanda trece în „În procesare”.</p></td></tr>';
	echo '<tr><th>URL API dkw (webhook)</th><td><input type="url" name="url" value="' . esc_attr( $o['url'] ) . '" class="regular-text" placeholder="https://dkw.ro/api/dropship/orders"></td></tr>';
	echo '<tr><th>Email furnizor</th><td><input type="email" name="email" value="' . esc_attr( $o['email'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Expeditor pe colet</th><td><input type="text" name="sender" value="' . esc_attr( $o['sender'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th>Secret comun (HMAC)</th><td><code>' . esc_html( $o['secret'] ? $o['secret'] : '(se generează la salvare)' ) . '</code> <label><input type="checkbox" name="newsecret" value="1"> generează altul</label><p class="description">Îl dai furnizorului: semnează AWB-urile trimise înapoi și verifică comenzile primite (antetul X-P3D-Signature).</p></td></tr>';
	echo '<tr><th>Adresa pentru AWB</th><td><code>' . esc_html( rest_url( 'p3d/v1/dkw-awb' ) ) . '</code></td></tr>';
	echo '<tr><th>La primirea AWB-ului</th><td><label><input type="checkbox" name="complete" value="1"' . checked( ! empty( $o['complete'] ), true, false ) . '> Marchează comanda „Finalizată” (dacă are doar produse dkw)</label></td></tr>';
	echo '</table>';
	submit_button( 'Salvează setările pentru comenzi' );
	echo '</form></div>';
} );


/* =====================================================================
 * Magazin: filamentele au pagina lor; în /magazin/ apare un singur card „Filamente 3D”
 * ===================================================================== */

function p3d_dkw_root_term() {
	$t = get_term_by( 'slug', 'filamente', 'product_cat' );
	return $t ? $t : null;
}

/** Suntem pe pagina „Filamente” sau pe o subcategorie a ei? */
function p3d_dkw_is_fil_archive() {
	if ( ! is_product_category() ) {
		return false;
	}
	$root = p3d_dkw_root_term();
	$cur  = get_queried_object();
	if ( ! $root || ! $cur || empty( $cur->term_id ) ) {
		return false;
	}
	return (int) $cur->term_id === (int) $root->term_id || term_is_ancestor_of( $root->term_id, $cur->term_id, 'product_cat' );
}

function p3d_dkw_published_stats() {
	$root = p3d_dkw_root_term();
	if ( ! $root ) {
		return array( 0, 0 );
	}
	global $wpdb;
	$ids = array_merge( array( (int) $root->term_id ), array_map( 'intval', get_term_children( $root->term_id, 'product_cat' ) ) );
	$in  = implode( ',', $ids );
	$row = $wpdb->get_row( "SELECT COUNT(DISTINCT p.ID) n, MIN(CAST(pm.meta_value AS DECIMAL(10,2))) mn FROM {$wpdb->posts} p
		JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id IN ($in)
		LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_price'
		WHERE p.post_type = 'product' AND p.post_status = 'publish'" ); // phpcs:ignore
	return array( (int) ( $row->n ?? 0 ), (float) ( $row->mn ?? 0 ) );
}

// În /magazin/ nu listăm filamentele una câte una; filtrul de material pe pagina filamentelor.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'product' ) && ! $q->is_search() && p3d_dkw_root_term() ) {
		$tax   = (array) $q->get( 'tax_query' );
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => array( 'filamente' ), 'operator' => 'NOT IN', 'include_children' => true );
		$q->set( 'tax_query', $tax );
	}
	if ( $q->is_tax( 'product_cat' ) && ! empty( $_GET['material'] ) ) { // phpcs:ignore
		$meta   = (array) $q->get( 'meta_query' );
		$meta[] = array( 'key' => '_dkw_material', 'value' => sanitize_text_field( wp_unslash( $_GET['material'] ) ) ); // phpcs:ignore
		$q->set( 'meta_query', $meta );
	}
} );

// Cardul „Filamente 3D”, primul în prima pagină a magazinului.
add_action( 'the_post', function ( $post, $query ) {
	static $i = 0;
	if ( ! $query->is_main_query() || ! is_shop() || is_search() || is_paged() || ! did_action( 'woocommerce_before_shop_loop' ) || did_action( 'woocommerce_after_shop_loop' ) ) {
		return;
	}
	$i++;
	if ( 1 !== $i ) {
		return;
	}
	list( $n, $min ) = p3d_dkw_published_stats();
	$admin           = current_user_can( 'manage_woocommerce' );
	$root            = p3d_dkw_root_term();
	if ( ! $root || ( ! $n && ! $admin ) ) {
		return;
	}
	$img = get_term_meta( $root->term_id, 'thumbnail_id', true );
	$url = get_term_link( $root );
	echo '<li class="product type-product p3d-cat-card">';
	echo '<a href="' . esc_url( $url ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
	echo $img ? wp_get_attachment_image( (int) $img, 'woocommerce_thumbnail', false, array( 'alt' => 'Filamente 3D' ) ) : ''; // phpcs:ignore
	echo '<h2 class="woocommerce-loop-product__title">Filamente 3D – PLA, PETG, ABS, ASA, TPU</h2>';
	if ( $min > 0 ) {
		echo '<span class="price">de la ' . wc_price( $min ) . '</span>'; // phpcs:ignore
	}
	echo '<span class="p3d-loop-ship">' . p3d_icon( 'palette', 14 ) . ' ' . ( $n ? (int) $n . ' de produse · ' : '' ) . 'Bambu, Sunlu, Eryone și altele</span>'; // phpcs:ignore
	if ( ! $n && $admin ) {
		echo '<span class="p3d-loop-ship" style="color:#b32d2e">Vizibil doar pentru administrator – încă nu e niciun filament publicat.</span>';
	}
	echo '</a><a href="' . esc_url( $url ) . '" class="button">Vezi toate filamentele</a></li>';
}, 10, 2 );

/** Filtrele de pe pagina filamentelor: brand (subcategorii) și material. */
function p3d_dkw_filters_html() {
	$root = p3d_dkw_root_term();
	if ( ! $root ) {
		return '';
	}
	$cur  = get_queried_object();
	$mat  = isset( $_GET['material'] ) ? sanitize_text_field( wp_unslash( $_GET['material'] ) ) : ''; // phpcs:ignore
	$out  = '<div class="p3d-fil-filters"><div class="p3d-fil-row"><span class="p3d-fil-label">Brand</span><div class="p3d-fil-chips">';
	$out .= '<a class="p3d-fil-chip' . ( (int) $cur->term_id === (int) $root->term_id ? ' is-on' : '' ) . '" href="' . esc_url( get_term_link( $root ) ) . '">Toate</a>';
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $root->term_id, 'hide_empty' => true ) ) as $t ) {
		$out .= '<a class="p3d-fil-chip' . ( (int) $cur->term_id === (int) $t->term_id ? ' is-on' : '' ) . '" href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( preg_replace( '/^Filament\s+/', '', $t->name ) ) . ' <small>' . (int) $t->count . '</small></a>';
	}
	$out .= '</div></div>';
	// Materialele disponibile în categoria curentă (doar produse publicate).
	global $wpdb;
	$ids  = array_merge( array( (int) $cur->term_id ), array_map( 'intval', get_term_children( $cur->term_id, 'product_cat' ) ) );
	$in   = implode( ',', $ids );
	$mats = $wpdb->get_col( "SELECT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish'
		JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.term_id IN ($in)
		WHERE pm.meta_key = '_dkw_material' AND pm.meta_value <> '' GROUP BY pm.meta_value ORDER BY pm.meta_value" ); // phpcs:ignore
	if ( $mats ) {
		$base = get_term_link( $cur );
		$out .= '<div class="p3d-fil-row"><span class="p3d-fil-label">Material</span><div class="p3d-fil-chips">';
		$out .= '<a class="p3d-fil-chip' . ( '' === $mat ? ' is-on' : '' ) . '" href="' . esc_url( $base ) . '">Toate</a>';
		foreach ( $mats as $m ) {
			$out .= '<a class="p3d-fil-chip' . ( $m === $mat ? ' is-on' : '' ) . '" href="' . esc_url( add_query_arg( 'material', rawurlencode( $m ), $base ) ) . '">' . esc_html( $m ) . '</a>';
		}
		$out .= '</div></div>';
	}
	return $out . '</div>';
}
