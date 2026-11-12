const { test, expect } = require('@playwright/test');

async function gotoApp(page, path) {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  expect(response, `No response received for ${path}`).not.toBeNull();
  expect(response.status(), `Unexpected HTTP status for ${path}`).toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
  return response;
}

test.describe('Reader convenience & smoothness', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Desktop reader shortcuts and paged navigation');
  });

  test('hint bar matches real keys, H hides chrome, and the hint can be dismissed', async ({ page }) => {
    await gotoApp(page, '/truyen/solo-leveling/chapter-1');

    const hint = page.locator('#reader-hint-text');
    await expect(hint).toContainText('H');
    await expect(hint).toContainText('M');

    await page.keyboard.press('h');
    await expect(page.locator('body')).toHaveClass(/ui-hidden/);
    await page.keyboard.press('h');
    await expect(page.locator('body')).not.toHaveClass(/ui-hidden/);

    await page.locator('#reader-hint-dismiss').click();
    await expect(page.locator('#reader-hint-bar')).toBeHidden();

    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('#reader-hint-bar')).toBeHidden();
  });

  test('last page waits for the reader when auto-advance is off', async ({ page }) => {
    await gotoApp(page, '/truyen/reader-performance/chapter-1');

    await page.locator('#btn-open-settings').click();
    await expect(page.locator('#btn-auto-advance-off')).toHaveClass(/active/);
    await page.locator('#btn-mode-single').click();
    await page.keyboard.press('Escape');

    await page.evaluate(() => window.showPage(9999));
    await expect(page.locator('#dock-page-counter')).toHaveText(/^14 \/ 14$/);

    await page.locator('#dock-btn-page-next').click();

    const toast = page.locator('#reader-chapter-end-toast');
    await expect(toast).toBeVisible();
    await expect(page.locator('#reader-chapter-end-next')).toHaveAttribute('href', /\/chapter-2$/);
    await expect(page).toHaveURL(/\/chapter-1(?:#|$)/);

    await page.locator('#reader-chapter-end-next').click();
    await page.waitForURL(/\/chapter-2(?:#|$)/);
  });

  test('enabling auto-advance still moves to the next chapter at the last page', async ({ page }) => {
    await gotoApp(page, '/truyen/reader-performance/chapter-1');

    await page.locator('#btn-open-settings').click();
    await page.locator('#btn-open-advanced-modal').click();
    await page.locator('#tab-btn-behaviors').click();
    await page.locator('#btn-auto-advance-on').click();
    await page.locator('#btn-close-advanced-modal').click();
    await page.locator('#btn-mode-single').click();
    await page.keyboard.press('Escape');

    await page.evaluate(() => window.showPage(9999));
    await expect(page.locator('#dock-page-counter')).toHaveText(/^14 \/ 14$/);

    await page.locator('#dock-btn-page-next').click();
    await page.waitForURL(/\/chapter-2(?:#|$)/);
  });

  test('comic detail keeps the server-rendered chapter list while hydrating', async ({ page }) => {
    await page.addInitScript(() => {
      window.__chapterListCleared = false;
      window.__serverRowCount = null;
      document.addEventListener('DOMContentLoaded', () => {
        const list = document.getElementById('chapter-list');
        if (!list) return;
        window.__serverRowCount = list.querySelectorAll('.chapter-row').length;
        new MutationObserver(() => {
          if (!list.querySelectorAll('.chapter-row').length) window.__chapterListCleared = true;
        }).observe(list, { childList: true });
      });
    });

    // reader-performance is created exclusively by ReaderE2ESeeder, so other specs
    // cannot add chapters to it and change the server-rendered row count.
    await gotoApp(page, '/truyen/reader-performance');

    const rows = page.locator('#chapter-list .chapter-row');
    await expect(rows.first()).toBeVisible();

    const serverRowCount = await page.evaluate(() => window.__serverRowCount);
    expect(serverRowCount).toBeGreaterThan(0);

    await expect(rows).toHaveCount(serverRowCount);
    await expect(page.locator('.chapter-catalog-status'))
      .toContainText(new RegExp(`Hiển thị ${serverRowCount}/${serverRowCount} chapter`));
    await expect(page.locator('#chapter-list .roadmap-empty-state')).toHaveCount(0);

    expect(await page.evaluate(() => window.__chapterListCleared)).toBe(false);
  });

  test('guest reading position is stored without writing on every scroll event', async ({ page }) => {
    await gotoApp(page, '/truyen/reader-performance/chapter-1');

    await page.evaluate(() => window.scrollTo(0, Math.floor(document.body.scrollHeight * 0.7)));
    await page.waitForTimeout(1200);

    const percent = await page.evaluate(() => {
      const list = JSON.parse(localStorage.getItem('webcomics_guest_history') || '[]');
      return list[0]?.percent ?? 0;
    });

    expect(percent).toBeGreaterThan(0);

    const before = await page.evaluate(() => JSON.parse(localStorage.getItem('webcomics_guest_history') || '[]')[0]?.time ?? 0);
    await page.evaluate(() => window.scrollTo(0, window.scrollY + 5));
    const immediatelyAfter = await page.evaluate(() => JSON.parse(localStorage.getItem('webcomics_guest_history') || '[]')[0]?.time ?? 0);
    expect(immediatelyAfter).toBe(before);
  });
});
