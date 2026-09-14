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

test.describe('Reader Modes & Settings Behavior', () => {
  test('A. Cuộn dọc: all pages vertical, direction hidden, gap changes spacing', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    await expect(page.locator('#reader-settings-panel')).toBeVisible();

    // In vertical continuous mode (default):
    await expect(page.locator('body')).toHaveClass(/reader-layout-vertical/);
    await expect(page.locator('#btn-mode-vertical')).toHaveClass(/active/);
    await expect(page.locator('#btn-mode-vertical')).toHaveAttribute('aria-pressed', 'true');

    // Direction section MUST be hidden in vertical mode
    await expect(page.locator('#section-reading-direction')).not.toBeVisible();

    // Spacing section MUST be visible and labeled "KHOẢNG CÁCH GIỮA ẢNH"
    await expect(page.locator('#section-page-spacing')).toBeVisible();
    await expect(page.locator('#label-page-spacing')).toHaveText('KHOẢNG CÁCH GIỮA ẢNH');

    // Change gap to 16px
    await page.locator('#btn-space-16').click();
    await expect(page.locator('#reader-container')).toHaveCSS('gap', '16px');
    await expect(page.locator('#btn-space-16')).toHaveAttribute('aria-pressed', 'true');

    // Change gap to 0px
    await page.locator('#btn-space-0').click();
    await expect(page.locator('#reader-container')).toHaveCSS('gap', '0px');
    await expect(page.locator('#btn-space-0')).toHaveAttribute('aria-pressed', 'true');

    // In vertical mode, pages are stacked (more than 1 wrapper visible)
    const pageWrappers = page.locator('.comic-page-wrapper');
    const count = await pageWrappers.count();
    expect(count).toBeGreaterThan(1);
    await expect(pageWrappers.first()).toBeVisible();
    await expect(pageWrappers.nth(1)).toBeVisible();

    await expectNoDocumentOverflow(page);
  });

  test('B. Từng trang: exactly 1 page visible, direction visible, spacing hidden, page navigation works', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Switch to Single Page
    await page.locator('#btn-mode-single').click();
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expect(page.locator('#btn-mode-single')).toHaveAttribute('aria-pressed', 'true');

    // Exactly 1 page wrapper is active and visible
    await expect(page.locator('.comic-page-wrapper.active-page')).toHaveCount(1);
    await expect(page.locator('#page-1')).toHaveClass(/active-page/);
    await expect(page.locator('#page-2')).not.toHaveClass(/active-page/);

    // Direction section MUST be visible in single mode
    await expect(page.locator('#section-reading-direction')).toBeVisible();

    // Spacing section MUST be hidden in single mode
    await expect(page.locator('#section-page-spacing')).not.toBeVisible();

    // Dock page nav visible with counter
    await expect(page.locator('#dock-page-nav')).toBeVisible();
    await expect(page.locator('#dock-page-counter')).toHaveText(/^1 \/ \d+/);

    // Navigate to next page using dock button
    await page.locator('#dock-btn-page-next').click();
    await expect(page.locator('.comic-page-wrapper.active-page')).toHaveCount(1);
    await expect(page.locator('#page-2')).toHaveClass(/active-page/);
    await expect(page.locator('#dock-page-counter')).toHaveText(/^2 \/ \d+/);

    // Switch direction to RTL
    await page.locator('#btn-dir-rtl').click();
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('#btn-dir-rtl')).toHaveAttribute('aria-pressed', 'true');

    // Switch back to LTR
    await page.locator('#btn-dir-ltr').click();
    await expect(page.locator('body')).not.toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('#btn-dir-ltr')).toHaveAttribute('aria-pressed', 'true');

    await expectNoDocumentOverflow(page);
  });

  test('C. Trang đôi: exactly 2 pages visible, spread order LTR/RTL, spacing labeled "KHOẢNG CÁCH GIỮA 2 TRANG"', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Switch to Double Page
    await page.locator('#btn-mode-double').click();
    await expect(page.locator('body')).toHaveClass(/reader-layout-double/);
    await expect(page.locator('#btn-mode-double')).toHaveAttribute('aria-pressed', 'true');

    // In double mode, exactly 2 pages active
    await expect(page.locator('.comic-page-wrapper.active-page')).toHaveCount(2);

    // Direction section MUST be visible in double mode
    await expect(page.locator('#section-reading-direction')).toBeVisible();

    // Spacing section MUST be visible and labeled "KHOẢNG CÁCH GIỮA 2 TRANG"
    await expect(page.locator('#section-page-spacing')).toBeVisible();
    await expect(page.locator('#label-page-spacing')).toHaveText('KHOẢNG CÁCH GIỮA 2 TRANG');

    // Change spread gap
    await page.locator('#btn-space-16').click();
    await expect(page.locator('#reader-container')).toHaveCSS('gap', '16px');

    // RTL direction reverses row direction for Japanese Manga spread
    await page.locator('#btn-dir-rtl').click();
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('#reader-container')).toHaveCSS('flex-direction', 'row-reverse');

    // LTR direction has standard row direction
    await page.locator('#btn-dir-ltr').click();
    await expect(page.locator('body')).not.toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('#reader-container')).toHaveCSS('flex-direction', 'row');

    await expectNoDocumentOverflow(page);
  });

  test('D & E & F & G. Kích thước hiển thị: Fit Width, Fit Height, and 680/800/1000/100% computed size changes', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    const container = page.locator('#reader-container');

    // Open Advanced Modal to configure Width Presets in Tab Image
    await page.locator('#btn-open-advanced-modal').click();
    await page.locator('#tab-btn-image').click();

    // 680px
    await page.locator('#btn-w-680').click();
    await expect(page.locator('#btn-w-680')).toHaveAttribute('aria-pressed', 'true');
    await expect(container).toHaveCSS('max-width', '680px');
    const w680 = await container.evaluate(el => el.getBoundingClientRect().width);
    expect(w680).toBeLessThanOrEqual(682);

    // 800px
    await page.locator('#btn-w-800').click();
    await expect(page.locator('#btn-w-800')).toHaveAttribute('aria-pressed', 'true');
    await expect(container).toHaveCSS('max-width', '800px');

    // 1000px
    await page.locator('#btn-w-1000').click();
    await expect(page.locator('#btn-w-1000')).toHaveAttribute('aria-pressed', 'true');
    await expect(container).toHaveCSS('max-width', '1000px');

    // 100%
    await page.locator('#btn-w-full').click();
    await expect(page.locator('#btn-w-full')).toHaveAttribute('aria-pressed', 'true');
    await expect(container).toHaveCSS('max-width', '100%');

    // Fit Width
    await page.locator('#adv-btn-fit-width').click();
    await expect(page.locator('body')).toHaveClass(/reader-fit-width/);
    await expect(page.locator('#btn-fit-width')).toHaveAttribute('aria-pressed', 'true');

    // Fit Height
    await page.locator('#adv-btn-fit-height').click();
    await expect(page.locator('body')).toHaveClass(/reader-fit-height/);
    await expect(page.locator('#btn-fit-height')).toHaveAttribute('aria-pressed', 'true');

    await expectNoDocumentOverflow(page);
  });

  test('H & I. Night mode and Brightness slider change appearance directly', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Toggle night mode
    const nightBtn = page.locator('#btn-toggle-night');
    await nightBtn.click();
    await expect(page.locator('body')).toHaveClass(/reader-night-mode/);
    await expect(nightBtn).toHaveAttribute('aria-pressed', 'true');
    await expect(nightBtn).toHaveText(/Đang bật giảm chói/);

    // Turn off night mode
    await nightBtn.click();
    await expect(page.locator('body')).not.toHaveClass(/reader-night-mode/);
    await expect(nightBtn).toHaveAttribute('aria-pressed', 'false');
    await expect(nightBtn).toHaveText(/Giảm chói mắt/);

    // Set brightness to 70%
    await page.locator('#brightness-slider').fill('70');
    await expect(page.locator('#brightness-val')).toHaveText('Độ sáng: 70%');
    await expect(page.locator('#reader-container')).toHaveCSS('filter', /brightness\(0?\.?7/);

    // Header should NOT be dimmed
    const headerFilter = await page.locator('#site-header').evaluate(el => getComputedStyle(el).filter);
    expect(headerFilter).toBe('none');

    // Restore to 100%
    await page.locator('#brightness-slider').fill('100');
    await expect(page.locator('#brightness-val')).toHaveText('Độ sáng: 100%');

    await expectNoDocumentOverflow(page);
  });

  test('J & K. Settings persistence across reload and next chapter', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Configure settings: Single page, RTL, 680px, brightness 75%, night mode ON
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-dir-rtl').click();
    await page.locator('#btn-open-advanced-modal').click();
    await page.locator('#tab-btn-image').click();
    await page.locator('#btn-w-680').click();
    await page.locator('#btn-close-advanced-modal').click();
    await page.locator('#btn-toggle-night').click();
    await page.locator('#brightness-slider').fill('75');

    // Verify localStorage has saved both JSON and independent keys
    const storage = await page.evaluate(() => ({
      json: localStorage.getItem('webcomics_reader_settings'),
      mode: localStorage.getItem('reader_mode'),
      dir: localStorage.getItem('reading_direction'),
      width: localStorage.getItem('image_width'),
      night: localStorage.getItem('night_mode'),
      bright: localStorage.getItem('brightness'),
    }));

    expect(storage.mode).toBe('single');
    expect(storage.dir).toBe('rtl');
    expect(storage.width).toBe('680');
    expect(storage.night).toBe('1');
    expect(storage.bright).toBe('75');

    // Reload page (F5)
    await page.reload({ waitUntil: 'domcontentloaded' });

    // Settings must remain applied after reload
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('body')).toHaveClass(/reader-night-mode/);
    await expect(page.locator('#reader-container')).toHaveCSS('max-width', '680px');

    // Navigate to chapter 2
    await page.locator('#btn-next-chap').click();
    await page.waitForURL(/\/chapter-2(?:\?|#|$)/);

    // Settings must still remain applied on chapter 2
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('body')).toHaveClass(/reader-night-mode/);
    await expect(page.locator('#reader-container')).toHaveCSS('max-width', '680px');

    await expectNoDocumentOverflow(page);
  });
});
