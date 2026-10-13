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
