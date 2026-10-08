<?php
/**
 * Articol de blog.
 */
defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article>
		<section class="page-head">
			<div class="wrap">
				<div class="head">
					<nav class="crumbs" aria-label="Breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a><span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">Ghiduri</a>
					</nav>
					<?php the_title( '<h1 class="h2">', '</h1>' ); ?>
					<span class="mono-sub"><?php echo esc_html( get_the_date() ); ?></span>
				</div>
			</div>
		</section>
		<section class="section">
			<div class="wrap">
				<div class="card content-card">
					<div class="prose">
						<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
						<?php the_content(); ?>
					</div>
				</div>
			</div>
		</section>
	</article>
	<section class="section">
		<div class="wrap">
			<div class="cta-band">
				<div>
					<h2 class="h2">Ai nevoie de o piesă printată sau de service?</h2>
					<p>Sună-ne sau scrie-ne și te ajutăm.</p>
				</div>
				<div class="ctas">
					<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-dark"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
					<a href="<?php echo esc_url( p3d_wa() ); ?>" class="btn btn-light" rel="noopener"><?php echo p3d_icon( 'whatsapp', 18 ); // phpcs:ignore ?> WhatsApp</a>
				</div>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
