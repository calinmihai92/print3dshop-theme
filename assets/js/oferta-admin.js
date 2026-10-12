/* Configurator printare 3D (wp-admin). */
(function ($) {
  'use strict';

  var $parts = $('#p3d-parts');
  if (!$parts.length) return;
  var cfg = window.p3dOf || { materials: {}, courier: { cost: 25, free_over: 200 } };
  var seq = 1000;

  function num(v) { return parseFloat(String(v === undefined ? '' : v).replace(',', '.')) || 0; }
  function money(v) { return v.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' lei'; }

  function calc() {
    var sum = 0;
    $parts.find('.p3d-part').each(function (n) {
      var $p = $(this);
      var desc = $.trim($p.find('input[name$="[desc]"]').val());
      $p.find('.p3d-part-num').text('Piesa ' + (n + 1) + (desc ? ' — ' + desc : ''));
      var unit = Math.round((num($p.find('.p3d-grams').val()) * num($p.find('.p3d-ppg').val()) + num($p.find('.p3d-hours').val()) * num($p.find('.p3d-pph').val())) * 100) / 100;
      var qty = Math.max(1, parseInt($p.find('.p3d-qty').val(), 10) || 1);
      var total = Math.round((unit * qty + num($p.find('.p3d-extra').val())) * 100) / 100;
      $p.find('.p3d-unit').text(money(unit));
      $p.find('.p3d-ptotal strong').text(money(total));
      sum += total;
    });
    var disc = Math.min(num($('#p3d-discount').val()), sum);
    var curier = $('#p3d-del-method').val() === 'curier';
    var ship = curier ? num($('#p3d-del-cost').val()) : 0;
    $('#p3d-s-parts').text(money(sum));
    $('#p3d-s-disc').text(disc > 0 ? '−' + money(disc) : '—');
    $('#p3d-s-ship').text(curier ? (ship > 0 ? money(ship) : 'gratuit') : 'ridicare');
    $('#p3d-s-total').text(money(sum - disc + ship));
    return sum - disc;
  }

  /* Tipul clientului: câmpurile de firmă apar doar pentru firme. */
  function clientType() {
    $('.p3d-pj').toggle($('input[name="p3d_of[client][type]"]:checked').val() === 'pj');
  }

  /* Livrare: la curier propunem costul standard (gratuit peste prag), dacă nu l-ai scris tu. */
  var costTouched = num($('#p3d-del-cost').val()) > 0;
  function delivery(suggest) {
    var curier = $('#p3d-del-method').val() === 'curier';
    $('.p3d-curier').toggle(curier);
    if (curier && suggest && !costTouched) {
      var sub = calc();
      $('#p3d-del-cost').val(sub >= cfg.courier.free_over ? 0 : cfg.courier.cost);
    }
    calc();
  }

  $('#p3d-add-part').on('click', function () {
    $parts.append($('#p3d-part-tpl').html().replace(/__i__/g, seq++));
    calc();
  });

  $parts.on('click', '.p3d-del-part', function () {
    if ($parts.find('.p3d-part').length < 2) return;
    if (!window.confirm('Ștergi această piesă din ofertă?')) return;
    $(this).closest('.p3d-part').remove();
    calc();
  });

  /* Materialul completează prețul pe gram. */
  $parts.on('change', '.p3d-mat', function () {
    var ppg = cfg.materials[$(this).val()];
    if (ppg !== undefined && ppg > 0) $(this).closest('.p3d-part').find('.p3d-ppg').val(ppg);
    calc();
  });

  $(document).on('input change', '.p3d-of input, .p3d-of select', function () {
    if (this.id === 'p3d-del-cost') costTouched = true;
    calc();
  });
  $('input[name="p3d_of[client][type]"]').on('change', clientType);
  $('#p3d-del-method').on('change', function () { delivery(true); });

  /* Poza piesei. */
  $parts.on('click', '.p3d-pick-photo', function (e) {
    e.preventDefault();
    var $box = $(this).closest('.p3d-part-photo');
    var frame = wp.media({ title: 'Poza piesei', button: { text: 'Folosește poza' }, library: { type: 'image' }, multiple: false });
    frame.on('select', function () {
      var a = frame.state().get('selection').first().toJSON();
      var src = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
      $box.find('.p3d-photo-list').empty().append(
        $('<span class="p3d-ph">').attr('data-id', a.id).append($('<img>').attr('src', src).attr('alt', '')).append('<button type="button" aria-label="Scoate poza">×</button>')
      );
      $box.find('.p3d-photo-id').val(a.id);
    });
    frame.open();
  });
  $parts.on('click', '.p3d-ph button', function () {
    var $box = $(this).closest('.p3d-part-photo');
    $box.find('.p3d-photo-list').empty();
    $box.find('.p3d-photo-id').val('');
  });

  $(document).on('click', '.p3d-copy', function () { this.select(); });

  clientType();
  delivery(false);
  calc();
})(jQuery);
