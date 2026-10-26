<?php
/**
 * Pagini obișnuite (legale, coș, finalizare comandă etc.).
 */
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	if ( function_exists( 'is_account_page' ) && is_account_page() ) :
		$p3d_in = is_user_logged_in();
		?>
		<section class="page-head account-head">
			<div class="wrap"><div class="head">
				<span class="pill"><?php echo p3d_icon( 'user', 13 ); // phpcs:ignore ?> Contul meu</span>
				<h1 class="h2"><?php echo $p3d_in ? 'Contul <em>meu</em>' : 'Intră în <em>cont</em>'; // phpcs:ignore ?></h1>
				<?php if ( ! $p3d_in ) : ?><p class="lead">Vezi comenzile tale, adresele de livrare și comanzi mai repede data viitoare.</p><?php endif; ?>
			</div></div>
		</section>
		<section class="section account-section">
			<div class="wrap"><?php the_content(); ?></div>
		</section>
		<?php
		continue;
	endif;
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
