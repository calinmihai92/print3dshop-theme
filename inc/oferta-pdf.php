<?php
/**
 * Configurator 3D — oferta în PDF (doar pentru administrator).
 *
 * Pentru clienții de pe WhatsApp: completezi oferta (emailul nu e obligatoriu), apeși
 * „Salvează și descarcă PDF” și trimiți fișierul. Linkul de plată se generează separat, după acceptare.
 */

defined( 'ABSPATH' ) || exit;

const P3D_OF_VALID_DAYS = 15;

function p3d_of_pdf_url( $id ) {
	return add_query_arg( array( 'action' => 'p3d_of_pdf', 'id' => (int) $id, '_wpnonce' => wp_create_nonce( 'p3d_of_pdf_' . (int) $id ) ), admin_url( 'admin-post.php' ) );
}

// Link „Descarcă PDF” în lista de oferte.
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( P3D_OF_CPT === $post->post_type && 'trash' !== $post->post_status && get_post_meta( $post->ID, '_p3d_of_number', true ) && current_user_can( 'manage_options' ) ) {
		$actions = array( 'p3d_pdf' => '<a href="' . esc_url( p3d_of_pdf_url( $post->ID ) ) . '"><strong>Descarcă PDF</strong></a>' ) + $actions;
	}
	return $actions;
}, 10, 2 );

// Caseta „Ofertă PDF” în editor.
add_action( 'add_meta_boxes_' . P3D_OF_CPT, function () {
	add_meta_box( 'p3d_of_pdf', 'Ofertă PDF', 'p3d_of_box_pdf', P3D_OF_CPT, 'side', 'high' );
}, 5 );

function p3d_of_box_pdf( $post ) {
	echo '<input type="hidden" name="p3d_of_pdf" id="p3d-of-pdf-flag" value="">';
	echo '<p><button type="button" class="button button-primary button-large" id="p3d-of-pdf-btn" style="width:100%">Salvează și descarcă PDF</button></p>';
	echo '<p class="description">Oferta oficială, de trimis clientului (de ex. pe WhatsApp). Emailul nu e necesar. Linkul de plată îl generezi după ce clientul acceptă.</p>';
	if ( get_post_meta( $post->ID, '_p3d_of_number', true ) ) {
		echo '<p><a href="' . esc_url( p3d_of_pdf_url( $post->ID ) ) . '">Descarcă PDF-ul ofertei salvate</a></p>';
	}
}

// După salvare cu „Salvează și descarcă PDF”, pagina se reîncarcă și pornește descărcarea.
add_filter( 'redirect_post_location', function ( $location, $post_id ) {
	if ( P3D_OF_CPT === get_post_type( $post_id ) && ! empty( $_POST['p3d_of_pdf'] ) ) { // phpcs:ignore
		$location = add_query_arg( 'p3d_pdf', 1, $location );
	}
	return $location;
}, 10, 2 );

add_action( 'admin_footer', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base || P3D_OF_CPT !== $screen->post_type ) {
		return;
	}
	global $post;
	$url = ( $post && get_post_meta( $post->ID, '_p3d_of_number', true ) ) ? p3d_of_pdf_url( $post->ID ) : '';
	?>
	<script>
	(function () {
		var btn = document.getElementById('p3d-of-pdf-btn');
		if (btn) {
			btn.addEventListener('click', function () {
				document.getElementById('p3d-of-pdf-flag').value = '1';
				var pub = document.getElementById('publish');
				if (pub) { pub.click(); }
			});
		}
		var form = document.getElementById('post');
		if (form) {
			// Dacă formularul e oprit (câmp obligatoriu gol), nu păstrăm cererea de PDF pentru alt buton.
			form.addEventListener('invalid', function () { document.getElementById('p3d-of-pdf-flag').value = ''; }, true);
		}
		var url = <?php echo wp_json_encode( $url ); ?>;
		if (url) {
			var h = document.querySelector('.wrap .page-title-action');
			if (h) {
				var a = document.createElement('a');
				a.href = url;
				a.className = 'page-title-action';
				a.textContent = 'Descarcă PDF';
				h.parentNode.insertBefore(a, h.nextSibling);
			}
			if (/[?&]p3d_pdf=1/.test(location.search)) {
				history.replaceState(null, '', location.href.replace(/([?&])p3d_pdf=1&?/, '$1').replace(/[?&]$/, ''));
				window.location.href = url;
			}
		}
	})();
	</script>
	<?php
} );

