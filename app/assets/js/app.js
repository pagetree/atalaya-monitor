import { els, state } from './state.js';
import { $, cleanInterval, cleanKind, cleanPeriod, cleanPort, findMonitor, hostFrom, parseExclude, parseUrl, portUrl } from './util.js';
import { importLocal, loadBoard, loadPrefs, postMonitor, savePrefs, setBanner, setFormError } from './storage.js';
import { render, renderRows } from './render.js';

function closeClear() {
  els.clearAsk.hidden = true;
  if (state.returnFocus && state.returnFocus.focus) state.returnFocus.focus();
}

function onClick(event) {
  var btn = event.target.closest('[data-action]');
  if (!btn || btn.tagName === 'SELECT') return;
  var action = btn.getAttribute('data-action');
  var id = btn.getAttribute('data-id');

  if (action === 'period') {
    state.settings.period = cleanPeriod(btn.getAttribute('data-period'));
    savePrefs();
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
    if (!edited || !nameField || state.saving) return;
    var nextName = nameField.value.trim().slice(0, 80);
    var nextInterval = everyField ? cleanInterval(Number(everyField.value)) : edited.interval;
    state.saving = true;
    postMonitor('update', {
      id: id,
      name: nextName || hostFrom(edited.url),
      interval: nextInterval
    }).then(function (data) {
      state.saving = false;
      if (!data || !data.ok) {
        setBanner((data && data.error) || 'Could not save that change.');
        return;
      }
      state.editId = null;
      return loadBoard().then(render);
    });
    return;
  }

  if (action === 'more') {
    state.moreId = state.moreId === id ? null : id;
    state.pendingRemove = null;
    renderRows();
    return;
  }

  if (action === 'pause') {
    if (!findMonitor(id) || state.saving) return;
    state.pendingRemove = null;
    state.saving = true;
    postMonitor('pause', { id: id }).then(function (data) {
      state.saving = false;
      if (!data || !data.ok) {
        setBanner((data && data.error) || 'Could not save that change.');
        return;
      }
      return loadBoard().then(render);
    });
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
    if (state.saving) return;
    state.saving = true;
    postMonitor('delete', { id: id }).then(function (data) {
      state.saving = false;
      state.pendingRemove = null;
      if (!data || !data.ok) {
        setBanner((data && data.error) || 'Could not remove that monitor.');
        render();
        return;
      }
      return loadBoard().then(render);
    });
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
    if (state.saving) return;
    state.saving = true;
    postMonitor('delete-all').then(function (data) {
      state.saving = false;
      if (!data || !data.ok) {
        setBanner((data && data.error) || 'Could not delete the monitors.');
        return;
      }
      state.pendingRemove = null;
      els.clearAsk.hidden = true;
      setBanner('');
      return loadBoard().then(render);
    });
  }
}

function onAdd(event) {
  event.preventDefault();
  var form = els.form;
  var kind = cleanKind(form.kind.value);
  var port = 0;
  var url = '';
  if (kind === 'port') {
    port = cleanPort(form.port && form.port.value);
    if (!port) {
      setFormError('Enter a port from 1 to 65535.');
      if (form.port) form.port.focus();
      return;
    }
    url = portUrl(form.url.value, port);
    if (!url) {
      setFormError('Enter the host for this port.');
      form.url.focus();
      return;
    }
  } else {
    url = parseUrl(form.url.value);
    if (!url) {
      setFormError('Enter a full http or https address.');
      form.url.focus();
      return;
    }
  }
  var regionBoxes = form.querySelectorAll('input[name="region"]:checked');
  if (!regionBoxes.length) {
    setFormError('Pick at least one region.');
    return;
  }
  var notifyBox = form.querySelector('input[name="notify"]:checked');
  if (!notifyBox) {
    setFormError('Choose email so we can tell you about an incident.');
    return;
  }
  var regions = [];
  var r;
  for (r = 0; r < regionBoxes.length; r += 1) regions.push(regionBoxes[r].value);
  var phrase = form.phrase ? form.phrase.value.trim().slice(0, 120) : '';
  if (kind === 'keyword' && !phrase) {
    setFormError('Type the phrase this page must contain.');
    if (form.phrase) form.phrase.focus();
    return;
  }
  var exclude = [];
  if (kind === 'website' || kind === 'api') {
    exclude = parseExclude(form.exclude ? form.exclude.value : '');
    if (exclude === null) {
      setFormError('Enter status codes from 100 to 599, separated by commas.');
      if (form.exclude) form.exclude.focus();
      return;
    }
  }
  if (state.saving) return;
  var name = form.name.value.trim().slice(0, 80);
  var interval = cleanInterval(Number(form.interval.value));
  state.saving = true;
  postMonitor('create', {
    name: name,
    url: url,
    kind: kind,
    phrase: kind === 'keyword' ? phrase : '',
    exclude: exclude,
    port: kind === 'port' ? port : 0,
    interval: interval,
    regions: regions
  }).then(function (data) {
    state.saving = false;
    if (!data || !data.ok) {
      setFormError((data && data.error) || 'Could not save this monitor.');
      return;
    }
    state.settings.interval = interval;
    savePrefs();
    setFormError('');
    form.name.value = '';
    form.url.value = '';
    if (form.phrase) form.phrase.value = '';
    if (form.exclude) form.exclude.value = '';
    if (form.port) form.port.value = '';
    if (location.hash === '#add') location.hash = 'monitors';
    return loadBoard().then(render);
  });
}

