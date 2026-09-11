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

function watchPageErrors(page) {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  return errors;
}

async function login(page, email) {
  await gotoApp(page, '/login');
  await page.locator('#login-email').fill(email);
  await page.locator('#login-password').fill('12345678');
  await page.getByRole('button', { name: /Đăng Nhập/i }).click();
}

test.describe('WebComics browser journeys', () => {
  test('homepage is usable and responsive', async ({ page }, testInfo) => {
    const pageErrors = watchPageErrors(page);
    await gotoApp(page, '/');

    await expect(page.locator('#site-header')).toBeVisible();
    await expect(page.locator('body')).toHaveAttribute('data-auth-state', 'guest');
    await expectNoDocumentOverflow(page);

    const isMobile = testInfo.project.name.includes('mobile');
    if (isMobile) {
      const button = page.locator('#mobile-menu-btn');
      await expect(button).toBeVisible();
      await button.click();
      await expect(button).toHaveAttribute('aria-expanded', 'true');
      await expect(page.locator('#mobile-menu')).toHaveClass(/open/);
      await expect(page.locator('#mobile-menu')).toHaveAttribute('aria-hidden', 'false');

      await page.locator('#mobile-menu a[href$="/genres"]').first().click();
      await page.waitForURL(/\/genres(?:\?|$)/);
      await expectNoDocumentOverflow(page);
    } else {
      await expect(page.locator('.main-nav')).toBeVisible();
      await expect(page.locator('#mobile-menu-btn')).toBeHidden();
    }

    expect(pageErrors).toEqual([]);
  });

  test('comic detail and reader work without layout overflow', async ({ page }) => {
    const pageErrors = watchPageErrors(page);

    await gotoApp(page, '/truyen/solo-leveling');
    await expect(page.getByRole('heading', { name: /Solo Leveling/i }).first()).toBeVisible();
    await expectNoDocumentOverflow(page);

    await gotoApp(page, '/truyen/solo-leveling/chapter-1');
    await expect(page.locator('#reader-container')).toBeVisible();
    await expect(page.locator('#reader-top-bar')).toBeVisible();
    await page.locator('#reader-top-bar [data-open-chapter-picker]').click();
    await expect(page.locator('#reader-chapter-picker [aria-current="page"]')).toHaveAttribute('href', /chapter-1$/);
    await page.locator('#reader-picker-close').click();

    await page.locator('#btn-open-settings').click();
    await expect(page.locator('#reader-settings-panel')).toBeVisible();
    await page.locator('#btn-mode-single').click();
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expectNoDocumentOverflow(page);

    expect(pageErrors).toEqual([]);
  });

  test('member can login and reach personal library', async ({ page }) => {
    const pageErrors = watchPageErrors(page);
    await login(page, 'user@webcomics.com');

    await page.waitForURL(/\/user\/library(?:\?|$)/);
    await expect(page.locator('body')).toHaveAttribute('data-auth-state', 'member');
    await expect(page.locator('h1.library-title')).toHaveText('Tủ Truyện');
    await expect(page.locator('.library-shell')).toBeVisible();
    await expectNoDocumentOverflow(page);

    expect(pageErrors).toEqual([]);
  });

  test('authenticated member receives admin inbox notification in realtime', async ({ page, browser }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Realtime transport is covered once on desktop; mobile uses the same EventSource client.');

    const pageErrors = watchPageErrors(page);
    await login(page, 'user@webcomics.com');
    await page.waitForURL(/\/user\/library(?:\?|$)/);
    await expect(page.locator('body')).toHaveAttribute('data-realtime-seen', '1', { timeout: 10_000 });

    const badge = page.locator('.wc-notification-badge');
    const baseline = await badge.evaluate((node) => node.hidden ? 0 : Number.parseInt(node.textContent || '0', 10) || 0);
    const uniqueTitle = `Realtime ${Date.now()}`;

    const adminContext = await browser.newContext();
    const adminPage = await adminContext.newPage();
    await login(adminPage, 'admin@webcomics.com');
    await adminPage.waitForURL(/\/admin(?:\/|$)/);

    const result = await adminPage.evaluate(async ({ title }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const body = new URLSearchParams({
        title,
        message: 'Thông báo kiểm thử realtime từ Playwright.',
        severity: 'info',
        audience: 'authenticated',
        send_to_inbox: '1',
      });
      const response = await fetch('/admin/notifications', {
        method: 'POST',
        headers: {
          'Accept': 'text/html',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        redirect: 'follow',
        body,
      });
      return { ok: response.ok, status: response.status };
    }, { title: uniqueTitle });

    expect(result.ok, `Admin notification request failed with HTTP ${result.status}`).toBeTruthy();

    await expect.poll(async () => {
      return badge.evaluate((node) => node.hidden ? 0 : Number.parseInt(node.textContent || '0', 10) || 0);
    }, { timeout: 10_000 }).toBeGreaterThan(baseline);

    await expect(page.locator('#wc-realtime-toast')).toContainText(uniqueTitle, { timeout: 10_000 });
    await adminContext.close();

    expect(pageErrors).toEqual([]);
  });

  test('admin chapter uploader exposes ZIP mode and naturally sorts loose image files', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Admin bulk upload workflow is covered on desktop Chromium.');

    const pageErrors = watchPageErrors(page);
    await login(page, 'admin@webcomics.com');
    await page.waitForURL(/\/admin(?:\/|$)/);
    await gotoApp(page, '/admin/comics/1/chapters/create');

    const zipInput = page.locator('#zip-file-input');
    await expect(zipInput).toBeVisible();
    await expect(zipInput).toHaveAttribute('name', 'zip_file');
    await expect(page.locator('#tab-zip')).toContainText('1, 2, 10');

    await page.getByRole('button', { name: /Ảnh rời/i }).click();
    const imageInput = page.locator('#images-input');
    await imageInput.setInputFiles([
      { name: '10.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('page-10') },
      { name: '2.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('page-2') },
      { name: '1.jpg', mimeType: 'image/jpeg', buffer: Buffer.from('page-1') },
    ]);

    const fileNames = page.locator('[data-testid="preview-file-name"]');
    await expect(fileNames).toHaveCount(3);
    await expect.poll(async () => fileNames.allTextContents()).toEqual(['1.jpg', '2.jpg', '10.jpg']);
    await expect(page.locator('[data-testid="preview-page-label"]')).toHaveText(['Trang 1', 'Trang 2', 'Trang 3']);

    await page.locator('#btn-auto-sort').click();
    await expect.poll(async () => fileNames.allTextContents()).toEqual(['1.jpg', '2.jpg', '10.jpg']);
    await expectNoDocumentOverflow(page);

    expect(pageErrors).toEqual([]);
  });

  test('desktop live search supports Vietnamese without accents', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop live-search UI is intentionally hidden on mobile.');

    const pageErrors = watchPageErrors(page);
    await gotoApp(page, '/');

    const search = page.locator('#search-input');
    await search.fill('thang cap');
    await expect(page.locator('#search-dropdown')).toHaveClass(/visible/);
    await expect(page.locator('#search-dropdown')).toContainText('Solo Leveling', { timeout: 10_000 });

    await page.locator('#search-dropdown a[href*="/truyen/solo-leveling"]').first().click();
    await page.waitForURL(/\/truyen\/solo-leveling$/);
    await expect(page.getByRole('heading', { name: /Solo Leveling/i }).first()).toBeVisible();

    expect(pageErrors).toEqual([]);
  });
});
