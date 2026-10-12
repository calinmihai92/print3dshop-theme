<?php
/**
 * Configurator printare 3D (oferte de printare cu plată online).
 *
 * Admin (doar administratori): wp-admin → Configurator 3D. Se completează clientul (persoană fizică
 * sau firmă), piesele (poză, material, grame, timp de printare, cantitate), livrarea și eventual o reducere.
 * Prețul se calculează automat (material × grame + timp × tarif orar), dar orice valoare se poate ajusta.
 *
 * La „Generează link de plată” se creează o comandă WooCommerce (în așteptarea plății) cu aceleași
 * rânduri. Clientul primește linkul ofertei (/oferta-printare/<cod>/), vede piesele și plătește cu cardul
 * prin procesatorul de plăți instalat în WooCommerce. Pentru că totul trece printr-o comandă WooCommerce,
 * modulul de facturare (ex. SmartBill pentru WooCommerce) poate emite factura direct din comandă.
 */

defined( 'ABSPATH' ) || exit;

const P3D_OF_CPT = 'p3d_oferta';

/* ------------------------------------------------------------------ *
 * Înregistrare
 * ------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( P3D_OF_CPT, array(
		'labels'              => array(
			'name'          => 'Configurator 3D',
			'singular_name' => 'Ofertă printare 3D',
			'menu_name'     => 'Configurator 3D',
			'add_new'       => 'Ofertă nouă',
			'add_new_item'  => 'Ofertă nouă de printare 3D',
			'edit_item'     => 'Editează oferta de printare 3D',
			'all_items'     => 'Toate ofertele',
			'search_items'  => 'Caută oferte',
			'not_found'     => 'Nicio ofertă încă.',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_position'       => 4,
		'menu_icon'           => 'dashicons-calculator',
		'supports'            => array( 'title' ),
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
		'capabilities'        => array( 'create_posts' => 'manage_options' ),
	) );
	add_rewrite_rule( '^oferta-printare/([A-Za-z0-9]{20,40})/?$', 'index.php?p3d_oferta=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $v ) {
	$v[] = 'p3d_oferta';
	return $v;
} );

// Ofertele sunt vizibile doar administratorilor.
add_filter( 'user_has_cap', function ( $allcaps, $caps, $args ) {
	if ( isset( $args[2] ) && in_array( $args[0], array( 'edit_post', 'delete_post', 'read_post' ), true ) && get_post_type( $args[2] ) === P3D_OF_CPT && empty( $allcaps['manage_options'] ) ) {
		foreach ( $caps as $c ) {
			$allcaps[ $c ] = false;
		}
	}
	return $allcaps;
}, 10, 3 );

add_action( 'admin_menu', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'edit.php?post_type=' . P3D_OF_CPT );
	}
}, 99 );

/* ------------------------------------------------------------------ *
 * Date și calcule
 * ------------------------------------------------------------------ */

/** Materiale și preț orientativ pe gram (lei, cu TVA) — se pot ajusta pe fiecare piesă. */
function p3d_of_materials() {
	return array(
		'PLA'  => 1.0,
		'PETG' => 1.5,
		'ABS'  => 1.5,
		'ASA'  => 1.5,
		'TPU'  => 1.5,
		'Alt material' => 0,
	);
}

function p3d_of_rate() {
	return 15.0; // lei / oră de printare
}

function p3d_of_courier() {
	return array( 'cost' => 25.0, 'free_over' => 200.0 );
}

function p3d_of_statuses() {
	return array(
		'ciorna'   => array( 'Ciornă', '#6b7280' ),
		'trimisa'  => array( 'Trimisă clientului', '#b45309' ),
		'platita'  => array( 'Plătită', '#15803d' ),
		'lucru'    => array( 'În lucru', '#1d4ed8' ),
		'livrata'  => array( 'Livrată / predată', '#374151' ),
		'anulata'  => array( 'Anulată', '#b91c1c' ),
	);
}

function p3d_of_defaults() {
	return array(
		'client'   => array( 'type' => 'pf', 'name' => '', 'email' => '', 'phone' => '', 'company' => '', 'cui' => '', 'regcom' => '', 'address' => '', 'city' => '', 'county' => '' ),
		'parts'    => array(),
		'delivery' => array( 'method' => 'ridicare', 'cost' => 0, 'address' => '' ),
		'discount' => 0,
		'term'     => '',
		'notes'    => '',
	);
}

function p3d_of_part_defaults() {
	return array( 'desc' => '', 'photo' => 0, 'material' => 'PLA', 'color' => '', 'grams' => '', 'hours' => '', 'qty' => 1, 'ppg' => 1.0, 'pph' => p3d_of_rate(), 'extra' => 0, 'extra_label' => '' );
}

function p3d_of_get( $id ) {
	$d = get_post_meta( $id, '_p3d_of', true );
	$d = array_replace_recursive( p3d_of_defaults(), is_array( $d ) ? $d : array() );
	$d['parts'] = array_values( array_map( function ( $p ) {
		return array_merge( p3d_of_part_defaults(), (array) $p );
	}, (array) $d['parts'] ) );
	return $d;
}

function p3d_of_num( $v ) {
	return round( (float) str_replace( ',', '.', (string) $v ), 2 );
}

/** Prețul unei piese (pentru toată cantitatea) și prețul unitar. */
function p3d_of_part_price( $p ) {
	$unit  = p3d_of_num( $p['grams'] ) * p3d_of_num( $p['ppg'] ) + p3d_of_num( $p['hours'] ) * p3d_of_num( $p['pph'] );
	$unit  = round( $unit, 2 );
	$qty   = max( 1, (int) $p['qty'] );
	$extra = p3d_of_num( $p['extra'] );
	return array( 'unit' => $unit, 'qty' => $qty, 'extra' => $extra, 'total' => round( $unit * $qty + $extra, 2 ) );
}

function p3d_of_totals( $d ) {
	$parts = 0.0;
	foreach ( $d['parts'] as $p ) {
		$parts += p3d_of_part_price( $p )['total'];
	}
	$disc = min( p3d_of_num( $d['discount'] ), $parts );
	$ship = 'curier' === $d['delivery']['method'] ? p3d_of_num( $d['delivery']['cost'] ) : 0.0;
	return array( 'parts' => round( $parts, 2 ), 'discount' => round( $disc, 2 ), 'shipping' => round( $ship, 2 ), 'total' => round( $parts - $disc + $ship, 2 ) );
}

