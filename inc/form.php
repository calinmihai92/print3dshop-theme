<?php
/**
 * Formular „Te sun eu” — trimite un email la adresa din Date de contact.
 */

defined( 'ABSPATH' ) || exit;

function p3d_callback_form( $preset = '' ) {
	$status = isset( $_GET['trimis'] ) ? sanitize_key( wp_unslash( $_GET['trimis'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$opts   = array( 'Service imprimantă 3D', 'Printare 3D', 'Proiectare 3D', 'Altceva' );
	ob_start();
	?>
	<form class="p3d-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php if ( 'ok' === $status ) : ?>
			<p class="form-ok" role="status">Mulțumim! Am primit mesajul și te sunăm cât de repede putem.</p>
		<?php elseif ( 'eroare' === $status ) : ?>
			<p class="form-err" role="alert">Mesajul nu a putut fi trimis. Te rugăm să ne suni la <?php echo esc_html( p3d_opt( 'phone' ) ); ?>.</p>
		<?php endif; ?>
		<input type="hidden" name="action" value="p3d_callback">
		<input type="hidden" name="back" value="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
		<?php wp_nonce_field( 'p3d_callback', 'p3d_nonce' ); ?>
		<div class="row">
			<label>Nume<input type="text" name="nume" autocomplete="name" required></label>
			<label>Telefon<input type="tel" name="telefon" autocomplete="tel" required pattern="[0-9 +().-]{8,}"></label>
		</div>
		<label>De ce ai nevoie?
			<select name="tip">
				<?php foreach ( $opts as $o ) : ?><option<?php selected( $preset, $o ); ?>><?php echo esc_html( $o ); ?></option><?php endforeach; ?>
			</select>
		</label>
		<label>Pe scurt (opțional)<textarea name="mesaj" placeholder="Ex.: imprimanta nu mai extrudează / aș vrea o piesă printată…"></textarea></label>
		<label class="hp" aria-hidden="true">Nu completa<input type="text" name="site" tabindex="-1" autocomplete="off"></label>
		<label class="consent"><input type="checkbox" name="acord" value="1" required> <span>Sunt de acord ca datele mele să fie folosite pentru a fi contactat, conform <a href="<?php echo esc_url( home_url( '/politica-de-confidentialitate/' ) ); ?>">politicii de confidențialitate</a>.</span></label>
		<div><button type="submit" class="btn btn-primary">Te sunăm noi <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></button></div>
	</form>
	<?php
	return ob_get_clean();
}

function p3d_handle_callback() {
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : home_url( '/' );
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$fail = add_query_arg( 'trimis', 'eroare', $back ) . '#contact';

	if ( ! isset( $_POST['p3d_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['p3d_nonce'] ) ), 'p3d_callback' ) ) {
		wp_safe_redirect( $fail );
		exit;
	}
	// Câmp-capcană pentru boți.
	if ( ! empty( $_POST['site'] ) ) {
		wp_safe_redirect( add_query_arg( 'trimis', 'ok', $back ) . '#contact' );
		exit;
	}

	$nume    = sanitize_text_field( wp_unslash( $_POST['nume'] ?? '' ) );
	$telefon = sanitize_text_field( wp_unslash( $_POST['telefon'] ?? '' ) );
	$tip     = sanitize_text_field( wp_unslash( $_POST['tip'] ?? '' ) );
	$mesaj   = sanitize_textarea_field( wp_unslash( $_POST['mesaj'] ?? '' ) );

	if ( '' === $nume || strlen( preg_replace( '/\D/', '', $telefon ) ) < 8 || empty( $_POST['acord'] ) ) {
		wp_safe_redirect( $fail );
		exit;
	}

	$body = "Cerere nouă de pe print3dshop.ro\n\n"
		. "Nume: {$nume}\nTelefon: {$telefon}\nDe ce are nevoie: {$tip}\n\nMesaj:\n{$mesaj}\n\nPagina: {$back}\n";
	$sent = wp_mail( p3d_opt( 'email' ), 'Te sun eu: ' . $tip . ' — ' . $nume, $body );

	wp_safe_redirect( add_query_arg( 'trimis', $sent ? 'ok' : 'eroare', $back ) . '#contact' );
	exit;
}
add_action( 'admin_post_p3d_callback', 'p3d_handle_callback' );
add_action( 'admin_post_nopriv_p3d_callback', 'p3d_handle_callback' );
