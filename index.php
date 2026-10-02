<?php
/**
 * Șablon de bază (în lucru — tema nu se activează pe live până la final).
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main>
<?php
while ( have_posts() ) :
	the_post();
	the_title( '<h1>', '</h1>' );
	the_content();
endwhile;
?>
</main>
<?php wp_footer(); ?>
</body>
</html>
