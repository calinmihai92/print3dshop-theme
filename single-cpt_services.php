<?php
/**
 * Pagină de serviciu (/services/<slug>/). Conținutul vine din inc/data.php.
 */
defined( 'ABSPATH' ) || exit;

$slug = get_post_field( 'post_name', get_queried_object_id() );
$s    = p3d_service( $slug );

if ( ! $s ) {
	// Serviciu fără conținut definit în temă: afișăm conținutul din editor.
	get_template_part( 'page' );
	return;
}

$is_service = ( 'support' === $slug );
get_header();
?>

<section class="svc-hero">
	<div class="wrap hero-in">
		<nav class="crumbs" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a><span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( home_url( '/our-services/' ) ); ?>">Servicii</a><span aria-hidden="true">/</span>
			<span aria-current="page"><?php echo esc_html( $s['label'] ); ?></span>
		</nav>
		<span class="pill"><?php echo p3d_icon( $s['icon'], 13 ); // phpcs:ignore ?> <?php echo esc_html( $s['eyebrow'] ); ?></span>
		<h1 class="h1"><?php echo esc_html( $s['h1a'] ); ?><br><em><?php echo esc_html( $s['h1b'] ); ?></em></h1>
		<p class="lead"><?php echo esc_html( $s['lead'] ); ?></p>
		<div class="ctas">
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
			<a href="<?php echo esc_url( p3d_wa( $s['wa'] ) ); ?>" class="btn btn-ghost" rel="noopener"><?php echo p3d_icon( 'whatsapp', 18 ); // phpcs:ignore ?> Scrie pe WhatsApp</a>
		</div>
		<ul class="hero-points">
			<?php foreach ( $s['points'] as $pt ) : ?>
				<li><?php echo p3d_icon( 'check', 16 ); // phpcs:ignore ?> <?php echo esc_html( $pt ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php if ( $is_service ) : ?>
	<section class="section">
		<div class="wrap">
			<div class="head"><h2 class="h2">Probleme pe care <em>le rezolvăm</em></h2></div>
			<div class="grid grid-3">
				<?php foreach ( $s['problems'] as $p ) : ?>
					<article class="card feat"><span class="icon-tile"><?php echo p3d_icon( 'alert', 22 ); // phpcs:ignore ?></span><h3><?php echo esc_html( $p[0] ); ?></h3><p><?php echo esc_html( $p[1] ); ?></p></article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="section">
	<div class="wrap">
		<div class="head"><h2 class="h2"><?php echo esc_html( $s['whatTitle'][0] ); ?><em><?php echo esc_html( $s['whatTitle'][1] ); ?></em></h2></div>
		<div class="grid grid-3">
			<?php foreach ( $s['what'] as $w ) : ?>
				<article class="card<?php echo $is_service ? '-soft' : ''; ?> feat"><span class="icon-tile"><?php echo p3d_icon( $w[0], 22 ); // phpcs:ignore ?></span><h3><?php echo esc_html( $w[1] ); ?></h3><p><?php echo esc_html( $w[2] ); ?></p></article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( ! empty( $s['brands'] ) ) : ?>
	<section class="section">
		<div class="wrap">
			<div class="card box center">
				<span class="eyebrow">Mărci cu care lucrăm</span>
				<div class="brands" style="justify-content:center;margin-top:14px">
					<?php foreach ( $s['brands'] as $b ) : ?><span class="pill outline"><?php echo esc_html( $b ); ?></span><?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="section">
	<div class="wrap">
		<div class="head"><h2 class="h2"><?php echo esc_html( $s['stepsTitle'][0] ); ?><em><?php echo esc_html( $s['stepsTitle'][1] ); ?></em></h2></div>
		<ol class="steps">
			<?php foreach ( $s['steps'] as $i => $st ) : ?>
				<li class="card step"><span class="num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><strong><?php echo esc_html( $st[0] ); ?></strong><span><?php echo esc_html( $st[1] ); ?></span></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="cta-band">
			<div>
				<h2 class="h2"><?php echo $is_service ? 'Imprimanta ta are nevoie de service?' : 'Ai un proiect în minte?'; ?></h2>
				<p><?php echo $is_service ? 'Sună-ne sau scrie-ne ce face imprimanta.' : 'Spune-ne ideea ta și îți spunem cum o realizăm.'; ?></p>
			</div>
			<div class="ctas">
				<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-dark"><?php echo p3d_icon( 'phone', 18 ); // phpcs:ignore ?> <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
				<a href="<?php echo esc_url( p3d_wa( $s['wa'] ) ); ?>" class="btn btn-light" rel="noopener"><?php echo p3d_icon( 'whatsapp', 18 ); // phpcs:ignore ?> WhatsApp</a>
			</div>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/faq', null, array( 'items' => $s['faq'] ) ); ?>

<section class="section">
	<div class="wrap">
		<div class="head"><h2 class="h2">Alte <em>servicii</em></h2></div>
		<div class="grid grid-2">
			<?php foreach ( $s['related'] as $r ) : $rs = p3d_service( $r ); if ( ! $rs ) { continue; } ?>
				<a href="<?php echo esc_url( p3d_service_url( $r ) ); ?>" class="card path">
					<span class="icon-tile"><?php echo p3d_icon( $rs['icon'], 22 ); // phpcs:ignore ?></span>
					<h3 class="h3"><?php echo esc_html( $rs['h1a'] ); ?> <em><?php echo esc_html( $rs['h1b'] ); ?></em></h3>
					<p><?php echo esc_html( $rs['card'] ); ?></p>
					<span class="path-more">Detalii <?php echo p3d_icon( 'arrow', 16 ); // phpcs:ignore ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/contact', null, array( 'wa' => $s['wa'] ) ); ?>

<?php
get_footer();
