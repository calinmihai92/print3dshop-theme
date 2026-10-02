<?php
/**
 * Plugin Name:       Print3D Shop — Actualizare temă
 * Description:       Actualizează tema Print3D Shop direct din GitHub (repo public), dintr-un click. Unelte → Actualizare temă.
 * Version:           1.0.0
 * Author:            123ai
 * Requires PHP:      8.0
 */

defined( 'ABSPATH' ) || exit;

const P3D_DEPLOY_REPO  = 'calinmihai92/print3dshop-theme';
const P3D_DEPLOY_THEME = 'print3dshop-theme';

add_action( 'admin_menu', function () {
	add_management_page( 'Actualizare temă', 'Actualizare temă', 'manage_options', 'p3d-deployer', 'p3d_deployer_page' );
} );

/**
 * Descarcă ramura cerută și înlocuiește folderul temei.
 *
 * @return string|WP_Error Versiunea instalată.
 */
function p3d_deployer_run( $branch = 'main' ) {
	if ( ! preg_match( '/^[A-Za-z0-9._\/-]+$/', $branch ) ) {
		return new WP_Error( 'branch', 'Nume de ramură invalid.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	$url = 'https://codeload.github.com/' . P3D_DEPLOY_REPO . '/zip/refs/heads/' . $branch;
	$zip = download_url( $url, 120 );
	if ( is_wp_error( $zip ) ) {
		return $zip;
	}

	$tmp = trailingslashit( get_temp_dir() ) . 'p3d-deploy-' . wp_generate_password( 8, false );
	$res = unzip_file( $zip, $tmp );
	wp_delete_file( $zip );
	if ( is_wp_error( $res ) ) {
		return $res;
	}

	$dirs = glob( $tmp . '/*', GLOB_ONLYDIR );
	$src  = $dirs ? $dirs[0] : '';
	if ( ! $src || ! file_exists( $src . '/style.css' ) ) {
		$wp_filesystem->delete( $tmp, true );
		return new WP_Error( 'zip', 'Arhiva nu conține o temă validă (lipsește style.css).' );
	}

	$dest = trailingslashit( get_theme_root() ) . P3D_DEPLOY_THEME;
	$old  = $dest . '-old';
	if ( $wp_filesystem->exists( $old ) ) {
		$wp_filesystem->delete( $old, true );
	}
	if ( $wp_filesystem->exists( $dest ) && ! $wp_filesystem->move( $dest, $old ) ) {
		$wp_filesystem->delete( $tmp, true );
		return new WP_Error( 'move', 'Nu am putut muta tema veche.' );
	}

	$wp_filesystem->mkdir( $dest );
	$copied = copy_dir( $src, $dest );
	$wp_filesystem->delete( $tmp, true );

	if ( is_wp_error( $copied ) ) {
		// Revenim la versiunea veche.
		$wp_filesystem->delete( $dest, true );
		$wp_filesystem->move( $old, $dest );
		return $copied;
	}
	$wp_filesystem->delete( $old, true );

	wp_clean_themes_cache();
	if ( function_exists( 'opcache_reset' ) ) {
		@opcache_reset(); // phpcs:ignore
	}
	do_action( 'p3d_theme_deployed', $branch );

	$data = get_file_data( $dest . '/style.css', array( 'Version' => 'Version' ) );
	update_option( 'p3d_last_deploy', array( 'time' => time(), 'branch' => $branch, 'version' => $data['Version'] ), false );
	return $data['Version'];
}

function p3d_deployer_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['p3d_deploy'] ) && check_admin_referer( 'p3d_deploy' ) ) {
		$branch = sanitize_text_field( wp_unslash( $_POST['branch'] ?? 'main' ) );
		$r      = p3d_deployer_run( $branch ?: 'main' );
		if ( is_wp_error( $r ) ) {
			$msg = '<div class="notice notice-error"><p>Eroare: ' . esc_html( $r->get_error_message() ) . '</p></div>';
		} else {
			// Golim cache-ul SiteGround, dacă există.
			if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
				sg_cachepress_purge_cache();
			}
			$msg = '<div class="notice notice-success"><p>Tema a fost actualizată la versiunea <strong>' . esc_html( $r ) . '</strong>.</p></div>';
		}
	}
	$installed = wp_get_theme( P3D_DEPLOY_THEME );
	$last      = get_option( 'p3d_last_deploy' );
	?>
	<div class="wrap">
		<h1>Actualizare temă Print3D Shop</h1>
		<?php echo $msg; // phpcs:ignore ?>
		<p>Versiune instalată: <strong><?php echo $installed->exists() ? esc_html( $installed->get( 'Version' ) ) : '—'; ?></strong>
		<?php if ( $last ) : ?> · ultima actualizare: <?php echo esc_html( wp_date( 'j M Y, H:i', $last['time'] ) ); ?> (ramura <?php echo esc_html( $last['branch'] ); ?>)<?php endif; ?></p>
		<p>Sursa: <code>github.com/<?php echo esc_html( P3D_DEPLOY_REPO ); ?></code></p>
		<form method="post">
			<?php wp_nonce_field( 'p3d_deploy' ); ?>
			<p><label>Ramura: <input type="text" name="branch" value="main" class="regular-text" style="width:160px"></label></p>
			<p><button type="submit" name="p3d_deploy" value="1" class="button button-primary">Actualizează tema din GitHub</button></p>
		</form>
	</div>
	<?php
}
