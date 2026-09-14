const base = require('./playwright.config');

module.exports = {
  ...base,
  testMatch: 'recommendation-chatbot.spec.js',
  testIgnore: [],
  workers: 1,
  use: { ...base.use, baseURL: 'http://127.0.0.1:8765', serviceWorkers: 'block' },
  projects: base.projects,
  outputDir: 'test-results/chatbot',
  reporter: [['list'], ['html', { outputFolder: 'playwright-report/chatbot', open: 'never' }]],
  globalSetup: require.resolve('./fixtures/chatbot-server.cjs'),
};
