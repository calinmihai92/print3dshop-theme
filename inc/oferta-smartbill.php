<?php
/**
 * Configurator 3D — „Emite factura SmartBill” direct din ofertă.
 *
 * Factura se emite prin modulul SmartBill pentru WooCommerce (deja conectat), pe comanda
 * WooCommerce a ofertei. Dacă oferta nu are încă o comandă (link de plată), se creează acum.
 * Pentru emitere folosim exact acțiunea butonului SmartBill din pagina comenzii.
 */

defined( 'ABSPATH' ) || exit;

function p3d_of_sb_active() {
	return function_exists( 'wc_create_order' ) && ( has_action( 'wp_ajax_smartbill_woocommerce_issue_document' ) || false !== get_option( 'smartbill_plugin_options_settings', false ) );
}

/** Factura SmartBill emisă pe comanda ofertei: array( 'nr' => 'MED 118', 'url' => … ) sau null. */
function p3d_of_sb_invoice( $order ) {
	if ( ! $order ) {
		return null;
	}
	$log = $order->get_meta( 'smartbill_invoice_log' );
	if ( is_string( $log ) ) {
		$log = maybe_unserialize( $log );
	}
	if ( ! is_array( $log ) || empty( $log['smartbill_status'] ) || empty( $log['smartbill_invoice_id'] ) ) {
		return null;
	}
	return array(
		'nr'  => trim( ( $log['smartbill_series'] ?? '' ) . ' ' . $log['smartbill_invoice_id'] ),
		'url' => $log['smartbill_view_document_url'] ?? ( $log['smartbill_document_url'] ?? '' ),
	);
}

add_action( 'add_meta_boxes_' . P3D_OF_CPT, function () {
	add_meta_box( 'p3d_of_sb', 'Factură SmartBill', 'p3d_of_box_sb', P3D_OF_CPT, 'side', 'high' );
}, 6 );

function p3d_of_box_sb( $post ) {
	if ( ! p3d_of_sb_active() ) {
		echo '<p>Modulul SmartBill pentru WooCommerce nu este activ.</p>';
		return;
	}
	if ( ! get_post_meta( $post->ID, '_p3d_of_number', true ) ) {
		echo '<p class="description">Salvează întâi oferta. Factura se emite după ce clientul a acceptat oferta.</p>';
		return;
	}
	$order = p3d_of_order( $post->ID );
	$inv   = p3d_of_sb_invoice( $order );
	if ( $inv ) {
		echo '<p><strong style="color:#15803d">Factura ' . esc_html( $inv['nr'] ) . ' a fost emisă.</strong></p>';
		if ( $inv['url'] ) {
			echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( $inv['url'] ) . '">Vezi factura în SmartBill</a></p>';
		}
		echo '<p class="description">Pentru retrimitere sau stornare folosește SmartBill ori <a href="' . esc_url( $order->get_edit_order_url() ) . '">comanda #' . esc_html( $order->get_order_number() ) . '</a>.</p>';
		return;
	}
	$d   = p3d_of_get( $post->ID );
	$tot = p3d_of_totals( $d );
	$who = 'pj' === $d['client']['type'] && $d['client']['company'] ? $d['client']['company'] . ( $d['client']['cui'] ? ' (' . $d['client']['cui'] . ')' : '' ) : $d['client']['name'];
	$paid = $order && $order->is_paid();
	printf(
		'<p><button type="button" class="button button-large" id="p3d-of-sb-btn" style="width:100%%" data-id="%d" data-n="%s" data-who="%s" data-total="%s">Emite factura SmartBill</button></p>',
		(int) $post->ID,
		esc_attr( wp_create_nonce( 'p3d_of_sb_' . $post->ID ) ),
		esc_attr( $who ),
		esc_attr( p3d_money( $tot['total'] ) )
	);
	echo '<p class="description">Se emite factura (seria implicită din SmartBill) pe datele clientului și piesele salvate în ofertă. Salvează mai întâi modificările. ';
	echo $paid ? 'Comanda e plătită, deci factura apare ca încasată.' : 'Comanda nu e marcată ca plătită, deci factura apare neîncasată.';
	echo '</p><p id="p3d-of-sb-msg"></p>';
}