function syncView() {
  var hash = location.hash.replace(/^#/, '');
  if (hash === 'logs') hash = 'log';
  var known = hash === 'main' || hash === 'monitors' || hash === 'log' || hash === 'settings' || hash === 'alerts' || hash === 'add' || hash === 'status';
  var page = known ? hash : 'main';
  $('view-main').hidden = page !== 'main';
  $('view-monitors').hidden = page !== 'monitors';
  $('view-add').hidden = page !== 'add';
  $('view-log').hidden = page !== 'log';
  $('view-settings').hidden = page !== 'settings';
  $('view-alerts').hidden = page !== 'alerts';
  $('view-status').hidden = page !== 'status';
  var titles = {
    main: ['Main', 'The state of the services you watch.'],
    monitors: ['Monitors', 'Addresses you are watching.'],
    add: ['Add a monitor', 'Set the address, the type, and how often we look.'],
    alerts: ['Alerts', 'Notices from your checks.'],
    log: ['Logs', 'Every check on the board.'],
    status: ['Public Status Page', 'What visitors see about these services.'],
    settings: ['Settings', 'Monitors stored on your account.']
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

  loadPrefs();
  els.form.interval.value = String(state.settings.interval);

  if (!location.hash || location.hash === '#') {
    history.replaceState(null, '', '#main');
  }

  document.addEventListener('click', onClick);
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
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

  function syncKindFields() {
    var picked = els.form.querySelector('input[name="kind"]:checked');
    var kind = picked ? picked.value : 'website';
    var phrase = $('phrase-field');
    var port = $('port-field');
    var exclude = $('exclude-field');
    var address = els.form.url;
    if (phrase) phrase.hidden = kind !== 'keyword';
    if (port) port.hidden = kind !== 'port';
    if (exclude) exclude.hidden = kind !== 'website' && kind !== 'api';
    if (address) address.placeholder = kind === 'port' ? 'example.com' : 'https://';
  }

  els.form.addEventListener('change', function (event) {
    if (event.target && event.target.name === 'kind') syncKindFields();
  });
  els.form.addEventListener('submit', onAdd);
  syncKindFields();

  document.addEventListener('focusin', function (event) {
    var el = event.target;
    if (el && el.matches && el.matches('input, select, textarea')) {
      el.scrollIntoView({ block: 'center', inline: 'nearest' });
    }
  });

  window.addEventListener('hashchange', function () {
    syncView();
    if (location.hash === '#add' && els.form && els.form.url) els.form.url.focus();
  });
  syncView();
  if (location.hash === '#add' && els.form && els.form.url) els.form.url.focus();
  render();
  loadBoard().then(function (ok) {
    render();
    if (ok) return importLocal().then(render);
  });
  setInterval(function () {
    if (document.hidden || state.editId || state.moreId || state.pendingRemove || state.saving) return;
    loadBoard().then(render);
  }, 20000);
}
