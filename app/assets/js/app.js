import { KEY, els, state } from './state.js';
import { $, cleanInterval, cleanPeriod, hostFrom, parseUrl, uid } from './util.js';
import { load, save, setBanner, setFormError } from './storage.js';
import { render, renderRows } from './render.js';
import { enqueue, findMonitor, tick } from './checks.js';

function closeClear() {
  els.clearAsk.hidden = true;
  if (state.returnFocus && state.returnFocus.focus) state.returnFocus.focus();
}

export function setAddOpen(open) {
  var panel = $('add');
  var scrim = $('add-scrim');
  var already = panel.classList.contains('is-open');
  if (open === already) return;
  panel.classList.toggle('is-open', open);
  scrim.classList.toggle('is-open', open);
  panel.setAttribute('aria-hidden', open ? 'false' : 'true');
  document.body.classList.toggle('add-open', open);
  if (open) {
    panel.removeAttribute('inert');
    state.addReturn = document.activeElement;
    window.setTimeout(function () {
      if (els.form && els.form.name) els.form.name.focus();
    }, 30);
    return;
  }
  panel.setAttribute('inert', '');
  if (state.addReturn && state.addReturn.focus) state.addReturn.focus();
  state.addReturn = null;
}

function onClick(event) {
  var btn = event.target.closest('[data-action]');
  if (!btn || btn.tagName === 'SELECT') return;
  var action = btn.getAttribute('data-action');
  var id = btn.getAttribute('data-id');

  if (action === 'period') {
    state.settings.period = cleanPeriod(btn.getAttribute('data-period'));
    save();
    render();
    return;
  }

  if (action === 'edit') {
    state.editId = id;
    state.moreId = null;
    state.pendingRemove = null;
    renderRows();
    var nameInput = $('edit-name-' + id);
    if (nameInput) nameInput.focus();
    return;
  }

  if (action === 'edit-cancel') {
    state.editId = null;
    renderRows();
    return;
  }

  if (action === 'edit-save') {
    var edited = findMonitor(id);
    var nameField = $('edit-name-' + id);
    var everyField = $('edit-every-' + id);
    if (edited && nameField) {
      var nextName = nameField.value.trim().slice(0, 80);
      edited.name = nextName || hostFrom(edited.url);
      if (everyField) edited.interval = cleanInterval(Number(everyField.value));
    }
    state.editId = null;
    save();
    render();
    return;
  }

  if (action === 'more') {
    state.moreId = state.moreId === id ? null : id;
    state.pendingRemove = null;
    renderRows();
    return;
  }

  if (action === 'check-all') {
    state.pendingRemove = null;
    state.monitors.forEach(function (m) {
      if (!m.paused) enqueue(m.id);
    });
    renderRows();
    return;
  }

  if (action === 'check') {
    state.pendingRemove = null;
    enqueue(id);
    renderRows();
    return;
  }

  if (action === 'pause') {
    var monitor = findMonitor(id);
    if (!monitor) return;
    monitor.paused = !monitor.paused;
    state.pendingRemove = null;
    save();
    render();
    return;
  }

  if (action === 'remove') {
    if (state.pendingRemove !== id) {
      state.pendingRemove = id;
      renderRows();
      var confirmBtn = $('remove-' + id);
      if (confirmBtn) confirmBtn.focus();
      return;
    }
    state.monitors = state.monitors.filter(function (m) { return m.id !== id; });
    state.pendingRemove = null;
    save();
    render();
    return;
  }

  if (action === 'close-add') {
    if (location.hash === '#add') location.hash = 'monitors';
    else setAddOpen(false);
    return;
  }

  if (action === 'clear-ask') {
    state.returnFocus = document.activeElement;
    els.clearAsk.hidden = false;
    $('delete-saved').focus();
    return;
  }

  if (action === 'clear-cancel') {
    closeClear();
    return;
  }

  if (action === 'clear-do') {
    try {
      localStorage.removeItem(KEY);
    } catch (e) {}
    state.monitors = [];
    state.log = [];
    state.pendingRemove = null;
    state.storageBroken = false;
    els.clearAsk.hidden = true;
    setBanner('');
    save();
    render();
  }
}

