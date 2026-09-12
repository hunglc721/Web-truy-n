const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

// Exercise only notification code, with deterministic network/timers and tab events.
const source = fs.readFileSync(path.join(__dirname, '../../laravel-blade/public/js/roadmap.js'), 'utf8');
const notificationCode = source.slice(source.indexOf('  function setupRealtimeNotifications()'), source.lastIndexOf('})();'));
function setup({ auth = 'member', transport = 'polling', hidden = false, pending = false } = {}) {
  const timers = new Map(), events = {}, sources = [], requests = [];
  let id = 0, resolveRequest;
  const document = { body: { dataset: { authState: auth, notificationTransport: transport } }, hidden,
    addEventListener: (name, fn) => { events[name] = fn; } };
  const window = {
    setTimeout: (fn, ms) => { timers.set(++id, { fn, ms }); return id; },
    clearTimeout: id => timers.delete(id),
    addEventListener: (name, fn) => { events[name] = fn; },
    EventSource: class { constructor(url) { this.url = url; sources.push(this); } addEventListener() {} close() { this.closed = true; } },
  };
  const response = { ok: true, json: async () => ({ notifications: [], unread_count: 0 }) };
  const context = vm.createContext({ document, window, EventSource: window.EventSource, AbortController,
    $: () => null, fetch: (url, options) => {
      requests.push({ url, options });
      return pending ? new Promise(resolve => { resolveRequest = () => resolve(response); }) : Promise.resolve(response);
    } });
  const initialize = () => vm.runInContext(notificationCode + '\nsetupRealtimeNotifications();', context);
  initialize();
  return { initialize, document, events, sources, requests, timers, resolve: () => resolveRequest(),
    tick: async () => { const [key, timer] = timers.entries().next().value; timers.delete(key); await timer.fn(); } };
}
const flush = () => new Promise(resolve => setImmediate(resolve));

test('local polls once every 15 seconds without EventSource or duplicate initialization', async () => {
  const app = setup();
  app.initialize();
  await flush();
  assert.equal(app.sources.length, 0);
  assert.equal(app.requests.length, 1);
  assert.equal(app.requests[0].url, '/user/notifications/header');
  assert.equal(app.timers.size, 1);
  assert.equal([...app.timers.values()][0].ms, 15000);
  await app.tick();
  assert.equal(app.requests.length, 2);
  assert.equal(app.timers.size, 1);
});

test('guest creates neither stream nor polling', async () => {
  const app = setup({ auth: 'guest' });
  await flush();
  assert.equal(app.sources.length + app.requests.length + app.timers.size, 0);
});

test('hidden tab pauses polling, visible tab resumes and pagehide aborts requests', async () => {
  const app = setup({ hidden: true, pending: true });
  assert.equal(app.requests.length, 0);
  app.document.hidden = false;
  app.events.visibilitychange();
  app.events.visibilitychange();
  assert.equal(app.requests.length, 1);
  app.document.hidden = true;
  app.events.visibilitychange();
  app.resolve();
  await flush();
  assert.equal(app.timers.size, 0);
  app.document.hidden = false;
  app.events.visibilitychange();
  assert.equal(app.requests.length, 2);
  app.events.pagehide();
  assert.equal(app.requests[1].options.signal.aborted, true);
  app.resolve();
  await flush();
  assert.equal(app.timers.size, 0);
});

test('production retains SSE and closes it on pagehide', () => {
  const app = setup({ transport: 'sse' });
  app.initialize();
  assert.equal(app.sources.length, 1);
  assert.equal(app.sources[0].url, '/user/notifications/header?stream=1');
  assert.equal(app.requests.length, 0);
  app.events.pagehide();
  assert.equal(app.sources[0].closed, true);
  app.events.pageshow({ persisted: true });
  assert.equal(app.sources.length, 2);
});

test('production stream failures switch to one polling loop', async () => {
  const app = setup({ transport: 'sse' });
  for (let i = 0; i < 4; i++) app.sources[0].onerror();
  await flush();
  assert.equal(app.sources[0].closed, true);
  assert.equal(app.requests.length, 1);
  assert.equal(app.timers.size, 1);
});
