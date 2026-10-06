<?php
/**
 * Fișa de service — descărcare PDF (doar pentru administrator, direct din panou).
 */

defined( 'ABSPATH' ) || exit;

function p3d_fisa_pdf_url( $id ) {
	return add_query_arg( array( 'action' => 'p3d_fisa_pdf', 'id' => (int) $id, '_wpnonce' => wp_create_nonce( 'p3d_fisa_pdf_' . (int) $id ) ), admin_url( 'admin-post.php' ) );
}

// Link „Descarcă PDF” în lista de fișe.
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( P3D_FISA_CPT === $post->post_type && 'trash' !== $post->post_status && current_user_can( 'edit_post', $post->ID ) ) {
		$actions = array( 'p3d_pdf' => '<a href="' . esc_url( p3d_fisa_pdf_url( $post->ID ) ) . '"><strong>Descarcă PDF</strong></a>' ) + $actions;
	}
	return $actions;
}, 10, 2 );

// Buton „Descarcă PDF” în pagina fișei (sus, lângă „Fișă de service nouă”).
add_action( 'admin_footer-post.php', function () {
	global $post;
	if ( ! $post || P3D_FISA_CPT !== $post->post_type || ! get_post_meta( $post->ID, '_p3d_number', true ) ) {
		return;
	}
	$url = p3d_fisa_pdf_url( $post->ID );
	?>
	<script>
	(function () {
		var h = document.querySelector('.wrap .page-title-action');
		if (!h) { return; }
		var a = document.createElement('a');
		a.href = <?php echo wp_json_encode( $url ); ?>;
		a.className = 'page-title-action p3d-pdf-btn';
		a.textContent = 'Descarcă PDF';
		h.parentNode.insertBefore(a, h.nextSibling);
	})();
	</script>
	<?php
} );

add_action( 'admin_post_p3d_fisa_pdf', function () {
	$id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
	if ( ! $id || P3D_FISA_CPT !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Nu ai acces la această fișă.', 403 );
	}
	check_admin_referer( 'p3d_fisa_pdf_' . $id );

	require_once __DIR__ . '/vendor/autoload.php';

	$tmp = trailingslashit( get_temp_dir() ) . 'p3d-dompdf';
	wp_mkdir_p( $tmp );
	$opts = new \Dompdf\Options();
	$opts->setDefaultFont( 'DejaVu Sans' );
	$opts->setIsRemoteEnabled( false );
	$opts->setIsPhpEnabled( false );
	$opts->setIsJavascriptEnabled( false );
	$opts->setFontDir( __DIR__ . '/vendor/dompdf/lib/fonts' );
	$opts->setFontCache( $tmp );
	$opts->setTempDir( $tmp );
	$opts->setChroot( array( wp_upload_dir()['basedir'], get_template_directory() ) );
	$opts->setDpi( 96 );

	$pdf = new \Dompdf\Dompdf( $opts );
	$pdf->loadHtml( p3d_fisa_pdf_html( $id ), 'UTF-8' );
	$pdf->setPaper( 'A4' );
	$pdf->render();

	$canvas = $pdf->getCanvas();
	$font   = $pdf->getFontMetrics()->getFont( 'DejaVu Sans' );
	$canvas->page_text( 510, 812, 'Pagina {PAGE_NUM} / {PAGE_COUNT}', $font, 7, array( 0.45, 0.45, 0.45 ) );

	$d    = p3d_fisa_get( $id );
	$file = 'Fisa-service-' . get_post_meta( $id, '_p3d_number', true ) . '-' . sanitize_title( remove_accents( $d['client']['name'] ) ) . '.pdf';

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . $file . '"' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	echo $pdf->output(); // phpcs:ignore
	exit;
} );

/** Calea locală a unei poze (mărime medie), ca Dompdf s-o citească direct de pe disc. */
function p3d_fisa_pdf_img_path( $att_id ) {
	$src = wp_get_attachment_image_src( $att_id, 'medium' );
	if ( ! $src ) {
		return '';
	}
	$up   = wp_upload_dir();
	$path = str_replace( $up['baseurl'], $up['basedir'], strtok( $src[0], '?' ) );
	return is_file( $path ) ? $path : '';
}

function p3d_fisa_pdf_qty( $q ) {
	return rtrim( rtrim( number_format( (float) $q, 2, ',', '' ), '0' ), ',' );
}

