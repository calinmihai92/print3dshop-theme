<?php
/**
 * Blog, arhive, căutare.
 */
defined( 'ABSPATH' ) || exit;
get_header();

if ( is_home() ) {
	$title = 'Blog <em>Print 3D</em>';
	$lead  = 'Inspirație, idei de proiecte și sfaturi despre printarea 3D.';
} elseif ( is_search() ) {
	$title = 'Rezultate pentru <em>„' . esc_html( get_search_query() ) . '”</em>';
	$lead  = '';
} else {
	$title = esc_html( wp_strip_all_tags( get_the_archive_title() ) );
	$lead  = wp_strip_all_tags( get_the_archive_description() );
}
?>

<section class="page-head">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">Blog</span>
			<h1 class="h1"><?php echo wp_kses( $title, array( 'em' => array() ) ); ?></h1>
			<?php if ( $lead ) : ?><p class="lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
		</div>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a href="<?php the_permalink(); ?>" class="card post-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<span class="thumb"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?></span>
						<?php else : ?>
							<span class="thumb empty"><?php echo p3d_icon( 'layers', 40 ); // phpcs:ignore ?></span>
						<?php endif; ?>
						<span class="body">
							<span class="mono-sub"><?php echo esc_html( get_the_date() ); ?></span>
							<h2><?php the_title(); ?></h2>
							<p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<nav class="pagination" aria-label="Pagini"><?php echo paginate_links( array( 'prev_text' => '‹', 'next_text' => '›' ) ); // phpcs:ignore ?></nav>
		<?php else : ?>
			<div class="card content-card center"><p style="margin:0">Nu am găsit articole.</p></div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
