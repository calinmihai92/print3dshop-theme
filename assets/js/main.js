// Închide meniul mobil la click în afara lui sau pe un link.
(function () {
  var menu = document.querySelector('.nav-mobile');
  if (!menu) return;
  document.addEventListener('click', function (e) {
    if (menu.open && !menu.contains(e.target)) menu.open = false;
  });
  menu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { menu.open = false; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.open) { menu.open = false; menu.querySelector('summary').focus(); }
  });
})();
