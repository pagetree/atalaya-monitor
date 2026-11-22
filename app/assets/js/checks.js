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
  probe(monitor.url).then(function (result) {
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

function probe(url) {
  return attempt(url, 'cors').then(function (cors) {
    if (cors.kind === 'response') {
      return {
        state: cors.response.ok ? 'up' : 'down',
        code: cors.response.status,
        ms: cors.ms,
        note: ''
      };
    }
    if (cors.kind === 'timeout') {
      return { state: 'down', code: null, ms: null, note: 'timed out' };
    }
    return attempt(url, 'no-cors').then(function (opaque) {
      if (opaque.kind === 'response') {
        return { state: 'answered', code: null, ms: opaque.ms, note: 'status hidden' };
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
