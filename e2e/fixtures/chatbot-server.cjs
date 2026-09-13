// Always create a fresh, disposable SQLite database; never read the app's DB settings.
const { spawn, spawnSync } = require('node:child_process');
const { mkdtempSync, writeFileSync } = require('node:fs');
const { tmpdir } = require('node:os');
const path = require('node:path');
const { randomBytes } = require('node:crypto');
const { once } = require('node:events');
module.exports = async () => {
const app = path.resolve(__dirname, '../../laravel-blade');
const temporary = mkdtempSync(path.join(tmpdir(), 'comicx-chatbot-e2e-'));
const database = path.join(temporary, 'test.sqlite');
writeFileSync(database, '');
const env = {
  ...process.env,
  APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: 'http://127.0.0.1:8765',
  APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
  APP_CONFIG_CACHE: path.join(temporary, 'config.php'),
  DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '',
  CACHE_STORE: 'array', SESSION_DRIVER: 'file', QUEUE_CONNECTION: 'sync',
  MAIL_MAILER: 'array', BROADCAST_CONNECTION: 'log',
  AI_MAX_CALLS: '0', AI_API_KEY: '', AI_BASE_URL: '', AI_MODEL: '',
};
for (const args of [['artisan', 'migrate', '--force', '--no-interaction'], [path.join(__dirname, 'seed-chatbot.php')]]) {
  const result = spawnSync('php', args, { cwd: app, env, stdio: 'inherit' });
  if (result.error) throw result.error;
  if (result.status !== 0) process.exit(result.status || 1);
}
const server = spawn('php', ['-S', '127.0.0.1:8765',
  path.join(app, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
{ cwd: path.join(app, 'public'), env, stdio: ['ignore', 'ignore', 'pipe'] });
// PHP announces readiness on stderr. Keep ownership of the child for Windows cleanup.
try {
  const [output] = await Promise.race([
    once(server.stderr, 'data'),
    once(server, 'error').then(([error]) => { throw error; }),
    once(server, 'exit').then(() => { throw new Error('Chatbot PHP server exited before readiness'); }),
  ]);
  if (!output.toString().includes('started')) throw new Error(output.toString());
  server.stderr.resume();
  await new Promise((resolve, reject) => {
    require('node:http').get('http://127.0.0.1:8765/about', response => {
      response.resume();
      response.on('end', () => response.statusCode === 200 ? resolve()
        : reject(new Error(`Chatbot readiness failed: ${response.statusCode}`)));
    }).on('error', reject);
  });
} catch (error) {
  server.kill();
  throw error;
}
return async () => {
  const stopped = once(server, 'exit');
  server.kill();
  await stopped;
};
};