function p3d_of_status( $id ) {
	$s = get_post_meta( $id, '_p3d_of_status', true );
	return $s && isset( p3d_of_statuses()[ $s ] ) ? $s : 'ciorna';
}

function p3d_of_url( $id ) {
	$t = get_post_meta( $id, '_p3d_of_token', true );
	return $t ? home_url( '/oferta-printare/' . $t . '/' ) : '';
}

function p3d_of_by_token( $tok ) {
	if ( ! preg_match( '/^[A-Za-z0-9]{20,40}$/', $tok ) ) {
		return 0;
	}
	$q = get_posts( array( 'post_type' => P3D_OF_CPT, 'post_status' => 'publish', 'meta_key' => '_p3d_of_token', 'meta_value' => $tok, 'numberposts' => 1, 'fields' => 'ids' ) );
	return $q ? (int) $q[0] : 0;
}

function p3d_of_log( $id, $text ) {
	$log   = get_post_meta( $id, '_p3d_of_log', true );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array( 't' => time(), 'd' => $text );
	update_post_meta( $id, '_p3d_of_log', $log );
}

/** Comanda WooCommerce legată de ofertă (dacă există). */
function p3d_of_order( $id ) {
	$oid = (int) get_post_meta( $id, '_p3d_of_order', true );
	return ( $oid && function_exists( 'wc_get_order' ) ) ? wc_get_order( $oid ) : null;
}

function p3d_of_is_paid( $id ) {
	$o = p3d_of_order( $id );
	return $o && $o->is_paid();
}

/** Există o metodă de plată online (card) activă? Rambursul, transferul și cecul nu contează. */
function p3d_of_card_ready() {
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return false;
	}
	foreach ( WC()->payment_gateways()->payment_gateways() as $gid => $g ) {
		if ( 'yes' === $g->enabled && ! in_array( $gid, array( 'cod', 'bacs', 'cheque' ), true ) ) {
			return true;
		}
	}
	return false;
}

/* ------------------------------------------------------------------ *
 * Admin: listă
 * ------------------------------------------------------------------ */

add_filter( 'manage_' . P3D_OF_CPT . '_posts_columns', function () {
	return array(
		'cb'       => '<input type="checkbox">',
		'title'    => 'Oferta',
		'of_cli'   => 'Client',
		'of_total' => 'Total',
		'of_pay'   => 'Plată',
		'of_st'    => 'Stare',
		'date'     => 'Data',
	);
} );

add_action( 'manage_' . P3D_OF_CPT . '_posts_custom_column', function ( $col, $id ) {
	$d = p3d_of_get( $id );
	if ( 'of_cli' === $col ) {
		echo esc_html( 'pj' === $d['client']['type'] && $d['client']['company'] ? $d['client']['company'] : $d['client']['name'] );
		if ( $d['client']['phone'] ) {
			echo '<br><small>' . esc_html( $d['client']['phone'] ) . '</small>';
		}
	} elseif ( 'of_total' === $col ) {
		echo esc_html( p3d_money( p3d_of_totals( $d )['total'] ) );
	} elseif ( 'of_pay' === $col ) {
		$o = p3d_of_order( $id );
		if ( ! $o ) {
			echo '<span style="color:#6b7280">fără link</span>';
		} elseif ( $o->is_paid() ) {
			echo '<span style="color:#15803d;font-weight:600">Plătită</span>';
		} else {
			echo '<span style="color:#b45309">Așteaptă plata</span>';
		}
	} elseif ( 'of_st' === $col ) {
		$s = p3d_of_statuses()[ p3d_of_status( $id ) ];
		printf( '<span style="color:%s;font-weight:600">%s</span>', esc_attr( $s[1] ), esc_html( $s[0] ) );
	}
}, 10, 2 );

add_filter( 'enter_title_here', function ( $t, $post ) {
	return P3D_OF_CPT === $post->post_type ? 'Se completează automat (nr. ofertă — client)' : $t;
}, 10, 2 );

