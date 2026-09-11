const { test, expect } = require('@playwright/test');

async function gotoApp(page, path) {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  expect(response, `No response received for ${path}`).not.toBeNull();
  expect(response.status(), `Unexpected HTTP status for ${path}`).toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
}

async function login(page, email) {
  await gotoApp(page, '/login');
  await page.locator('#login-email').fill(email);
  await page.locator('#login-password').fill('12345678');
  await page.getByRole('button', { name: /Đăng Nhập/i }).click();
}

test('admin multi-chapter folder uploader parses chapter folders without loading image previews', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  const pageErrors = [];
  page.on('pageerror', (error) => pageErrors.push(error.message));

  await login(page, 'admin@webcomics.com');
  await page.waitForURL(/\/admin(?:\/|$)/);
  await gotoApp(page, '/admin/comics/1/chapters/create');

  await page.getByRole('button', { name: /Folder nhiều Chapter/i }).click();

  const folderInput = page.locator('#bulk-folder-input');
  await expect(folderInput).toHaveAttribute('webkitdirectory', '');
  await expect(page.locator('#tab-bulk-folder')).toContainText('2.8GB');
  await expect(page.locator('#tab-bulk-folder')).toContainText('SHA-256');

  await page.evaluate(() => {
    const input = document.getElementById('bulk-folder-input');
    const dt = new DataTransfer();
    const definitions = [
      ['10.jpg', 'Kimetsu/Vol.16 Ch.0140 - Opening the Decisive Battle (en)/10.jpg'],
      ['2.jpg', 'Kimetsu/Vol.16 Ch.0140 - Opening the Decisive Battle (en)/2.jpg'],
      ['1.jpg', 'Kimetsu/Vol.17 Ch.0141 - Revenge (en)/1.jpg'],
    ];

    definitions.forEach(([name, relativePath], index) => {
      const file = new File([new Uint8Array([index + 1, 2, 3, 4])], name, { type: 'image/jpeg' });
      Object.defineProperty(file, 'webkitRelativePath', {
        configurable: true,
        value: relativePath,
      });
      dt.items.add(file);
    });

    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  const rows = page.locator('[data-testid="bulk-chapter-row"]');
  await expect(rows).toHaveCount(2);
  await expect.poll(async () => {
    return page.locator('[data-testid="bulk-chapter-number"]').evaluateAll((nodes) => nodes.map((node) => node.value));
  }).toEqual(['140', '141']);
  await expect(page.locator('[data-testid="bulk-chapter-title"]').first()).toHaveValue('Opening the Decisive Battle');
  await expect(page.locator('#bulk-summary-chapters')).toHaveText('2');
  await expect(page.locator('#bulk-summary-pages')).toHaveText('3');
  await expect(page.locator('#bulk-validation-message')).toContainText('Sẵn sàng upload 2 chapter');
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();

  const overflow = await page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));
  expect(overflow.scrollWidth).toBeLessThanOrEqual(overflow.viewport + 2);
  expect(pageErrors).toEqual([]);
});
