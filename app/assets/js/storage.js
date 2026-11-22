import { KEY, els, state } from './state.js';
import { cleanInterval, cleanPeriod } from './util.js';
import { normalizeLog, normalizeMonitor, seedSamples } from './samples.js';

export function setBanner(message) {
  els.banner.textContent = message || '';
  els.banner.hidden = !message;
}

export function setFormError(message) {
  els.formError.textContent = message || '';
  els.formError.hidden = !message;
}

export function load() {
  var raw;
  try {
    raw = localStorage.getItem(KEY);
    if (!raw) {
      raw = localStorage.getItem('watchtower.v1');
      if (raw) {
        localStorage.setItem(KEY, raw);
        localStorage.removeItem('watchtower.v1');
      }
    }
  } catch (e) {
    state.storageBroken = true;
    setBanner('Saved data on this browser could not be read. New checks will not be stored until you clear saved data in Settings.');
    return;
  }
  if (!raw) return;
  try {
    var data = JSON.parse(raw);
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
    if (data && data.settings) {
      state.settings.interval = cleanInterval(Number(data.settings.interval));
      state.settings.period = cleanPeriod(data.settings.period);
    }
    for (i = 0; i < state.monitors.length; i += 1) seedSamples(state.monitors[i], state.log);
  } catch (e) {
    state.storageBroken = true;
    setBanner('Saved data on this browser could not be read. New checks will not be stored until you clear saved data in Settings.');
  }
}

export function save() {
  if (state.storageBroken) return;
  try {
    localStorage.setItem(KEY, JSON.stringify({
      monitors: state.monitors,
      log: state.log.slice(0, 200),
      settings: state.settings
    }));
  } catch (e) {
    setBanner('Could not store data in this browser. The list is still on screen, and it may be gone after a reload.');
  }
}
