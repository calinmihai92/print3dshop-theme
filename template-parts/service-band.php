<?php
/**
 * Bandă închisă: probleme frecvente la imprimante + mărci + apel.
 */
defined( 'ABSPATH' ) || exit;
$s = p3d_service( 'support' );
?>
<div class="band">
	<div class="band-intro">
		<span class="pill dark"><?php echo p3d_icon( 'wrench', 13 ); // phpcs:ignore ?> Service imprimante 3D</span>
		<h2 class="h2">Imprimanta nu mai printează? <em>O reparăm.</em></h2>
		<p class="lead">Diagnoză, piese de schimb, reparații și reglaje pentru cele mai populare imprimante 3D. Spune-ne ce face și te ajutăm.</p>
		<div class="brands" aria-label="Mărci">
			<?php foreach ( $s['brands'] as $b ) : ?><span class="brand-chip"><?php echo esc_html( $b ); ?></span><?php endforeach; ?>
		</div>
		<div class="ctas" style="justify-content:flex-start">
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
			<a href="<?php echo esc_url( p3d_service_url( 'support' ) ); ?>" class="btn btn-outline-light">Despre service <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
		</div>
	</div>
	<div class="problems">
		<?php foreach ( $s['problems'] as $p ) : ?>
			<div class="problem">
				<?php echo p3d_icon( 'alert', 20 ); // phpcs:ignore ?>
				<div><strong><?php echo esc_html( $p[0] ); ?></strong><span><?php echo esc_html( $p[1] ); ?></span></div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
