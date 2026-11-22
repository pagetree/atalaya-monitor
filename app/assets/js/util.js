import { INTERVALS, inflight, queue } from './state.js';

export function $(id) {
  return document.getElementById(id);
}

export function esc(value) {
  return String(value).replace(/[&<>"']/g, function (ch) {
    return ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[ch];
  });
}

export function uid() {
  if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
  return String(Date.now()) + String(Math.random()).slice(2);
}

export function parseUrl(raw) {
  try {
    var url = new URL(String(raw).trim());
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return '';
    return url.href;
  } catch (e) {
    return '';
  }
}

export function hostFrom(url) {
  try {
    return new URL(url).host;
  } catch (e) {
    return url;
  }
}

export function cleanInterval(n) {
  var i;
  for (i = 0; i < INTERVALS.length; i += 1) {
    if (INTERVALS[i][0] === n) return n;
  }
  return 300000;
}

export function cleanPeriod(n) {
  n = Number(n);
  if (n === 7 || n === 30) return n;
  return 24;
}

export function cleanKind(value) {
  if (value === 'api' || value === 'keyword' || value === 'website' || value === 'port') return value;
  return 'website';
}

export function parseExclude(raw) {
  var text = String(raw || '').trim();
  if (!text) return [];
  var parts = text.split(/[\s,]+/);
  var out = [];
  var i;
  var n;
  for (i = 0; i < parts.length; i += 1) {
    if (!parts[i]) continue;
    if (!/^\d{3}$/.test(parts[i])) return null;
    n = Number(parts[i]);
    if (n < 100 || n > 599) return null;
    if (out.indexOf(n) === -1) out.push(n);
  }
  return out;
}

export function cleanPort(n) {
  n = Math.round(Number(n));
  if (!isFinite(n) || n < 1 || n > 65535) return 0;
  return n;
}

export function portUrl(raw, port) {
  port = cleanPort(port);
  if (!port) return '';
  var text = String(raw || '').trim();
  if (!text) return '';
  if (!/^https?:\/\//i.test(text)) text = 'http://' + text;
  try {
    var url = new URL(text);
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return '';
    if (!url.hostname) return '';
    url.port = String(port);
    url.pathname = '/';
    url.search = '';
    url.hash = '';
    return url.href;
  } catch (e) {
    return '';
  }
}

export function cleanRegions(list) {
  var places = { frankfurt: 1, capetown: 1, singapore: 1, sydney: 1 };
  var out = [];
  var i;
  if (!Array.isArray(list)) return ['frankfurt', 'capetown', 'singapore', 'sydney'];
  for (i = 0; i < list.length; i += 1) {
    if (places[list[i]] && out.indexOf(list[i]) === -1) out.push(list[i]);
  }
  if (!out.length) return ['frankfurt', 'capetown', 'singapore', 'sydney'];
  return out;
}

export function isQueued(id) {
  return inflight.has(id) || queue.indexOf(id) !== -1;
}

export function ago(ts) {
  var sec = Math.round((Date.now() - ts) / 1000);
  if (sec < 10) return 'just now';
  if (sec < 60) return sec + ' sec ago';
  var min = Math.round(sec / 60);
  if (min < 60) return min === 1 ? '1 min ago' : min + ' min ago';
  var hr = Math.round(min / 60);
  if (hr < 48) return hr === 1 ? '1 hour ago' : hr + ' hours ago';
  var day = Math.round(hr / 24);
  return day === 1 ? '1 day ago' : day + ' days ago';
}

export function clock(ts) {
  var d = new Date(ts);
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
}

export function stamp(ts) {
  var d = new Date(ts);
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var hh = String(d.getHours()).padStart(2, '0');
  var mm = String(d.getMinutes()).padStart(2, '0');
  return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hh + ':' + mm;
}
