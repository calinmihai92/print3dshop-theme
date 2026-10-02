<?php
/**
 * Pagina „Servicii” (/our-services/).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="hero">
	<div class="wrap hero-in">
		<span class="eyebrow">Servicii</span>
		<h1 class="h1">Servicii de <em>printare 3D</em></h1>
		<p class="lead">Service și reparații imprimante 3D, printare 3D personalizată și proiectare 3D, în București. Alege de ce ai nevoie sau sună-ne și te îndrumăm.</p>
		<div class="ctas">
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
		</div>
	</div>
</section>

<section class="section">
	<div class="wrap"><?php get_template_part( 'template-parts/paths' ); ?></div>
</section>

<section class="section">
	<div class="wrap"><?php get_template_part( 'template-parts/service-band' ); ?></div>
</section>

<section class="section">
	<div class="wrap">
		<div class="head">
			<h2 class="h2">Cum <em>funcționează</em></h2>
		</div>
		<ol class="steps">
			<li class="card step"><span class="num">01</span><strong>Trimite modelul 3D</strong><span>Trimite-ne modelul 3D sau descrie-ne ideea, iar noi îl proiectăm.</span></li>
			<li class="card step"><span class="num">02</span><strong>Alege materialul</strong><span>Alegi materialul, culoarea și dimensiunile potrivite nevoilor tale.</span></li>
			<li class="card step"><span class="num">03</span><strong>Primește modelul</strong><span>Poți opta pentru ridicarea personală sau livrarea la domiciliu.</span></li>
		</ol>
	</div>
</section>

<?php get_template_part( 'template-parts/contact' ); ?>

<?php
get_footer();
