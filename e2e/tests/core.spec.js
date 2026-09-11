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
    await expect(page.locator('#reader-top-bar .reader-chapter-select')).toHaveValue(/chapter-1$/);

    await page.locator('#btn-open-settings').click();
    await expect(page.locator('#reader-settings-panel')).toBeVisible();
    await page.locator('#btn-mode-single').click();
    await expect(page.locator('body')).toHaveClass(/reader-layout-single/);
    await expectNoDocumentOverflow(page);

    expect(pageErrors).toEqual([]);
  });

  test('member can login and reach personal library', async ({ page }) => {
    const pageErrors = watchPageErrors(page);
    await gotoApp(page, '/login');

    await page.locator('#login-email').fill('user@webcomics.com');
    await page.locator('#login-password').fill('12345678');
    await page.getByRole('button', { name: /Đăng Nhập/i }).click();

    await page.waitForURL(/\/user\/library(?:\?|$)/);
    await expect(page.locator('body')).toHaveAttribute('data-auth-state', 'member');
    await expect(page.locator('h1.library-title')).toHaveText('Tủ Truyện');
    await expect(page.locator('.library-shell')).toBeVisible();
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
