<?php
/**
 * Întrebări frecvente. Primește $args['items'] = [[întrebare, răspuns], …].
 */
defined( 'ABSPATH' ) || exit;
$items = $args['items'] ?? array();
if ( ! $items ) {
	return;
}
?>
<section class="section" id="intrebari">
	<div class="wrap">
		<div class="head"><h2 class="h2">Întrebări <em>frecvente</em></h2></div>
		<div class="faq">
			<?php foreach ( $items as $i => $q ) : ?>
				<details class="card qa"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $q[0] ); ?><span class="plus" aria-hidden="true">+</span></summary>
					<p><?php echo esc_html( $q[1] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
