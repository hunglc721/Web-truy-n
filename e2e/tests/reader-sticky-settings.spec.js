const { test, expect } = require('@playwright/test');

async function gotoApp(page, path) {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  expect(response, `No response received for ${path}`).not.toBeNull();
  expect(response.status(), `Unexpected HTTP status for ${path}`).toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
  return response;
}

async function expectNoDocumentOverflow(page) {
  await page.waitForTimeout(100);
  const metrics = await page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));
  expect(metrics.scrollWidth, `Horizontal overflow: ${metrics.scrollWidth}px > ${metrics.viewport}px`).toBeLessThanOrEqual(metrics.viewport + 2);
}

test.describe('Reader sticky/floating settings panel', () => {
  test('desktop: settings panel follows viewport on scroll, controls work mid-scroll, and stays open across chapter navigation', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop sticky behavior covered on desktop viewport');

    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');
    await expect(page.locator('#reader-container')).toBeVisible();

    const panel = page.locator('#reader-settings-panel');
    const openBtn = page.locator('#btn-open-settings');

    // 1. Initially hidden or closed
    await expect(panel).not.toBeVisible();

    // 2. Open settings panel
    await openBtn.click();
    await expect(panel).toBeVisible();

    const initialBox = await panel.boundingBox();
    expect(initialBox).not.toBeNull();

    // 3. Scroll down deeply (e.g. 1600px into the chapter)
    await page.evaluate(() => window.scrollTo(0, 1600));
    await page.waitForTimeout(200);

    // 4. Panel must still be visible and sticky in viewport
    await expect(panel).toBeVisible();
    const scrolledBox = await panel.boundingBox();
    expect(scrolledBox).not.toBeNull();
    // Y position should remain in visible upper area of viewport (not scrolled off screen at 0 or below)
    expect(scrolledBox.y).toBeGreaterThanOrEqual(40);
    expect(scrolledBox.y).toBeLessThan(400);

    // 5. Test controls while scrolled mid-chapter
    await page.locator('#btn-w-680').click();
    const container = page.locator('#reader-container');
    await expect(container).toHaveCSS('max-width', '680px');

    await page.locator('#btn-space-16').click();
    await expect(page.locator('#reader-container')).toHaveCSS('gap', '16px');

    // Switch to single mode to reveal direction control and verify RTL
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-dir-rtl').click();
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);

    // Check no horizontal overflow while panel is open mid-scroll
    await expectNoDocumentOverflow(page);

    // 6. Navigate to Chapter 2 and verify panel remains open & state persisted
    const nextChapterBtn = page.locator('#btn-next-chap');
    await nextChapterBtn.click();
    await page.waitForURL(/\/chapter-2(?:\?|$)/);

    // Panel should auto-reopen on chapter 2
    await expect(panel).toBeVisible();
    await expect(page.locator('body')).toHaveClass(/reader-panel-open/);

    // 7. ESC key closes panel
    await page.keyboard.press('Escape');
    await expect(panel).not.toBeVisible();
    await expect(page.locator('body')).not.toHaveClass(/reader-panel-open/);

    await expectNoDocumentOverflow(page);
  });

  test('tablet: floating panel follows viewport without breaking layout', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Covered on tablet viewport');

    await page.setViewportSize({ width: 820, height: 1180 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    const panel = page.locator('#reader-settings-panel');
    const openBtn = page.locator('#btn-open-settings');

    await openBtn.click();
    await expect(panel).toBeVisible();

    // Scroll down
    await page.evaluate(() => window.scrollTo(0, 1200));
    await page.waitForTimeout(200);

    // Panel remains visible in viewport
    await expect(panel).toBeVisible();
    const box = await panel.boundingBox();
    expect(box).not.toBeNull();
    expect(box.y).toBeGreaterThanOrEqual(40);
    expect(box.y).toBeLessThan(500);

    // Close button works
    await page.locator('.reader-settings-close-btn').click();
    await expect(panel).not.toBeVisible();

    await expectNoDocumentOverflow(page);
  });

  test('mobile: opens as bottom sheet with backdrop and can be closed smoothly', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    const panel = page.locator('#reader-settings-panel');
    const backdrop = page.locator('#reader-settings-backdrop');

    // On mobile, dock has settings button
    const dockSettingsBtn = page.locator('.reader-bottom-dock button[title*="Cài đặt"]');
    await expect(dockSettingsBtn).toBeVisible();
    await dockSettingsBtn.click();

    // Panel opens as bottom sheet
    await expect(panel).toBeVisible();
    await expect(backdrop).toBeVisible();

    const box = await panel.boundingBox();
    expect(box).not.toBeNull();
    // In bottom sheet mode, bottom should be near screen bottom (844)
    expect(box.y + box.height).toBeGreaterThanOrEqual(800);

    // Click backdrop to close
    await backdrop.click({ position: { x: 20, y: 50 }, force: true });
    await expect(panel).not.toBeVisible();

    await expectNoDocumentOverflow(page);
  });
});
