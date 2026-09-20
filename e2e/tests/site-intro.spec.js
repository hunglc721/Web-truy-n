const { test, expect } = require('@playwright/test');

const storageKey = 'comicx:site-intro:v1';

test('fresh homepage visit shows the Comicx intro once and skip unlocks the page', async ({ page }) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));

  const response = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(response?.status()).toBeLessThan(400);

  const intro = page.getByTestId('site-intro');
  await expect(intro).toBeVisible();
  await expect(intro).toHaveAttribute('data-state', 'visible');
  await expect(intro).toHaveAttribute('role', 'dialog');
  await expect(page.locator('body')).toHaveClass(/site-intro-open/);
  await expect(page.locator('#site-header')).toHaveAttribute('aria-hidden', 'true');
  await expect(intro.locator('.site-intro__title-brand')).not.toBeEmpty();
  await expect(page.getByRole('button', { name: 'Bỏ qua intro' })).toBeFocused();

  const viewport = await page.evaluate(() => ({
    width: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));
  expect(viewport.scrollWidth).toBeLessThanOrEqual(viewport.width + 1);

  await page.getByRole('button', { name: 'Bỏ qua intro' }).click();
  await expect(intro).toBeHidden();
  await expect(intro).toHaveAttribute('data-state', 'dismissed');
  await expect(page.locator('body')).not.toHaveClass(/site-intro-open/);
  await expect(page.locator('#site-header')).not.toHaveAttribute('aria-hidden', 'true');
  expect(await page.evaluate(key => sessionStorage.getItem(key), storageKey)).toBe('1');

  await page.locator('#site-header .logo-link').focus();
  await expect(page.locator('#site-header .logo-link')).toBeFocused();
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(intro).toBeHidden();
  await expect(intro).toHaveAttribute('data-state', 'dismissed');
  expect(pageErrors).toEqual([]);
});

test('intro auto-closes quickly for users who prefer reduced motion', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const intro = page.getByTestId('site-intro');
  await expect(intro).toBeHidden();
  await expect(intro).toHaveAttribute('data-state', 'dismissed');
  await expect(page.locator('body')).not.toHaveClass(/site-intro-open/);
  await expect(page.locator('#site-header')).toBeVisible();
  expect(await page.evaluate(key => sessionStorage.getItem(key), storageKey)).toBe('1');
});

test('intro auto-closes after the full animation and then starts the hero carousel', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Desktop coverage is enough for the shared timing logic.');

  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const intro = page.getByTestId('site-intro');
  const activeSlide = page.locator('#banner-carousel .banner-slide.active');
  const openingSlide = await activeSlide.getAttribute('data-slide-index');

  await page.waitForTimeout(2200);
  await expect(activeSlide).toHaveAttribute('data-slide-index', openingSlide);
  await expect(intro).toBeVisible();

  await expect(intro).toBeHidden({ timeout: 6000 });
  await expect(page.locator('#site-header .logo-link')).toBeFocused();
  await expect(activeSlide).not.toHaveAttribute('data-slide-index', openingSlide, { timeout: 3500 });
});
