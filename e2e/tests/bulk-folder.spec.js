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

async function openBulkUploader(page) {
  await login(page, 'admin@webcomics.com');
  await page.waitForURL(/\/admin(?:\/|$)/);
  await gotoApp(page, '/admin/comics/1/chapters/create');
  await page.getByRole('button', { name: /Folder nhiều Chapter/i }).click();

  const folderInput = page.locator('#bulk-folder-input');
  await expect(folderInput).toHaveAttribute('webkitdirectory', '');
  await expect(page.locator('#tab-bulk-folder')).toContainText('2.8GB');
  await expect(page.locator('#tab-bulk-folder')).toContainText('SHA-256');

  return folderInput;
}

async function setDirectoryFiles(page, definitions) {
  await page.evaluate((items) => {
    const input = document.getElementById('bulk-folder-input');
    const dt = new DataTransfer();
    const validGifBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    const decodeBase64 = (value) => {
      const binary = atob(value);
      return Uint8Array.from(binary, (char) => char.charCodeAt(0));
    };

    items.forEach((item) => {
      const bytes = item.invalid
        ? new Uint8Array([1, 2, 3, 4, 5, 6])
        : decodeBase64(validGifBase64);

      const file = new File([bytes], item.name, { type: 'image/gif' });
      Object.defineProperty(file, 'webkitRelativePath', {
        configurable: true,
        value: item.relativePath,
      });
      dt.items.add(file);
    });

    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }, definitions);
}

test('admin can preview and verify every chapter before bulk upload is enabled', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  const pageErrors = [];
  page.on('pageerror', (error) => pageErrors.push(error.message));

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    {
      name: '10.gif',
      relativePath: 'Kimetsu/Vol.16 Ch.0140 - Opening the Decisive Battle (en)/10.gif',
    },
    {
      name: '2.gif',
      relativePath: 'Kimetsu/Vol.16 Ch.0140 - Opening the Decisive Battle (en)/2.gif',
    },
    {
      name: '1.gif',
      relativePath: 'Kimetsu/Vol.17 Ch.0141 - Revenge (en)/1.gif',
    },
  ]);

  const rows = page.locator('[data-testid="bulk-chapter-row"]');
  await expect(rows).toHaveCount(2);

  await expect.poll(async () => {
    return page.locator('[data-testid="bulk-chapter-number"]').evaluateAll((nodes) => nodes.map((node) => node.value));
  }).toEqual(['140', '141']);

  await expect(page.locator('[data-testid="bulk-chapter-title"]').first()).toHaveValue('Opening the Decisive Battle');
  await expect(page.locator('#bulk-summary-chapters')).toHaveText('2');
  await expect(page.locator('#bulk-summary-pages')).toHaveText('3');
  await expect(page.locator('#bulk-validation-message')).toContainText('Còn 2 chapter chưa kiểm tra ảnh');
  await expect(page.locator('#bulk-start-upload')).toBeDisabled();

  await page.locator('[data-testid="bulk-chapter-inspect"]').first().click();

  await expect(page.locator('#bulk-chapter-inspector')).toBeVisible();
  await expect(page.locator('#bulk-inspector-title')).toContainText('Chapter 140');
  await expect(page.locator('[data-testid="bulk-preview-page"]')).toHaveCount(2);
  await expect(page.locator('.bulk-preview-page-meta strong').nth(0)).toContainText('2.gif');
  await expect(page.locator('.bulk-preview-page-meta strong').nth(1)).toContainText('10.gif');
  await expect(page.locator('[data-testid="bulk-preflight-state"]').first()).toHaveText('✓ Đạt');
  await expect(page.locator('#bulk-inspector-errors')).toHaveText('0');
  await expect(page.locator('#bulk-start-upload')).toBeDisabled();

  await page.locator('#bulk-check-all').click();

  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText(['✓ Đạt', '✓ Đạt']);
  await expect(page.locator('#bulk-validation-message')).toContainText('Sẵn sàng upload 2 chapter');
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();

  const overflow = await page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));

  expect(overflow.scrollWidth).toBeLessThanOrEqual(overflow.viewport + 2);
  expect(pageErrors).toEqual([]);
});

test('pre-upload chapter check blocks upload when one image is corrupted', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    {
      name: '1.gif',
      relativePath: 'Kimetsu/Vol.18 Ch.0152 - The See-Through World/1.gif',
    },
    {
      name: '2.gif',
      relativePath: 'Kimetsu/Vol.18 Ch.0152 - The See-Through World/2.gif',
      invalid: true,
    },
  ]);

  await expect(page.locator('[data-testid="bulk-chapter-row"]')).toHaveCount(1);
  await page.locator('[data-testid="bulk-chapter-inspect"]').click();

  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('⚠ Có lỗi');
  await expect(page.locator('#bulk-inspector-status')).toHaveText('⚠ Có lỗi');
  await expect(page.locator('#bulk-inspector-errors')).toHaveText('1');
  await expect(page.locator('#bulk-inspector-message')).toContainText('Không thể giải mã ảnh');
  await expect(page.locator('.bulk-preview-page.is-error')).toHaveCount(1);
  await expect(page.locator('#bulk-start-upload')).toBeDisabled();
  await expect(page.locator('#bulk-validation-message')).toContainText('Không thể giải mã ảnh');
});