// Pasul 1: comanda WooCommerce a ofertei (o creează dacă lipsește) + datele de firmă pentru SmartBill.
add_action( 'wp_ajax_p3d_of_sb_prepare', function () {
	$id = (int) ( $_POST['id'] ?? 0 ); // phpcs:ignore
	if ( ! $id || P3D_OF_CPT !== get_post_type( $id ) || ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'p3d_of_sb_' . $id, 'n', false ) ) {
		wp_send_json_error( 'Nu ai acces la această ofertă.' );
	}
	if ( ! p3d_of_sb_active() ) {
		wp_send_json_error( 'Modulul SmartBill nu este activ.' );
	}
	$d = p3d_of_get( $id );
	if ( ! $d['parts'] ) {
		wp_send_json_error( 'Oferta nu are piese.' );
	}
	$order = p3d_of_order( $id );
	if ( p3d_of_sb_invoice( $order ) ) {
		wp_send_json_error( 'Factura a fost deja emisă.' );
	}
	if ( ! $order ) {
		$order = p3d_of_sync_order( $id, null );
		if ( ! $order ) {
			wp_send_json_error( 'Comanda WooCommerce nu a putut fi creată.' );
		}
		p3d_of_log( $id, 'Comandă creată pentru factură (#' . $order->get_order_number() . ')' );
	} elseif ( ! $order->is_paid() && in_array( $order->get_status(), array( 'pending', 'failed', 'on-hold' ), true ) ) {
		$order = p3d_of_sync_order( $id, $order );
	}
	// Câmpurile de firmă ale modulului SmartBill.
	$c  = $d['client'];
	$pj = 'pj' === $c['type'];
	foreach ( array( '', '_' ) as $px ) {
		$order->update_meta_data( $px . 'smartbill_billing_type', $pj ? 'pj' : 'pf' );
		$order->update_meta_data( $px . 'smartbill_billing_cif', $pj ? $c['cui'] : '' );
		$order->update_meta_data( $px . 'smartbill_billing_nr_reg_com', $pj ? $c['regcom'] : '' );
	}
	$order->save();
	wp_send_json_success( array( 'order' => $order->get_id(), 'edit' => $order->get_edit_order_url() ) );
} );

// Pasul 3: notează rezultatul în istoricul ofertei.
add_action( 'wp_ajax_p3d_of_sb_done', function () {
	$id = (int) ( $_POST['id'] ?? 0 ); // phpcs:ignore
	if ( ! $id || P3D_OF_CPT !== get_post_type( $id ) || ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'p3d_of_sb_' . $id, 'n', false ) ) {
		wp_send_json_error();
	}
	$order = p3d_of_order( $id );
	if ( $order ) {
		$order = wc_get_order( $order->get_id() ); // citire proaspătă
	}
	$inv = p3d_of_sb_invoice( $order );
	if ( $inv ) {
		p3d_of_log( $id, 'Factura SmartBill ' . $inv['nr'] . ' emisă' );
		wp_send_json_success( $inv );
	}
	$err = sanitize_text_field( wp_unslash( $_POST['err'] ?? '' ) ); // phpcs:ignore
	p3d_of_log( $id, 'Factura SmartBill nu a fost emisă' . ( $err ? ': ' . $err : '' ) );
	wp_send_json_error( $err ?: 'SmartBill nu a confirmat emiterea facturii.' );
} );

add_action( 'admin_footer', function () {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base || P3D_OF_CPT !== $screen->post_type ) {
		return;
	}
	?>
	<script>
	(function ($) {
		var $b = $('#p3d-of-sb-btn');
		if (!$b.length) { return; }
		var $m = $('#p3d-of-sb-msg');
		function say(t, bad) { $m.text(t).css('color', bad ? '#b91c1c' : '#374151'); }
		$b.on('click', async function () {
			var id = $b.data('id'), n = $b.data('n');
			if (!window.confirm('Emiți factura în SmartBill?\n\nClient: ' + $b.data('who') + '\nTotal: ' + $b.data('total') + '\n\nFactura este un document fiscal și apare imediat în SmartBill.')) { return; }
			$b.prop('disabled', true);
			var err = '';
			try {
				say('Pregătesc comanda…');
				var p = await $.post(ajaxurl, { action: 'p3d_of_sb_prepare', id: id, n: n });
				if (!p || !p.success) { throw new Error(p && p.data ? p.data : 'Eroare la pregătirea comenzii.'); }
				say('Emit factura în SmartBill…');
				var html = await (await fetch(p.data.edit, { credentials: 'same-origin' })).text();
				var m = html.match(/var smartbill\s*=\s*(\{[\s\S]*?\});/);
				var sec = m ? JSON.parse(m[1]).security : '';
				if (!sec) { throw new Error('Nu am putut porni modulul SmartBill. Emite factura din pagina comenzii.'); }
				var r = await $.post(ajaxurl, { action: 'smartbill_woocommerce_issue_document', security: sec, data: { type: 10, order_id: p.data.order } });
				try {
					var j = typeof r === 'string' ? JSON.parse(r) : r;
					if (j && !j.status) { err = $('<div>').html(j.message || j.error || '').text().trim(); }
				} catch (e) {}
			} catch (e) {
				err = e.message || String(e);
			}
			var d = await $.post(ajaxurl, { action: 'p3d_of_sb_done', id: id, n: n, err: err });
			if (d && d.success) {
				say('Factura ' + d.data.nr + ' a fost emisă.');
				window.location.reload();
			} else {
				say(err || (d && d.data) || 'Factura nu a fost emisă.', true);
				$b.prop('disabled', false);
			}
		});
	})(jQuery);
	</script>
	<?php
} );
