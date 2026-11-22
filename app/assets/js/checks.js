import { inflight, queue, state } from './state.js';
import { isQueued } from './util.js';
import { normalizeSamples } from './samples.js';
import { save } from './storage.js';
import { render } from './render.js';

export function enqueue(id) {
  if (!id || inflight.has(id) || queue.indexOf(id) !== -1) return;
  queue.push(id);
  pump();
}

function pump() {
  while (inflight.size < 3 && queue.length) {
    runCheck(queue.shift());
  }
}

function runCheck(id) {
  var monitor = null;
  var i;
  for (i = 0; i < state.monitors.length; i += 1) {
    if (state.monitors[i].id === id) monitor = state.monitors[i];
  }
  if (!monitor) {
    pump();
    return;
  }
  inflight.add(id);
  render();
  var job = monitor.kind === 'port' ? probePort(monitor) : probe(monitor);
  job.then(function (result) {
    finishCheck(id, result);
  }, function () {
    finishCheck(id, { state: 'down', code: null, ms: null, note: 'no response' });
  });
}

function finishCheck(id, result) {
  inflight.delete(id);
  var current = null;
  var i;
  for (i = 0; i < state.monitors.length; i += 1) {
    if (state.monitors[i].id === id) current = state.monitors[i];
  }
  if (current) {
    current.last = {
      at: Date.now(),
      state: result.state,
      code: result.code,
      ms: result.ms,
      note: result.note || ''
    };
    state.log.unshift({
      at: current.last.at,
      name: current.name,
      url: current.url,
      state: result.state,
      code: result.code,
      ms: result.ms,
      note: result.note || ''
    });
    if (state.log.length > 200) state.log.length = 200;
    current.samples = current.samples || [];
    current.samples.push({ at: current.last.at, state: current.last.state, ms: current.last.ms });
    current.samples = normalizeSamples(current.samples);
    save();
  }
  render();
  pump();
}

function attempt(url, mode) {
  var controller = new AbortController();
  var started = performance.now();
  var timer = setTimeout(function () { controller.abort(); }, 12000);
  return fetch(url, {
    method: 'GET',
    mode: mode,
    cache: 'no-store',
    redirect: 'follow',
    signal: controller.signal
  }).then(function (response) {
    clearTimeout(timer);
    return { kind: 'response', response: response, ms: Math.round(performance.now() - started) };
  }, function (error) {
    clearTimeout(timer);
    var timedOut = error && (error.name === 'AbortError' || error.name === 'TimeoutError');
    return { kind: timedOut ? 'timeout' : 'error' };
  });
}

function looksLikeApi(type) {
  type = String(type || '').toLowerCase();
  if (!type) return true;
  if (type.indexOf('json') !== -1) return true;
  if (type.indexOf('xml') !== -1) return true;
  if (type.indexOf('text/plain') !== -1) return true;
  if (type.indexOf('text/csv') !== -1) return true;
  if (type.indexOf('grpc') !== -1) return true;
  if (type.indexOf('octet-stream') !== -1) return true;
  return false;
}

function isExcluded(monitor, status) {
  var list = monitor.exclude;
  var i;
  if (!list || !list.length) return false;
  if (monitor.kind !== 'website' && monitor.kind !== 'api') return false;
  for (i = 0; i < list.length; i += 1) {
    if (Number(list[i]) === status) return true;
  }
  return false;
}

function readBody(monitor, cors) {
  var response = cors.response;
  var base = {
    state: response.ok ? 'up' : 'down',
    code: response.status,
    ms: cors.ms,
    note: ''
  };
  if (isExcluded(monitor, response.status)) {
    base.state = 'down';
    base.note = 'excluded';
    return base;
  }
  if (!response.ok) return base;
  if (monitor.kind === 'api') {
    if (!looksLikeApi(response.headers.get('content-type'))) {
      base.state = 'answered';
      base.note = 'html page';
    }
    return base;
  }
  if (monitor.kind === 'keyword') {
    return response.text().then(function (body) {
      var hay = String(body || '').slice(0, 500000).toLowerCase();
      var needle = String(monitor.phrase || '').toLowerCase();
      if (!needle || hay.indexOf(needle) === -1) {
        return { state: 'down', code: response.status, ms: cors.ms, note: 'phrase missing' };
      }
      return { state: 'up', code: response.status, ms: cors.ms, note: '' };
    }, function () {
      return { state: 'answered', code: response.status, ms: cors.ms, note: 'body unread' };
    });
  }
  return base;
}

function probePort(monitor) {
  var host = '';
  try { host = new URL(monitor.url).hostname; } catch (e) { host = ''; }
  if (!host || !monitor.port) {
    return Promise.resolve({ state: 'down', code: null, ms: null, note: 'closed' });
  }
  return fetch('actions/probe-port.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ host: host, port: monitor.port })
  }).then(function (response) {
    if (!response.ok) return { state: 'down', code: null, ms: null, note: 'no response' };
    return response.json().then(function (data) {
      if (!data || (data.state !== 'up' && data.state !== 'down')) {
        return { state: 'down', code: null, ms: null, note: 'no response' };
      }
      var ms = data.ms == null ? null : Number(data.ms);
      return {
        state: data.state,
        code: null,
        ms: isFinite(ms) ? ms : null,
        note: data.state === 'down' ? 'closed' : ''
      };
    });
  }, function () {
    return { state: 'down', code: null, ms: null, note: 'no response' };
  });
}

function probe(monitor) {
  var url = monitor.url;
  return attempt(url, 'cors').then(function (cors) {
    if (cors.kind === 'response') return readBody(monitor, cors);
    if (cors.kind === 'timeout') {
      return { state: 'down', code: null, ms: null, note: 'timed out' };
    }
    return attempt(url, 'no-cors').then(function (opaque) {
      if (opaque.kind === 'response') {
        var note = monitor.kind === 'keyword' ? 'body hidden' : 'status hidden';
        return { state: 'answered', code: null, ms: opaque.ms, note: note };
      }
      if (opaque.kind === 'timeout') {
        return { state: 'down', code: null, ms: null, note: 'timed out' };
      }
      return { state: 'down', code: null, ms: null, note: 'no response' };
    });
  });
}

export function findMonitor(id) {
  var i;
  for (i = 0; i < state.monitors.length; i += 1) {
    if (state.monitors[i].id === id) return state.monitors[i];
  }
  return null;
}

export function tick() {
  var now = Date.now();
  state.monitors.forEach(function (m) {
    if (m.paused || isQueued(m.id)) return;
    if (!m.last || now - m.last.at >= m.interval) enqueue(m.id);
  });
}
