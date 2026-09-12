const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const blade = readFileSync(path.join(__dirname, '../../laravel-blade/resources/views/layouts/main.blade.php'), 'utf8');
const code = blade.split('// Local PWA cleanup: only this application\'s worker and caches.')[1].split('// End local PWA cleanup.')[0];
const run = (navigator, window) => vm.runInNewContext(code, { navigator, window, URL, console: { debug() {} } });

test('unregisters only own active/waiting/installing workers and deletes only app caches', async () => {
  const removed = [], deleted = [];
  const registration = (name, state, url) => ({ [state]: { scriptURL: url }, unregister: async () => { removed.push(name); } });
  await run({ serviceWorker: { getRegistrations: async () => [
    registration('active', 'active', 'http://localhost/sw.js'),
    registration('waiting', 'waiting', 'http://localhost/sw.js?v=old'),
    registration('installing', 'installing', 'http://localhost/sw.js'),
    registration('other-script', 'active', 'http://localhost/other/sw.js'),
    registration('other-origin', 'active', 'http://example.com/sw.js'),
    { unregister: async () => { removed.push('empty'); } },
  ] } }, { location: { origin: 'http://localhost' }, caches: {
    keys: async () => ['webcomics-static-v5', 'webcomics-reader-images-v5', 'other-app', 'webcomics'],
    delete: async name => { deleted.push(name); },
  } });
  assert.deepEqual(removed, ['active', 'waiting', 'installing']);
  assert.deepEqual(deleted, ['webcomics-static-v5', 'webcomics-reader-images-v5']);
});

test('missing APIs are safe and caches still clean if service workers are unavailable', async () => {
  await run({}, {});
  const deleted = [];
  await run({}, { caches: { keys: async () => ['webcomics-static-v5'], delete: async name => deleted.push(name) } });
  assert.deepEqual(deleted, ['webcomics-static-v5']);
});

test('denied browser APIs do not crash or prevent independent cache cleanup', async () => {
  const deleted = [];
  await run({ serviceWorker: { getRegistrations: async () => { throw new Error('denied'); } } }, {
    caches: { keys: async () => ['webcomics-static-v5'], delete: async name => deleted.push(name) },
  });
  assert.deepEqual(deleted, ['webcomics-static-v5']);
  await run({}, { get caches() { throw new Error('denied'); } });
});
