<?php
/**
 * Fișa de service.
 *
 * Admin (doar administratori): wp-admin → Fișe service. Se completează clientul, imprimantele (poze,
 * problema reclamată, diagnostic) și lista de piese + manoperă cu prețuri.
 * Client: link unic /fisa-service/<cod>/ + email + parolă generată. Clientul vede fișa și apasă
 * „Confirm începerea lucrării”. Confirmarea se salvează cu dată, oră, IP, nume tastat și amprenta
 * exactă a fișei (dacă fișa se modifică după confirmare, trebuie confirmată din nou).
 */

defined( 'ABSPATH' ) || exit;

const P3D_FISA_CPT = 'p3d_fisa';

/* ------------------------------------------------------------------ *
 * Înregistrare
 * ------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( P3D_FISA_CPT, array(
		'labels'              => array(
			'name'          => 'Fișe service',
			'singular_name' => 'Fișă service',
			'add_new'       => 'Fișă nouă',
			'add_new_item'  => 'Fișă de service nouă',
			'edit_item'     => 'Editează fișa de service',
			'all_items'     => 'Toate fișele',
			'search_items'  => 'Caută fișe',
			'not_found'     => 'Nicio fișă încă.',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_position'       => 3,
		'menu_icon'           => 'dashicons-clipboard',
		'supports'            => array( 'title' ),
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
		'capabilities'        => array( 'create_posts' => 'manage_options' ),
	) );

	add_rewrite_rule( '^fisa-service/([A-Za-z0-9]{20,40})/?$', 'index.php?p3d_fisa=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'p3d_fisa';
	return $vars;
} );

// Fișele sunt vizibile doar administratorilor în wp-admin.
add_filter( 'user_has_cap', function ( $allcaps, $caps, $args ) {
	if ( isset( $args[2] ) && in_array( $args[0], array( 'edit_post', 'delete_post', 'read_post' ), true ) && get_post_type( $args[2] ) === P3D_FISA_CPT && empty( $allcaps['manage_options'] ) ) {
		foreach ( $caps as $c ) {
			$allcaps[ $c ] = false;
		}
	}
	return $allcaps;
}, 10, 3 );

add_action( 'admin_menu', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'edit.php?post_type=' . P3D_FISA_CPT );
	}
}, 99 );

// Regulile de URL se regenerează o dată la fiecare versiune nouă a temei.
add_action( 'init', function () {
	if ( get_option( 'p3d_rewrite_ver' ) !== P3D_VER ) {
		flush_rewrite_rules( false );
		update_option( 'p3d_rewrite_ver', P3D_VER, false );
	}
}, 99 );

/* ------------------------------------------------------------------ *
 * Date
 * ------------------------------------------------------------------ */

function p3d_fisa_statuses() {
	return array(
		'ciorna'     => array( 'Ciornă', '#6b7280' ),
		'trimisa'    => array( 'Trimisă clientului', '#b45309' ),
		'modificata' => array( 'Modificată — necesită reconfirmare', '#b45309' ),
		'confirmata' => array( 'Confirmată de client', '#15803d' ),
		'intrebari'  => array( 'Clientul are întrebări', '#b91c1c' ),
		'lucru'      => array( 'În lucru', '#1d4ed8' ),
		'finalizata' => array( 'Finalizată', '#15803d' ),
		'predata'    => array( 'Predată clientului', '#374151' ),
	);
}

function p3d_fisa_defaults() {
	return array(
		'client'     => array( 'name' => '', 'email' => '', 'phone' => '' ),
		'devices'    => array(),
		'items'      => array(),
		'term'       => '',
		'warranty'   => '3 luni pentru manoperă; piesele noi au garanția producătorului.',
		'notes'      => '',
		'price_note' => 'Prețuri în lei.',
		'demo'       => 0,
	);
}

function p3d_fisa_get( $id ) {
	$d = get_post_meta( $id, '_p3d_fisa', true );
	$d = is_array( $d ) ? $d : array();
	return array_replace_recursive( p3d_fisa_defaults(), $d );
}

function p3d_fisa_status( $id ) {
	$s = get_post_meta( $id, '_p3d_status', true );
	return $s && isset( p3d_fisa_statuses()[ $s ] ) ? $s : 'ciorna';
}

function p3d_fisa_totals( $data ) {
	$t = array( 'piesa' => 0.0, 'manopera' => 0.0, 'total' => 0.0 );
	foreach ( $data['items'] as $it ) {
		$v = round( (float) $it['qty'] * (float) $it['price'], 2 );
		$k = 'manopera' === $it['type'] ? 'manopera' : 'piesa';
		$t[ $k ]    += $v;
		$t['total'] += $v;
	}
	return $t;
}

function p3d_money( $v ) {
	return number_format( (float) $v, 2, ',', '.' ) . ' lei';
}

/** Amprenta conținutului (ce confirmă clientul). */
function p3d_fisa_hash( $data ) {
	$c = $data;
	unset( $c['client']['phone'] );
	return hash( 'sha256', wp_json_encode( $c ) );
}

function p3d_fisa_url( $id ) {
	$tok = get_post_meta( $id, '_p3d_token', true );
	return $tok ? home_url( '/fisa-service/' . $tok . '/' ) : '';
}

function p3d_fisa_log( $id, $event, $details = '' ) {
	$log   = get_post_meta( $id, '_p3d_log', true );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array( 't' => time(), 'e' => $event, 'd' => $details );
	update_post_meta( $id, '_p3d_log', $log );
}

function p3d_fisa_client_ip() {
	return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore
}

/* ------------------------------------------------------------------ *
 * Admin: coloane listă
 * ------------------------------------------------------------------ */

add_filter( 'manage_' . P3D_FISA_CPT . '_posts_columns', function () {
	return array(
		'cb'        => '<input type="checkbox">',
		'title'     => 'Fișa',
		'p3d_cli'   => 'Client',
		'p3d_total' => 'Total',
		'p3d_st'    => 'Stare',
		'date'      => 'Data',
	);
} );

add_action( 'manage_' . P3D_FISA_CPT . '_posts_custom_column', function ( $col, $id ) {
	$d = p3d_fisa_get( $id );
	if ( 'p3d_cli' === $col ) {
		echo esc_html( $d['client']['name'] );
		if ( $d['client']['phone'] ) {
			echo '<br><small>' . esc_html( $d['client']['phone'] ) . '</small>';
		}
	} elseif ( 'p3d_total' === $col ) {
		echo esc_html( p3d_money( p3d_fisa_totals( $d )['total'] ) );
	} elseif ( 'p3d_st' === $col ) {
		$s = p3d_fisa_statuses()[ p3d_fisa_status( $id ) ];
		printf( '<span style="color:%s;font-weight:600">%s</span>', esc_attr( $s[1] ), esc_html( $s[0] ) );
	}
}, 10, 2 );

