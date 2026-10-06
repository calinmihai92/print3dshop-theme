<?php
/**
 * Prima pagină.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="hero">
	<div class="wrap hero-in">
		<span class="pill"><?php echo p3d_icon( 'pin', 13 ); // phpcs:ignore ?> București · Șoseaua Iancului 53</span>
		<h1 class="h1">Print 3D București<br><em>și service imprimante 3D</em></h1>
		<p class="lead">Reparăm și întreținem imprimante 3D, printăm piese la comandă și proiectăm modelele 3D de care ai nevoie. De la prototipuri la piese personalizate, transformăm ideile tale în obiecte reale.</p>
		<div class="ctas">
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
			<a href="<?php echo esc_url( p3d_wa() ); ?>" class="btn btn-ghost" rel="noopener"><?php echo p3d_icon( 'whatsapp', 18 ); // phpcs:ignore ?> Scrie pe WhatsApp</a>
		</div>
		<ul class="hero-points">
			<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Service și reparații imprimante 3D</li>
			<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Printare 3D la comandă</li>
			<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Proiectare 3D</li>
			<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> Prelucrare CNC și matrițe</li>
		</ul>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">Ce facem</span>
			<h2 class="h2">Tot ce ai nevoie, <em>într-un singur loc</em></h2>
		</div>
		<?php get_template_part( 'template-parts/paths' ); ?>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<?php get_template_part( 'template-parts/service-band' ); ?>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">Printare 3D</span>
			<h2 class="h2">Procesul de <em>printare 3D</em></h2>
			<p class="lead">Simplu, în trei pași. Dacă nu ai încă un model 3D, îl proiectăm noi.</p>
		</div>
		<ol class="steps">
			<li class="card step"><span class="num">01</span><strong>Trimite modelul 3D</strong><span>Încarcă modelul tău 3D sau adu piesa pe care vrei să o printezi.</span></li>
			<li class="card step"><span class="num">02</span><strong>Alege materialul și culoarea</strong><span>Alegi materialul, culoarea și dimensiunile potrivite nevoilor tale.</span></li>
			<li class="card step"><span class="num">03</span><strong>Primește piesele</strong><span>Inspectăm fiecare piesă, apoi o ridici personal sau ți-o livrăm.</span></li>
		</ol>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">De ce noi</span>
			<h2 class="h2">Tehnologii 3D moderne, <em>făcute cu grijă</em></h2>
		</div>
		<div class="grid grid-4">
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'palette', 22 ); // phpcs:ignore ?></span><h3>Personalizare</h3><p>Diverse materiale și culori, ca fiecare piesă să fie exact cum ți-ai imaginat-o.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'zap', 22 ); // phpcs:ignore ?></span><h3>Expertiză și inovație</h3><p>Tehnicieni pentru printare și service, designeri pentru modelare 3D.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'shield', 22 ); // phpcs:ignore ?></span><h3>Calitate verificată</h3><p>Fiecare piesă este inspectată înainte de a ajunge la tine.</p></article>
			<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'truck', 22 ); // phpcs:ignore ?></span><h3>Ridicare sau livrare</h3><p>Vii la noi pe Șoseaua Iancului sau îți trimitem piesele acasă.</p></article>
		</div>
	</div>
</section>

<?php
$p3d_shop_items = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 3, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
if ( $p3d_shop_items ) :
	?>
<section class="section">
	<div class="wrap">
		<div class="head">
			<span class="eyebrow">Din magazin</span>
			<h2 class="h2">Proiectate și printate <em>de noi</em></h2>
		</div>
		<div class="grid grid-3 home-products">
			<?php foreach ( $p3d_shop_items as $p3d_p ) : ?>
				<a class="card home-product" href="<?php echo esc_url( $p3d_p->get_permalink() ); ?>">
					<?php echo $p3d_p->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore ?>
					<span class="home-product-name"><?php echo esc_html( $p3d_p->get_name() ); ?></span>
					<span class="home-product-price"><?php echo wp_kses_post( $p3d_p->get_price_html() ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="center" style="margin-top:28px"><a href="<?php echo esc_url( home_url( '/magazin/' ) ); ?>" class="btn btn-dark">Vezi tot magazinul <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></a></p>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/faq', null, array( 'items' => p3d_home_faq() ) ); ?>
<?php get_template_part( 'template-parts/contact' ); ?>

<?php
get_footer();
