import { INTERVALS, els, state } from './state.js';
import { $, clock, esc, parseUrl, stamp } from './util.js';
import { GLOBE, ico } from './icons.js';
import { sampleTone, toneWord, uptimeText } from './samples.js';

function kindLabel(kind) {
  if (kind === 'api') return 'API';
  if (kind === 'keyword') return 'Keyword';
  if (kind === 'port') return 'Port';
  return 'Website';
}

var PLACES = { frankfurt: 'Frankfurt', capetown: 'Cape Town', singapore: 'Singapore', sydney: 'Sydney' };

function detailText(item) {
  if (!item) return '';
  var bits = [];
  if (item.code != null) bits.push(String(item.code));
  if (item.ms != null) bits.push(item.ms + ' ms');
  if (item.region && PLACES[item.region]) bits.push(PLACES[item.region]);
  if (item.note) bits.push(item.note);
  return bits.join(', ');
}

function statusText(m) {
  if (m.paused && m.last) return toneWord(sampleTone(m.last)) + ', paused';
  if (m.paused) return 'paused';
  if (!m.last) return 'waiting';
  return toneWord(sampleTone(m.last));
}

function pipClass(m) {
  if (!m.last) return 'pip-muted';
  var tone = sampleTone(m.last);
  if (tone === 'up') return 'pip-up';
  if (tone === 'down') return 'pip-down';
  if (tone === 'slow') return 'pip-slow';
  return 'pip-muted';
}

function rank(m) {
  if (m.last && m.last.state === 'down' && !m.paused) return 0;
  if (!m.last && !m.paused) return 2;
  if (m.paused) return 4;
  return 3;
}

function displayList() {
  var q = state.filter.trim().toLowerCase();
  var rows = [];
  var i;
  for (i = 0; i < state.monitors.length; i += 1) {
    var m = state.monitors[i];
    if (q && (m.name + ' ' + m.url).toLowerCase().indexOf(q) === -1) continue;
    rows.push({ m: m, index: i });
  }
  rows.sort(function (a, b) {
    return rank(a.m) - rank(b.m) || a.index - b.index;
  });
  return rows;
}

function optionsHtml(selected) {
  var html = '';
  var i;
  for (i = 0; i < INTERVALS.length; i += 1) {
    var value = INTERVALS[i][0];
    html += '<option value="' + value + '"' + (value === selected ? ' selected' : '') + '>' + INTERVALS[i][1] + '</option>';
  }
  return html;
}

function pillFor(monitor) {
  if (monitor.paused) return { tone: 'wait', word: 'Paused' };
  if (!monitor.last) return { tone: 'wait', word: 'Waiting' };
  var tone = sampleTone(monitor.last);
  if (tone === 'up') return { tone: 'up', word: 'Up' };
  if (tone === 'slow') return { tone: 'slow', word: 'Warning' };
  if (tone === 'down') return { tone: 'down', word: 'Down' };
  return { tone: 'wait', word: 'Waiting' };
}

export function renderSummary() {
  if (els.logStatus) els.logStatus.hidden = true;
  els.count.textContent = 'Monitors (' + state.monitors.length + ')';
}

