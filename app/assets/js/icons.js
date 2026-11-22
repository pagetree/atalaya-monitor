export function ico(name) {
  var d = {
    view: '<path d="M1.6 8s2.5-4.2 6.4-4.2S14.4 8 14.4 8 11.9 12.2 8 12.2 1.6 8 1.6 8z"/><circle cx="8" cy="8" r="1.6"/>',
    edit: '<path d="M9.2 2.7l4.1 4.1L5.5 14.6H1.4v-4.1L9.2 2.7z"/>',
    check: '<path d="M2.4 8a5.6 5.6 0 1 0 1.1-3.3"/><path d="M2.2 2.6v3h3"/>',
    pause: '<path d="M5.2 3.2v9.6M10.8 3.2v9.6"/>',
    play: '<path d="M5.2 3.2l6.6 4.8-6.6 4.8V3.2z"/>',
    trash: '<path d="M3 4.2h10M6.2 4.2V2.8h3.6v1.4M4.2 4.2l.6 9h6.4l.6-9"/>',
    more: '<circle class="fill" cx="3.4" cy="8" r="1.05"/><circle class="fill" cx="8" cy="8" r="1.05"/><circle class="fill" cx="12.6" cy="8" r="1.05"/>',
    save: '<path d="M3 8.2l3.2 3.2L13 4.6"/>',
    x: '<path d="M4 4l8 8M12 4L4 12"/>',
    clock: '<circle cx="8" cy="8" r="5.4"/><path d="M8 5v3.2l2.2 1.4"/>',
    bars: '<path d="M3 12.5V9M8 12.5V6M13 12.5V3.5"/>',
    empty: '<circle cx="8" cy="8" r="5.4"/><path d="M8 5.2V8l2 1.4"/>'
  };
  return '<svg class="ico" viewBox="0 0 16 16" aria-hidden="true">' + (d[name] || '') + '</svg>';
}

export var GLOBE = '<svg class="type-icon" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3"/><ellipse cx="8" cy="8" rx="2.5" ry="6" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M2.2 8h11.6M3.4 5.3h9.2M3.4 10.7h9.2" fill="none" stroke="currentColor" stroke-width="1.1"/></svg>';
