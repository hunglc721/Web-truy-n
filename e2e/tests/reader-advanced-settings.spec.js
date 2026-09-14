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

test.describe('Reader Advanced Settings (Full 4-Tabs & Persistence)', () => {
  test('1. Tabs navigation, Header visibility, Progress bar styling & Reader background', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    await expect(page.locator('#reader-settings-panel')).toBeVisible();

    // Tab buttons must be present and in Vietnamese
    await expect(page.locator('#tab-btn-layout')).toHaveText('Bố cục');
    await expect(page.locator('#tab-btn-image')).toHaveText('Ảnh');
    await expect(page.locator('#tab-btn-keybinds')).toHaveText('Phím tắt');
    await expect(page.locator('#tab-btn-behaviors')).toHaveText('Hành vi');

    // Switch tabs
    await page.locator('#tab-btn-image').click();
    await expect(page.locator('#tab-btn-image')).toHaveClass(/active/);

    await page.locator('#tab-btn-keybinds').click();
    await expect(page.locator('#tab-btn-keybinds')).toHaveClass(/active/);

    await page.locator('#tab-btn-behaviors').click();
    await expect(page.locator('#tab-btn-behaviors')).toHaveClass(/active/);

    await page.locator('#tab-btn-layout').click();
    await expect(page.locator('#tab-btn-layout')).toHaveClass(/active/);

    // Test Header Visibility
    await page.locator('#btn-header-hide').click();
    await expect(page.locator('body')).toHaveClass(/reader-header-hidden/);
    await expect(page.locator('#floating-menu-btn')).toBeVisible();

    await page.locator('#btn-header-show').click();
    await expect(page.locator('body')).not.toHaveClass(/reader-header-hidden/);
    await expect(page.locator('#floating-menu-btn')).not.toBeVisible();

    // Test Progress Bar Position & Style
    await page.locator('#btn-progress-pos-bottom').click();
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-bottom/);

    await page.locator('#btn-progress-pos-left').click();
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-left/);

    await page.locator('#btn-progress-hidden').click();
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/is-hidden/);

    // Test Extras: Show page number when progress bar is hidden
    await page.locator('#toggle-extra-page-num').check();
    await expect(page.locator('#reader-floating-page-number')).toBeVisible();

    // Test Reader Background
    await page.locator('#btn-bg-black').click();
    await expect(page.locator('body')).toHaveClass(/reader-bg-black/);

    await page.locator('#btn-bg-white').click();
    await expect(page.locator('body')).toHaveClass(/reader-bg-white/);

    await page.locator('#btn-bg-theme').click();
    await expect(page.locator('body')).not.toHaveClass(/reader-bg-white/);
    await expect(page.locator('body')).not.toHaveClass(/reader-bg-black/);

    // Test Grayscale and Dim
    await page.locator('#toggle-extra-grayscale').check();
    await expect(page.locator('body')).toHaveClass(/reader-grayscale/);
    await page.locator('#toggle-extra-grayscale').uncheck();
    await expect(page.locator('body')).not.toHaveClass(/reader-grayscale/);

    await page.locator('#toggle-extra-dim').check();
    await expect(page.locator('body')).toHaveClass(/reader-dim/);
    await page.locator('#toggle-extra-dim').uncheck();
    await expect(page.locator('body')).not.toHaveClass(/reader-dim/);

    await expectNoDocumentOverflow(page);
  });

  test('2. Image Display tab: stretch small pages & max height limit', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Stretch small pages toggle
    await page.locator('#btn-stretch-small').click();
    await expect(page.locator('body')).toHaveClass(/reader-stretch-small/);

    // Max height limits: 70vh, 85vh, 100vh, none
    await page.locator('#btn-h-70vh').click();
    const firstImg = page.locator('.comic-page-img').first();
    await expect(firstImg).toHaveClass(/max-h-70vh/);

    await page.locator('#btn-h-85vh').click();
    await expect(firstImg).toHaveClass(/max-h-85vh/);

    await page.locator('#btn-h-none').click();
    await expect(firstImg).not.toHaveClass(/max-h-85vh/);

    await expectNoDocumentOverflow(page);
  });

  test('3. Dynamic Keybinds Manager: add key, delete key, and reset', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();
    await page.locator('#tab-btn-keybinds').click();

    // Keybinds table should be populated
    const container = page.locator('#keybinds-list-container');
    await expect(container).toBeVisible();

    // Find the add button for toggle_fullscreen
    const addBtn = container.locator('.keybind-add-btn').first();
    await addBtn.click();

    // Press a key e.g. 'q'
    await page.keyboard.press('q');

    // Verify key was added to localStorage
    const savedKeys = await page.evaluate(() => JSON.parse(localStorage.getItem('webcomics_reader_keybinds') || '{}'));
    expect(savedKeys.toggle_menu).toContain('q');

    // Click reset on this action row
    const resetRowBtn = container.locator('.keybind-reset-btn').first();
    await resetRowBtn.click();

    const resetKeys = await page.evaluate(() => JSON.parse(localStorage.getItem('webcomics_reader_keybinds') || '{}'));
    expect(resetKeys.toggle_menu).not.toContain('q');

    await expectNoDocumentOverflow(page);
  });

  test('4. Behaviors tab and settings persistence across reload and next chapter', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Configure comprehensive settings across all tabs
    // Tab 1
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-dir-rtl').click();
    await page.locator('#btn-header-hide').click();
    await page.locator('#btn-progress-pos-bottom').click();
    await page.locator('#btn-bg-black').click();

    // Tab 2
    await page.locator('#btn-stretch-small').click();
    await page.locator('#btn-h-85vh').click();

    // Tab 4: Behaviors
    await page.locator('#btn-auto-advance-on').click();
    await page.locator('#btn-history-push').click();
    await page.locator('#btn-tap-always').click();
    await page.locator('#btn-scroll-both').click();
    await page.locator('#btn-dblclick-fs-on').click();
    await page.locator('#btn-autoscroll-width').click();
    await page.locator('#input-scroll-offset').fill('25');

    // Verify localStorage has persisted all expected keys
    const storage = await page.evaluate(() => ({
      mode: localStorage.getItem('reader_mode'),
      dir: localStorage.getItem('reading_direction'),
      header: localStorage.getItem('header_visibility'),
      progressPos: localStorage.getItem('progress_bar_position'),
      bg: localStorage.getItem('reader_background'),
      stretch: localStorage.getItem('stretch_small_pages'),
      maxHeight: localStorage.getItem('max_height_limit'),
      autoAdvance: localStorage.getItem('auto_advance_chapter'),
      historyMode: localStorage.getItem('history_mode'),
      tapTurn: localStorage.getItem('tap_turn_mode'),
      scrollTurn: localStorage.getItem('scroll_turn_mode'),
      dblClick: localStorage.getItem('fullscreen_toggle'),
      autoScrollFit: localStorage.getItem('auto_scroll_fit_mode'),
      autoScrollOffset: localStorage.getItem('auto_scroll_offset')
    }));

    expect(storage.mode).toBe('single');
    expect(storage.dir).toBe('rtl');
    expect(storage.header).toBe('hidden');
    expect(storage.progressPos).toBe('bottom');
    expect(storage.bg).toBe('black');
    expect(storage.stretch).toBe('1');
    expect(storage.maxHeight).toBe('85vh');
    expect(storage.autoAdvance).toBe('1');
    expect(storage.historyMode).toBe('push');
    expect(storage.tapTurn).toBe('always_forward');
    expect(storage.scrollTurn).toBe('both');
    expect(storage.dblClick).toBe('1');
    expect(storage.autoScrollFit).toBe('width');
    expect(storage.autoScrollOffset).toBe('25');

    // Reload page (F5)
    await page.reload({ waitUntil: 'domcontentloaded' });

    // Assert behaviors and appearance remained intact
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('body')).toHaveClass(/reader-header-hidden/);
    await expect(page.locator('body')).toHaveClass(/reader-bg-black/);
    await expect(page.locator('body')).toHaveClass(/reader-stretch-small/);
    await expect(page.locator('.comic-page-img').first()).toHaveClass(/max-h-85vh/);
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-bottom/);

    // Navigate to chapter 2 (via visible bottom dock button since header is hidden)
    await page.locator('#dock-btn-next-chap').click();
    await page.waitForURL(/\/chapter-2(?:\?|$)/);

    // Assert settings still applied on next chapter
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expect(page.locator('body')).toHaveClass(/reader-dir-rtl/);
    await expect(page.locator('body')).toHaveClass(/reader-header-hidden/);
    await expect(page.locator('body')).toHaveClass(/reader-bg-black/);
    await expect(page.locator('body')).toHaveClass(/reader-stretch-small/);
    await expect(page.locator('.comic-page-img').first()).toHaveClass(/max-h-85vh/);
    await expect(page.locator('#reader-progress-bar')).toHaveClass(/pos-bottom/);

    await expectNoDocumentOverflow(page);
  });

  test('5. Reset all settings to default values', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop view');
    await page.setViewportSize({ width: 1440, height: 900 });
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    await page.locator('#btn-open-settings').click();

    // Set custom settings
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-bg-black').click();
    await page.locator('#btn-stretch-small').click();

    // Click Reset all to default
    await page.locator('#btn-reset-all-settings').click();

    // Verify reset to vertical, default bg, no stretch
    await expect(page.locator('body')).toHaveClass(/reader-layout-vertical/);
    await expect(page.locator('body')).not.toHaveClass(/reader-bg-black/);
    await expect(page.locator('body')).not.toHaveClass(/reader-stretch-small/);

    await expectNoDocumentOverflow(page);
  });
});
