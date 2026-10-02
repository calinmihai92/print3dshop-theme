<?php
/**
 * Paginile WooCommerce: magazinul (/magazin/), categoriile și paginile de produs.
 */
defined( 'ABSPATH' ) || exit;
get_header();

if ( is_shop() || is_product_taxonomy() ) :
	?>
	<section class="hero shop-hero">
		<div class="wrap hero-in">
			<span class="pill"><?php echo p3d_icon( 'cart', 13 ); // phpcs:ignore ?> Magazin</span>
			<h1 class="h1"><?php echo is_shop() ? 'Produse printate 3D, <em>făcute de noi</em>' : esc_html( single_term_title( '', false ) ); // phpcs:ignore ?></h1>
			<p class="lead">Proiectate și printate în atelierul nostru din București. Livrare rapidă în toată țara.</p>
			<ul class="hero-points">
				<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Produse proprii, printate la noi</li>
				<li><?php echo p3d_icon( 'palette', 16 ); // phpcs:ignore ?> Culori la alegere</li>
				<li><?php echo p3d_icon( 'truck', 16 ); // phpcs:ignore ?> Plata la livrare</li>
			</ul>
		</div>
	</section>
	<section class="section shop-list">
		<div class="wrap"><?php woocommerce_content(); ?></div>
	</section>
	<?php
else :
	?>
	<section class="section shop-single">
		<div class="wrap"><?php woocommerce_content(); ?></div>
	</section>
	<?php
endif;

get_footer();