function p3d_fisa_pdf_html( $id ) {
	$d     = p3d_fisa_get( $id );
	$nr    = get_post_meta( $id, '_p3d_number', true );
	$tot   = p3d_fisa_totals( $d );
	$conf  = get_post_meta( $id, '_p3d_confirm', true );
	$st    = p3d_fisa_status( $id );
	$sts   = p3d_fisa_statuses();
	$demo  = ! empty( $d['demo'] );
	$multi = count( $d['devices'] ) > 1;
	$e     = 'esc_html';
	$logo  = get_template_directory() . '/assets/img/icon-180.png';
	$conf_ok = $conf && in_array( $st, array( 'confirmata', 'lucru', 'finalizata', 'predata' ), true );

	ob_start();
	?>
<!doctype html>
<html lang="ro"><head><meta charset="utf-8"><title>Fișa de service <?php echo $e( $nr ); ?></title>
<style>
@page { margin: 34px 38px 44px; }
body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #17181b; line-height: 1.35; }
h1 { font-size: 17pt; margin: 0 0 2px; font-weight: bold; }
h2 { font-size: 11pt; margin: 0 0 6px; }
.acc { color: #c4540f; }
.muted { color: #6b6f76; }
.label { font-family: "DejaVu Sans Mono", monospace; font-size: 6.5pt; letter-spacing: 1px; text-transform: uppercase; color: #6b6f76; margin: 5px 0 1px; }
table { border-collapse: collapse; width: 100%; }
.head td { vertical-align: top; }
.brand { font-size: 15pt; font-weight: bold; }
.box { border: 1px solid #e3ded7; border-radius: 6px; padding: 8px 11px; margin-bottom: 8px; }
.items th { font-family: "DejaVu Sans Mono", monospace; font-size: 7pt; text-transform: uppercase; letter-spacing: .5px; color: #6b6f76; text-align: left; border-bottom: 1px solid #cfc9c1; padding: 5px 6px; }
.items td { padding: 4px 6px; border-bottom: 1px solid #eee9e3; vertical-align: top; }
.r, .items th.r { text-align: right; }
.items tfoot td { border: 0; padding: 4px 6px; }
.items .total td { border-top: 1.5px solid #17181b; font-size: 10.5pt; font-weight: bold; padding-top: 5px; }
.photos td { padding: 3px; width: 25%; vertical-align: top; }
.photos img { width: 100%; border-radius: 4px; }
.demo { border: 2px dashed #b91c1c; background: #fef2f2; color: #7f1d1d; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
.demo strong { color: #b91c1c; font-size: 11pt; }
.tag { background: #b91c1c; color: #fff; font-size: 7pt; padding: 1px 4px; border-radius: 3px; }
.conf { border: 1.5px solid #15803d; background: #f0fdf4; page-break-inside: avoid; }
.conf h2 { color: #15803d; }
.small { font-size: 7.5pt; }
.sign { page-break-inside: avoid; }
.sign td { width: 50%; padding-top: 22px; }
.cond td { vertical-align: top; padding: 0 10px 0 0; }
.line { border-top: 1px solid #17181b; padding-top: 3px; margin-right: 30px; }
</style></head><body>

<table class="head"><tr>
	<td style="width:55%">
		<table><tr>
			<td style="width:40px"><img src="<?php echo esc_attr( $logo ); ?>" style="width:34px"></td>
			<td><span class="brand">print3d<span class="acc">shop</span></span><br><span class="muted small">Service imprimante 3D · printare 3D · proiectare 3D</span></td>
		</tr></table>
	</td>
	<td class="r small muted" style="width:45%">
		<?php echo $e( p3d_opt( 'address' ) ); ?><br>
		Tel. <?php echo $e( p3d_opt( 'phone' ) ); ?> · <?php echo $e( p3d_opt( 'email' ) ); ?><br>
		print3dshop.ro
	</td>
</tr></table>

<div style="height:10px"></div>
<?php if ( $demo ) : ?>
	<div class="demo"><strong>EXEMPLU — PREȚURI FICTIVE</strong><br>Fișă demonstrativă. Toate prețurile sunt fictive, doar pentru exemplificare, și nu reprezintă o ofertă.</div>
<?php endif; ?>

<table class="head"><tr>
	<td><h1>Fișa de service nr. <span class="acc"><?php echo $e( $nr ); ?></span></h1>
		<span class="muted">Emisă: <?php echo $e( get_the_date( 'j F Y', $id ) ); ?> · Stare: <?php echo $e( $sts[ $st ][0] ); ?></span></td>
</tr></table>

<div style="height:6px"></div>
<div class="box">
	<div class="label" style="margin-top:0">Client</div>
	<strong><?php echo $e( $d['client']['name'] ); ?></strong>
	<?php if ( $d['client']['phone'] ) : ?> · <?php echo $e( $d['client']['phone'] ); ?><?php endif; ?>
	<?php if ( $d['client']['email'] ) : ?> · <?php echo $e( $d['client']['email'] ); ?><?php endif; ?>
</div>

<?php foreach ( $d['devices'] as $i => $dv ) : ?>
	<div class="box">
		<h2><?php echo $e( ( $multi ? ( $i + 1 ) . '. ' : '' ) . $dv['model'] ); ?><?php if ( $dv['serial'] ) : ?> <span class="muted small" style="font-weight:normal">· serie <?php echo $e( $dv['serial'] ); ?></span><?php endif; ?></h2>
		<?php if ( $dv['issue'] ) : ?><div class="label">Problema reclamată</div><div><?php echo nl2br( $e( $dv['issue'] ) ); ?></div><?php endif; ?>
		<?php if ( $dv['diagnostic'] ) : ?><div class="label">Diagnostic</div><div><?php echo nl2br( $e( $dv['diagnostic'] ) ); ?></div><?php endif; ?>
		<?php
		$imgs = array_filter( array_map( 'p3d_fisa_pdf_img_path', (array) $dv['photos'] ) );
		if ( $imgs ) :
			echo '<div class="label">Poze</div><table class="photos">';
			foreach ( array_chunk( array_values( $imgs ), 4 ) as $row ) {
				echo '<tr>';
				for ( $k = 0; $k < 4; $k++ ) {
					echo '<td>' . ( isset( $row[ $k ] ) ? '<img src="' . esc_attr( $row[ $k ] ) . '">' : '' ) . '</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		endif;
		?>
	</div>
<?php endforeach; ?>

<div class="box">
	<h2>Piese și manoperă<?php echo $demo ? ' <span class="tag">PREȚURI FICTIVE</span>' : ''; ?></h2>
	<table class="items">
		<thead><tr><th>Descriere</th><th>Tip</th><th class="r">Cant.</th><th class="r">Preț unitar</th><th class="r">Valoare</th></tr></thead>
		<tbody>
		<?php foreach ( $d['items'] as $it ) : $dev = '' !== $it['device'] && isset( $d['devices'][ $it['device'] ] ) ? $d['devices'][ $it['device'] ]['model'] : ''; ?>
			<tr>
				<td><?php echo $e( $it['desc'] ); ?><?php if ( $dev && $multi ) : ?><br><span class="muted small"><?php echo $e( $dev ); ?></span><?php endif; ?></td>
				<td><?php echo 'manopera' === $it['type'] ? 'Manoperă' : 'Piesă'; ?></td>
				<td class="r"><?php echo $e( p3d_fisa_pdf_qty( $it['qty'] ) ); ?></td>
				<td class="r"><?php echo $e( p3d_money( $it['price'] ) ); ?></td>
				<td class="r"><?php echo $e( p3d_money( $it['qty'] * $it['price'] ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<?php if ( $tot['piesa'] && $tot['manopera'] ) : ?>
				<tr><td colspan="4" class="r muted">Piese</td><td class="r"><?php echo $e( p3d_money( $tot['piesa'] ) ); ?></td></tr>
				<tr><td colspan="4" class="r muted">Manoperă</td><td class="r"><?php echo $e( p3d_money( $tot['manopera'] ) ); ?></td></tr>
			<?php endif; ?>
			<tr class="total"><td colspan="4" class="r">Total<?php echo $demo ? ' <span class="tag">FICTIV</span>' : ''; ?></td><td class="r"><?php echo $e( p3d_money( $tot['total'] ) ); ?></td></tr>
		</tfoot>
	</table>
	<?php if ( $d['price_note'] ) : ?><p class="muted small" style="margin:4px 0 0"><?php echo $e( $d['price_note'] ); ?></p><?php endif; ?>
	<?php if ( $d['term'] || $d['warranty'] ) : ?>
	<table class="cond" style="margin-top:4px"><tr>
		<?php if ( $d['term'] ) : ?><td style="width:50%"><div class="label">Termen estimat</div><?php echo $e( $d['term'] ); ?></td><?php endif; ?>
		<?php if ( $d['warranty'] ) : ?><td><div class="label">Garanție</div><?php echo $e( $d['warranty'] ); ?></td><?php endif; ?>
	</tr></table>
	<?php endif; ?>
	<?php if ( $d['notes'] ) : ?><div class="label">Observații</div><div><?php echo nl2br( $e( $d['notes'] ) ); ?></div><?php endif; ?>
</div>

<?php if ( $conf_ok ) : ?>
<div class="box conf">
	<h2>Lucrare confirmată de client</h2>
	<div>Nume tastat: <strong><?php echo $e( $conf['name'] ); ?></strong> · Email: <?php echo $e( $conf['email'] ); ?></div>
	<div>Data: <?php echo $e( wp_date( 'j F Y, H:i:s', $conf['t'] ) ); ?> · Total confirmat: <strong><?php echo $e( p3d_money( $conf['total'] ) ); ?></strong></div>
	<div class="small muted">IP <?php echo $e( $conf['ip'] ); ?> · amprentă conținut <?php echo $e( substr( (string) $conf['hash'], 0, 16 ) ); ?>…</div>
	<?php if ( p3d_fisa_hash( $d ) !== $conf['hash'] ) : ?><div class="small" style="color:#b91c1c">Atenție: fișa a fost modificată după confirmare.</div><?php endif; ?>
</div>
<?php endif; ?>

<table class="sign"><tr>
	<td><div class="line small muted">Print3D Shop — semnătură</div></td>
	<td><div class="line small muted">Client — semnătură</div></td>
</tr></table>

</body></html>
	<?php
	return ob_get_clean();
}
