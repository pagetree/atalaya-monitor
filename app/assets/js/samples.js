import { INTERVALS, STATES, state } from './state.js';
import { cleanInterval, cleanKind, cleanPeriod, cleanPort, cleanRegions, hostFrom, parseExclude, parseUrl } from './util.js';

export function normalizeSamples(list) {
  if (!Array.isArray(list)) return [];
  var cutoff = Date.now() - (30 * 86400000);
  var seen = {};
  var out = [];
  var i;
  for (i = 0; i < list.length; i += 1) {
    var sample = list[i];
    if (!sample || !STATES[sample.state]) continue;
    var at = Number(sample.at);
    if (!isFinite(at) || at < cutoff) continue;
    var key = at + ':' + sample.state;
    if (seen[key]) continue;
    seen[key] = 1;
    var ms = sample.ms == null ? null : Number(sample.ms);
    out.push({ at: at, state: sample.state, ms: isFinite(ms) ? ms : null });
  }
  out.sort(function (a, b) { return a.at - b.at; });
  if (out.length > 8000) out = out.slice(out.length - 8000);
  return out;
}

export function seedSamples(monitor, log) {
  if (monitor.samples.length) return;
  var i;
  for (i = 0; i < log.length; i += 1) {
    if (log[i].url === monitor.url) {
      monitor.samples.push({ at: log[i].at, state: log[i].state, ms: log[i].ms });
    }
  }
  if (monitor.last) {
    monitor.samples.push({ at: monitor.last.at, state: monitor.last.state, ms: monitor.last.ms });
  }
  monitor.samples = normalizeSamples(monitor.samples);
}

export function sampleTone(item) {
  if (!item) return 'empty';
  if (item.state === 'down') return 'down';
  if (item.state === 'answered') return 'slow';
  if (item.state === 'up' && item.ms != null && item.ms >= 2000) return 'slow';
  if (item.state === 'up') return 'up';
  return 'empty';
}

export function toneWord(tone) {
  if (tone === 'up' || tone === 'slow' || tone === 'down') return tone;
  return '';
}

export function periodWindow() {
  var now = new Date();
  var period = cleanPeriod(state.settings.period);
  var size;
  var count;
  var end;
  var unit;
  if (period === 24) {
    end = new Date(now);
    end.setMinutes(0, 0, 0);
    end.setHours(end.getHours() + 1);
    size = 3600000;
    count = 24;
    unit = 'hour';
  } else {
    end = new Date(now);
    end.setHours(24, 0, 0, 0);
    size = 86400000;
    count = period;
    unit = 'day';
  }
  return {
    count: count,
    size: size,
    start: end.getTime() - (count * size),
    end: end.getTime(),
    unit: unit
  };
}

export function buildBuckets(monitor) {
  var window = periodWindow();
  var cells = [];
  var i;
  for (i = 0; i < window.count; i += 1) {
    cells.push({
      from: window.start + (i * window.size),
      tone: 'empty',
      n: 0
    });
  }
  var samples = monitor.samples || [];
  var rank = { empty: 0, up: 1, slow: 2, down: 3 };
  for (i = 0; i < samples.length; i += 1) {
    var sample = samples[i];
    if (sample.at < window.start || sample.at >= window.end) continue;
    var idx = Math.floor((sample.at - window.start) / window.size);
    if (idx < 0 || idx >= window.count) continue;
    var tone = sampleTone(sample);
    cells[idx].n += 1;
    if (rank[tone] > rank[cells[idx].tone]) cells[idx].tone = tone;
  }
  return { cells: cells, window: window };
}

function unitPhrase(n, unit) {
  return n + ' ' + unit + (n === 1 ? '' : 's');
}

export function bucketLine(built) {
  var up = 0;
  var slow = 0;
  var down = 0;
  var empty = 0;
  var i;
  for (i = 0; i < built.cells.length; i += 1) {
    if (built.cells[i].tone === 'up') up += 1;
    else if (built.cells[i].tone === 'slow') slow += 1;
    else if (built.cells[i].tone === 'down') down += 1;
    else empty += 1;
  }
  if (!up && !slow && !down) {
    return { text: 'No checks in this period.', kind: 'empty' };
  }
  var unit = built.window.unit;
  var parts = [];
  if (down) parts.push(unitPhrase(down, unit) + ' down');
  if (slow) parts.push(unitPhrase(slow, unit) + ' slow');
  if (up) parts.push(unitPhrase(up, unit) + ' up');
  if (empty) parts.push(unitPhrase(empty, unit) + ' with no check');
  var kind = down ? 'down' : slow ? 'slow' : empty ? 'empty' : 'up';
  return { text: parts.join(', '), kind: kind };
}