/* ------------------------------------------------------------------ *
 * Admin: editor
 * ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_' . P3D_FISA_CPT, function () {
	add_meta_box( 'p3d_fisa_main', 'Fișa de service', 'p3d_fisa_box_main', P3D_FISA_CPT, 'normal', 'high' );
	add_meta_box( 'p3d_fisa_send', 'Trimitere și confirmare', 'p3d_fisa_box_send', P3D_FISA_CPT, 'side', 'high' );
	add_meta_box( 'p3d_fisa_log', 'Istoric', 'p3d_fisa_box_log', P3D_FISA_CPT, 'side', 'default' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || P3D_FISA_CPT !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'p3d-fisa-admin', P3D_URI . '/assets/js/fisa-admin.js', array( 'jquery' ), P3D_VER, true );
	wp_enqueue_style( 'p3d-fisa-admin', P3D_URI . '/assets/css/fisa-admin.css', array(), P3D_VER );
} );

// Titlul se completează automat.
add_filter( 'enter_title_here', function ( $t, $post ) {
	return P3D_FISA_CPT === $post->post_type ? 'Se completează automat (nr. fișă — client)' : $t;
}, 10, 2 );

function p3d_fisa_box_main( $post ) {
	$d  = p3d_fisa_get( $post->ID );
	$st = p3d_fisa_status( $post->ID );
	wp_nonce_field( 'p3d_fisa_save', 'p3d_fisa_nonce' );
	$conf = get_post_meta( $post->ID, '_p3d_confirm', true );
	if ( 'confirmata' === $st && $conf ) {
		echo '<div class="p3d-alert ok"><strong>Confirmată de client</strong> — ' . esc_html( $conf['name'] ) . ', ' . esc_html( wp_date( 'j M Y, H:i', $conf['t'] ) ) . '. Orice modificare la aparate, piese sau prețuri va cere o nouă confirmare.</div>';
	} elseif ( 'modificata' === $st ) {
		echo '<div class="p3d-alert warn"><strong>Fișa a fost modificată după confirmare.</strong> Trimite-o din nou clientului ca să o reconfirme.</div>';
	}
	?>
	<div class="p3d-fisa">
		<h3>Client</h3>
		<div class="p3d-row3">
			<label>Nume<input type="text" name="p3d[client][name]" value="<?php echo esc_attr( $d['client']['name'] ); ?>" required></label>
			<label>Email<input type="email" name="p3d[client][email]" value="<?php echo esc_attr( $d['client']['email'] ); ?>" required></label>
			<label>Telefon<input type="tel" name="p3d[client][phone]" value="<?php echo esc_attr( $d['client']['phone'] ); ?>"></label>
		</div>

		<h3>Imprimante</h3>
		<div id="p3d-devices">
			<?php
			$devs = $d['devices'] ?: array( array() );
			foreach ( $devs as $i => $dev ) {
				p3d_fisa_device_row( $i, $dev );
			}
			?>
		</div>
		<template id="p3d-device-tpl"><?php p3d_fisa_device_row( '__i__', array() ); ?></template>
		<p><button type="button" class="button" id="p3d-add-device">+ Adaugă imprimantă</button></p>

		<h3>Piese și manoperă</h3>
		<table class="p3d-items widefat">
			<thead><tr><th style="width:18%">Imprimantă</th><th style="width:12%">Tip</th><th>Descriere</th><th style="width:8%">Cant.</th><th style="width:12%">Preț unitar (lei)</th><th style="width:12%">Valoare</th><th style="width:4%"></th></tr></thead>
			<tbody id="p3d-items">
				<?php
				$items = $d['items'] ?: array( array( 'type' => 'manopera', 'qty' => 1 ) );
				foreach ( $items as $i => $it ) {
					p3d_fisa_item_row( $i, $it, $d['devices'] );
				}
				?>
			</tbody>
			<tfoot>
				<tr><td colspan="5" class="r">Piese</td><td id="p3d-sum-piesa">—</td><td></td></tr>
				<tr><td colspan="5" class="r">Manoperă</td><td id="p3d-sum-manopera">—</td><td></td></tr>
				<tr><td colspan="5" class="r"><strong>Total</strong></td><td id="p3d-sum-total"><strong>—</strong></td><td></td></tr>
			</tfoot>
		</table>
		<template id="p3d-item-tpl"><?php p3d_fisa_item_row( '__i__', array( 'type' => 'piesa', 'qty' => 1 ), $d['devices'] ); ?></template>
		<p><button type="button" class="button" id="p3d-add-item">+ Adaugă rând</button></p>

		<label class="p3d-demo"><input type="checkbox" name="p3d[demo]" value="1" <?php checked( ! empty( $d['demo'] ) ); ?>> <strong>Fișă demonstrativă — PREȚURI FICTIVE</strong> <span class="description">(afișează pe fișă un banner mare „Exemplu — prețurile sunt fictive”; clientul nu o poate confirma)</span></label>

		<h3>Condiții</h3>
		<div class="p3d-row2">
			<label>Termen estimat<input type="text" name="p3d[term]" value="<?php echo esc_attr( $d['term'] ); ?>" placeholder="ex. 2–3 zile lucrătoare de la confirmare"></label>
			<label>Mențiune prețuri<input type="text" name="p3d[price_note]" value="<?php echo esc_attr( $d['price_note'] ); ?>"></label>
		</div>
		<label>Garanție<input type="text" name="p3d[warranty]" value="<?php echo esc_attr( $d['warranty'] ); ?>"></label>
		<label>Observații pentru client<textarea name="p3d[notes]" rows="3" placeholder="ex. Dacă la desfacere apar defecte suplimentare, vă contactăm înainte de orice cost în plus."><?php echo esc_textarea( $d['notes'] ); ?></textarea></label>

		<h3>Stare</h3>
		<select name="p3d_status">
			<?php foreach ( p3d_fisa_statuses() as $k => $s ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $st, $k ); ?>><?php echo esc_html( $s[0] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

function p3d_fisa_device_row( $i, $dev ) {
	$dev    = array_merge( array( 'model' => '', 'serial' => '', 'issue' => '', 'diagnostic' => '', 'photos' => array() ), (array) $dev );
	$photos = array_filter( array_map( 'absint', (array) $dev['photos'] ) );
	$n      = 'p3d[devices][' . $i . ']';
	?>
	<div class="p3d-device" data-i="<?php echo esc_attr( $i ); ?>">
		<div class="p3d-device-head"><strong class="p3d-dev-num">Imprimanta</strong><button type="button" class="button-link p3d-del-device">Șterge</button></div>
		<div class="p3d-row2">
			<label>Marcă și model<input type="text" class="p3d-dev-model" name="<?php echo esc_attr( $n ); ?>[model]" value="<?php echo esc_attr( $dev['model'] ); ?>" placeholder="ex. Bambu Lab P1S"></label>
			<label>Serie / identificare<input type="text" name="<?php echo esc_attr( $n ); ?>[serial]" value="<?php echo esc_attr( $dev['serial'] ); ?>"></label>
		</div>
		<label>Problema reclamată de client<textarea name="<?php echo esc_attr( $n ); ?>[issue]" rows="2"><?php echo esc_textarea( $dev['issue'] ); ?></textarea></label>
		<label>Diagnostic<textarea name="<?php echo esc_attr( $n ); ?>[diagnostic]" rows="4"><?php echo esc_textarea( $dev['diagnostic'] ); ?></textarea></label>
		<div class="p3d-photos">
			<input type="hidden" class="p3d-photo-ids" name="<?php echo esc_attr( $n ); ?>[photos]" value="<?php echo esc_attr( implode( ',', $photos ) ); ?>">
			<div class="p3d-photo-list">
				<?php foreach ( $photos as $pid ) : ?>
					<span class="p3d-ph" data-id="<?php echo esc_attr( $pid ); ?>"><?php echo wp_get_attachment_image( $pid, 'thumbnail' ); ?><button type="button" aria-label="Scoate poza">×</button></span>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button p3d-add-photos">Adaugă poze</button>
		</div>
	</div>
	<?php
}

function p3d_fisa_item_row( $i, $it, $devices ) {
	$it = array_merge( array( 'device' => '', 'type' => 'piesa', 'desc' => '', 'qty' => 1, 'price' => '' ), (array) $it );
	$n  = 'p3d[items][' . $i . ']';
	?>
	<tr class="p3d-item">
		<td><select class="p3d-item-dev" name="<?php echo esc_attr( $n ); ?>[device]" data-val="<?php echo esc_attr( $it['device'] ); ?>">
			<option value="">Toate</option>
			<?php foreach ( (array) $devices as $di => $dv ) : ?>
				<option value="<?php echo esc_attr( $di ); ?>" <?php selected( (string) $it['device'], (string) $di ); ?>><?php echo esc_html( ( $di + 1 ) . '. ' . ( $dv['model'] ?? '' ) ); ?></option>
			<?php endforeach; ?>
		</select></td>
		<td><select name="<?php echo esc_attr( $n ); ?>[type]" class="p3d-item-type">
			<option value="piesa" <?php selected( $it['type'], 'piesa' ); ?>>Piesă</option>
			<option value="manopera" <?php selected( $it['type'], 'manopera' ); ?>>Manoperă</option>
		</select></td>
		<td><input type="text" name="<?php echo esc_attr( $n ); ?>[desc]" value="<?php echo esc_attr( $it['desc'] ); ?>" placeholder="ex. Hotend complet / Diagnoză și calibrare"></td>
		<td><input type="number" step="1" min="0" class="p3d-qty" name="<?php echo esc_attr( $n ); ?>[qty]" value="<?php echo esc_attr( $it['qty'] ); ?>"></td>
		<td><input type="number" step="0.01" min="0" class="p3d-price" name="<?php echo esc_attr( $n ); ?>[price]" value="<?php echo esc_attr( $it['price'] ); ?>"></td>
		<td class="p3d-line">—</td>
		<td><button type="button" class="button-link p3d-del-item" aria-label="Șterge rândul">×</button></td>
	</tr>
	<?php
}

function p3d_fisa_box_send( $post ) {
	$url  = p3d_fisa_url( $post->ID );
	$sent = (int) get_post_meta( $post->ID, '_p3d_sent', true );
	$conf = get_post_meta( $post->ID, '_p3d_confirm', true );
	$q    = get_post_meta( $post->ID, '_p3d_question', true );
	$once = get_transient( 'p3d_fisa_pass_' . $post->ID . '_' . get_current_user_id() );

	if ( $once ) {
		delete_transient( 'p3d_fisa_pass_' . $post->ID . '_' . get_current_user_id() );
		$d   = p3d_fisa_get( $post->ID );
		$msg = "Bună ziua! Fișa de service pentru imprimanta dvs. este gata:\n" . $url . "\nEmail: " . $d['client']['email'] . "\nParolă: " . $once . "\nVă rugăm să o verificați și să confirmați începerea lucrării. Mulțumim, Print3D Shop";
		echo '<div class="p3d-alert ok"><strong>Parolă nouă:</strong> <code class="p3d-pass">' . esc_html( $once ) . '</code><br><small>Se afișează o singură dată.' . ( $once && get_post_meta( $post->ID, '_p3d_mail_ok', true ) ? ' Emailul a fost trimis.' : '' ) . '</small></div>';
		echo '<label>Mesaj pentru WhatsApp / SMS<textarea class="p3d-copy" rows="7" readonly>' . esc_textarea( $msg ) . '</textarea></label>';
		echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . preg_replace( '/\D/', '', p3d_fisa_ro_phone( $d['client']['phone'] ) ) . '?text=' . rawurlencode( $msg ) ) . '">Trimite pe WhatsApp</a></p>';
	}

	if ( $url ) {
		echo '<p><strong>Link client</strong><br><input type="text" readonly class="widefat" value="' . esc_attr( $url ) . '" onclick="this.select()"></p>';
	} else {
		echo '<p>Linkul se creează la prima salvare.</p>';
	}
	if ( $sent ) {
		echo '<p>Ultima trimitere: ' . esc_html( wp_date( 'j M Y, H:i', $sent ) ) . '</p>';
	}
	if ( $conf ) {
		echo '<div class="p3d-alert ok"><strong>Confirmare client</strong><br>' . esc_html( $conf['name'] ) . '<br>' . esc_html( wp_date( 'j M Y, H:i:s', $conf['t'] ) ) . '<br><small>IP ' . esc_html( $conf['ip'] ) . ' · total confirmat ' . esc_html( p3d_money( $conf['total'] ) ) . '</small></div>';
	}
	if ( $q ) {
		echo '<div class="p3d-alert warn"><strong>Întrebare de la client</strong> (' . esc_html( wp_date( 'j M, H:i', $q['t'] ) ) . ')<br>' . nl2br( esc_html( $q['msg'] ) ) . '</div>';
	}
	echo '<p><label><input type="checkbox" name="p3d_send" value="1"> Generează parolă nouă și trimite fișa clientului pe email</label></p>';
	echo '<p class="description">Bifează și apasă „Actualizează”. Parola apare aici o singură dată, împreună cu un mesaj gata de trimis pe WhatsApp.</p>';
}

/** Parolă ușor de citit și de tastat: ABCD-2345 (fără 0/O, 1/I/L). */
function p3d_fisa_new_pass() {
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	$out   = '';
	for ( $i = 0; $i < 4; $i++ ) {
		$out .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
	}
	return $out . '-' . random_int( 2000, 9999 );
}

