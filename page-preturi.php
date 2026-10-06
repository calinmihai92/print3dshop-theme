<?php
/**
 * Prețuri orientative (/preturi/). Datele vin din p3d_prices() în inc/data.php.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$prices = p3d_prices();
$wa     = 'Bună ziua! Aș vrea o ofertă pentru: ';
?>

<section class="hero">
	<div class="wrap hero-in">
		<span class="eyebrow">Prețuri</span>
		<h1 class="h1">Prețuri <em>transparente</em></h1>
		<p class="lead">Prețuri orientative pentru service imprimante 3D, printare și proiectare 3D, plus scanare 3D, prelucrare CNC și matrițe la cerere. Toate includ TVA, iar prețul final ți-l confirmăm înainte să începem.</p>
		<nav class="price-jump" aria-label="Categorii de prețuri">
			<?php foreach ( $prices as $key => $cat ) : ?>
				<a href="#<?php echo esc_attr( $key ); ?>" class="pill outline"><?php echo esc_html( $cat['title'][0] . $cat['title'][1] ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>

<section class="section">
	<div class="wrap price-cats">
		<?php foreach ( $prices as $key => $cat ) : ?>
			<article class="card price-cat" id="<?php echo esc_attr( $key ); ?>">
				<header class="price-head">
					<span class="icon-tile"><?php echo p3d_icon( $cat['icon'], 22 ); // phpcs:ignore ?></span>
					<div>
						<h2 class="h3"><?php echo esc_html( $cat['title'][0] ); ?><em><?php echo esc_html( $cat['title'][1] ); ?></em></h2>
						<p><?php echo esc_html( $cat['intro'] ); ?></p>
					</div>
				</header>
				<?php p3d_price_rows( $cat['rows'] ); ?>
				<?php if ( ! empty( $cat['note'] ) ) : ?>
					<p class="price-note"><?php echo esc_html( $cat['note'] ); ?></p>
				<?php endif; ?>
				<div class="price-foot">
					<a href="<?php echo esc_url( p3d_wa( $wa . strtolower( $cat['title'][0] . $cat['title'][1] ) . '. ' ) ); ?>" class="btn btn-primary" rel="noopener"><?php echo p3d_icon( 'whatsapp', 17 ); // phpcs:ignore ?> <?php echo 'cnc' === $key ? 'Cere ofertă' : 'Cere prețul exact'; ?></a>
					<?php if ( $cat['service'] ) : ?>
						<a href="<?php echo esc_url( p3d_service_url( $cat['service'] ) ); ?>" class="btn btn-ghost">Despre serviciu <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
		<p class="price-legal">Prețurile sunt orientative și includ TVA. Piesele de schimb se plătesc separat, la prețul lor. Prețul final depinde de lucrare și îl confirmăm înainte de începere.</p>
	</div>
</section>

<?php get_template_part( 'template-parts/faq', null, array( 'items' => p3d_prices_faq() ) ); ?>

<?php get_template_part( 'template-parts/contact', null, array( 'title' => 'Vrei prețul <em>exact</em>?', 'wa' => $wa ) ); ?>

<?php
get_footer();
