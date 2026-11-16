<?php
/**
 * Buton „Email” în wp-admin (meniu + bara de sus): deschide webmail-ul SiteGround
 * pentru adresele @print3dshop.ro, fără să mai intri în panoul de hosting.
 */

defined( 'ABSPATH' ) || exit;

function p3d_webmail_url() {
	return home_url( '/webmail' );
}

// Meniu în stânga.
add_action( 'admin_menu', function () {
	add_menu_page( 'Email', 'Email', 'manage_options', p3d_webmail_url(), '', 'dashicons-email-alt', 2 );
}, 20 );

// Buton în bara de sus (și în admin, și pe site, cât ești logat ca administrator).
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$bar->add_node( array(
		'id'    => 'p3d-webmail',
		'title' => '<span class="ab-icon dashicons dashicons-email-alt" style="top:2px"></span><span class="ab-label">Email</span>',
		'href'  => p3d_webmail_url(),
		'meta'  => array( 'target' => '_blank', 'rel' => 'noopener', 'title' => 'Deschide emailul (webmail)' ),
	) );
}, 80 );

// Linkul din meniu se deschide într-un tab nou.
add_action( 'admin_footer', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<script>
	(function () {
		var a = document.querySelector('#adminmenu a[href="<?php echo esc_js( p3d_webmail_url() ); ?>"]');
		if (a) { a.target = '_blank'; a.rel = 'noopener'; }
	})();
	</script>
	<?php
} );