add_action( 'admin_post_p3d_of_pdf', function () {
	$id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore
	if ( ! $id || P3D_OF_CPT !== get_post_type( $id ) || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nu ai acces la această ofertă.', 403 );
	}
	check_admin_referer( 'p3d_of_pdf_' . $id );

	$d    = p3d_of_get( $id );
	$who  = 'pj' === $d['client']['type'] && $d['client']['company'] ? $d['client']['company'] : $d['client']['name'];
	$file = 'Oferta-printare-3D-' . get_post_meta( $id, '_p3d_of_number', true ) . ( $who ? '-' . sanitize_title( remove_accents( $who ) ) : '' ) . '.pdf';
	p3d_of_log( $id, 'PDF ofertă descărcat' );
	p3d_pdf_send( p3d_of_pdf_html( $id ), $file );
} );

function p3d_of_pdf_html( $id ) {
	$d     = p3d_of_get( $id );
	$c     = $d['client'];
	$nr    = get_post_meta( $id, '_p3d_of_number', true );
	$tot   = p3d_of_totals( $d );
	$e     = 'esc_html';
	$logo  = get_template_directory() . '/assets/img/icon-180.png';
	$date  = get_post_time( 'U', true, $id );
	$pj    = 'pj' === $c['type'];
	$curier = 'curier' === $d['delivery']['method'];
	$addr  = implode( ', ', array_filter( array( $c['address'], $c['city'], $c['county'] ) ) );
	$n     = 0;

	ob_start();
	?>
<!doctype html>
<html lang="ro"><head><meta charset="utf-8"><title>Oferta de printare 3D <?php echo $e( $nr ); ?></title>
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
.items td { padding: 5px 6px; border-bottom: 1px solid #eee9e3; vertical-align: top; }
.items .ph { width: 62px; }
.items .ph img { width: 58px; border-radius: 4px; }
.r, .items th.r { text-align: right; }
.items tfoot td { border: 0; padding: 3px 6px; }
.items .total td { border-top: 1.5px solid #17181b; font-size: 11pt; font-weight: bold; padding-top: 6px; }
.small { font-size: 7.5pt; }
.cond td { vertical-align: top; padding: 0 10px 0 0; width: 33%; }
.next { border: 1.5px solid #c4540f; background: #fff7ed; page-break-inside: avoid; }
.sign { page-break-inside: avoid; }
.sign td { width: 50%; padding-top: 26px; }
.line { border-top: 1px solid #17181b; padding-top: 3px; margin-right: 30px; }
</style></head><body>

<table class="head"><tr>
	<td style="width:55%">
		<table><tr>
			<td style="width:40px"><img src="<?php echo esc_attr( $logo ); ?>" style="width:34px"></td>
			<td><span class="brand">print3d<span class="acc">shop</span></span><br><span class="muted small">Printare 3D · proiectare 3D · service imprimante 3D</span></td>
		</tr></table>
	</td>
	<td class="r small muted" style="width:45%">
		MIRIEXPERT DESIGN CONCEPT S.R.L.<br>
		CUI: RO46735434 · Reg. Com.: J40/16901/29.08.2022<br>
		Tel. <?php echo $e( p3d_opt( 'phone' ) ); ?> · <?php echo $e( p3d_opt( 'email' ) ); ?><br>
		print3dshop.ro
	</td>
</tr></table>

<div style="height:14px"></div>
<h1>Ofertă de printare 3D nr. <span class="acc"><?php echo $e( $nr ); ?></span></h1>
<span class="muted">Data: <?php echo $e( wp_date( 'j F Y', $date ) ); ?> · Valabilă până la: <?php echo $e( wp_date( 'j F Y', $date + P3D_OF_VALID_DAYS * DAY_IN_SECONDS ) ); ?></span>

<div style="height:10px"></div>
<div class="box">
	<div class="label" style="margin-top:0">Către</div>
	<?php if ( $pj && $c['company'] ) : ?>
		<strong><?php echo $e( $c['company'] ); ?></strong>
		<?php if ( $c['cui'] || $c['regcom'] ) : ?><br><span class="small"><?php echo $e( implode( ' · ', array_filter( array( $c['cui'] ? 'CUI: ' . $c['cui'] : '', $c['regcom'] ? 'Reg. Com.: ' . $c['regcom'] : '' ) ) ) ); ?></span><?php endif; ?>
		<?php if ( $c['name'] ) : ?><br>În atenția: <?php echo $e( $c['name'] ); ?><?php endif; ?>
	<?php else : ?>
		<strong><?php echo $e( $c['name'] ?: 'Client' ); ?></strong>
	<?php endif; ?>
	<?php $contact = array_filter( array( $c['phone'], $c['email'], $addr ) ); if ( $contact ) : ?><br><span class="small muted"><?php echo $e( implode( ' · ', $contact ) ); ?></span><?php endif; ?>
</div>

<div class="box">
	<h2>Piese de printat</h2>
	<table class="items">
		<thead><tr><th>#</th><th></th><th>Descriere</th><th class="r">Cant.</th><th class="r">Preț / buc.</th><th class="r">Valoare</th></tr></thead>
		<tbody>
		<?php foreach ( $d['parts'] as $p ) : $pp = p3d_of_part_price( $p ); $img = $p['photo'] ? p3d_fisa_pdf_img_path( $p['photo'] ) : ''; ?>
			<tr>
				<td><?php echo (int) ++$n; ?></td>
				<td class="ph"><?php if ( $img ) : ?><img src="<?php echo esc_attr( $img ); ?>"><?php endif; ?></td>
				<td><strong><?php echo $e( $p['desc'] ?: 'Piesă' ); ?></strong><br>
					<span class="small muted"><?php
					$spec = array( 'Material: ' . $p['material'] . ( $p['color'] ? ', ' . $p['color'] : '' ) );
					if ( $p['grams'] ) {
						$spec[] = rtrim( rtrim( number_format( (float) $p['grams'], 1, ',', '' ), '0' ), ',' ) . ' g / buc.';
					}
					if ( $p['hours'] ) {
						$spec[] = 'timp de printare ' . p3d_of_hours( $p['hours'] ) . ' / buc.';
					}
					echo $e( implode( ' · ', $spec ) );
					?></span></td>
				<td class="r"><?php echo (int) $pp['qty']; ?></td>
				<td class="r"><?php echo $e( p3d_money( $pp['unit'] ) ); ?></td>
				<td class="r"><?php echo $e( p3d_money( $pp['unit'] * $pp['qty'] ) ); ?></td>
			</tr>
			<?php if ( $pp['extra'] > 0 ) : ?>
			<tr>
				<td></td><td></td>
				<td><?php echo $e( ( $p['extra_label'] ?: 'Cost suplimentar' ) . ' — ' . ( $p['desc'] ?: 'piesă' ) ); ?></td>
				<td class="r">1</td>
				<td class="r"><?php echo $e( p3d_money( $pp['extra'] ) ); ?></td>
				<td class="r"><?php echo $e( p3d_money( $pp['extra'] ) ); ?></td>
			</tr>
			<?php endif; ?>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr><td colspan="5" class="r muted">Piese</td><td class="r"><?php echo $e( p3d_money( $tot['parts'] ) ); ?></td></tr>
			<?php if ( $tot['discount'] > 0 ) : ?><tr><td colspan="5" class="r muted">Reducere</td><td class="r">−<?php echo $e( p3d_money( $tot['discount'] ) ); ?></td></tr><?php endif; ?>
			<tr><td colspan="5" class="r muted"><?php echo $curier ? 'Livrare prin curier' : 'Ridicare personală'; ?></td><td class="r"><?php echo $e( $curier && $tot['shipping'] > 0 ? p3d_money( $tot['shipping'] ) : 'gratuit' ); ?></td></tr>
			<tr class="total"><td colspan="5" class="r">Total de plată</td><td class="r"><?php echo $e( p3d_money( $tot['total'] ) ); ?></td></tr>
		</tfoot>
	</table>
	<p class="muted small" style="margin:4px 0 0">Prețuri în lei, cu TVA inclus.</p>

	<table class="cond" style="margin-top:6px"><tr>
		<td><div class="label">Termen de execuție</div><?php echo $e( $d['term'] ?: 'se stabilește la confirmare' ); ?></td>
		<td><div class="label">Livrare</div><?php echo $curier ? 'prin curier' . ( $d['delivery']['address'] ? ', la ' . $e( $d['delivery']['address'] ) : ', oriunde în România' ) : 'ridicare personală din atelierul nostru din București'; ?></td>
		<td><div class="label">Valabilitate</div><?php echo (int) P3D_OF_VALID_DAYS; ?> zile de la data ofertei</td>
	</tr></table>
	<?php if ( $d['notes'] ) : ?><div class="label">Observații</div><div><?php echo nl2br( $e( $d['notes'] ) ); ?></div><?php endif; ?>
</div>

<div class="box next">
	<h2 class="acc">Cum mergem mai departe</h2>
	Dacă sunteți de acord cu oferta, ne confirmați pe WhatsApp, telefonic (<?php echo $e( p3d_opt( 'phone' ) ); ?>) sau pe email (<?php echo $e( p3d_opt( 'email' ) ); ?>), menționând numărul ofertei <strong><?php echo $e( $nr ); ?></strong>. Vă trimitem apoi linkul de plată, iar după plată începem printarea. Factura se emite pe datele de mai sus.
</div>

<table class="sign"><tr>
	<td><div class="line small muted">Print3D Shop — MIRIEXPERT DESIGN CONCEPT S.R.L.</div></td>
	<td><div class="line small muted">Client — acceptare ofertă</div></td>
</tr></table>

</body></html>
	<?php
	return ob_get_clean();
}
