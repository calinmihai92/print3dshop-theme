<?php
/**
 * Magazin — „În curând” (cât timp pagina nu e setată ca pagină-magazin WooCommerce).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="hero">
	<div class="wrap hero-in">
		<span class="pill"><?php echo p3d_icon( 'cart', 13 ); // phpcs:ignore ?> Magazin</span>
		<h1 class="h1">Magazinul vine <em>în curând</em></h1>
		<p class="lead">Lucrăm la noul magazin cu modele printate 3D. Până atunci, îți printăm orice piesă la comandă sau îți reparăm imprimanta.</p>
		<div class="ctas">
			<a href="<?php echo esc_url( p3d_service_url( 'printare-3d-personalizata' ) ); ?>" class="btn btn-primary">Printare 3D la comandă <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
			<a href="<?php echo esc_url( p3d_service_url( 'support' ) ); ?>" class="btn btn-ghost">Service imprimante 3D</a>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/contact' ); ?>

<?php
get_footer();
