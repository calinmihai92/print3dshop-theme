<?php
/**
 * Paginile WooCommerce: magazinul (/magazin/), categoriile și paginile de produs.
 */
defined( 'ABSPATH' ) || exit;
get_header();

if ( function_exists( 'p3d_dkw_is_fil_archive' ) && p3d_dkw_is_fil_archive() ) :
	$p3d_term = get_queried_object();
	?>
	<section class="hero shop-hero">
		<div class="wrap hero-in">
			<span class="pill"><?php echo p3d_icon( 'layers', 13 ); // phpcs:ignore ?> Filamente 3D</span>
			<h1 class="h1"><?php echo 'filamente' === $p3d_term->slug ? 'Filamente pentru <em>imprimante 3D</em>' : esc_html( $p3d_term->name ); // phpcs:ignore ?></h1>
			<p class="lead">PLA, PETG, ABS, ASA, TPU și materiale speciale, de la branduri cunoscute. Stocul se actualizează automat.</p>
			<ul class="hero-points">
				<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Stoc actualizat automat</li>
				<li><?php echo p3d_icon( 'truck', 16 ); // phpcs:ignore ?> Livrare gratuită peste 250 lei</li>
				<li><?php echo p3d_icon( 'lock', 16 ); // phpcs:ignore ?> Plata la livrare sau cu cardul</li>
			</ul>
		</div>
	</section>
	<section class="section shop-list">
		<div class="wrap"><?php echo p3d_dkw_filters_html(); // phpcs:ignore ?><?php woocommerce_content(); ?></div>
	</section>
	<?php
elseif ( is_shop() || is_product_taxonomy() ) :
	?>
	<section class="hero shop-hero">
		<div class="wrap hero-in">
			<span class="pill"><?php echo p3d_icon( 'cart', 13 ); // phpcs:ignore ?> Magazin</span>
			<h1 class="h1"><?php echo is_shop() ? 'Produse printate 3D, <em>făcute de noi</em>' : esc_html( single_term_title( '', false ) ); // phpcs:ignore ?></h1>
			<p class="lead">Proiectate și printate în atelierul nostru din București. Livrare rapidă în toată țara.</p>
			<ul class="hero-points">
				<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Produse proprii, printate la noi</li>
				<li><?php echo p3d_icon( 'palette', 16 ); // phpcs:ignore ?> Culori la alegere</li>
				<li><?php echo p3d_icon( 'truck', 16 ); // phpcs:ignore ?> Livrare gratuită peste 250 lei</li>
				<li><?php echo p3d_icon( 'lock', 16 ); // phpcs:ignore ?> Plata la livrare sau cu cardul</li>
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
