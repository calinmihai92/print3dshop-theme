<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="notfound">
	<div class="wrap hero-in">
		<span class="eyebrow">Eroare 404</span>
		<h1 class="h1">Pagina nu <em>există</em></h1>
		<p class="lead">Poate a fost mutată. Încearcă una dintre paginile de mai jos.</p>
		<div class="ctas">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">Prima pagină</a>
			<a href="<?php echo esc_url( p3d_service_url( 'support' ) ); ?>" class="btn btn-ghost">Service imprimante 3D</a>
		</div>
	</div>
</section>
<?php
get_footer();