/* ------------------------------------------------------------------ *
 * Admin: editor
 * ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_' . P3D_OF_CPT, function () {
	add_meta_box( 'p3d_of_main', 'Configurator printare 3D', 'p3d_of_box_main', P3D_OF_CPT, 'normal', 'high' );
	add_meta_box( 'p3d_of_pay', 'Link de plată', 'p3d_of_box_pay', P3D_OF_CPT, 'side', 'high' );
	add_meta_box( 'p3d_of_log', 'Istoric', 'p3d_of_box_log', P3D_OF_CPT, 'side', 'default' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || P3D_OF_CPT !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'p3d-fisa-admin', P3D_URI . '/assets/css/fisa-admin.css', array(), P3D_VER );
	wp_enqueue_script( 'p3d-oferta-admin', P3D_URI . '/assets/js/oferta-admin.js', array( 'jquery' ), P3D_VER, true );
	wp_localize_script( 'p3d-oferta-admin', 'p3dOf', array(
		'materials' => p3d_of_materials(),
		'courier'   => p3d_of_courier(),
	) );
} );

function p3d_of_part_row( $i, $p ) {
	$p   = array_merge( p3d_of_part_defaults(), (array) $p );
	$n   = 'p3d_of[parts][' . $i . ']';
	$pid = absint( $p['photo'] );
	?>
	<div class="p3d-device p3d-part" data-i="<?php echo esc_attr( $i ); ?>">
		<div class="p3d-device-head"><strong class="p3d-part-num">Piesa</strong><button type="button" class="button-link p3d-del-part">Șterge</button></div>
		<div class="p3d-part-grid">
			<div class="p3d-photos p3d-part-photo">
				<input type="hidden" class="p3d-photo-id" name="<?php echo esc_attr( $n ); ?>[photo]" value="<?php echo esc_attr( $pid ?: '' ); ?>">
				<div class="p3d-photo-list"><?php if ( $pid ) : ?><span class="p3d-ph" data-id="<?php echo esc_attr( $pid ); ?>"><?php echo wp_get_attachment_image( $pid, 'thumbnail' ); ?><button type="button" aria-label="Scoate poza">×</button></span><?php endif; ?></div>
				<button type="button" class="button p3d-pick-photo">Poza piesei</button>
			</div>
			<div>
				<label>Descriere piesă<input type="text" name="<?php echo esc_attr( $n ); ?>[desc]" value="<?php echo esc_attr( $p['desc'] ); ?>" placeholder="ex. Suport telefon pentru bicicletă" required></label>
				<div class="p3d-row3">
					<label>Material<select class="p3d-mat" name="<?php echo esc_attr( $n ); ?>[material]">
						<?php foreach ( p3d_of_materials() as $m => $ppg ) : ?><option value="<?php echo esc_attr( $m ); ?>" <?php selected( $p['material'], $m ); ?>><?php echo esc_html( $m ); ?></option><?php endforeach; ?>
					</select></label>
					<label>Culoare<input type="text" name="<?php echo esc_attr( $n ); ?>[color]" value="<?php echo esc_attr( $p['color'] ); ?>" placeholder="ex. negru"></label>
					<label>Cantitate (buc.)<input type="number" min="1" step="1" class="p3d-calc p3d-qty" name="<?php echo esc_attr( $n ); ?>[qty]" value="<?php echo esc_attr( $p['qty'] ); ?>"></label>
				</div>
				<div class="p3d-row3">
					<label>Gramaj / buc. (g)<input type="number" min="0" step="0.1" class="p3d-calc p3d-grams" name="<?php echo esc_attr( $n ); ?>[grams]" value="<?php echo esc_attr( $p['grams'] ); ?>"></label>
					<label>Timp printare / buc. (ore)<input type="number" min="0" step="0.05" class="p3d-calc p3d-hours" name="<?php echo esc_attr( $n ); ?>[hours]" value="<?php echo esc_attr( $p['hours'] ); ?>" placeholder="ex. 2.5"></label>
					<label>Preț / buc.<output class="p3d-unit">—</output></label>
				</div>
				<div class="p3d-row3">
					<label>Lei / gram<input type="number" min="0" step="0.01" class="p3d-calc p3d-ppg" name="<?php echo esc_attr( $n ); ?>[ppg]" value="<?php echo esc_attr( $p['ppg'] ); ?>"></label>
					<label>Lei / oră<input type="number" min="0" step="0.5" class="p3d-calc p3d-pph" name="<?php echo esc_attr( $n ); ?>[pph]" value="<?php echo esc_attr( $p['pph'] ); ?>"></label>
					<label>Total piesă<output class="p3d-ptotal"><strong>—</strong></output></label>
				</div>
				<div class="p3d-row2">
					<label>Cost suplimentar (lei, opțional)<input type="number" min="0" step="0.01" class="p3d-calc p3d-extra" name="<?php echo esc_attr( $n ); ?>[extra]" value="<?php echo esc_attr( $p['extra'] ?: '' ); ?>" placeholder="ex. proiectare, finisare"></label>
					<label>Ce reprezintă costul suplimentar<input type="text" name="<?php echo esc_attr( $n ); ?>[extra_label]" value="<?php echo esc_attr( $p['extra_label'] ); ?>" placeholder="ex. Proiectare 3D"></label>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function p3d_of_box_main( $post ) {
	$d    = p3d_of_get( $post->ID );
	$c    = $d['client'];
	$paid = p3d_of_is_paid( $post->ID );
	wp_nonce_field( 'p3d_of_save', 'p3d_of_nonce' );
	if ( $paid ) {
		echo '<div class="p3d-alert ok"><strong>Oferta e plătită.</strong> Modificările de aici nu mai schimbă comanda plătită.</div>';
	}
	?>
	<div class="p3d-fisa p3d-of">
		<h3>Client</h3>
		<p class="p3d-of-type">
			<label><input type="radio" name="p3d_of[client][type]" value="pf" <?php checked( $c['type'], 'pf' ); ?>> Persoană fizică</label>
			<label><input type="radio" name="p3d_of[client][type]" value="pj" <?php checked( $c['type'], 'pj' ); ?>> Firmă</label>
		</p>
		<div class="p3d-row3">
			<label>Nume și prenume<input type="text" name="p3d_of[client][name]" value="<?php echo esc_attr( $c['name'] ); ?>" required></label>
			<label>Email<input type="email" name="p3d_of[client][email]" value="<?php echo esc_attr( $c['email'] ); ?>" required></label>
			<label>Telefon<input type="tel" name="p3d_of[client][phone]" value="<?php echo esc_attr( $c['phone'] ); ?>"></label>
		</div>
		<div class="p3d-row3 p3d-pj">
			<label>Denumire firmă<input type="text" name="p3d_of[client][company]" value="<?php echo esc_attr( $c['company'] ); ?>"></label>
			<label>CUI / CIF<input type="text" name="p3d_of[client][cui]" value="<?php echo esc_attr( $c['cui'] ); ?>" placeholder="ex. RO12345678"></label>
			<label>Nr. Reg. Com.<input type="text" name="p3d_of[client][regcom]" value="<?php echo esc_attr( $c['regcom'] ); ?>" placeholder="ex. J40/123/2020"></label>
		</div>
		<div class="p3d-row3">
			<label>Adresă (pentru factură)<input type="text" name="p3d_of[client][address]" value="<?php echo esc_attr( $c['address'] ); ?>" placeholder="stradă, număr"></label>
			<label>Localitate<input type="text" name="p3d_of[client][city]" value="<?php echo esc_attr( $c['city'] ); ?>"></label>
			<label>Județ / Sector<input type="text" name="p3d_of[client][county]" value="<?php echo esc_attr( $c['county'] ); ?>"></label>
		</div>

		<h3>Piese</h3>
		<div id="p3d-parts">
			<?php
			$parts = $d['parts'] ?: array( array() );
			foreach ( $parts as $i => $p ) {
				p3d_of_part_row( $i, $p );
			}
			?>
		</div>
		<template id="p3d-part-tpl"><?php p3d_of_part_row( '__i__', array() ); ?></template>
		<p><button type="button" class="button" id="p3d-add-part">+ Adaugă piesă</button></p>

		<h3>Livrare</h3>
		<div class="p3d-row3">
			<label>Mod de livrare<select name="p3d_of[delivery][method]" id="p3d-del-method">
				<option value="ridicare" <?php selected( $d['delivery']['method'], 'ridicare' ); ?>>Ridicare personală (gratuit)</option>
				<option value="curier" <?php selected( $d['delivery']['method'], 'curier' ); ?>>Curier</option>
			</select></label>
			<label class="p3d-curier">Cost livrare (lei)<input type="number" min="0" step="0.01" class="p3d-calc" id="p3d-del-cost" name="p3d_of[delivery][cost]" value="<?php echo esc_attr( $d['delivery']['cost'] ); ?>"><small class="description">Curier 25 lei, gratuit peste 200 lei</small></label>
			<label class="p3d-curier">Adresă de livrare<input type="text" name="p3d_of[delivery][address]" value="<?php echo esc_attr( $d['delivery']['address'] ); ?>" placeholder="dacă diferă de cea de facturare"></label>
		</div>

		<h3>Total</h3>
		<div class="p3d-row3">
			<label>Reducere (lei, opțional)<input type="number" min="0" step="0.01" class="p3d-calc" id="p3d-discount" name="p3d_of[discount]" value="<?php echo esc_attr( $d['discount'] ?: '' ); ?>"></label>
			<label>Termen de execuție<input type="text" name="p3d_of[term]" value="<?php echo esc_attr( $d['term'] ); ?>" placeholder="ex. 2–3 zile lucrătoare de la plată"></label>
			<div class="p3d-of-sum">
				<div>Piese <span id="p3d-s-parts">—</span></div>
				<div>Reducere <span id="p3d-s-disc">—</span></div>
				<div>Livrare <span id="p3d-s-ship">—</span></div>
				<div class="tot">Total <span id="p3d-s-total">—</span></div>
			</div>
		</div>
		<label>Observații pentru client<textarea name="p3d_of[notes]" rows="3" placeholder="ex. Piesa se printează cu umplere 30%, pentru rezistență."><?php echo esc_textarea( $d['notes'] ); ?></textarea></label>
		<p class="description">Prețuri în lei, cu TVA. Prețul pe gram și tariful orar se completează automat după material, dar le poți modifica pentru fiecare piesă.</p>

		<h3>Stare</h3>
		<select name="p3d_of_status">
			<?php foreach ( p3d_of_statuses() as $k => $s ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( p3d_of_status( $post->ID ), $k ); ?>><?php echo esc_html( $s[0] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

function p3d_of_box_pay( $post ) {
	$url   = p3d_of_url( $post->ID );
	$order = p3d_of_order( $post->ID );
	$card  = p3d_of_card_ready();
	$d     = p3d_of_get( $post->ID );

	if ( ! function_exists( 'wc_create_order' ) ) {
		echo '<p>WooCommerce nu este activ, așa că nu se poate genera linkul de plată.</p>';
		return;
	}
	if ( ! $card ) {
		echo '<div class="p3d-alert warn">Plata cu cardul nu e activă încă. Linkul de plată se poate genera, iar butonul „Plătește cu cardul” apare automat la client din momentul în care activezi procesatorul de plăți în WooCommerce.</div>';
	}
	if ( $order ) {
		$paid = $order->is_paid();
		echo '<p><strong>Comanda WooCommerce:</strong> <a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a> — ' . ( $paid ? '<span style="color:#15803d;font-weight:600">plătită</span>' : '<span style="color:#b45309">așteaptă plata</span>' ) . '<br><small>Total comandă: ' . esc_html( p3d_money( $order->get_total() ) ) . '</small></p>';
		if ( ! $paid && abs( (float) $order->get_total() - p3d_of_totals( $d )['total'] ) > 0.009 ) {
			echo '<div class="p3d-alert warn">Totalul ofertei diferă de comandă. Salvează („Actualizează”) ca să se actualizeze și comanda.</div>';
		}
		if ( $url ) {
			echo '<p><strong>Link pentru client</strong> (oferta + plata)<br><input type="text" readonly class="widefat p3d-copy" value="' . esc_attr( $url ) . '"></p>';
		}
		if ( ! $paid ) {
			echo '<p><strong>Link direct de plată</strong><br><input type="text" readonly class="widefat p3d-copy" value="' . esc_attr( $order->get_checkout_payment_url() ) . '"></p>';
			$msg = "Bună ziua! Oferta pentru printarea 3D este gata:\n" . $url . "\nTotal: " . p3d_money( p3d_of_totals( $d )['total'] ) . ( $card ? "\nO puteți plăti online, cu cardul, direct din link." : '' ) . "\nMulțumim, Print3D Shop";
			echo '<label>Mesaj pentru WhatsApp<textarea class="p3d-copy" rows="6" readonly>' . esc_textarea( $msg ) . '</textarea></label>';
			if ( $d['client']['phone'] ) {
				echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . p3d_fisa_ro_phone( $d['client']['phone'] ) . '?text=' . rawurlencode( $msg ) ) . '">Trimite pe WhatsApp</a></p>';
			}
			echo '<p><label><input type="checkbox" name="p3d_of_mail" value="1"> Trimite oferta clientului pe email</label></p>';
		}
	} else {
		echo '<p><label><input type="checkbox" name="p3d_of_gen" value="1"> Generează link de plată</label></p>';
		echo '<p class="description">Bifează și salvează. Se creează o comandă în WooCommerce (în așteptarea plății) și linkul ofertei pentru client. După plată, factura se poate emite din comanda WooCommerce.</p>';
		echo '<p><label><input type="checkbox" name="p3d_of_mail" value="1"> Trimite oferta și pe email</label></p>';
	}
}

function p3d_of_box_log( $post ) {
	$log = get_post_meta( $post->ID, '_p3d_of_log', true );
	if ( ! $log ) {
		echo '<p>—</p>';
		return;
	}
	echo '<ul class="p3d-log">';
	foreach ( array_reverse( $log ) as $l ) {
		echo '<li><small>' . esc_html( wp_date( 'j M, H:i', $l['t'] ) ) . '</small><br>' . esc_html( $l['d'] ) . '</li>';
	}
	echo '</ul>';
}

/* ------------------------------------------------------------------ *
 * Salvare
 * ------------------------------------------------------------------ */

