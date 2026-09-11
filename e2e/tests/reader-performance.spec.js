const { test, expect } = require('@playwright/test');

const readerUrl = '/truyen/reader-performance/chapter-1';

async function noOverlap(page) {
  const result = await page.locator('.reader-bottom-dock').evaluate(dock => {
    const bounds = dock.getBoundingClientRect();
    const children = [...dock.children].filter(el => el.getClientRects().length);
    const rectangles = children.map(el => el.getBoundingClientRect());
    const overlap = rectangles.some((a, i) => rectangles.slice(i + 1).some(b =>
      Math.min(a.right, b.right) - Math.max(a.left, b.left) > 1 &&
      Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top) > 1));
    return { overlap, left: bounds.left, right: bounds.right, viewport: innerWidth,
      overflow: dock.scrollWidth > dock.clientWidth + 1 };
  });
  expect(result.overlap).toBe(false);
  expect(result.overflow).toBe(false);
  expect(result.left).toBeGreaterThanOrEqual(0);
  expect(result.right).toBeLessThanOrEqual(result.viewport);
}

for (const width of [360, 390, 400, 430]) {
  test(`reader chooses small variants and usable controls at ${width}px high DPR`, async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width, height: 873 }, deviceScaleFactor: 3, isMobile: true, serviceWorkers: 'block' });
    const page = await context.newPage();
    const errors = [];
    const imageRequests = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('request', request => {
      if (request.resourceType() === 'image' && request.url().includes('/storage/comics/')) imageRequests.push(request.url());
    });
    await page.goto(readerUrl, { waitUntil: 'domcontentloaded' });
    const first = page.locator('.comic-page-img').first();
    await expect(first).toHaveAttribute('fetchpriority', 'high');
    await expect(first).toHaveAttribute('srcset', /480w.*800w.*1200w/);
    await expect.poll(() => first.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    expect(await first.evaluate(img => img.currentSrc)).toMatch(/\/480\/.*\.webp$/);
    await expect(page.locator('.comic-page-img').last()).toHaveAttribute('loading', 'lazy');
    await expect(page.locator('.comic-page-img').last()).not.toHaveAttribute('src');
    expect(imageRequests.every(url => url.includes('/reader/'))).toBe(true);
    expect(new Set(imageRequests).size).toBeLessThanOrEqual(6);
    expect(imageRequests.length).toBe(new Set(imageRequests).size);
    await noOverlap(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

    await page.locator('#reader-top-bar [data-open-chapter-picker]').click();
    const picker = page.locator('#reader-chapter-picker');
    await expect(picker).toBeVisible();
    await expect(picker.locator('[aria-current="page"]')).toHaveAttribute('href', /chapter-1$/);
    await page.locator('#reader-chapter-search').fill('kiểm thử 2');
    await expect(picker.locator('nav a:visible')).toHaveCount(1);
    await expect(picker.locator('nav a:visible')).toHaveAttribute('href', /chapter-2$/);
    expect(await picker.evaluate(el => getComputedStyle(el).colorScheme)).toBe('dark');
    await page.locator('#reader-picker-close').click();

    await page.locator('#page-6').scrollIntoViewIfNeeded();
    await expect.poll(() => page.locator('#page-6 img.comic-page-img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.locator('#btn-open-settings').click();
    await page.locator('#btn-mode-single').click();
    await page.locator('#btn-open-settings').click();
    await noOverlap(page);
    await page.locator('#dock-page-nav button').last().click();
    await expect(page.locator('#page-2')).toHaveClass(/active-page/);
    await page.locator('#btn-open-settings').click();
    await page.locator('#btn-mode-double').click();
    await page.locator('#btn-open-settings').click();
    await expect(page.locator('.active-page')).toHaveCount(2);
    await noOverlap(page);
    expect(errors).toEqual([]);
    await context.close();
  });
}

test('desktop responsive reader loads and falls back if a variant disappears', async ({ page, browser }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(readerUrl, { waitUntil: 'domcontentloaded' });
  const first = page.locator('.comic-page-img').first();
  await expect.poll(() => first.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
  expect(await first.evaluate(img => img.currentSrc)).toMatch(/\/reader\//);
  // A warm service-worker cache can legitimately serve a deleted variant. Use a
  // fresh uncached visit so this test really exercises the network-error path.
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, serviceWorkers: 'block' });
  try {
    const fallbackPage = await context.newPage();
    let blocked = 0;
    await fallbackPage.route('**/reader/**/*.webp', route => { blocked++; return route.abort(); });
    await fallbackPage.goto(readerUrl, { waitUntil: 'domcontentloaded' });
    const fallback = fallbackPage.locator('.comic-page-img').first();
    await expect.poll(() => fallback.evaluate(img => img.complete && img.naturalWidth > 0 && /\/1\.png$/.test(img.currentSrc))).toBe(true);
    expect(blocked).toBeGreaterThan(0);
    await expect(fallback).not.toHaveAttribute('srcset');
  } finally {
    await context.close();
  }
});
