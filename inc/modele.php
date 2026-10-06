<?php
/**
 * Produse variabile cu „modele” (ex. Breloc Halloween): fiecare variație are poza ei.
 *  - sub preț apare o grilă cu pozele modelelor și codul lor; click = alegi modelul;
 *  - când clientul trece prin galerie la poza unui model, modelul se selectează automat
 *    (în coș intră exact modelul de pe poza la care se uită).
 * Se activează automat pentru produsele variabile cu un singur atribut în care fiecare variație are imagine.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

function p3d_is_model_product( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) || count( $product->get_variation_attributes() ) !== 1 ) {
		return false;
	}
	foreach ( $product->get_children() as $vid ) {
		if ( ! get_post_thumbnail_id( $vid ) ) {
			return false;
		}
	}
	return (bool) $product->get_children();
}

add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	return p3d_is_model_product( $product ) ? 'Alege modelul' : $text;
}, 20, 2 );

add_action( 'woocommerce_before_variations_form', function () {
	global $product;
	if ( ! p3d_is_model_product( $product ) ) {
		return;
	}
	echo '<div class="p3d-models" data-p3d-models><span class="p3d-field-label">Alege modelul: <strong class="p3d-model-current">—</strong></span><div class="p3d-model-grid"></div></div>';
} );

add_action( 'woocommerce_after_add_to_cart_form', function () {
	global $product;
	if ( ! p3d_is_model_product( $product ) ) {
		return;
	}
	?>
	<script>
	jQuery(function ($) {
		var $form = $('form.variations_form'), box = document.querySelector('[data-p3d-models]');
		if (!$form.length || !box) return;
		var vars = $form.data('product_variations') || [], $sel = $form.find('.variations select').first();
		var attr = $sel.attr('name'), grid = box.querySelector('.p3d-model-grid'), cur = box.querySelector('.p3d-model-current');
		$form.addClass('p3d-has-models');
		var byUrl = {};
		vars.forEach(function (v) {
			var val = v.attributes[attr]; if (!val || !v.image) return;
			byUrl[v.image.full_src] = val;
			var b = document.createElement('button');
			b.type = 'button'; b.className = 'p3d-model'; b.dataset.val = val; b.dataset.full = v.image.full_src;
			b.innerHTML = '<img src="' + (v.image.gallery_thumbnail_src || v.image.thumb_src) + '" alt="Model ' + val + '" loading="lazy"><span>#' + val + '</span>';
			grid.appendChild(b);
		});
		function slides() { return $('.woocommerce-product-gallery__image'); }
		function goTo(full) {
			var idx = -1;
			slides().each(function (i) { var a = this.querySelector('a'); if (a && a.getAttribute('href') === full) idx = i; });
			var $g = $('.woocommerce-product-gallery'), fs = $g.data('flexslider');
			if (idx > -1 && fs && fs.currentSlide !== idx) $g.flexslider(idx);
		}
		function mark(val) {
			cur.textContent = val ? '#' + val : '—';
			grid.querySelectorAll('.p3d-model').forEach(function (b) { b.classList.toggle('is-on', b.dataset.val === val); });
		}
		function choose(val) {
			if ($sel.val() === val) return;
			$sel.val(val).trigger('change');
		}
		// WooCommerce nu mai înlocuiește prima poză: mergem la poza modelului ales.
		$.fn.wc_variations_image_update = function (v) { if (v && v.image && v.image.full_src) goTo(v.image.full_src); return this; };
		grid.addEventListener('click', function (e) {
			var b = e.target.closest('.p3d-model'); if (!b) return;
			choose(b.dataset.val); goTo(b.dataset.full);
		});
		$sel.on('change', function () { mark(this.value); });
		// Poza din galerie -> modelul din coș.
		var gal = document.querySelector('.woocommerce-product-gallery');
		function sync() {
			var a = document.querySelector('.woocommerce-product-gallery__image.flex-active-slide a');
			if (!a) return;
			var val = byUrl[a.getAttribute('href')];
			if (val) choose(val);
		}
		if (gal) new MutationObserver(sync).observe(gal, { subtree: true, attributes: true, attributeFilter: ['class'] });
		mark($sel.val());
	});
	</script>
	<?php
} );
