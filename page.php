<?php
/**
 * Pagini obișnuite (legale, coș, finalizare comandă etc.).
 */
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page-head">
		<div class="wrap"><div class="head"><?php the_title( '<h1 class="h2">', '</h1>' ); ?></div></div>
	</section>
	<section class="section">
		<div class="wrap">
			<div class="card content-card"><div class="prose"><?php the_content(); ?></div></div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
