<?php
/**
 * Panoul contului (suprascrie șablonul WooCommerce).
 *
 * @version 4.4.0
 */
defined( 'ABSPATH' ) || exit;

$p3d_user   = wp_get_current_user();
$p3d_orders = wc_get_orders( array( 'customer_id' => $p3d_user->ID, 'limit' => 1, 'return' => 'objects' ) );
$p3d_last   = $p3d_orders ? $p3d_orders[0] : null;
?>
<p class="p3d-acc-hello">Bună, <strong><?php echo esc_html( $p3d_user->first_name ? $p3d_user->first_name : $p3d_user->display_name ); ?></strong>! Aici vezi comenzile tale și îți poți schimba datele.</p>

<?php if ( $p3d_last ) : ?>
	<div class="p3d-acc-last">
		<div>
			<span class="p3d-acc-k">Ultima comandă</span>
			<strong>#<?php echo esc_html( $p3d_last->get_order_number() ); ?></strong> · <?php echo esc_html( wc_format_datetime( $p3d_last->get_date_created() ) ); ?> · <?php echo wp_kses_post( $p3d_last->get_formatted_order_total() ); ?>
		</div>
		<span class="p3d-acc-status"><?php echo esc_html( wc_get_order_status_name( $p3d_last->get_status() ) ); ?></span>
		<a class="button" href="<?php echo esc_url( $p3d_last->get_view_order_url() ); ?>">Vezi comanda</a>
	</div>
<?php endif; ?>

<div class="p3d-acc-cards">
	<a class="p3d-acc-card" href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>"><span class="icon-tile"><?php echo p3d_icon( 'box', 20 ); // phpcs:ignore ?></span><strong>Comenzile mele</strong><small>Starea comenzilor și AWB-ul de livrare</small></a>
	<a class="p3d-acc-card" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>"><span class="icon-tile"><?php echo p3d_icon( 'pin', 20 ); // phpcs:ignore ?></span><strong>Adrese</strong><small>Adresa de livrare și de facturare</small></a>
	<a class="p3d-acc-card" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-account' ) ); ?>"><span class="icon-tile"><?php echo p3d_icon( 'lock', 20 ); // phpcs:ignore ?></span><strong>Detalii cont</strong><small>Nume, email și parolă</small></a>
	<a class="p3d-acc-card" href="<?php echo esc_url( home_url( '/contacts/' ) ); ?>"><span class="icon-tile"><?php echo p3d_icon( 'phone', 20 ); // phpcs:ignore ?></span><strong>Contact și asistență</strong><small><?php echo esc_html( p3d_opt( 'phone' ) ); ?> · <?php echo esc_html( p3d_opt( 'email' ) ); ?></small></a>
</div>
<?php
do_action( 'woocommerce_account_dashboard' );
