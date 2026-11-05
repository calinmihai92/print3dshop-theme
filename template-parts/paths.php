<?php
/**
 * Cele trei servicii, cu service-ul pus în față.
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="paths">
	<?php
	$p3d_others = count( p3d_services() ) - 1;
	$p3d_rows   = (int) ceil( $p3d_others / 2 );
	$p3d_n      = 0;
	foreach ( p3d_services() as $slug => $s ) :
		$featured = ( 'support' === $slug );
		$wide     = ! $featured && ( ++$p3d_n === $p3d_others ) && ( $p3d_others % 2 );
		?>
		<a href="<?php echo esc_url( p3d_service_url( $slug ) ); ?>" class="card path<?php echo $featured ? ' featured' : ''; ?><?php echo $wide ? ' wide' : ''; ?>"<?php echo $featured ? ' style="--rows:' . (int) $p3d_rows . '"' : ''; ?>>
			<?php $p3d_thumb = ( ! $featured && ! empty( $s['photos'][0] ) && wp_attachment_is_image( $s['photos'][0] ) ) ? (int) $s['photos'][0] : 0; ?>
			<?php if ( $p3d_thumb ) : ?>
				<span class="path-thumb"><?php echo wp_get_attachment_image( $p3d_thumb, 'medium_large', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 640px) 92vw, (max-width: 1000px) 45vw, 360px', 'alt' => $s['label'] ) ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<span class="path-top">
				<span class="icon-tile"><?php echo p3d_icon( $s['icon'], 22 ); // phpcs:ignore ?></span>
				<?php if ( $featured ) : ?><span class="pill dark">Cel mai cerut</span><?php endif; ?>
			</span>
			<h3 class="h3"><?php echo esc_html( $s['h1a'] ); ?> <em><?php echo esc_html( $s['h1b'] ); ?></em></h3>
			<p><?php echo esc_html( $s['card'] ); ?></p>
			<?php if ( $featured && wp_attachment_is_image( 24527 ) ) : ?>
				<span class="path-img"><?php echo wp_get_attachment_image( 24527, 'large', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 1000px) 92vw, 420px' ) ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<span class="path-more">Detalii <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></span>
		</a>
	<?php endforeach; ?>
</div>
