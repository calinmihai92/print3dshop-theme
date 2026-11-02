<?php
/**
 * Configurator 3D — acceptarea ofertei de către client (ca la fișa de service).
 *
 * Clientul primește linkul ofertei (/oferta-printare/<cod>/), verifică piesele și apasă „Accept oferta”
 * (bifă de acord + nume). Se păstrează numele, data, IP-ul, totalul și amprenta conținutului; dacă oferta
 * se modifică după acceptare, clientul trebuie s-o accepte din nou. Linkul de plată rămâne separat.
 */

defined( 'ABSPATH' ) || exit;

/** Amprenta conținutului acceptat (fără telefon/email). */
function p3d_of_hash( $d ) {
	unset( $d['client']['phone'], $d['client']['email'] );
	return hash( 'sha256', wp_json_encode( $d ) );
}

/** Oferta e acceptată, iar conținutul nu s-a schimbat de atunci. */
function p3d_of_accept_valid( $id ) {
	$a = get_post_meta( $id, '_p3d_of_accept', true );
	return is_array( $a ) && ! empty( $a['hash'] ) && hash_equals( $a['hash'], p3d_of_hash( p3d_of_get( $id ) ) );
}

function p3d_of_form_token( $id, $action ) {
	return hash_hmac( 'sha256', $action . '|' . $id . '|' . get_post_meta( $id, '_p3d_of_token', true ), wp_salt( 'nonce' ) );
}

function p3d_of_ip() {
	return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore
}

/* ------------------------------------------------------------------ *
 * Admin: caseta „Link de acceptare”
 * ------------------------------------------------------------------ */

function p3d_of_box_accept( $post ) {
	$url = p3d_of_url( $post->ID );
	if ( ! $url || ! get_post_meta( $post->ID, '_p3d_of_number', true ) ) {
		echo '<p class="description">Salvează oferta („Publică”) și aici apare linkul pe care îl trimiți clientului ca s-o verifice și s-o accepte.</p>';
		return;
	}
	$d   = p3d_of_get( $post->ID );
	$acc = get_post_meta( $post->ID, '_p3d_of_accept', true );
	$ok  = p3d_of_accept_valid( $post->ID );
	$q   = get_post_meta( $post->ID, '_p3d_of_question', true );

	if ( $ok ) {
		echo '<div class="p3d-alert ok"><strong>Acceptată de client</strong><br>' . esc_html( $acc['name'] ) . '<br>' . esc_html( wp_date( 'j M Y, H:i:s', $acc['t'] ) ) . '<br><small>IP ' . esc_html( $acc['ip'] ) . ' · total acceptat ' . esc_html( p3d_money( $acc['total'] ) ) . '</small></div>';
		echo '<p class="description">Acum poți genera linkul de plată (mai jos) sau emite factura.</p>';
	} elseif ( $acc ) {
		echo '<div class="p3d-alert warn">Oferta a fost modificată după acceptare. Trimite din nou linkul ca s-o reaccepte.</div>';
	}
	if ( $q && 'intrebari' === p3d_of_status( $post->ID ) ) {
		echo '<div class="p3d-alert warn"><strong>Întrebare de la client</strong> (' . esc_html( wp_date( 'j M, H:i', $q['t'] ) ) . ')<br>' . nl2br( esc_html( $q['msg'] ) ) . '</div>';
	}

	echo '<p><strong>Link pentru client</strong><br><input type="text" readonly class="widefat p3d-copy" value="' . esc_attr( $url ) . '"></p>';
	if ( ! $ok ) {
		$msg = p3d_of_accept_msg( $post->ID );
		echo '<label>Mesaj pentru WhatsApp<textarea class="p3d-copy" rows="6" readonly>' . esc_textarea( $msg ) . '</textarea></label>';
		if ( $d['client']['phone'] ) {
			$send = add_query_arg( array( 'action' => 'p3d_of_wa', 'id' => $post->ID, '_wpnonce' => wp_create_nonce( 'p3d_of_wa_' . $post->ID ) ), admin_url( 'admin-post.php' ) );
			echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $send ) . '">Trimite pe WhatsApp</a></p>';
		}
		if ( $d['client']['email'] ) {
			echo '<p><label><input type="checkbox" name="p3d_of_mail" value="1"> Trimite linkul și pe email la salvare</label></p>';
		}
	}
}

function p3d_of_accept_msg( $id ) {
	$d  = p3d_of_get( $id );
	$nr = get_post_meta( $id, '_p3d_of_number', true );
	return 'Bună ziua' . ( $d['client']['name'] ? ', ' . $d['client']['name'] : '' ) . "!\nOferta de printare 3D nr. " . $nr . " este gata:\n" . p3d_of_url( $id )
		. "\nTotal: " . p3d_money( p3d_of_totals( $d )['total'] ) . " (cu TVA)\nVă rugăm să o verificați și, dacă sunteți de acord, să apăsați „Accept oferta”. După acceptare vă trimitem linkul de plată.\nMulțumim, Print3D Shop";
}