add_action( 'save_post_' . P3D_OF_CPT, function ( $id, $post ) {
	static $running = false;
	if ( $running || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( ! isset( $_POST['p3d_of_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['p3d_of_nonce'] ) ), 'p3d_of_save' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$running = true;
	$in      = wp_unslash( $_POST['p3d_of'] ?? array() ); // phpcs:ignore
	$t       = static function ( $v ) { return sanitize_text_field( (string) $v ); };

	$c = (array) ( $in['client'] ?? array() );
	$d = array(
		'client'   => array(
			'type'    => 'pj' === ( $c['type'] ?? '' ) ? 'pj' : 'pf',
			'name'    => $t( $c['name'] ?? '' ),
			'email'   => sanitize_email( $c['email'] ?? '' ),
			'phone'   => $t( $c['phone'] ?? '' ),
			'company' => $t( $c['company'] ?? '' ),
			'cui'     => strtoupper( preg_replace( '/\s+/', '', $t( $c['cui'] ?? '' ) ) ),
			'regcom'  => strtoupper( $t( $c['regcom'] ?? '' ) ),
			'address' => $t( $c['address'] ?? '' ),
			'city'    => $t( $c['city'] ?? '' ),
			'county'  => $t( $c['county'] ?? '' ),
		),
		'parts'    => array(),
		'delivery' => array(
			'method'  => 'curier' === ( $in['delivery']['method'] ?? '' ) ? 'curier' : 'ridicare',
			'cost'    => max( 0, p3d_of_num( $in['delivery']['cost'] ?? 0 ) ),
			'address' => $t( $in['delivery']['address'] ?? '' ),
		),
		'discount' => max( 0, p3d_of_num( $in['discount'] ?? 0 ) ),
		'term'     => $t( $in['term'] ?? '' ),
		'notes'    => sanitize_textarea_field( (string) ( $in['notes'] ?? '' ) ),
	);
	$mats = p3d_of_materials();
	foreach ( (array) ( $in['parts'] ?? array() ) as $p ) {
		$p = (array) $p;
		if ( '' === trim( (string) ( $p['desc'] ?? '' ) ) && ! p3d_of_num( $p['grams'] ?? 0 ) ) {
			continue;
		}
		$d['parts'][] = array(
			'desc'        => $t( $p['desc'] ?? '' ),
			'photo'       => absint( $p['photo'] ?? 0 ),
			'material'    => isset( $mats[ $p['material'] ?? '' ] ) ? $p['material'] : 'PLA',
			'color'       => $t( $p['color'] ?? '' ),
			'grams'       => max( 0, p3d_of_num( $p['grams'] ?? 0 ) ),
			'hours'       => max( 0, p3d_of_num( $p['hours'] ?? 0 ) ),
			'qty'         => max( 1, (int) ( $p['qty'] ?? 1 ) ),
			'ppg'         => max( 0, p3d_of_num( $p['ppg'] ?? 0 ) ),
			'pph'         => max( 0, p3d_of_num( $p['pph'] ?? 0 ) ),
			'extra'       => max( 0, p3d_of_num( $p['extra'] ?? 0 ) ),
			'extra_label' => $t( $p['extra_label'] ?? '' ),
		);
	}
	update_post_meta( $id, '_p3d_of', $d );

	// Număr, cod pentru link, titlu.
	$nr = get_post_meta( $id, '_p3d_of_number', true );
	if ( ! $nr ) {
		$year = wp_date( 'Y' );
		$max  = 0;
		foreach ( get_posts( array( 'post_type' => P3D_OF_CPT, 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_p3d_of_number' ) ) as $oid ) {
			$on = (string) get_post_meta( $oid, '_p3d_of_number', true );
			if ( 0 === strpos( $on, $year . '-' ) ) {
				$max = max( $max, (int) substr( $on, 5 ) );
			}
		}
		$nr = $year . '-' . str_pad( (string) ( $max + 1 ), 3, '0', STR_PAD_LEFT );
		update_post_meta( $id, '_p3d_of_number', $nr );
		p3d_of_log( $id, 'Ofertă creată' );
	}
	if ( ! get_post_meta( $id, '_p3d_of_token', true ) ) {
		update_post_meta( $id, '_p3d_of_token', wp_generate_password( 28, false, false ) );
	}
	$who   = 'pj' === $d['client']['type'] && $d['client']['company'] ? $d['client']['company'] : $d['client']['name'];
	$title = 'Oferta ' . $nr . ( $who ? ' — ' . $who : '' );
	if ( $post->post_title !== $title ) {
		wp_update_post( array( 'ID' => $id, 'post_title' => $title ) );
	}

	// Stare.
	$new_st = sanitize_key( $_POST['p3d_of_status'] ?? '' ); // phpcs:ignore
	if ( isset( p3d_of_statuses()[ $new_st ] ) && p3d_of_status( $id ) !== $new_st ) {
		update_post_meta( $id, '_p3d_of_status', $new_st );
		p3d_of_log( $id, 'Stare: ' . p3d_of_statuses()[ $new_st ][0] );
	}

	// Comanda WooCommerce: se creează la cerere, apoi se ține la zi cât timp nu e plătită.
	if ( 'publish' === get_post_status( $id ) && function_exists( 'wc_create_order' ) ) {
		$order = p3d_of_order( $id );
		if ( ! $order && ! empty( $_POST['p3d_of_gen'] ) ) { // phpcs:ignore
			$order = p3d_of_sync_order( $id, null );
			if ( $order ) {
				p3d_of_log( $id, 'Link de plată generat (comanda #' . $order->get_order_number() . ')' );
				if ( 'ciorna' === p3d_of_status( $id ) ) {
					update_post_meta( $id, '_p3d_of_status', 'trimisa' );
				}
			}
		} elseif ( $order && ! $order->is_paid() && in_array( $order->get_status(), array( 'pending', 'failed', 'on-hold' ), true ) ) {
			p3d_of_sync_order( $id, $order );
		}
		if ( ! empty( $_POST['p3d_of_mail'] ) && p3d_of_order( $id ) && $d['client']['email'] ) { // phpcs:ignore
			$ok = p3d_of_mail_client( $id );
			p3d_of_log( $id, $ok ? 'Ofertă trimisă pe email la ' . $d['client']['email'] : 'Emailul nu a putut fi trimis' );
		}
	}
	$running = false;
}, 10, 2 );

/** Creează sau actualizează comanda WooCommerce cu rândurile ofertei. */
function p3d_of_sync_order( $id, $order ) {
	$d   = p3d_of_get( $id );
	$c   = $d['client'];
	$nr  = get_post_meta( $id, '_p3d_of_number', true );
	$tot = p3d_of_totals( $d );

	if ( ! $order ) {
		$order = wc_create_order( array( 'status' => 'pending', 'created_via' => 'p3d_oferta' ) );
		if ( is_wp_error( $order ) || ! $order ) {
			return null;
		}
		$order->update_meta_data( '_p3d_oferta_id', $id );
		update_post_meta( $id, '_p3d_of_order', $order->get_id() );
	}
	foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping', 'coupon' ) ) as $item_id => $item ) {
		$order->remove_item( $item_id );
	}

	foreach ( $d['parts'] as $p ) {
		$pp   = p3d_of_part_price( $p );
		$name = 'Printare 3D: ' . ( $p['desc'] ?: 'piesă' ) . ' — ' . $p['material'] . ( $p['color'] ? ', ' . $p['color'] : '' ) . ', ' . $pp['qty'] . ' buc.';
		$fee  = new WC_Order_Item_Fee();
		$fee->set_name( $name );
		$fee->set_amount( $pp['unit'] * $pp['qty'] );
		$fee->set_total( $pp['unit'] * $pp['qty'] );
		$fee->set_tax_status( 'none' );
		$order->add_item( $fee );
		if ( $pp['extra'] > 0 ) {
			$x = new WC_Order_Item_Fee();
			$x->set_name( ( $p['extra_label'] ?: 'Cost suplimentar' ) . ' — ' . ( $p['desc'] ?: 'piesă' ) );
			$x->set_amount( $pp['extra'] );
			$x->set_total( $pp['extra'] );
			$x->set_tax_status( 'none' );
			$order->add_item( $x );
		}
	}
	if ( $tot['discount'] > 0 ) {
		$r = new WC_Order_Item_Fee();
		$r->set_name( 'Reducere' );
		$r->set_amount( -$tot['discount'] );
		$r->set_total( -$tot['discount'] );
		$r->set_tax_status( 'none' );
		$order->add_item( $r );
	}
	if ( 'curier' === $d['delivery']['method'] ) {
		$s = new WC_Order_Item_Shipping();
		$s->set_method_title( 'Livrare prin curier' );
		$s->set_method_id( 'flat_rate' );
		$s->set_total( $tot['shipping'] );
		$order->add_item( $s );
	}

	$parts = preg_split( '/\s+/', trim( $c['name'] ), 2 );
	$order->set_billing_first_name( $parts[0] ?? '' );
	$order->set_billing_last_name( $parts[1] ?? '' );
	$order->set_billing_email( $c['email'] );
	$order->set_billing_phone( $c['phone'] );
	$order->set_billing_company( 'pj' === $c['type'] ? $c['company'] : '' );
	$order->set_billing_address_1( $c['address'] );
	$order->set_billing_city( $c['city'] );
	$order->set_billing_state( $c['county'] );
	$order->set_billing_country( 'RO' );
	if ( 'curier' === $d['delivery']['method'] ) {
		$order->set_shipping_first_name( $parts[0] ?? '' );
		$order->set_shipping_last_name( $parts[1] ?? '' );
		$order->set_shipping_company( 'pj' === $c['type'] ? $c['company'] : '' );
		$order->set_shipping_address_1( $d['delivery']['address'] ?: $c['address'] );
		$order->set_shipping_city( $c['city'] );
		$order->set_shipping_state( $c['county'] );
		$order->set_shipping_country( 'RO' );
	}
	// Date de firmă pentru factură (le citește modulul de facturare, după ce îl configurăm).
	$order->update_meta_data( '_p3d_client_type', $c['type'] );
	$order->update_meta_data( '_billing_cui', 'pj' === $c['type'] ? $c['cui'] : '' );
	$order->update_meta_data( '_billing_regcom', 'pj' === $c['type'] ? $c['regcom'] : '' );
	$order->set_customer_note( 'Ofertă printare 3D nr. ' . $nr . ( $d['term'] ? ' · Termen: ' . $d['term'] : '' ) );
	$order->set_currency( get_woocommerce_currency() );
	$order->calculate_totals( false );
	$order->save();
	return $order;
}

function p3d_of_mail_client( $id ) {
	$d   = p3d_of_get( $id );
	$nr  = get_post_meta( $id, '_p3d_of_number', true );
	$tot = p3d_of_totals( $d );
	$msg = 'Bună ziua, ' . $d['client']['name'] . ",\n\n"
		. "Vă mulțumim pentru cerere. Oferta pentru printarea 3D (nr. {$nr}) este gata.\n\n";
	foreach ( $d['parts'] as $p ) {
		$pp   = p3d_of_part_price( $p );
		$msg .= '- ' . ( $p['desc'] ?: 'Piesă' ) . ' (' . $p['material'] . ( $p['color'] ? ', ' . $p['color'] : '' ) . '), ' . $pp['qty'] . ' buc. — ' . p3d_money( $pp['total'] ) . "\n";
	}
	if ( $tot['discount'] > 0 ) {
		$msg .= '- Reducere: −' . p3d_money( $tot['discount'] ) . "\n";
	}
	$msg .= 'curier' === $d['delivery']['method'] ? '- Livrare prin curier: ' . p3d_money( $tot['shipping'] ) . "\n" : "- Ridicare personală, din atelierul nostru din București\n";
	$msg .= "\nTOTAL: " . p3d_money( $tot['total'] ) . " (cu TVA)\n";
	if ( $d['term'] ) {
		$msg .= 'Termen de execuție: ' . $d['term'] . "\n";
	}
	$msg .= "\nVedeți oferta completă" . ( p3d_of_card_ready() ? ' și o puteți plăti online, cu cardul' : '' ) . ":\n" . p3d_of_url( $id ) . "\n\nPentru întrebări: " . p3d_opt( 'phone' ) . "\n\nMulțumim,\nPrint3D Shop\nprint3dshop.ro";
	return wp_mail( $d['client']['email'], 'Oferta de printare 3D nr. ' . $nr . ' — Print3D Shop', $msg, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: Print3D Shop <' . p3d_opt( 'email' ) . '>' ) );
}

/* ------------------------------------------------------------------ *
 * WooCommerce: plată
 * ------------------------------------------------------------------ */

// La plata ofertelor nu oferim ramburs (plata e online, cu cardul).
add_filter( 'woocommerce_available_payment_gateways', function ( $gws ) {
	if ( is_admin() || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
		return $gws;
	}
	$oid   = absint( get_query_var( 'order-pay' ) );
	$order = $oid ? wc_get_order( $oid ) : null;
	if ( $order && $order->get_meta( '_p3d_oferta_id' ) ) {
		unset( $gws['cod'] );
	}
	return $gws;
} );

// Plata confirmată → oferta devine „Plătită”.
add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to, $order ) {
	$oid = (int) $order->get_meta( '_p3d_oferta_id' );
	if ( ! $oid || get_post_type( $oid ) !== P3D_OF_CPT ) {
		return;
	}
	if ( in_array( $to, array( 'processing', 'completed' ), true ) && in_array( p3d_of_status( $oid ), array( 'ciorna', 'trimisa' ), true ) ) {
		update_post_meta( $oid, '_p3d_of_status', 'platita' );
		p3d_of_log( $oid, 'Plătită online (comanda #' . $order->get_order_number() . ')' );
	} elseif ( 'cancelled' === $to ) {
		p3d_of_log( $oid, 'Comanda WooCommerce a fost anulată' );
	}
}, 10, 4 );

// După plată, clientul revine la pagina ofertei.
add_filter( 'woocommerce_get_return_url', function ( $url, $order ) {
	$oid = $order ? (int) $order->get_meta( '_p3d_oferta_id' ) : 0;
	return $oid && p3d_of_url( $oid ) ? add_query_arg( 'platit', '1', p3d_of_url( $oid ) ) : $url;
}, 10, 2 );

/* ------------------------------------------------------------------ *
 * Pagina clientului: /oferta-printare/<cod>/
 * ------------------------------------------------------------------ */

add_action( 'template_redirect', function () {
	$tok = get_query_var( 'p3d_oferta' );
	if ( ! $tok ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	global $wp_query;
	$wp_query->is_home           = false;
	$wp_query->is_page           = false;
	$wp_query->is_singular       = false;
	$wp_query->is_404            = false;
	$wp_query->queried_object    = null;
	$wp_query->queried_object_id = 0;

	$id = p3d_of_by_token( sanitize_text_field( $tok ) );
	status_header( $id ? 200 : 404 );
	add_filter( 'pre_get_document_title', function () use ( $id ) {
		return $id ? 'Oferta de printare 3D ' . get_post_meta( $id, '_p3d_of_number', true ) . ' — Print3D Shop' : 'Ofertă — Print3D Shop';
	} );
	add_filter( 'rank_math/frontend/title', function () use ( $id ) {
		return $id ? 'Oferta de printare 3D ' . get_post_meta( $id, '_p3d_of_number', true ) . ' — Print3D Shop' : 'Ofertă — Print3D Shop';
	}, 99 );
	add_filter( 'rank_math/frontend/canonical', '__return_false', 99 );
	add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
	add_filter( 'rank_math/frontend/robots', function () {
		return array( 'index' => 'noindex', 'follow' => 'nofollow' );
	} );
	get_header();
	echo '<section class="section fisa-wrap"><div class="wrap">';
	if ( ! $id ) {
		echo '<div class="card fisa-card center"><h1 class="h2">Oferta nu a fost găsită</h1><p>Verificați linkul primit sau sunați-ne la <a href="' . esc_attr( p3d_tel() ) . '">' . esc_html( p3d_opt( 'phone' ) ) . '</a>.</p></div>';
	} else {
		p3d_of_render( $id );
	}
	echo '</div></section>';
	get_footer();
	exit;
}, 1 );

function p3d_of_render( $id ) {
	$d     = p3d_of_get( $id );
	$nr    = get_post_meta( $id, '_p3d_of_number', true );
	$tot   = p3d_of_totals( $d );
	$order = p3d_of_order( $id );
	$paid  = $order && $order->is_paid();
	$card  = p3d_of_card_ready();
	$st    = p3d_of_status( $id );
	$who   = 'pj' === $d['client']['type'] && $d['client']['company'] ? $d['client']['company'] : $d['client']['name'];
	?>
	<div class="fisa-head">
		<div>
			<span class="pill"><?php echo p3d_icon( 'layers', 13 ); // phpcs:ignore ?> Oferta de printare 3D nr. <?php echo esc_html( $nr ); ?></span>
			<h1 class="h2">Oferta ta de <em>printare 3D</em></h1>
			<p class="muted">Pentru: <strong><?php echo esc_html( $who ); ?></strong> · Emisă: <?php echo esc_html( get_the_date( 'j F Y', $id ) ); ?></p>
		</div>
		<button type="button" class="btn btn-ghost fisa-print" onclick="window.print()"><?php echo p3d_icon( 'doc', 16 ); // phpcs:ignore ?> Printează / PDF</button>
	</div>

	<?php if ( isset( $_GET['platit'] ) && $paid ) : // phpcs:ignore ?>
		<p class="form-ok" role="status">Mulțumim! Plata a fost înregistrată. Începem printarea și te anunțăm când piesele sunt gata.</p>
	<?php endif; ?>
	<?php if ( 'anulata' === $st ) : ?>
		<p class="form-err">Această ofertă nu mai este valabilă. Pentru o ofertă nouă, sunați-ne la <?php echo esc_html( p3d_opt( 'phone' ) ); ?>.</p>
	<?php endif; ?>

	<article class="card fisa-card">
		<h2 class="h3">Piesele <em>de printat</em></h2>
		<div class="of-parts">
			<?php foreach ( $d['parts'] as $p ) : $pp = p3d_of_part_price( $p ); ?>
				<div class="of-part">
					<?php if ( $p['photo'] && wp_get_attachment_image_url( $p['photo'], 'medium' ) ) : ?>
						<a class="of-ph" href="<?php echo esc_url( wp_get_attachment_image_url( $p['photo'], 'large' ) ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $p['photo'], 'medium', false, array( 'alt' => $p['desc'], 'loading' => 'lazy' ) ); ?></a>
					<?php else : ?>
						<span class="of-ph empty"><?php echo p3d_icon( 'cube', 34 ); // phpcs:ignore ?></span>
					<?php endif; ?>
					<div class="of-info">
						<h3><?php echo esc_html( $p['desc'] ?: 'Piesă' ); ?></h3>
						<ul class="of-specs">
							<li><span>Material</span><?php echo esc_html( $p['material'] . ( $p['color'] ? ', ' . $p['color'] : '' ) ); ?></li>
							<li><span>Cantitate</span><?php echo esc_html( $pp['qty'] ); ?> buc.</li>
							<?php if ( $p['grams'] ) : ?><li><span>Gramaj</span><?php echo esc_html( rtrim( rtrim( number_format( (float) $p['grams'], 1, ',', '' ), '0' ), ',' ) ); ?> g / buc.</li><?php endif; ?>
							<?php if ( $p['hours'] ) : ?><li><span>Timp de printare</span><?php echo esc_html( p3d_of_hours( $p['hours'] ) ); ?> / buc.</li><?php endif; ?>
							<?php if ( $pp['extra'] > 0 ) : ?><li><span><?php echo esc_html( $p['extra_label'] ?: 'Cost suplimentar' ); ?></span><?php echo esc_html( p3d_money( $pp['extra'] ) ); ?></li><?php endif; ?>
						</ul>
					</div>
					<div class="of-price"><?php if ( $pp['qty'] > 1 ) : ?><small><?php echo esc_html( $pp['qty'] . ' × ' . p3d_money( $pp['unit'] ) ); ?></small><?php endif; ?><strong><?php echo esc_html( p3d_money( $pp['total'] ) ); ?></strong></div>
				</div>
			<?php endforeach; ?>
		</div>

		<table class="fisa-table of-sum">
			<tfoot>
				<tr><td class="r">Piese</td><td class="r"><?php echo esc_html( p3d_money( $tot['parts'] ) ); ?></td></tr>
				<?php if ( $tot['discount'] > 0 ) : ?><tr><td class="r">Reducere</td><td class="r">−<?php echo esc_html( p3d_money( $tot['discount'] ) ); ?></td></tr><?php endif; ?>
				<tr><td class="r"><?php echo 'curier' === $d['delivery']['method'] ? 'Livrare prin curier' : 'Ridicare personală'; ?></td><td class="r"><?php echo 'curier' === $d['delivery']['method'] ? esc_html( $tot['shipping'] > 0 ? p3d_money( $tot['shipping'] ) : 'gratuit' ) : 'gratuit'; ?></td></tr>
				<tr class="fisa-total"><td class="r">Total</td><td class="r"><?php echo esc_html( p3d_money( $tot['total'] ) ); ?></td></tr>
			</tfoot>
		</table>
		<p class="muted">Prețuri în lei, cu TVA.</p>
		<ul class="check-list fisa-terms">
			<?php if ( $d['term'] ) : ?><li><?php echo p3d_icon( 'clock', 18 ); // phpcs:ignore ?><span><strong>Termen de execuție:</strong> <?php echo esc_html( $d['term'] ); ?></span></li><?php endif; ?>
			<li><?php echo p3d_icon( 'pin', 18 ); // phpcs:ignore ?><span><strong>Livrare:</strong> <?php echo 'curier' === $d['delivery']['method'] ? 'prin curier, oriunde în România' : 'ridicare personală din atelierul nostru din București'; ?></span></li>
		</ul>
		<?php if ( $d['notes'] ) : ?><p><?php echo nl2br( esc_html( $d['notes'] ) ); ?></p><?php endif; ?>
	</article>

	<?php if ( $paid ) : ?>
		<div class="card fisa-card fisa-confirmed">
			<span class="icon-tile solid"><?php echo p3d_icon( 'check', 22 ); // phpcs:ignore ?></span>
			<div><strong>Oferta este plătită</strong><br>Mulțumim! Comanda nr. <?php echo esc_html( $order->get_order_number() ); ?> · <?php echo esc_html( p3d_money( $order->get_total() ) ); ?></div>
		</div>
	<?php elseif ( 'anulata' !== $st ) : ?>
		<div class="card fisa-card fisa-confirm of-pay">
			<h2 class="h3">Plata</h2>
			<?php if ( $order && $card ) : ?>
				<p>Poți plăti online, în siguranță, cu cardul. După plată începem printarea.</p>
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'shield', 18 ); // phpcs:ignore ?> Plătește <?php echo esc_html( p3d_money( $tot['total'] ) ); ?> cu cardul</a>
			<?php else : ?>
				<p>Plata online cu cardul va fi disponibilă în curând. Până atunci, sună-ne sau scrie-ne pe WhatsApp și stabilim împreună plata și începerea lucrului.</p>
				<div class="ctas">
					<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
					<a href="<?php echo esc_url( p3d_wa( 'Bună ziua! Am primit oferta de printare 3D nr. ' . $nr . '. ' ) ); ?>" class="btn btn-ghost" rel="noopener"><?php echo p3d_icon( 'whatsapp', 18 ); // phpcs:ignore ?> WhatsApp</a>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<p class="muted center">Întrebări? Sunați la <a href="<?php echo esc_attr( p3d_tel() ); ?>"><?php echo esc_html( p3d_opt( 'phone' ) ); ?></a> · <?php echo esc_html( p3d_opt( 'hours' ) ); ?></p>
	<?php
}

function p3d_of_hours( $h ) {
	$m = (int) round( (float) $h * 60 );
	$hh = intdiv( $m, 60 );
	$mm = $m % 60;
	return ( $hh ? $hh . ' h' : '' ) . ( $mm ? ( $hh ? ' ' : '' ) . $mm . ' min' : '' ) ?: '0 min';
}
