/* Editorul fișei de service (wp-admin). */
(function ($) {
  'use strict';

  var $devs = $('#p3d-devices');
  var $items = $('#p3d-items');
  var itemSeq = 0;
  if (!$devs.length) return;

  function nextIndex($rows, attr) {
    var max = -1;
    $rows.each(function () {
      var v = parseInt($(this).attr(attr), 10);
      if (!isNaN(v) && v > max) max = v;
    });
    return max + 1;
  }

  function money(v) {
    return v.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' lei';
  }

  /* Numerotare aparate + opțiunile din coloana „Imprimantă” a pieselor. */
  function refreshDevices() {
    var opts = [];
    $devs.find('.p3d-device').each(function (n) {
      var i = $(this).attr('data-i');
      var model = $.trim($(this).find('.p3d-dev-model').val()) || 'Imprimanta ' + (n + 1);
      $(this).find('.p3d-dev-num').text((n + 1) + '. ' + model);
      opts.push({ v: i, t: (n + 1) + '. ' + model });
    });
    $items.find('.p3d-item-dev').each(function () {
      var $s = $(this);
      var cur = $s.val() !== null ? $s.val() : $s.attr('data-val');
      $s.empty().append($('<option>', { value: '', text: opts.length > 1 ? 'Toate' : '—' }));
      opts.forEach(function (o) { $s.append($('<option>', { value: o.v, text: o.t })); });
      $s.val(cur && $s.find('option[value="' + cur + '"]').length ? cur : '');
    });
  }

  /* Totaluri. */
  function refreshTotals() {
    var sum = { piesa: 0, manopera: 0 };
    $items.find('.p3d-item').each(function () {
      var q = parseFloat(String($(this).find('.p3d-qty').val()).replace(',', '.')) || 0;
      var p = parseFloat(String($(this).find('.p3d-price').val()).replace(',', '.')) || 0;
      var v = Math.round(q * p * 100) / 100;
      $(this).find('.p3d-line').text(money(v));
      sum[$(this).find('.p3d-item-type').val() === 'manopera' ? 'manopera' : 'piesa'] += v;
    });
    $('#p3d-sum-piesa').text(money(sum.piesa));
    $('#p3d-sum-manopera').text(money(sum.manopera));
    $('#p3d-sum-total strong').text(money(sum.piesa + sum.manopera));
  }

  $('#p3d-add-device').on('click', function () {
    var i = nextIndex($devs.find('.p3d-device'), 'data-i');
    $devs.append($('#p3d-device-tpl').html().replace(/__i__/g, i));
    refreshDevices();
  });

  $devs.on('click', '.p3d-del-device', function () {
    if ($devs.find('.p3d-device').length < 2) return;
    if (!window.confirm('Ștergi această imprimantă din fișă?')) return;
    $(this).closest('.p3d-device').remove();
    refreshDevices();
  });

  $devs.on('input', '.p3d-dev-model', refreshDevices);

  $('#p3d-add-item').on('click', function () {
    var i = 1000 + (itemSeq++);
    $items.append($('#p3d-item-tpl').html().replace(/__i__/g, i));
    refreshDevices();
    refreshTotals();
  });

  $items.on('click', '.p3d-del-item', function () {
    $(this).closest('.p3d-item').remove();
    refreshTotals();
  });

  $items.on('input change', 'input, select', refreshTotals);

  /* Poze (biblioteca media; pe telefon permite și camera). */
  $devs.on('click', '.p3d-add-photos', function (e) {
    e.preventDefault();
    var $box = $(this).closest('.p3d-photos');
    var frame = wp.media({ title: 'Poze imprimantă', button: { text: 'Adaugă în fișă' }, library: { type: 'image' }, multiple: 'add' });
    frame.on('select', function () {
      frame.state().get('selection').each(function (att) {
        var a = att.toJSON();
        if ($box.find('.p3d-ph[data-id="' + a.id + '"]').length) return;
        var src = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
        $box.find('.p3d-photo-list').append(
          $('<span class="p3d-ph">').attr('data-id', a.id).append($('<img>').attr('src', src).attr('alt', '')).append('<button type="button" aria-label="Scoate poza">×</button>')
        );
      });
      syncPhotos($box);
    });
    frame.open();
  });

  $devs.on('click', '.p3d-ph button', function () {
    var $box = $(this).closest('.p3d-photos');
    $(this).closest('.p3d-ph').remove();
    syncPhotos($box);
  });

  function syncPhotos($box) {
    $box.find('.p3d-photo-ids').val($box.find('.p3d-ph').map(function () { return $(this).attr('data-id'); }).get().join(','));
  }

  $(document).on('click', '.p3d-copy', function () { this.select(); });

  refreshDevices();
  refreshTotals();
})(jQuery);