// „Trimite pe WhatsApp”: marchează oferta ca trimisă și deschide WhatsApp cu mesajul gata scris.
add_action( 'admin_post_p3d_of_wa', function () {
	$id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
	if ( ! $id || P3D_OF_CPT !== get_post_type( $id ) || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nu ai acces la această ofertă.', 403 );
	}
	check_admin_referer( 'p3d_of_wa_' . $id );
	$d = p3d_of_get( $id );
	if ( 'ciorna' === p3d_of_status( $id ) ) {
		update_post_meta( $id, '_p3d_of_status', 'trimisa' );
	}
	p3d_of_log( $id, 'Link de acceptare trimis pe WhatsApp' );
	wp_redirect( 'https://wa.me/' . preg_replace( '/\D/', '', p3d_fisa_ro_phone( $d['client']['phone'] ) ) . '?text=' . rawurlencode( p3d_of_accept_msg( $id ) ) ); // phpcs:ignore
	exit;
} );

/* ------------------------------------------------------------------ *
 * Pagina clientului: acceptare / întrebare
 * ------------------------------------------------------------------ */

add_action( 'template_redirect', function () {
	$tok = get_query_var( 'p3d_oferta' );
	$act = sanitize_key( $_POST['p3d_of_act'] ?? '' ); // phpcs:ignore
	if ( ! $tok || ! $act || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}
	$id = p3d_of_by_token( sanitize_text_field( $tok ) );
	if ( ! $id || ! hash_equals( p3d_of_form_token( $id, $act ), (string) wp_unslash( $_POST['t'] ?? '' ) ) ) { // phpcs:ignore
		return;
	}
	$self = p3d_of_url( $id );
	$st   = p3d_of_status( $id );
	$d    = p3d_of_get( $id );
	$nr   = get_post_meta( $id, '_p3d_of_number', true );
	$adm  = admin_url( 'post.php?post=' . $id . '&action=edit' );
	$hdr  = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: Print3D Shop <' . p3d_opt( 'email' ) . '>' );

	// Limită simplă împotriva trimiterilor repetate.
	$rk = 'p3d_ofa_' . md5( p3d_of_ip() . '|' . $id );
	if ( (int) get_transient( $rk ) >= 10 ) {
		wp_safe_redirect( $self );
		exit;
	}
	set_transient( $rk, (int) get_transient( $rk ) + 1, 15 * MINUTE_IN_SECONDS );

	if ( 'anulata' === $st || p3d_of_is_paid( $id ) ) {
		wp_safe_redirect( $self );
		exit;
	}

	if ( 'accept' === $act ) {
		if ( p3d_of_accept_valid( $id ) ) {
			wp_safe_redirect( $self );
			exit;
		}
		$name = sanitize_text_field( wp_unslash( $_POST['nume'] ?? '' ) ); // phpcs:ignore
		if ( empty( $_POST['acord'] ) || mb_strlen( $name ) < 3 ) { // phpcs:ignore
			wp_safe_redirect( add_query_arg( 'eroare', '1', $self ) . '#acceptare' );
			exit;
		}
		$tot = p3d_of_totals( $d );
		$acc = array(
			't'     => time(),
			'name'  => $name,
			'ip'    => p3d_of_ip(),
			'ua'    => substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 250 ), // phpcs:ignore
			'hash'  => p3d_of_hash( $d ),
			'total' => $tot['total'],
			'snap'  => $d,
		);
		update_post_meta( $id, '_p3d_of_accept', $acc );
		add_post_meta( $id, '_p3d_of_accept_hist', $acc );
		update_post_meta( $id, '_p3d_of_status', 'acceptata' );
		delete_post_meta( $id, '_p3d_of_question' );
		p3d_of_log( $id, 'Acceptată de client: ' . $name . ' (IP ' . $acc['ip'] . ')' );

		$when = wp_date( 'j F Y, H:i:s', $acc['t'] );
		wp_mail( p3d_opt( 'email' ), 'ACCEPTATĂ: oferta de printare ' . $nr . ' — ' . $name, "Clientul a acceptat oferta de printare 3D.\n\nNume tastat: {$name}\nData: {$when}\nIP: {$acc['ip']}\nTotal: " . p3d_money( $tot['total'] ) . "\n\nGenerează linkul de plată sau emite factura din ofertă:\n" . $adm, $hdr );
		if ( $d['client']['email'] ) {
			wp_mail( $d['client']['email'], 'Confirmare acceptare — oferta de printare 3D nr. ' . $nr, 'Bună ziua, ' . $name . ",\n\nAm primit acceptarea ofertei de printare 3D nr. {$nr} ({$when}), în valoare de " . p3d_money( $tot['total'] ) . ".\nVă contactăm în scurt timp pentru plată și începerea printării.\n\nOferta: " . $self . "\n\nMulțumim,\nPrint3D Shop\n" . p3d_opt( 'phone' ), $hdr );
		}
		wp_safe_redirect( add_query_arg( 'acceptat', '1', $self ) );
		exit;
	}

	if ( 'question' === $act ) {
		$msg = sanitize_textarea_field( wp_unslash( $_POST['mesaj'] ?? '' ) ); // phpcs:ignore
		if ( mb_strlen( $msg ) >= 3 && ! p3d_of_accept_valid( $id ) ) {
			update_post_meta( $id, '_p3d_of_question', array( 't' => time(), 'msg' => $msg ) );
			update_post_meta( $id, '_p3d_of_status', 'intrebari' );
			p3d_of_log( $id, 'Întrebare de la client: ' . mb_substr( $msg, 0, 120 ) );
			wp_mail( p3d_opt( 'email' ), 'Întrebare la oferta de printare ' . $nr . ( $d['client']['name'] ? ' — ' . $d['client']['name'] : '' ), $msg . "\n\nOferta: " . $adm, $hdr );
		}
		wp_safe_redirect( add_query_arg( 'intrebare', '1', $self ) );
		exit;
	}
}, 0 );
