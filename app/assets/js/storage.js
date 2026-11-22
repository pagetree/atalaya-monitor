import { els, state } from './state.js';
import { cleanInterval, cleanPeriod } from './util.js';
import { normalizeLog, normalizeMonitor } from './samples.js';

var VIEW_KEY = 'atalaya.view';

export function setBanner(message) {
  els.banner.textContent = message || '';
  els.banner.hidden = !message;
}

export function setFormError(message) {
  els.formError.textContent = message || '';
  els.formError.hidden = !message;
}

export function loadPrefs() {
  var raw;
  try {
    raw = localStorage.getItem(VIEW_KEY);
  } catch (e) {
    return;
  }
  if (!raw) return;
  try {
    var data = JSON.parse(raw);
    if (!data) return;
    state.settings.interval = cleanInterval(Number(data.interval));
    state.settings.period = cleanPeriod(data.period);
  } catch (e) {}
}

export function savePrefs() {
  try {
    localStorage.setItem(VIEW_KEY, JSON.stringify({
      interval: state.settings.interval,
      period: state.settings.period
    }));
  } catch (e) {}
}

function applyBoard(data) {
  var monitors = [];
  var log = [];
  var i;
  if (data && Array.isArray(data.monitors)) {
    for (i = 0; i < data.monitors.length; i += 1) {
      var monitor = normalizeMonitor(data.monitors[i]);
      if (monitor) monitors.push(monitor);
    }
  }
  if (data && Array.isArray(data.log)) {
    for (i = 0; i < data.log.length && log.length < 200; i += 1) {
      var entry = normalizeLog(data.log[i]);
      if (entry) log.push(entry);
    }
  }
  state.monitors = monitors;
  state.log = log;
}

export function loadBoard() {
  return fetch('actions/monitors.php', {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' }
  }).then(function (response) {
    return response.json().then(function (data) {
      return { ok: response.ok, data: data };
    }, function () {
      return { ok: false, data: null };
    });
  }).then(function (res) {
    if (!res.ok || !res.data || !Array.isArray(res.data.monitors)) {
      setBanner((res.data && res.data.error) || 'The board could not be loaded.');
      return false;
    }
    setBanner('');
    applyBoard(res.data);
    return true;
  }, function () {
    setBanner('The board could not be loaded.');
    return false;
  });
}

export function postMonitor(action, body) {
  return fetch('actions/monitors.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(Object.assign({ action: action }, body || {}))
  }).then(function (response) {
    return response.json().then(function (data) {
      if (!data || typeof data !== 'object') return { ok: false, error: 'Could not save that change.' };
      if (!response.ok) data.ok = false;
      return data;
    }, function () {
      return { ok: false, error: 'Could not save that change.' };
    });
  }, function () {
    return { ok: false, error: 'Could not save that change.' };
  });
}

function readLocalMonitors() {
  var raw = null;
  try {
    raw = localStorage.getItem('atalaya.v1') || localStorage.getItem('watchtower.v1');
  } catch (e) {
    return [];
  }
  if (!raw) return [];
  try {
    var data = JSON.parse(raw);
    return data && Array.isArray(data.monitors) ? data.monitors : [];
  } catch (e) {
    return [];
  }
}

function writeLocalMonitors(list) {
  try {
    if (!list.length) {
      localStorage.removeItem('atalaya.v1');
      localStorage.removeItem('watchtower.v1');
      return;
    }
    localStorage.setItem('atalaya.v1', JSON.stringify({ monitors: list, log: [], settings: {} }));
    localStorage.removeItem('watchtower.v1');
  } catch (e) {}
}

export function importLocal() {
  var list = readLocalMonitors();
  if (!list.length) {
    writeLocalMonitors([]);
    return Promise.resolve();
  }
  var index = 0;
  function next() {
    if (index >= list.length) {
      writeLocalMonitors([]);
      return loadBoard();
    }
    var monitor = list[index];
    return postMonitor('create', {
      name: monitor && monitor.name,
      url: monitor && monitor.url,
      kind: monitor && monitor.kind,
      phrase: monitor && monitor.phrase,
      exclude: monitor && monitor.exclude,
      port: monitor && monitor.port,
      interval: monitor && monitor.interval,
      regions: monitor && monitor.regions
    }).then(function (data) {
      if (!data || !data.ok) {
        setBanner((data && data.error) || 'A monitor on this browser could not be saved to your account.');
        return;
      }
      index += 1;
      writeLocalMonitors(list.slice(index));
      return next();
    });
  }
  return next();
}
