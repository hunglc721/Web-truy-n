const { test, expect } = require('@playwright/test');
const path = require('node:path');

test('admin branding previews, persists across layouts and reload, and restores defaults', async ({ page }) => {
  await page.goto('/login');
  await page.locator('#login-email').fill('admin@webcomics.com');
  await page.locator('#login-password').fill('12345678');
  await page.getByRole('button', { name: /Đăng Nhập/i }).click();
  await page.waitForURL(/\/admin(?:\/|$)/);
  await page.goto('/admin/settings');

  // This journey belongs on a seeded E2E database, never an existing branded site.
  await expect(page.locator('#site_logo-preview')).toHaveAttribute('src', /\/images\/default-brand\.svg$/);
  await expect(page.locator('#site_favicon-preview')).toHaveAttribute('src', /\/images\/default-brand\.svg$/);
  const original = await page.locator('#settings-form').evaluate(form => Object.fromEntries(
    [...new FormData(form)].filter(([, value]) => typeof value === 'string')
  ));
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));

  try {
    await page.locator('[name="site_name"]').fill('Comicx Branding E2E');
    await page.locator('#site_logo').setInputFiles(path.join(__dirname, '../fixtures/test-cover.png'));
    await expect(page.locator('#site_logo-preview')).toHaveAttribute('src', /^data:image\/png;base64,/);
    await page.locator('#settings-form button[type="submit"]').click();
    await expect(page.locator('.sidebar-brand img')).toHaveAttribute('src', /\/branding\/logo\/.+\.png$/);
    const logo = await page.locator('.sidebar-brand img').getAttribute('src');
    await expect(page).toHaveTitle('Cài Đặt Website — Comicx Branding E2E');
    await expect(page.locator('link[rel="icon"]')).toHaveAttribute('href', logo);
    await expect(page.locator('.sidebar-brand-text')).toHaveText('Comicx Branding E2E');
    await expect.poll(() => page.locator('#site_logo-preview').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);

    await page.locator('#site_favicon').setInputFiles(path.join(__dirname, '../fixtures/test-cover.png'));
    await expect(page.locator('#site_favicon-preview')).toHaveAttribute('src', /^data:image\/png;base64,/);
    await page.locator('#settings-form button[type="submit"]').click();
    const favicon = await page.locator('link[rel="icon"]').getAttribute('href');
    expect(favicon).toMatch(/\/branding\/favicon\/.+\.png$/);
    expect(favicon).not.toBe(logo);
    await expect(page.locator('.sidebar-brand img')).toHaveAttribute('src', logo);

    await page.goto('/');
    await expect(page.locator('#site-header .logo-icon img')).toHaveAttribute('src', logo);
    await expect(page.locator('#site-header .logo-text')).toHaveText('Comicx Branding E2E');
    await expect(page.locator('link[rel="icon"]')).toHaveAttribute('href', favicon);
    await expect.poll(() => page.locator('#site-header .logo-icon img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    expect((await page.request.get(favicon)).ok()).toBe(true);
    await page.reload();
    await expect(page.locator('#site-header .logo-icon img')).toHaveAttribute('src', logo);
    await expect(page.locator('link[rel="icon"]')).toHaveAttribute('href', favicon);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    expect(errors).toEqual([]);
  } finally {
    const response = await page.request.post('/admin/settings', {
      form: { ...original, remove_site_logo: '1', remove_site_favicon: '1' },
    });
    expect(response.ok()).toBe(true);
  }

  await page.goto('/admin/settings');
  await expect(page.locator('.sidebar-brand-icon')).toHaveText('WC');
  await expect(page.locator('#site_logo-preview')).toHaveAttribute('src', /\/images\/default-brand\.svg$/);
  await page.goto('/');
  await expect(page.locator('#site-header .logo-icon svg')).toBeVisible();
  await expect(page.locator('link[rel="icon"]')).toHaveAttribute('href', /\/images\/default-brand\.svg$/);
});