export function renderRows() {
  var focusId = document.activeElement && document.activeElement.id;
  var rows = displayList();
  var html = '';

  if (!state.monitors.length) {
    html = '<tr class="is-empty"><td colspan="7"><span class="empty">' + ico('empty') + 'No monitors yet.</span></td></tr>';
  } else {
    var i;
    for (i = 0; i < rows.length; i += 1) {
      var m = rows[i].m;
      var pill = pillFor(m);
      var removeLabel = state.pendingRemove === m.id ? 'Confirm remove' : 'Remove';
      var pauseLabel = m.paused ? 'Resume' : 'Pause';
      var when = m.last ? esc(clock(m.last.at)) : '';
      var editing = state.editId === m.id;
      html += '<tr>';
      if (editing) {
        html += '<td class="name" data-label="Name"><span class="name-wrap">' + GLOBE + '<input id="edit-name-' + esc(m.id) + '" value="' + esc(m.name) + '" maxlength="80" aria-label="Name"></span></td>';
      } else {
        html += '<td class="name" data-label="Name"><span class="name-wrap">' + GLOBE + esc(m.name) + '</span></td>';
      }
      html += '<td data-label="Type">' + kindLabel(m.kind) + '</td>';
      html += '<td class="target" data-label="Target">' + esc(m.url) + '</td>';
      html += '<td data-label="Status"><span class="pill pill-' + pill.tone + '"><span class="dot" aria-hidden="true"></span>' + pill.word + '</span></td>';
      var uptime = uptimeText(m);
      html += '<td data-label="Last Check">' + (when ? '<span class="with-ico">' + ico('clock') + when + '</span>' : '') + '</td>';
      html += '<td data-label="Uptime (30d)">' + (uptime === 'no data' ? esc(uptime) : '<span class="with-ico">' + ico('bars') + esc(uptime) + '</span>') + '</td>';
      html += '<td data-label="Actions"><div class="actions">';
      if (editing) {
        html += '<select id="edit-every-' + esc(m.id) + '" aria-label="Interval for ' + esc(m.name) + '">' + optionsHtml(m.interval) + '</select>';
        html += '<button type="button" class="act" data-action="edit-save" data-id="' + esc(m.id) + '">' + ico('save') + 'Save</button>';
        html += '<button type="button" class="act" data-action="edit-cancel" data-id="' + esc(m.id) + '">' + ico('x') + 'Cancel</button>';
      } else {
        html += '<a class="act" href="' + esc(m.url) + '" target="_blank" rel="noopener noreferrer">' + ico('view') + 'View</a>';
        html += '<button type="button" class="act" data-action="edit" data-id="' + esc(m.id) + '">' + ico('edit') + 'Edit</button>';
        if (state.moreId === m.id) {
          html += '<button type="button" class="act" data-action="pause" data-id="' + esc(m.id) + '" id="pause-' + esc(m.id) + '">' + ico(m.paused ? 'play' : 'pause') + pauseLabel + '</button>';
          html += '<button type="button" class="act" data-action="remove" data-id="' + esc(m.id) + '" id="remove-' + esc(m.id) + '">' + ico('trash') + removeLabel + '</button>';
        } else {
          html += '<button type="button" class="act more" data-action="more" data-id="' + esc(m.id) + '" id="more-' + esc(m.id) + '" aria-label="More actions">' + ico('more') + '</button>';
        }
      }
      html += '</div></td></tr>';
    }
  }

  els.rows.innerHTML = html;
  if (focusId && (focusId.indexOf('edit-name-') === 0 || focusId.indexOf('check-') === 0 || focusId.indexOf('pause-') === 0 || focusId.indexOf('remove-') === 0 || focusId.indexOf('more-') === 0)) {
    var again = $(focusId);
    if (again) again.focus();
  }
}

export function renderRecent() {
  if (!state.log.length) {
    els.recent.innerHTML = '<li><span class="event-name empty">' + ico('empty') + 'No checks yet.</span></li>';
    return;
  }
  var html = '';
  var i;
  var max = Math.min(6, state.log.length);
  for (i = 0; i < max; i += 1) {
    var entry = state.log[i];
    var tone = sampleTone(entry);
    var older = null;
    var j;
    for (j = i + 1; j < state.log.length; j += 1) {
      if (state.log[j].url === entry.url) {
        older = state.log[j];
        break;
      }
    }
    var verb = 'was checked';
    if (tone === 'down') verb = 'is down';
    else if (tone === 'slow') verb = 'is slow';
    else if (tone === 'up' && older && sampleTone(older) === 'down') verb = 'is back online';
    else if (tone === 'up') verb = 'is healthy';
    var dot = tone === 'up' || tone === 'slow' || tone === 'down' ? tone : 'wait';
    html += '<li><span class="event-name"><span class="dot tone-' + dot + '" aria-hidden="true"></span>' + esc(entry.name) + ' ' + verb + '</span>';
    html += '<span class="event-time">' + esc(clock(entry.at)) + '</span></li>';
  }
  els.recent.innerHTML = html;
}

