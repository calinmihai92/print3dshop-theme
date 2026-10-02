<?php
/**
 * Despre noi (/despre-noi/).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="hero">
	<div class="wrap hero-in">
		<span class="eyebrow">Despre noi</span>
		<h1 class="h1">Inovație și pasiune, <em>la îndemâna ta</em></h1>
		<p class="lead">Print 3D SHOP este locul în care tehnologia se întâlnește cu creativitatea. Suntem o echipă tânără, cu sediul în București, dedicată printării și proiectării 3D.</p>
	</div>
</section>

<section class="section">
	<div class="wrap split">
		<div class="card box">
			<h2 class="h3">Cine <em>suntem</em></h2>
			<p>Suntem specialiști tineri, uniți de pasiunea pentru tehnologie și inovație. În echipă avem designeri, tehnicieni pentru printare 3D și specialiști în service-ul echipamentelor.</p>
			<p style="margin:0">De la proiecte mici până la cele mai ambițioase idei ale clienților noștri, ne propunem să le aducem la viață cât mai realist și mai inovator.</p>
		</div>
		<div class="card box">
			<h2 class="h3">Ce <em>facem</em></h2>
			<ul class="check-list">
				<li><?php echo p3d_icon( 'check', 18 ); // phpcs:ignore ?><span><a href="<?php echo esc_url( p3d_service_url( 'support' ) ); ?>">Service și reparații imprimante 3D</a></span></li>
				<li><?php echo p3d_icon( 'check', 18 ); // phpcs:ignore ?><span><a href="<?php echo esc_url( p3d_service_url( 'printare-3d-personalizata' ) ); ?>">Printare 3D personalizată</a>, de la prototipuri la obiecte decorative</span></li>
				<li><?php echo p3d_icon( 'check', 18 ); // phpcs:ignore ?><span><a href="<?php echo esc_url( p3d_service_url( 'prototyping' ) ); ?>">Proiectare și modelare 3D</a></span></li>
			</ul>
		</div>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="head"><h2 class="h2">Ce ne <em>diferențiază</em></h2></div>
		<div class="grid grid-4">
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'zap', 22 ); // phpcs:ignore ?></span><h3>Dedicare și pasiune</h3><p>Ne implicăm în fiecare proiect pentru un serviciu de calitate.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'gear', 22 ); // phpcs:ignore ?></span><h3>Expertiză și inovație</h3><p>Tehnicieni calificați și designeri talentați, la curent cu tehnologiile noi.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'palette', 22 ); // phpcs:ignore ?></span><h3>Personalizare</h3><p>Adaptăm serviciile la nevoile unice ale fiecărui client și proiect.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'shield', 22 ); // phpcs:ignore ?></span><h3>Calitate și precizie</h3><p>Echipamente și materiale de calitate, pentru rezultate precise și durabile.</p></article>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/contact' ); ?>

<?php
get_footer();
