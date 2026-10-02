<?php
/**
 * Bloc de contact: telefon, WhatsApp, email, adresă + formular.
 * $args['title'] opțional, $args['wa'] text precompletat WhatsApp.
 */
defined( 'ABSPATH' ) || exit;
$wa_text = $args['wa'] ?? 'Bună ziua! Vă scriu de pe print3dshop.ro.';
$form    = p3d_callback_form( $args['preset'] ?? '' );
?>
<section class="section" id="contact">
	<div class="wrap">
		<div class="head">
			<h2 class="h2"><?php echo wp_kses( $args['title'] ?? 'Hai să <em>vorbim</em>', array( 'em' => array() ) ); ?></h2>
			<p class="lead">Sună-ne, scrie-ne pe WhatsApp sau trece pe la noi. Îți răspundem cât de repede putem.</p>
		</div>
		<div class="contact-grid"<?php echo $form ? '' : ' style="grid-template-columns:1fr;max-width:640px;margin:0 auto"'; ?>>
			<div class="card contact-main">
				<ul class="contact-list">
					<li><a class="contact-item" href="<?php echo esc_attr( p3d_tel() ); ?>"><span class="icon-tile solid"><?php echo p3d_icon( 'phone', 22 ); // phpcs:ignore ?></span><span><small>Telefon</small><strong><?php echo esc_html( p3d_opt( 'phone' ) ); ?></strong></span></a></li>
					<li><a class="contact-item" href="<?php echo esc_url( p3d_wa( $wa_text ) ); ?>" rel="noopener"><span class="icon-tile"><?php echo p3d_icon( 'whatsapp', 22 ); // phpcs:ignore ?></span><span><small>WhatsApp</small><strong>Scrie-ne un mesaj</strong></span></a></li>
					<li><a class="contact-item" href="mailto:<?php echo esc_attr( p3d_opt( 'email' ) ); ?>"><span class="icon-tile"><?php echo p3d_icon( 'mail', 22 ); // phpcs:ignore ?></span><span><small>Email</small><strong><?php echo esc_html( p3d_opt( 'email' ) ); ?></strong></span></a></li>
					<li><a class="contact-item" href="<?php echo esc_url( p3d_opt( 'maps' ) ); ?>" rel="noopener"><span class="icon-tile"><?php echo p3d_icon( 'pin', 22 ); // phpcs:ignore ?></span><span><small>Adresă</small><strong><?php echo esc_html( p3d_opt( 'address' ) ); ?></strong></span></a></li>
				</ul>
			</div>
			<?php if ( $form ) : ?>
				<div class="card contact-form">
					<h3 class="h3" style="margin-bottom:18px">Te sunăm <em>noi</em></h3>
					<?php echo $form; // phpcs:ignore ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
