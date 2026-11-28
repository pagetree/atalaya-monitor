function closeDrops(except) {
  var open = document.querySelectorAll('.nav-drop[open]');
  var i;
  for (i = 0; i < open.length; i += 1) {
    if (open[i] !== except) open[i].removeAttribute('open');
  }
}

document.addEventListener('click', function (event) {
  var drop = event.target.closest ? event.target.closest('.nav-drop') : null;
  closeDrops(drop);
});

document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  var open = document.querySelector('.nav-drop[open]');
  if (!open) return;
  open.removeAttribute('open');
  var summary = open.querySelector('summary');
  if (summary) summary.focus();
});

window.addEventListener('hashchange', function () {
  closeDrops(null);
});
