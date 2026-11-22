export var KEY = 'atalaya.v1';
export var INTERVALS = [
  [60000, '1 min'],
  [300000, '5 min'],
  [900000, '15 min'],
  [1800000, '30 min'],
  [3600000, '1 hour']
];
export var STATES = { up: 1, down: 1, answered: 1 };

export var state = {
  monitors: [],
  log: [],
  settings: { interval: 300000, period: 24 },
  filter: '',
  saving: false,
  pendingRemove: null,
  editId: null,
  moreId: null,
  returnFocus: null
};

export var els = {};