function p3d_fisa_ro_phone( $p ) {
	$p = preg_replace( '/\D/', '', (string) $p );
	if ( 0 === strpos( $p, '07' ) ) {
		$p = '4' . $p;
	}
	return $p;
}

function p3d_fisa_box_log( $post ) {
	$log    = get_post_meta( $post->ID, '_p3d_log', true );
	$labels = array(
		'creata'     => 'Fișă creată',
		'trimisa'    => 'Trimisă clientului',
		'login'      => 'Clientul a deschis fișa',
		'confirmata' => 'Confirmată de client',
		'intrebare'  => 'Întrebare de la client',
		'modificata' => 'Modificată după confirmare',
		'stare'      => 'Stare schimbată',
	);
	if ( ! $log ) {
		echo '<p>—</p>';
		return;
	}
	echo '<ul class="p3d-log">';
	foreach ( array_reverse( $log ) as $l ) {
		echo '<li><small>' . esc_html( wp_date( 'j M Y, H:i', $l['t'] ) ) . '</small><br>' . esc_html( $labels[ $l['e'] ] ?? $l['e'] ) . ( $l['d'] ? ' — ' . esc_html( $l['d'] ) : '' ) . '</li>';
	}
	echo '</ul>';
}

/* ------------------------------------------------------------------ *
 * Admin: salvare
 * ------------------------------------------------------------------ */

