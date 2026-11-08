document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-menu]');
  var pop = document.getElementById('menu-pop');
  if (!pop) return;
  if (b) { pop.classList.toggle('open'); e.stopPropagation(); }
  else pop.classList.remove('open');
});