function onAdd(event) {
  event.preventDefault();
  var form = els.form;
  var url = parseUrl(form.url.value);
  if (!url) {
    setFormError('Enter a full http or https address.');
    form.url.focus();
    return;
  }
  var name = form.name.value.trim().slice(0, 80);
  var interval = cleanInterval(Number(form.interval.value));
  state.monitors.push({
    id: uid(),
    name: name || hostFrom(url),
    url: url,
    interval: interval,
    paused: false,
    last: null,
    samples: []
  });
  state.settings.interval = interval;
  setFormError('');
  form.name.value = '';
  form.url.value = '';
  save();
  render();
  enqueue(state.monitors[state.monitors.length - 1].id);
  if (location.hash === '#add') location.hash = 'monitors';
}

function syncView() {
  var hash = location.hash.replace(/^#/, '');
  if (hash === 'logs') hash = 'log';
  var known = hash === 'main' || hash === 'monitors' || hash === 'log' || hash === 'settings' || hash === 'alerts' || hash === 'add' || hash === 'status';
  var page = known ? hash : 'main';
  $('view-main').hidden = page !== 'main';
  $('view-monitors').hidden = page !== 'monitors' && page !== 'add';
  $('view-log').hidden = page !== 'log';
  $('view-settings').hidden = page !== 'settings';
  $('view-alerts').hidden = page !== 'alerts';
  $('view-status').hidden = page !== 'status';
  setAddOpen(page === 'add');
  var titles = {
    main: ['Main', 'The state of the services you watch.'],
    monitors: ['Monitors', 'Addresses you are watching.'],
    add: ['Add a monitor', 'Paste an http or https address.'],
    alerts: ['Alerts', 'Notices from your checks.'],
    log: ['Logs', 'Every check on the board.'],
    status: ['Public Status Page', 'What visitors see about these services.'],
    settings: ['Saved data', 'Monitors and the log for this board.']
  };
  var copy = titles[page] || titles.main;
  var titleEl = $('dash-title');
  var ledeEl = $('dash-title-lede');
  if (titleEl) titleEl.textContent = copy[0];
  if (ledeEl) ledeEl.textContent = copy[1];
  document.title = copy[0] + ' · atalaya';
  var current = page === 'add' ? 'monitors' : page;
  var links = document.querySelectorAll('.page-nav a');
  var i;
  for (i = 0; i < links.length; i += 1) {
    if (links[i].getAttribute('href') === '#' + current) links[i].setAttribute('aria-current', 'page');
    else links[i].removeAttribute('aria-current');
  }
}

export function init() {
  els.banner = $('banner');
  els.count = $('monitors-count');
  els.rows = $('monitor-rows');
  els.recent = $('recent');
  els.alerts = $('alert-list');
  els.statusBanner = $('status-banner');
  els.statusList = $('status-list');
  els.logRows = $('log-rows');
  els.logStatus = $('log-status');
  els.form = $('add-form');
  els.formError = $('form-error');
  els.clearAsk = $('clear-ask');

  load();
  els.form.interval.value = String(state.settings.interval);

  if (!location.hash || location.hash === '#') {
    history.replaceState(null, '', '#main');
  }

  document.addEventListener('click', onClick);
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if ($('add').classList.contains('is-open')) {
      if (location.hash === '#add') location.hash = 'monitors';
      else setAddOpen(false);
      return;
    }
    if (!els.clearAsk.hidden) {
      closeClear();
      return;
    }
    if (!state.pendingRemove) return;
    var id = state.pendingRemove;
    state.pendingRemove = null;
    renderRows();
    var again = $('remove-' + id);
    if (again) again.focus();
  });

  els.form.addEventListener('submit', onAdd);
  els.rows.addEventListener('change', function (event) {
    var sel = event.target;
    if (!sel || sel.getAttribute('data-action') !== 'interval') return;
    var monitor = findMonitor(sel.getAttribute('data-id'));
    if (!monitor) return;
    monitor.interval = cleanInterval(Number(sel.value));
    save();
  });

  document.addEventListener('focusin', function (event) {
    var el = event.target;
    if (el && el.matches && el.matches('input, select, textarea')) {
      el.scrollIntoView({ block: 'center', inline: 'nearest' });
    }
  });

  window.addEventListener('hashchange', syncView);
  syncView();
  render();
  tick();
  setInterval(tick, 5000);
}
