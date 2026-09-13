const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { chromium } = require('@playwright/test');

const app = path.join(__dirname, '../../laravel-blade');
const markup = readFileSync(path.join(app, 'resources/views/partials/recommendation-chat-widget.blade.php'), 'utf8')
  .replace(/<link[^>]+>/g, '').replace(/<script[\s\S]*?<\/script>/g, '');

test('widget opens, closes and stays within desktop/mobile viewports without API calls', async () => {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const viewport of [{ width: 1440, height: 900 }, { width: 393, height: 851 },
      { width: 320, height: 568 }, { width: 667, height: 375 }]) {
      const page = await browser.newPage({ viewport });
      let requests = 0;
      page.on('request', () => requests++);
      await page.setContent(`<!doctype html><html lang="vi"><body><main><h1>Thư viện truyện</h1></main>${markup}</body></html>`);
      for (const file of ['style.css', 'responsive.css', 'recommendation-chat-widget.css']) {
        await page.addStyleTag({ content: readFileSync(path.join(app, 'public/css', file), 'utf8') });
      }
      await page.addScriptTag({ content: readFileSync(path.join(app, 'public/js/recommendation-chat-widget.js'), 'utf8') });
      const toggle = page.locator('#recommendation-chat-toggle');
      const panel = page.locator('#recommendation-chat-panel');
      const close = page.locator('#recommendation-chat-close');
      assert.equal(await panel.isVisible(), false);
      await toggle.click();
      assert.equal(await panel.isVisible(), true);
      assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
      for (const locator of [toggle, panel, close]) {
        const box = await locator.boundingBox();
        assert.ok(box.x >= 0 && box.y >= 0);
        assert.ok(box.x + box.width <= viewport.width + 1);
        assert.ok(box.y + box.height <= viewport.height + 1);
      }
      if (viewport.width < 768 && viewport.height > 500) {
        const box = await toggle.boundingBox();
        assert.ok(box.y + box.height <= viewport.height - 79, 'reserve mobile navigation space');
      }
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
      if (process.env.WIDGET_SCREENSHOT_DIR) {
        await page.screenshot({ path: path.join(process.env.WIDGET_SCREENSHOT_DIR, `widget-${viewport.width}.png`) });
      }
      await close.click();
      assert.equal(await panel.isVisible(), false);
      assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
      assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
      await toggle.click();
      await page.keyboard.press('Escape');
      assert.equal(await panel.isVisible(), false);
      assert.equal(requests, 0);
      assert.equal(await page.locator('#recommendation-chat-widget input').count(), 0);
      await page.close();
    }
  } finally {
    await browser.close();
  }
});
