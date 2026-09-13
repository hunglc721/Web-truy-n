const base = require('./playwright.config');

module.exports = {
  ...base,
  testMatch: 'recommendation-chatbot.spec.js',
  workers: 1,
  use: { ...base.use, baseURL: 'http://127.0.0.1:8765', serviceWorkers: 'block' },
  projects: base.projects.filter(project => project.name === 'desktop-chromium'),
  globalSetup: require.resolve('./fixtures/chatbot-server.cjs'),
};