export function cellWhen(from, unit) {
  var d = new Date(from);
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  if (unit === 'day') return d.getDate() + ' ' + months[d.getMonth()];
  var hh = String(d.getHours()).padStart(2, '0');
  return d.getDate() + ' ' + months[d.getMonth()] + ', ' + hh + ':00';
}

export function worstTone(monitor) {
  var rank = { empty: 0, up: 1, slow: 2, down: 3 };
  var worst = 'empty';
  if (!monitor.paused && monitor.last) {
    var current = sampleTone(monitor.last);
    if (rank[current] > rank[worst]) worst = current;
  }
  var built = buildBuckets(monitor);
  var i;
  for (i = 0; i < built.cells.length; i += 1) {
    if (rank[built.cells[i].tone] > rank[worst]) worst = built.cells[i].tone;
  }
  return worst;
}

export function intervalLabel(ms) {
  var i;
  for (i = 0; i < INTERVALS.length; i += 1) {
    if (INTERVALS[i][0] === ms) return INTERVALS[i][1];
  }
  return '5 min';
}

export function normalizeLast(last) {
  if (!last || typeof last !== 'object') return null;
  if (!STATES[last.state]) return null;
  var at = Number(last.at);
  if (!isFinite(at)) return null;
  var code = last.code == null ? null : Number(last.code);
  var ms = last.ms == null ? null : Number(last.ms);
  return {
    at: at,
    state: last.state,
    code: isFinite(code) ? code : null,
    ms: isFinite(ms) ? ms : null,
    note: typeof last.note === 'string' ? last.note.slice(0, 80) : ''
  };
}

export function normalizeMonitor(m) {
  var url = parseUrl(m && m.url);
  if (!m || typeof m.id !== 'string' || !m.id || !url) return null;
  var name = typeof m.name === 'string' ? m.name.trim().slice(0, 80) : '';
  var kind = cleanKind(m.kind);
  var phrase = typeof m.phrase === 'string' ? m.phrase.trim().slice(0, 120) : '';
  if (kind !== 'keyword') phrase = '';
  var exclude = [];
  if (kind === 'website' || kind === 'api') {
    var parsedExclude = parseExclude(Array.isArray(m.exclude) ? m.exclude.join(',') : '');
    exclude = parsedExclude || [];
  }
  var port = 0;
  if (kind === 'port') {
    port = cleanPort(m.port);
    if (!port) {
      try { port = cleanPort(new URL(url).port); } catch (e) { port = 0; }
    }
  }
  return {
    id: m.id,
    name: name || hostFrom(url),
    url: url,
    kind: kind,
    phrase: phrase,
    exclude: exclude,
    port: port,
    interval: cleanInterval(Number(m.interval)),
    regions: cleanRegions(m.regions),
    notify: m.notify === 'email' ? 'email' : 'email',
    paused: Boolean(m.paused),
    last: normalizeLast(m.last),
    samples: normalizeSamples(m.samples)
  };
}

export function normalizeLog(entry) {
  if (!entry || typeof entry !== 'object') return null;
  if (!STATES[entry.state]) return null;
  var at = Number(entry.at);
  if (!isFinite(at)) return null;
  var code = entry.code == null ? null : Number(entry.code);
  var ms = entry.ms == null ? null : Number(entry.ms);
  var name = typeof entry.name === 'string' ? entry.name.slice(0, 80) : 'monitor';
  var url = typeof entry.url === 'string' ? entry.url.slice(0, 2000) : '';
  return {
    at: at,
    name: name || 'monitor',
    url: url,
    state: entry.state,
    code: isFinite(code) ? code : null,
    ms: isFinite(ms) ? ms : null,
    note: typeof entry.note === 'string' ? entry.note.slice(0, 80) : ''
  };
}

export function uptimeText(monitor) {
  var cutoff = Date.now() - (30 * 86400000);
  var samples = monitor.samples || [];
  var kept = [];
  var i;
  for (i = 0; i < samples.length; i += 1) {
    if (samples[i].at >= cutoff) kept.push(samples[i]);
  }
  if (!kept.length) return 'no data';
  var ok = 0;
  for (i = 0; i < kept.length; i += 1) {
    if (sampleTone(kept[i]) !== 'down') ok += 1;
  }
  var pct = Math.round((ok / kept.length) * 1000) / 10;
  return (pct % 1 === 0 ? String(pct) : pct.toFixed(1)) + '%';
}