export function renderAlerts() {
  var html = '';
  var n = 0;
  var i;
  for (i = 0; i < state.log.length; i += 1) {
    var entry = state.log[i];
    var tone = sampleTone(entry);
    if (tone !== 'down' && tone !== 'slow') continue;
    var verb = tone === 'down' ? 'is down' : 'is slow';
    html += '<li><span class="event-name"><span class="dot tone-' + tone + '" aria-hidden="true"></span>' + esc(entry.name) + ' ' + verb + '</span>';
    html += '<span class="event-time">' + esc(clock(entry.at)) + '</span></li>';
    n += 1;
    if (n === 40) break;
  }
  els.alerts.innerHTML = html || '<li><span class="event-name empty">' + ico('empty') + 'No alerts.</span></li>';
}

export function renderStatus() {
  if (!els.statusBanner || !els.statusList) return;
  var total = state.monitors.length;
  var down = 0;
  var slow = 0;
  var up = 0;
  var i;
  for (i = 0; i < state.monitors.length; i += 1) {
    var item = state.monitors[i];
    if (item.paused || !item.last) continue;
    var tone = sampleTone(item.last);
    if (tone === 'down') down += 1;
    else if (tone === 'slow') slow += 1;
    else if (tone === 'up') up += 1;
  }
  var word = 'No services yet';
  if (total && down) word = 'A service is down';
  else if (total && slow) word = 'A service is slow';
  else if (total && up) word = 'All systems operational';
  else if (total) word = 'Waiting for a check';
  els.statusBanner.textContent = word;

  if (!total) {
    els.statusList.innerHTML = '<li class="status-empty">No services on this page yet.</li>';
    return;
  }
  var html = '';
  for (i = 0; i < state.monitors.length; i += 1) {
    var monitor = state.monitors[i];
    var pill = pillFor(monitor);
    var uptime = uptimeText(monitor);
    html += '<li>';
    html += '<span class="status-name">' + esc(monitor.name) + '</span>';
    html += '<span class="status-meta">' + (uptime === 'no data' ? '' : esc(uptime)) + '</span>';
    html += '<span class="pill pill-' + pill.tone + '"><span class="dot" aria-hidden="true"></span>' + pill.word + '</span>';
    html += '</li>';
  }
  els.statusList.innerHTML = html;
}

export function renderLog() {
  if (!state.log.length) {
    els.logRows.innerHTML = '<tr class="is-empty"><td colspan="4"><span class="empty">' + ico('empty') + 'No checks yet. They show up here after the first check finishes.</span></td></tr>';
    return;
  }
  var html = '';
  var i;
  for (i = 0; i < state.log.length; i += 1) {
    var entry = state.log[i];
    var logTone = sampleTone(entry);
    var pip = logTone === 'up' ? 'pip-up' : logTone === 'down' ? 'pip-down' : logTone === 'slow' ? 'pip-slow' : 'pip-muted';
    var href = parseUrl(entry.url);
    var name = href
      ? '<a class="name" href="' + esc(href) + '" target="_blank" rel="noopener noreferrer">' + esc(entry.name) + '</a>'
      : '<span class="name">' + esc(entry.name) + '</span>';
    html += '<tr class="' + (entry.state === 'down' ? 'is-down' : '') + '">';
    html += '<td class="when" data-label="When">' + esc(stamp(entry.at)) + '</td>';
    html += '<td data-label="Monitor">' + name + (entry.url ? '<span class="url">' + esc(entry.url) + '</span>' : '') + '</td>';
    html += '<td class="status" data-label="Result"><span class="pip ' + pip + '" aria-hidden="true"></span>' + esc(toneWord(logTone) || entry.state) + '</td>';
    html += '<td class="detail" data-label="Detail">' + esc(detailText(entry)) + '</td>';
    html += '</tr>';
  }
  els.logRows.innerHTML = html;
}

export function render() {
  renderSummary();
  renderRows();
  renderRecent();
  renderAlerts();
  renderStatus();
  renderLog();
}
