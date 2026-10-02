<?php
/**
 * Cele trei servicii, cu service-ul pus în față.
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="paths">
	<?php foreach ( p3d_services() as $slug => $s ) : $featured = ( 'support' === $slug ); ?>
		<a href="<?php echo esc_url( p3d_service_url( $slug ) ); ?>" class="card path<?php echo $featured ? ' featured' : ''; ?>">
			<span class="path-top">
				<span class="icon-tile"><?php echo p3d_icon( $s['icon'], 22 ); // phpcs:ignore ?></span>
				<?php if ( $featured ) : ?><span class="pill dark">Cel mai cerut</span><?php endif; ?>
			</span>
			<h3 class="h3"><?php echo esc_html( $s['h1a'] ); ?> <em><?php echo esc_html( $s['h1b'] ); ?></em></h3>
			<p><?php echo esc_html( $s['card'] ); ?></p>
			<span class="path-more">Detalii <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></span>
		</a>
	<?php endforeach; ?>
</div>
