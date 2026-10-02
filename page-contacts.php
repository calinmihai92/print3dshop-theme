<?php
/**
 * Contact (/contacts/).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="page-head">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">Contact</span>
			<h1 class="h1">Ai întrebări? <em>Contactează-ne</em></h1>
			<p class="lead">Pentru service imprimante 3D, printare la comandă sau proiectare 3D, suntem la un telefon distanță.</p>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/contact', null, array( 'title' => 'Cum ne <em>găsești</em>' ) ); ?>

<?php
get_footer();
