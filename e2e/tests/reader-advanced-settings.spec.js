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

test.describe('Reader Quick & Advanced Settings (2-Tier Architecture)', () => {
  test('1. Quick Settings is compact, context-aware, and opens/closes Advanced Modal', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    const quickPanel = page.locator('#reader-settings-panel');
    await expect(quickPanel).toBeVisible();

    // 1.1. Quick panel must be compact: height <= 440px on desktop
    const panelMetrics = await quickPanel.evaluate(el => ({
      clientHeight: el.clientHeight,
      scrollHeight: el.scrollHeight,
      rectHeight: el.getBoundingClientRect().height
    }));
    expect(panelMetrics.rectHeight).toBeLessThanOrEqual(440);

    // 1.2. Context-aware controls test in Quick Settings
    // Mode Vertical (default): direction section is hidden, spacing is visible
    await expect(page.locator('#section-reading-direction')).toBeHidden();
    await expect(page.locator('#section-page-spacing')).toBeVisible();
    await expect(page.locator('#label-page-spacing')).toHaveText('KHOẢNG CÁCH GIỮA ẢNH');

    // Switch to Single mode: direction section becomes visible, spacing is hidden
    await page.locator('#btn-mode-single').click();
    await expect(page.locator('#section-reading-direction')).toBeVisible();
    await expect(page.locator('#section-page-spacing')).toBeHidden();

    // Switch to Double mode: direction section is visible, spacing is visible with new label
    await page.locator('#btn-mode-double').click();
    await expect(page.locator('#section-reading-direction')).toBeVisible();
    await expect(page.locator('#section-page-spacing')).toBeVisible();
    await expect(page.locator('#label-page-spacing')).toHaveText('KHOẢNG CÁCH GIỮA 2 TRANG');

    // 1.3. Open Advanced Modal via "⚙️ Cài đặt nâng cao"
    const advModal = page.locator('#reader-advanced-modal');
    const advBackdrop = page.locator('#reader-advanced-backdrop');
    await expect(advModal).not.toBeVisible();

    await page.locator('#btn-open-advanced-modal').click();
    await expect(advModal).toBeVisible();
    await expect(advBackdrop).toBeVisible();

    // Close via close button (✕)
    await page.locator('#btn-close-advanced-modal').click();
    await expect(advModal).not.toBeVisible();

    // Reopen and close via backdrop click
    await page.locator('#btn-open-advanced-modal').click();
    await expect(advModal).toBeVisible();
    await advBackdrop.click({ position: { x: 10, y: 10 } });
    await expect(advModal).not.toBeVisible();

    await expectNoDocumentOverflow(page);
  });

  test('2. Advanced Modal has 5 tabs in Vietnamese and switches smoothly', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    await page.locator('#btn-open-advanced-modal').click();

    // 5 Tabs verification
    await expect(page.locator('#tab-btn-layout')).toHaveText('Bố cục');
    await expect(page.locator('#tab-btn-image')).toHaveText('Hiển thị ảnh');
    await expect(page.locator('#tab-btn-keybinds')).toHaveText('Phím tắt');
    await expect(page.locator('#tab-btn-behaviors')).toHaveText('Hành vi');
    await expect(page.locator('#tab-btn-other')).toHaveText('Khác');

    // Switch to Image tab
    await page.locator('#tab-btn-image').click();
    await expect(page.locator('#tab-btn-image')).toHaveClass(/active/);
    await expect(page.locator('#tab-pane-image')).toBeVisible();
    await expect(page.locator('#tab-pane-layout')).toBeHidden();

    // Switch to Keybinds tab
    await page.locator('#tab-btn-keybinds').click();
    await expect(page.locator('#tab-btn-keybinds')).toHaveClass(/active/);
    await expect(page.locator('#tab-pane-keybinds')).toBeVisible();

    // Switch to Behaviors tab
    await page.locator('#tab-btn-behaviors').click();
    await expect(page.locator('#tab-btn-behaviors')).toHaveClass(/active/);
    await expect(page.locator('#tab-pane-behaviors')).toBeVisible();

    // Switch to Other tab
    await page.locator('#tab-btn-other').click();
    await expect(page.locator('#tab-btn-other')).toHaveClass(/active/);
    await expect(page.locator('#tab-pane-other')).toBeVisible();

    // Switch back to Layout tab
    await page.locator('#tab-btn-layout').click();
    await expect(page.locator('#tab-btn-layout')).toHaveClass(/active/);
    await expect(page.locator('#tab-pane-layout')).toBeVisible();

    await expectNoDocumentOverflow(page);
  });

  test('3. Two-way state sync between Quick Settings and Advanced Modal', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // 3.1. Change in Quick Settings -> reflect in Advanced Modal
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-fit-width').click();
    await page.locator('#brightness-slider').fill('70');

    // Open Advanced modal and verify values
    await page.locator('#btn-open-advanced-modal').click();
    await expect(page.locator('#adv-btn-mode-single')).toHaveAttribute('aria-pressed', 'true');

    await page.locator('#tab-btn-image').click();
    await expect(page.locator('#adv-btn-fit-width')).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('#adv-brightness-slider')).toHaveValue('70');

    // 3.2. Change in Advanced Modal -> reflect in Quick Settings
    await page.locator('#tab-btn-layout').click();
    await page.locator('#adv-btn-mode-double').click();

    await page.locator('#tab-btn-image').click();
    await page.locator('#adv-btn-fit-height').click();
    await page.locator('#adv-brightness-slider').fill('85');

    // Close Advanced modal and verify Quick Settings has updated
    await page.locator('#btn-close-advanced-modal').click();
    await expect(page.locator('#btn-mode-double')).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('#btn-fit-height')).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('#brightness-slider')).toHaveValue('85');

    await expectNoDocumentOverflow(page);
  });

  test('4. Full Settings: Layout, Image fit, Filters, Background, and Persistence', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    await page.locator('#btn-open-advanced-modal').click();

    // Tab 1: Header visibility & Progress & Background
    await page.locator('#btn-header-hide').click();
    await expect(page.locator('body')).toHaveClass(/reader-header-hidden/);

    await page.locator('#btn-progress-pos-bottom').click();
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-bottom/);

    await page.locator('#btn-bg-black').click();
    await expect(page.locator('body')).toHaveClass(/reader-bg-black/);

    // Tab 2: Stretch small, Max height, Max width, Filters
    await page.locator('#tab-btn-image').click();
    await page.locator('#btn-stretch-small').click();
    await expect(page.locator('body')).toHaveClass(/reader-stretch-small/);

    await page.locator('#btn-h-85vh').click();
    await expect(page.locator('.comic-page-img').first()).toHaveClass(/max-h-85vh/);

    await page.locator('#toggle-extra-grayscale').check();
    await expect(page.locator('body')).toHaveClass(/reader-grayscale/);

    // Tab 4: Behaviors
    await page.locator('#tab-btn-behaviors').click();
    await page.locator('#btn-auto-advance-on').click();
    await page.locator('#btn-history-push').click();
    await page.locator('#btn-tap-always').click();
    await page.locator('#btn-scroll-both').click();
    await page.locator('#btn-dblclick-fs-on').click();
    await page.locator('#btn-autoscroll-width').click();
    await page.locator('#input-scroll-offset').fill('30');

    // Verify localStorage
    const storage = await page.evaluate(() => ({
      header: localStorage.getItem('header_visibility'),
      progressPos: localStorage.getItem('progress_bar_position'),
      bg: localStorage.getItem('reader_background'),
      stretch: localStorage.getItem('stretch_small_pages'),
      maxHeight: localStorage.getItem('max_height_limit'),
      autoAdvance: localStorage.getItem('auto_advance_chapter'),
      offset: localStorage.getItem('auto_scroll_offset')
    }));

    expect(storage.header).toBe('hidden');
    expect(storage.progressPos).toBe('bottom');
    expect(storage.bg).toBe('black');
    expect(storage.stretch).toBe('1');
    expect(storage.maxHeight).toBe('85vh');
    expect(storage.autoAdvance).toBe('1');
    expect(storage.offset).toBe('30');

    // Reload page
    await page.reload({ waitUntil: 'domcontentloaded' });

    // Assert states persist after reload
    await expect(page.locator('body')).toHaveClass(/reader-header-hidden/);
    await expect(page.locator('body')).toHaveClass(/reader-bg-black/);
    await expect(page.locator('body')).toHaveClass(/reader-stretch-small/);
    await expect(page.locator('.comic-page-img').first()).toHaveClass(/max-h-85vh/);
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-bottom/);

    await expectNoDocumentOverflow(page);
  });

  test('5. Tab 5 (Khác): Reset all settings to default', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Set custom settings
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-open-advanced-modal').click();
    await page.locator('#btn-bg-black').click();

    // Open Tab 5 (Khác)
    await page.locator('#tab-btn-other').click();
    await expect(page.locator('#tab-pane-other')).toBeVisible();

    // Click Reset all
    await page.locator('#btn-reset-all-settings').click();

    // Verify modal is closed and settings reset to default vertical layout and normal theme
    await expect(page.locator('#reader-advanced-modal')).not.toBeVisible();
    await expect(page.locator('body')).toHaveClass(/reader-layout-vertical/);
    await expect(page.locator('body')).not.toHaveClass(/reader-bg-black/);

    await expectNoDocumentOverflow(page);
  });
});