add_action( 'save_post_' . P3D_FISA_CPT, function ( $id, $post ) {
	static $running = false;
	if ( $running || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( ! isset( $_POST['p3d_fisa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['p3d_fisa_nonce'] ) ), 'p3d_fisa_save' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$in  = isset( $_POST['p3d'] ) && is_array( $_POST['p3d'] ) ? wp_unslash( $_POST['p3d'] ) : array(); // phpcs:ignore
	$old = p3d_fisa_get( $id );

	$d = array(
		'client'     => array(
			'name'  => sanitize_text_field( $in['client']['name'] ?? '' ),
			'email' => sanitize_email( $in['client']['email'] ?? '' ),
			'phone' => sanitize_text_field( $in['client']['phone'] ?? '' ),
		),
		'devices'    => array(),
		'items'      => array(),
		'term'       => sanitize_text_field( $in['term'] ?? '' ),
		'warranty'   => sanitize_text_field( $in['warranty'] ?? '' ),
		'notes'      => sanitize_textarea_field( $in['notes'] ?? '' ),
		'price_note' => sanitize_text_field( $in['price_note'] ?? '' ),
		'demo'       => empty( $in['demo'] ) ? 0 : 1,
	);

	// Aparatele: reindexare 0..n și harta indexului vechi → nou (pentru rândurile de piese).
	$map = array();
	foreach ( (array) ( $in['devices'] ?? array() ) as $k => $dev ) {
		$row = array(
			'model'      => sanitize_text_field( $dev['model'] ?? '' ),
			'serial'     => sanitize_text_field( $dev['serial'] ?? '' ),
			'issue'      => sanitize_textarea_field( $dev['issue'] ?? '' ),
			'diagnostic' => sanitize_textarea_field( $dev['diagnostic'] ?? '' ),
			'photos'     => array_values( array_filter( array_map( 'absint', explode( ',', (string) ( $dev['photos'] ?? '' ) ) ) ) ),
		);
		if ( '' === $row['model'] && '' === $row['issue'] && '' === $row['diagnostic'] && ! $row['photos'] ) {
			continue;
		}
		$map[ (string) $k ] = count( $d['devices'] );
		$d['devices'][]     = $row;
	}
	foreach ( (array) ( $in['items'] ?? array() ) as $it ) {
		$row = array(
			'device' => isset( $it['device'] ) && '' !== $it['device'] && isset( $map[ (string) $it['device'] ] ) ? $map[ (string) $it['device'] ] : '',
			'type'   => ( $it['type'] ?? '' ) === 'manopera' ? 'manopera' : 'piesa',
			'desc'   => sanitize_text_field( $it['desc'] ?? '' ),
			'qty'    => max( 0, (float) str_replace( ',', '.', (string) ( $it['qty'] ?? 1 ) ) ),
			'price'  => max( 0, round( (float) str_replace( ',', '.', (string) ( $it['price'] ?? 0 ) ), 2 ) ),
		);
		if ( '' === $row['desc'] && ! $row['price'] ) {
			continue;
		}
		$d['items'][] = $row;
	}
	$running = true;
	update_post_meta( $id, '_p3d_fisa', $d );

	// Număr, cod de acces.
	if ( ! get_post_meta( $id, '_p3d_number', true ) ) {
		// Următorul număr din anul curent, după fișele existente (cele din coș nu contează).
		global $wpdb;
		$year = gmdate( 'Y' );
		$max  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(CAST(SUBSTRING(pm.meta_value, 6) AS UNSIGNED)) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_p3d_number' AND pm.meta_value LIKE %s AND p.post_type = %s AND p.post_status <> 'trash'",
			$wpdb->esc_like( $year . '-' ) . '%',
			P3D_FISA_CPT
		) );
		update_post_meta( $id, '_p3d_number', $year . '-' . str_pad( (string) ( $max + 1 ), 3, '0', STR_PAD_LEFT ) );
		p3d_fisa_log( $id, 'creata' );
	}
	if ( ! get_post_meta( $id, '_p3d_token', true ) ) {
		update_post_meta( $id, '_p3d_token', wp_generate_password( 28, false, false ) );
	}

	// Stare.
	$old_st = p3d_fisa_status( $id );
	$new_st = sanitize_key( $_POST['p3d_status'] ?? $old_st );
	$new_st = isset( p3d_fisa_statuses()[ $new_st ] ) ? $new_st : $old_st;

	// Modificare după confirmare → trebuie reconfirmată.
	$conf = get_post_meta( $id, '_p3d_confirm', true );
	if ( $conf && p3d_fisa_hash( $d ) !== $conf['hash'] && in_array( $old_st, array( 'confirmata', 'lucru' ), true ) ) {
		$new_st = 'modificata';
		p3d_fisa_log( $id, 'modificata' );
	} elseif ( $new_st !== $old_st ) {
		p3d_fisa_log( $id, 'stare', p3d_fisa_statuses()[ $new_st ][0] );
	}
	update_post_meta( $id, '_p3d_status', $new_st );

	// Titlu automat.
	$title = 'Fișa ' . get_post_meta( $id, '_p3d_number', true ) . ( $d['client']['name'] ? ' — ' . $d['client']['name'] : '' );
	if ( $post->post_title !== $title || 'publish' !== $post->post_status ) {
		wp_update_post( array( 'ID' => $id, 'post_title' => $title, 'post_status' => 'publish' ) );
	}

	// Trimitere către client.
	if ( ! empty( $_POST['p3d_send'] ) && is_email( $d['client']['email'] ) ) {
		$pass = p3d_fisa_new_pass();
		update_post_meta( $id, '_p3d_pass', wp_hash_password( $pass ) );
		update_post_meta( $id, '_p3d_sent', time() );
		if ( in_array( p3d_fisa_status( $id ), array( 'ciorna', 'intrebari' ), true ) ) {
			update_post_meta( $id, '_p3d_status', 'trimisa' );
		}
		$ok = p3d_fisa_mail_client_link( $id, $pass );
		update_post_meta( $id, '_p3d_mail_ok', $ok ? 1 : 0 );
		set_transient( 'p3d_fisa_pass_' . $id . '_' . get_current_user_id(), $pass, 10 * MINUTE_IN_SECONDS );
		p3d_fisa_log( $id, 'trimisa', $ok ? 'email + parolă nouă' : 'parolă nouă (emailul nu a putut fi trimis)' );
	}
}, 10, 2 );

/* ------------------------------------------------------------------ *
 * Emailuri
 * ------------------------------------------------------------------ */

function p3d_fisa_mail_headers() {
	return array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: Print3D Shop <' . p3d_opt( 'email' ) . '>' );
}

function p3d_fisa_mail_client_link( $id, $pass ) {
	$d   = p3d_fisa_get( $id );
	$nr  = get_post_meta( $id, '_p3d_number', true );
	$msg = 'Bună ziua, ' . $d['client']['name'] . ",\n\n"
		. "Fișa de service nr. {$nr} pentru imprimanta dvs. este gata. Conține diagnosticul, pozele, piesele de înlocuit și costurile.\n\n"
		. 'Link: ' . p3d_fisa_url( $id ) . "\n"
		. 'Email: ' . $d['client']['email'] . "\n"
		. 'Parolă: ' . $pass . "\n\n"
		. "Vă rugăm să o verificați și, dacă sunteți de acord, să apăsați „Confirm începerea lucrării”. Nu începem lucrarea fără confirmarea dvs.\n\n"
		. 'Pentru întrebări: ' . p3d_opt( 'phone' ) . "\n\nMulțumim,\nPrint3D Shop\n" . home_url( '/' );
	return wp_mail( $d['client']['email'], 'Fișa de service nr. ' . $nr . ' — Print3D Shop', $msg, p3d_fisa_mail_headers() );
}

function p3d_fisa_summary_text( $id ) {
	$d   = p3d_fisa_get( $id );
	$out = '';
	foreach ( $d['devices'] as $i => $dv ) {
		$out .= ( $i + 1 ) . '. ' . $dv['model'] . ( $dv['serial'] ? ' (' . $dv['serial'] . ')' : '' ) . "\n   Diagnostic: " . str_replace( "\n", "\n   ", $dv['diagnostic'] ) . "\n";
	}
	$out .= "\nPiese și manoperă:\n";
	foreach ( $d['items'] as $it ) {
		$out .= '- ' . ( 'manopera' === $it['type'] ? '[Manoperă] ' : '[Piesă] ' ) . $it['desc'] . ' — ' . rtrim( rtrim( number_format( $it['qty'], 2, ',', '' ), '0' ), ',' ) . ' × ' . p3d_money( $it['price'] ) . "\n";
	}
	$out .= "\nTOTAL: " . p3d_money( p3d_fisa_totals( $d )['total'] ) . ' (' . $d['price_note'] . ")\n";
	if ( ! empty( $d['demo'] ) ) {
		$out = "*** FIȘĂ DEMONSTRATIVĂ — PREȚURI FICTIVE ***\n\n" . $out . "\n*** Prețurile de mai sus sunt fictive, doar pentru exemplificare. ***\n";
	}
	return $out;
}

/* ------------------------------------------------------------------ *
 * Pagina clientului
 * ------------------------------------------------------------------ */

function p3d_fisa_by_token( $tok ) {
	$q = get_posts( array( 'post_type' => P3D_FISA_CPT, 'post_status' => 'publish', 'meta_key' => '_p3d_token', 'meta_value' => $tok, 'numberposts' => 1, 'fields' => 'ids' ) ); // phpcs:ignore
	return $q ? (int) $q[0] : 0;
}

function p3d_fisa_cookie_name( $id ) {
	return 'p3d_fisa_' . $id;
}

function p3d_fisa_sign( $id, $exp ) {
	$passhash = (string) get_post_meta( $id, '_p3d_pass', true );
	return hash_hmac( 'sha256', $id . '|' . $exp . '|' . $passhash, wp_salt( 'auth' ) );
}

function p3d_fisa_authed( $id ) {
	$c = $_COOKIE[ p3d_fisa_cookie_name( $id ) ] ?? ''; // phpcs:ignore
	if ( ! $c || false === strpos( $c, '|' ) ) {
		return false;
	}
	list( $exp, $sig ) = explode( '|', sanitize_text_field( wp_unslash( $c ) ), 2 );
	return (int) $exp > time() && hash_equals( p3d_fisa_sign( $id, (int) $exp ), $sig );
}

function p3d_fisa_form_token( $id, $action ) {
	return hash_hmac( 'sha256', $action . '|' . $id . '|' . ( $_COOKIE[ p3d_fisa_cookie_name( $id ) ] ?? '' ), wp_salt( 'nonce' ) ); // phpcs:ignore
}

add_action( 'template_redirect', function () {
	$tok = get_query_var( 'p3d_fisa' );
	if ( ! $tok ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );

	// Pagina fișei nu e prima pagină și nici altă pagină a site-ului.
	global $wp_query;
	$wp_query->is_home           = false;
	$wp_query->is_page           = false;
	$wp_query->is_singular       = false;
	$wp_query->is_404            = false;
	$wp_query->queried_object    = null;
	$wp_query->queried_object_id = 0;
	status_header( 200 );

	$id = p3d_fisa_by_token( sanitize_text_field( $tok ) );
	if ( ! $id ) {
		status_header( 404 );
		p3d_fisa_render( 0, 'notfound' );
		exit;
	}
	$self = p3d_fisa_url( $id );
	$act  = sanitize_key( $_POST['p3d_act'] ?? '' ); // phpcs:ignore

	// Autentificare.
	if ( 'login' === $act ) {
		$rk   = 'p3d_fl_' . md5( p3d_fisa_client_ip() . '|' . $id );
		$hits = (int) get_transient( $rk );
		if ( $hits >= 8 ) {
			p3d_fisa_render( $id, 'login', 'Prea multe încercări. Încercați din nou peste 15 minute sau sunați-ne.' );
			exit;
		}
		$d     = p3d_fisa_get( $id );
		$email = strtolower( trim( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) ); // phpcs:ignore
		$pass  = trim( (string) wp_unslash( $_POST['pass'] ?? '' ) ); // phpcs:ignore
		$hash  = (string) get_post_meta( $id, '_p3d_pass', true );
		if ( $hash && $email === strtolower( $d['client']['email'] ) && wp_check_password( strtoupper( $pass ), $hash ) ) {
			delete_transient( $rk );
			$exp = time() + 14 * DAY_IN_SECONDS;
			setcookie( p3d_fisa_cookie_name( $id ), $exp . '|' . p3d_fisa_sign( $id, $exp ), array( 'expires' => $exp, 'path' => '/fisa-service/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
			p3d_fisa_log( $id, 'login', p3d_fisa_client_ip() );
			wp_safe_redirect( $self );
			exit;
		}
		set_transient( $rk, $hits + 1, 15 * MINUTE_IN_SECONDS );
		p3d_fisa_render( $id, 'login', 'Emailul sau parola nu sunt corecte.' );
		exit;
	}

	if ( ! p3d_fisa_authed( $id ) ) {
		p3d_fisa_render( $id, 'login' );
		exit;
	}

	$st = p3d_fisa_status( $id );

	// Confirmare.
	if ( 'confirm' === $act && hash_equals( p3d_fisa_form_token( $id, 'confirm' ), (string) wp_unslash( $_POST['t'] ?? '' ) ) ) { // phpcs:ignore
		$name  = sanitize_text_field( wp_unslash( $_POST['nume'] ?? '' ) ); // phpcs:ignore
		$agree = ! empty( $_POST['acord'] ); // phpcs:ignore
		$d     = p3d_fisa_get( $id );
		if ( ! empty( $d['demo'] ) || ! in_array( $st, array( 'trimisa', 'modificata', 'intrebari' ), true ) ) {
			wp_safe_redirect( $self );
			exit;
		}
		if ( ! $agree || mb_strlen( $name ) < 3 ) {
			p3d_fisa_render( $id, 'view', 'Bifați acordul și scrieți numele complet pentru confirmare.' );
			exit;
		}
		$conf = array(
			't'     => time(),
			'name'  => $name,
			'email' => $d['client']['email'],
			'ip'    => p3d_fisa_client_ip(),
			'ua'    => substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 250 ), // phpcs:ignore
			'hash'  => p3d_fisa_hash( $d ),
			'total' => p3d_fisa_totals( $d )['total'],
			'snap'  => $d,
		);
		update_post_meta( $id, '_p3d_confirm', $conf );
		add_post_meta( $id, '_p3d_confirm_hist', $conf );
		update_post_meta( $id, '_p3d_status', 'confirmata' );
		delete_post_meta( $id, '_p3d_question' );
		p3d_fisa_log( $id, 'confirmata', $name . ', IP ' . $conf['ip'] );

		$nr   = get_post_meta( $id, '_p3d_number', true );
		$when = wp_date( 'j F Y, H:i:s', $conf['t'] );
		$sum  = p3d_fisa_summary_text( $id );
		wp_mail( p3d_opt( 'email' ), 'CONFIRMAT: fișa ' . $nr . ' — ' . $d['client']['name'], "Clientul a confirmat începerea lucrării.\n\nNume tastat: {$name}\nEmail: {$conf['email']}\nData: {$when}\nIP: {$conf['ip']}\n\n{$sum}\nFișa: " . admin_url( 'post.php?post=' . $id . '&action=edit' ), p3d_fisa_mail_headers() );
		wp_mail( $d['client']['email'], 'Confirmare lucrare — fișa de service nr. ' . $nr, 'Bună ziua, ' . $d['client']['name'] . ",\n\nAm primit confirmarea dvs. pentru începerea lucrării ({$when}).\n\n{$sum}\nPuteți revedea fișa oricând: " . $self . "\n\nMulțumim,\nPrint3D Shop\n" . p3d_opt( 'phone' ), p3d_fisa_mail_headers() );
		wp_safe_redirect( add_query_arg( 'ok', '1', $self ) );
		exit;
	}

	// Întrebare / nu sunt de acord.
	if ( 'question' === $act && hash_equals( p3d_fisa_form_token( $id, 'question' ), (string) wp_unslash( $_POST['t'] ?? '' ) ) ) { // phpcs:ignore
		$msg = sanitize_textarea_field( wp_unslash( $_POST['mesaj'] ?? '' ) ); // phpcs:ignore
		if ( mb_strlen( $msg ) >= 3 && in_array( $st, array( 'trimisa', 'modificata', 'intrebari' ), true ) ) {
			$d = p3d_fisa_get( $id );
			update_post_meta( $id, '_p3d_question', array( 't' => time(), 'msg' => $msg ) );
			update_post_meta( $id, '_p3d_status', 'intrebari' );
			p3d_fisa_log( $id, 'intrebare', mb_substr( $msg, 0, 120 ) );
			wp_mail( p3d_opt( 'email' ), 'Întrebare la fișa ' . get_post_meta( $id, '_p3d_number', true ) . ' — ' . $d['client']['name'], $msg . "\n\nFișa: " . admin_url( 'post.php?post=' . $id . '&action=edit' ), p3d_fisa_mail_headers() );
		}
		wp_safe_redirect( add_query_arg( 'q', '1', $self ) );
		exit;
	}

	p3d_fisa_render( $id, 'view' );
	exit;
}, 1 );

function p3d_fisa_render( $id, $mode, $error = '' ) {
	add_filter( 'pre_get_document_title', function () use ( $id ) {
		return $id ? 'Fișa de service ' . get_post_meta( $id, '_p3d_number', true ) . ' — Print3D Shop' : 'Fișa de service — Print3D Shop';
	} );
	add_filter( 'body_class', function ( $c ) {
		$c[] = 'p3d-fisa-page';
		return $c;
	} );
	add_filter( 'rank_math/frontend/title', function () use ( $id ) {
		return $id ? 'Fișa de service ' . get_post_meta( $id, '_p3d_number', true ) . ' — Print3D Shop' : 'Fișa de service — Print3D Shop';
	}, 99 );
	add_filter( 'rank_math/frontend/canonical', '__return_false', 99 );
	add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
	add_filter( 'rank_math/frontend/robots', function () {
		return array( 'index' => 'noindex', 'follow' => 'nofollow' );
	} );
	add_action( 'wp_head', function () {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}, 0 );
	get_header();
	echo '<section class="section fisa-wrap"><div class="wrap">';

	if ( 'notfound' === $mode ) {
		echo '<div class="card fisa-card center"><h1 class="h2">Fișa nu a fost găsită</h1><p>Verificați linkul primit sau sunați-ne la <a href="' . esc_attr( p3d_tel() ) . '">' . esc_html( p3d_opt( 'phone' ) ) . '</a>.</p></div>';
	} elseif ( 'login' === $mode ) {
		$nr = get_post_meta( $id, '_p3d_number', true );
		?>
		<div class="card fisa-login">
			<span class="pill"><?php echo p3d_icon( 'wrench', 13 ); // phpcs:ignore ?> Fișa de service <?php echo esc_html( $nr ); ?></span>
			<h1 class="h2">Accesați <em>fișa de service</em></h1>
			<p>Introduceți emailul și parola primite de la noi.</p>
			<?php if ( $error ) : ?><p class="form-err" role="alert"><?php echo esc_html( $error ); ?></p><?php endif; ?>
			<form method="post" class="p3d-form" action="<?php echo esc_url( p3d_fisa_url( $id ) ); ?>">
				<input type="hidden" name="p3d_act" value="login">
				<label>Email<input type="email" name="email" autocomplete="email" required></label>
				<label>Parolă<input type="text" name="pass" autocomplete="off" autocapitalize="characters" spellcheck="false" required></label>
				<button type="submit" class="btn btn-primary">Deschide fișa <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></button>
			</form>
		</div>
		<?php
	} else {
		p3d_fisa_render_view( $id, $error );
	}

	echo '</div></section>';
	get_footer();
}

function p3d_fisa_render_view( $id, $error ) {
	$d    = p3d_fisa_get( $id );
	$nr   = get_post_meta( $id, '_p3d_number', true );
	$st   = p3d_fisa_status( $id );
	$tot  = p3d_fisa_totals( $d );
	$conf = get_post_meta( $id, '_p3d_confirm', true );
	$demo = ! empty( $d['demo'] );
	$can  = ! $demo && in_array( $st, array( 'trimisa', 'modificata', 'intrebari' ), true );
	$self = p3d_fisa_url( $id );
	?>
	<?php if ( $demo ) : ?>
		<div class="fisa-demo" role="note"><strong>Exemplu — prețuri fictive</strong><span>Aceasta este o fișă demonstrativă. Toate prețurile de pe ea sunt fictive, doar pentru exemplificare, și nu reprezintă o ofertă.</span></div>
	<?php endif; ?>
	<div class="fisa-head">
		<div>
			<span class="pill"><?php echo p3d_icon( 'wrench', 13 ); // phpcs:ignore ?> Fișa de service nr. <?php echo esc_html( $nr ); ?></span>
			<h1 class="h2">Ce facem la <em>imprimanta dvs.</em></h1>
			<p class="muted">Client: <strong><?php echo esc_html( $d['client']['name'] ); ?></strong> · Emisă: <?php echo esc_html( get_the_date( 'j F Y', $id ) ); ?></p>
		</div>
		<button type="button" class="btn btn-ghost fisa-print" onclick="window.print()"><?php echo p3d_icon( 'doc', 16 ); // phpcs:ignore ?> Printează / PDF</button>
	</div>

	<?php if ( isset( $_GET['ok'] ) && $conf ) : // phpcs:ignore ?>
		<p class="form-ok" role="status">Mulțumim! Confirmarea a fost înregistrată, iar o copie v-a fost trimisă pe email. Începem lucrarea.</p>
	<?php elseif ( isset( $_GET['q'] ) ) : // phpcs:ignore ?>
		<p class="form-ok" role="status">Mesajul a fost trimis. Vă contactăm în cel mai scurt timp.</p>
	<?php endif; ?>
	<?php if ( 'modificata' === $st ) : ?>
		<p class="form-err">Fișa a fost actualizată după confirmarea anterioară. Vă rugăm să o verificați și să o confirmați din nou.</p>
	<?php endif; ?>

	<?php foreach ( $d['devices'] as $i => $dv ) : ?>
		<article class="card fisa-card">
			<div class="fisa-dev-head">
				<span class="icon-tile"><?php echo p3d_icon( 'nozzle', 22 ); // phpcs:ignore ?></span>
				<div><h2 class="h3"><?php echo esc_html( ( count( $d['devices'] ) > 1 ? ( $i + 1 ) . '. ' : '' ) . $dv['model'] ); ?></h2>
				<?php if ( $dv['serial'] ) : ?><span class="mono-sub">Serie: <?php echo esc_html( $dv['serial'] ); ?></span><?php endif; ?></div>
			</div>
			<?php if ( $dv['issue'] ) : ?>
				<h3 class="fisa-label">Problema reclamată</h3>
				<p><?php echo nl2br( esc_html( $dv['issue'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $dv['diagnostic'] ) : ?>
				<h3 class="fisa-label">Diagnostic</h3>
				<p><?php echo nl2br( esc_html( $dv['diagnostic'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $dv['photos'] ) : ?>
				<div class="fisa-photos">
					<?php foreach ( $dv['photos'] as $pid ) : $full = wp_get_attachment_image_url( $pid, 'large' ); if ( ! $full ) { continue; } ?>
						<a href="<?php echo esc_url( $full ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $pid, 'medium', false, array( 'loading' => 'lazy', 'alt' => 'Poză ' . $dv['model'] ) ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>

	<article class="card fisa-card">
		<h2 class="h3">Piese și <em>manoperă</em></h2>
		<div class="fisa-table-wrap">
			<table class="fisa-table">
				<?php if ( $demo ) : ?><caption class="fisa-demo-tag">Prețuri fictive</caption><?php endif; ?>
				<thead><tr><th>Descriere</th><th>Tip</th><th class="r">Cant.</th><th class="r">Preț unitar</th><th class="r">Valoare</th></tr></thead>
				<tbody>
					<?php foreach ( $d['items'] as $it ) : $dev = '' !== $it['device'] && isset( $d['devices'][ $it['device'] ] ) ? $d['devices'][ $it['device'] ]['model'] : ''; ?>
						<tr>
							<td><?php echo esc_html( $it['desc'] ); ?><?php if ( $dev && count( $d['devices'] ) > 1 ) : ?><br><small class="muted"><?php echo esc_html( $dev ); ?></small><?php endif; ?></td>
							<td><?php echo 'manopera' === $it['type'] ? 'Manoperă' : 'Piesă'; ?></td>
							<td class="r"><?php echo esc_html( rtrim( rtrim( number_format( $it['qty'], 2, ',', '' ), '0' ), ',' ) ); ?></td>
							<td class="r"><?php echo esc_html( p3d_money( $it['price'] ) ); ?></td>
							<td class="r"><?php echo esc_html( p3d_money( $it['qty'] * $it['price'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<?php if ( $tot['piesa'] && $tot['manopera'] ) : ?>
						<tr><td colspan="4" class="r">Piese</td><td class="r"><?php echo esc_html( p3d_money( $tot['piesa'] ) ); ?></td></tr>
						<tr><td colspan="4" class="r">Manoperă</td><td class="r"><?php echo esc_html( p3d_money( $tot['manopera'] ) ); ?></td></tr>
					<?php endif; ?>
					<tr class="fisa-total"><td colspan="4" class="r">Total<?php echo $demo ? ' <span class="fisa-demo-tag">fictiv</span>' : ''; ?></td><td class="r"><?php echo esc_html( p3d_money( $tot['total'] ) ); ?></td></tr>
				</tfoot>
			</table>
		</div>
		<?php if ( $d['price_note'] ) : ?><p class="muted"><?php echo esc_html( $d['price_note'] ); ?></p><?php endif; ?>
		<ul class="check-list fisa-terms">
			<?php if ( $d['term'] ) : ?><li><?php echo p3d_icon( 'clock', 18 ); // phpcs:ignore ?><span><strong>Termen estimat:</strong> <?php echo esc_html( $d['term'] ); ?></span></li><?php endif; ?>
			<?php if ( $d['warranty'] ) : ?><li><?php echo p3d_icon( 'shield', 18 ); // phpcs:ignore ?><span><strong>Garanție:</strong> <?php echo esc_html( $d['warranty'] ); ?></span></li><?php endif; ?>
		</ul>
		<?php if ( $d['notes'] ) : ?><p><?php echo nl2br( esc_html( $d['notes'] ) ); ?></p><?php endif; ?>
	</article>

	<?php if ( $conf && ! $can ) : ?>
		<div class="card fisa-card fisa-confirmed">
			<span class="icon-tile solid"><?php echo p3d_icon( 'check', 22 ); // phpcs:ignore ?></span>
			<div><strong>Lucrare confirmată</strong><br>de <?php echo esc_html( $conf['name'] ); ?>, pe <?php echo esc_html( wp_date( 'j F Y, H:i', $conf['t'] ) ); ?> · total <?php echo esc_html( p3d_money( $conf['total'] ) ); ?></div>
		</div>
	<?php elseif ( $can ) : ?>
		<div class="card fisa-card fisa-confirm" id="confirmare">
			<h2 class="h3">Confirmați <em>începerea lucrării</em></h2>
			<?php if ( $error ) : ?><p class="form-err" role="alert"><?php echo esc_html( $error ); ?></p><?php endif; ?>
			<form method="post" class="p3d-form" action="<?php echo esc_url( $self ); ?>#confirmare">
				<input type="hidden" name="p3d_act" value="confirm">
				<input type="hidden" name="t" value="<?php echo esc_attr( p3d_fisa_form_token( $id, 'confirm' ) ); ?>">
				<label class="consent"><input type="checkbox" name="acord" value="1" required> <span>Am citit diagnosticul și lista de piese și manoperă și sunt de acord cu efectuarea lucrării la prețul total de <strong><?php echo esc_html( p3d_money( $tot['total'] ) ); ?></strong>.</span></label>
				<label>Nume și prenume<input type="text" name="nume" autocomplete="name" value="<?php echo esc_attr( $d['client']['name'] ); ?>" required minlength="3"></label>
				<div><button type="submit" class="btn btn-primary"><?php echo p3d_icon( 'check', 18 ); // phpcs:ignore ?> Confirm începerea lucrării</button></div>
			</form>
			<details class="fisa-q">
				<summary>Am întrebări sau nu sunt de acord</summary>
				<form method="post" class="p3d-form" action="<?php echo esc_url( $self ); ?>">
					<input type="hidden" name="p3d_act" value="question">
					<input type="hidden" name="t" value="<?php echo esc_attr( p3d_fisa_form_token( $id, 'question' ) ); ?>">
					<label>Mesajul dvs.<textarea name="mesaj" required minlength="3"></textarea></label>
					<div><button type="submit" class="btn btn-ghost">Trimite mesajul</button></div>
				</form>
			</details>
		</div>
	<?php endif; ?>
	<p class="muted center">Întrebări? Sunați la <a href="<?php echo esc_attr( p3d_tel() ); ?>"><?php echo esc_html( p3d_opt( 'phone' ) ); ?></a> · <?php echo esc_html( p3d_opt( 'hours' ) ); ?></p>
	<?php
}
