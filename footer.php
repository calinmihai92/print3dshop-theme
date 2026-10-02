<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<footer class="site-footer">
	<div class="wrap foot">
		<div class="foot-col">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="Print3D Shop"><?php echo p3d_logo(); // phpcs:ignore ?></a>
			<p class="foot-about">Service imprimante 3D, printare 3D la comandă și proiectare 3D, în București.</p>
		</div>
		<div class="foot-col">
			<span class="foot-title">Servicii</span>
			<?php foreach ( p3d_services() as $slug => $s ) : ?>
				<a href="<?php echo esc_url( p3d_service_url( $slug ) ); ?>"><?php echo esc_html( $s['label'] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( home_url( '/magazin/' ) ); ?>">Magazin</a>
		</div>
		<div class="foot-col">
			<span class="foot-title">Print3D Shop</span>
			<a href="<?php echo esc_url( home_url( '/despre-noi/' ) ); ?>">Despre noi</a>
			<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog-standard/' ) ); ?>">Blog</a>
			<a href="<?php echo esc_url( home_url( '/contacts/' ) ); ?>">Contact</a>
		</div>
		<div class="foot-col">
			<span class="foot-title">Contact</span>
			<a href="<?php echo esc_attr( p3d_tel() ); ?>"><?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
			<a href="mailto:<?php echo esc_attr( p3d_opt( 'email' ) ); ?>"><?php echo esc_html( p3d_opt( 'email' ) ); ?></a>
			<a href="<?php echo esc_url( p3d_opt( 'maps' ) ); ?>" rel="noopener"><?php echo esc_html( p3d_opt( 'address' ) ); ?></a>
		</div>
	</div>
	<div class="foot-bottom">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Print3D Shop · Site realizat de <a href="https://www.123ai.ro/" class="credit">123ai</a> — website-uri și automatizări pentru firme</span>
		<span>
			<a href="<?php echo esc_url( home_url( '/politica-de-confidentialitate/' ) ); ?>">Confidențialitate</a> ·
			<a href="<?php echo esc_url( home_url( '/termeni-si-conditii/' ) ); ?>">Termeni și condiții</a> ·
			<a href="<?php echo esc_url( home_url( '/politica-de-utilizare-cookie-uri/' ) ); ?>">Cookie-uri</a> ·
			<a href="https://anpc.ro/" rel="noopener">ANPC</a>
		</span>
	</div>
</footer>

<div class="floats">
	<a class="float float-call" href="<?php echo esc_attr( p3d_tel() ); ?>" aria-label="Sună <?php echo esc_attr( p3d_opt( 'phone' ) ); ?>"><?php echo p3d_icon( 'phone', 24 ); // phpcs:ignore ?></a>
	<a class="float float-wa" href="<?php echo esc_url( p3d_wa() ); ?>" aria-label="Scrie-ne pe WhatsApp" rel="noopener"><?php echo p3d_icon( 'whatsapp', 27 ); // phpcs:ignore ?></a>
</div>

<?php wp_footer(); ?>
</body>
</html>
