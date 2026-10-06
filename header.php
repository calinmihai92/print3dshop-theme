<?php
defined( 'ABSPATH' ) || exit;

$p3d_nav = array(
	array( p3d_service_url( 'support' ), 'Service imprimante 3D', p3d_is( 'support' ), true, 'wrench' ),
	array( p3d_service_url( 'printare-3d-personalizata' ), 'Printare 3D', p3d_is( 'printare-3d-personalizata' ), false, 'layers' ),
	array( p3d_service_url( 'prototyping' ), 'Proiectare 3D', p3d_is( 'prototyping' ), false, 'pen' ),
	array( p3d_service_url( 'prelucrare-cnc-matrite' ), 'CNC și matrițe', p3d_is( 'prelucrare-cnc-matrite' ), false, 'gear' ),
	array( home_url( '/preturi/' ), 'Prețuri', is_page( 'preturi' ), false, 'doc' ),
	array( home_url( '/magazin/' ), 'Magazin', is_page( 'magazin' ) || ( function_exists( 'is_woocommerce' ) && is_woocommerce() ), false, 'cart' ),
	array( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog-standard/' ), 'Blog', is_home() || is_singular( 'post' ) || is_category(), false, 'doc' ),
	array( home_url( '/despre-noi/' ), 'Despre noi', is_page( 'despre-noi' ), false, 'users' ),
	array( home_url( '/contacts/' ), 'Contact', is_page( 'contacts' ), false, 'pin' ),
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">Sari la conținut</a>

<header class="site-header">
	<nav class="wrap nav" aria-label="Principal">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="Print3D Shop — prima pagină">
			<?php echo p3d_logo(); // phpcs:ignore ?>
		</a>

		<div class="nav-links">
			<?php foreach ( $p3d_nav as $n ) : ?>
				<a href="<?php echo esc_url( $n[0] ); ?>"<?php echo $n[2] ? ' aria-current="page"' : ''; ?><?php echo $n[3] ? ' class="nav-strong"' : ''; ?>><?php echo esc_html( $n[1] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary nav-cta"><?php echo p3d_icon( 'phone', 17 ); // phpcs:ignore ?> <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
		</div>

		<details class="nav-mobile">
			<summary><?php echo p3d_icon( 'menu', 18 ); // phpcs:ignore ?> Meniu</summary>
			<div class="nav-mobile-panel">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a>
				<?php foreach ( $p3d_nav as $n ) : ?>
					<a href="<?php echo esc_url( $n[0] ); ?>"<?php echo $n[3] ? ' class="is-service"' : ''; ?>><?php echo esc_html( $n[1] ); ?></a>
				<?php endforeach; ?>
				<span class="sep" aria-hidden="true"></span>
				<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 17 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
				<a href="<?php echo esc_url( p3d_wa() ); ?>" class="btn btn-ghost" rel="noopener"><?php echo p3d_icon( 'whatsapp', 17 ); // phpcs:ignore ?> Scrie pe WhatsApp</a>
			</div>
		</details>
	</nav>
</header>

<main id="main">
