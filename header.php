<?php
defined( 'ABSPATH' ) || exit;

$p3d_svc_more = array(
	array( p3d_service_url( 'printare-3d-personalizata' ), 'Printare 3D', p3d_is( 'printare-3d-personalizata' ), false, 'layers' ),
	array( p3d_service_url( 'prototyping' ), 'Proiectare 3D', p3d_is( 'prototyping' ), false, 'pen' ),
	array( p3d_service_url( 'scanare-3d' ), 'Scanare 3D', p3d_is( 'scanare-3d' ), false, 'scan' ),
	array( p3d_service_url( 'prelucrare-cnc-matrite' ), 'Prelucrare CNC și matrițe', p3d_is( 'prelucrare-cnc-matrite' ), false, 'gear' ),
	array( p3d_service_url( 'design-grafic-tipar' ), 'Design grafic și tipar', p3d_is( 'design-grafic-tipar' ), false, 'palette' ),
);
$p3d_svc_on = is_page( 'our-services' ) || in_array( true, wp_list_pluck( $p3d_svc_more, 2 ), true );
$p3d_nav = array(
	array( p3d_service_url( 'support' ), 'Service imprimante 3D', p3d_is( 'support' ), true, 'wrench' ),
	'servicii',
	array( home_url( '/preturi/' ), 'Prețuri', is_page( 'preturi' ), false, 'doc' ),
	array( home_url( '/magazin/' ), 'Magazin', is_page( 'magazin' ) || ( function_exists( 'is_woocommerce' ) && is_woocommerce() ), false, 'cart' ),
	array( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog-standard/' ), 'Ghiduri', is_home() || is_singular( 'post' ) || is_category(), false, 'doc' ),
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
				<?php if ( 'servicii' === $n ) : ?>
					<div class="nav-drop">
						<a href="<?php echo esc_url( home_url( '/our-services/' ) ); ?>" class="nav-drop-btn<?php echo $p3d_svc_on ? ' is-on' : ''; ?>" aria-haspopup="true">Alte servicii <?php echo p3d_icon( 'chevron', 14 ); // phpcs:ignore ?></a>
						<div class="nav-drop-menu">
							<?php foreach ( $p3d_svc_more as $m ) : ?>
								<a href="<?php echo esc_url( $m[0] ); ?>"<?php echo $m[2] ? ' aria-current="page"' : ''; ?>><span class="icon-tile sm"><?php echo p3d_icon( $m[4], 16 ); // phpcs:ignore ?></span><?php echo esc_html( $m[1] ); ?></a>
							<?php endforeach; ?>
							<a href="<?php echo esc_url( home_url( '/our-services/' ) ); ?>" class="nav-drop-all">Toate serviciile <?php echo p3d_icon( 'arrow', 14 ); // phpcs:ignore ?></a>
						</div>
					</div>
					<?php continue; ?>
				<?php endif; ?>
				<a href="<?php echo esc_url( $n[0] ); ?>"<?php echo $n[2] ? ' aria-current="page"' : ''; ?><?php echo $n[3] ? ' class="nav-strong"' : ''; ?>><?php echo esc_html( $n[1] ); ?></a>
			<?php endforeach; ?>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="nav-icon<?php echo is_account_page() ? ' is-on' : ''; ?>" title="<?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare'; ?>" aria-label="<?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare'; ?>"><?php echo p3d_icon( 'user', 20 ); // phpcs:ignore ?></a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="nav-icon nav-cart<?php echo is_cart() ? ' is-on' : ''; ?>" title="Coșul meu" aria-label="Coșul meu"><?php echo p3d_icon( 'cart', 20 ); // phpcs:ignore ?><span class="nav-cart-dot" hidden></span></a>
			<?php endif; ?>
			<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary nav-cta"><?php echo p3d_icon( 'phone', 17 ); // phpcs:ignore ?> <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
		</div>

		<details class="nav-mobile">
			<summary><?php echo p3d_icon( 'menu', 18 ); // phpcs:ignore ?> Meniu</summary>
			<div class="nav-mobile-panel">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Acasă</a>
				<?php foreach ( $p3d_nav as $n ) : ?>
					<?php if ( 'servicii' === $n ) : ?>
						<?php foreach ( $p3d_svc_more as $m ) : ?><a href="<?php echo esc_url( $m[0] ); ?>"><?php echo esc_html( $m[1] ); ?></a><?php endforeach; ?>
						<?php continue; ?>
					<?php endif; ?>
					<a href="<?php echo esc_url( $n[0] ); ?>"<?php echo $n[3] ? ' class="is-service"' : ''; ?>><?php echo esc_html( $n[1] ); ?></a>
				<?php endforeach; ?>
				<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<span class="sep" aria-hidden="true"></span>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo is_user_logged_in() ? 'Contul meu' : 'Autentificare / Cont nou'; ?></a>
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Coșul meu</a>
				<?php endif; ?>
				<span class="sep" aria-hidden="true"></span>
				<a href="<?php echo esc_attr( p3d_tel() ); ?>" class="btn btn-primary"><?php echo p3d_icon( 'phone', 17 ); // phpcs:ignore ?> Sună: <?php echo esc_html( p3d_opt( 'phone' ) ); ?></a>
				<a href="<?php echo esc_url( p3d_wa() ); ?>" class="btn btn-ghost" rel="noopener"><?php echo p3d_icon( 'whatsapp', 17 ); // phpcs:ignore ?> Scrie pe WhatsApp</a>
			</div>
		</details>
	</nav>
</header>

<main id="main">
