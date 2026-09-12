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

test('bulk uploader detects decimal chapter numbers and sorts them correctly', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    {
      name: '1.gif',
      relativePath: 'Eleceed/Ch.187/1.gif',
    },
    {
      name: '1.gif',
      relativePath: 'Eleceed/Vol.16 Ch.187.5 - Bonus Chapter/1.gif',
    },
    {
      name: '1.gif',
      relativePath: 'Eleceed/Ch.187.25/1.gif',
    },
    {
      name: '1.gif',
      relativePath: 'Eleceed/Ch.188/1.gif',
    },
  ]);

  const rows = page.locator('[data-testid="bulk-chapter-row"]');
  await expect(rows).toHaveCount(4);

  await expect.poll(async () => {
    return page.locator('[data-testid="bulk-chapter-number"]').evaluateAll((nodes) => nodes.map((node) => node.value));
  }).toEqual(['187', '187.25', '187.5', '188']);

  const titles = await page.locator('[data-testid="bulk-chapter-title"]').evaluateAll((nodes) => nodes.map((node) => node.value));
  expect(titles[2]).toBe('Bonus Chapter');
});

test('bulk uploader detects generic high-precision decimal chapters: 90, 90.001, 90.01, 90.1, 90.12345, 91', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Series/Ch.91/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.90.12345/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.90.01/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.90/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.90.1/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.90.001/1.gif' },
  ]);

  const rows = page.locator('[data-testid="bulk-chapter-row"]');
  await expect(rows).toHaveCount(6);

  await expect.poll(async () => {
    return page.locator('[data-testid="bulk-chapter-number"]').evaluateAll((nodes) => nodes.map((node) => node.value));
  }).toEqual(['90', '90.001', '90.01', '90.1', '90.12345', '91']);

  // Verify no validation issues (no false duplicates)
  const validationBox = page.locator('#bulk-validation-message');
  await expect(validationBox).not.toContainText('xuất hiện');
});

test('admin can delete a page in inspector, which invalidates preflight until re-checked', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '10.gif', relativePath: 'Kimetsu/Vol.16 Ch.0140 - Battle/10.gif' },
    { name: '2.gif', relativePath: 'Kimetsu/Vol.16 Ch.0140 - Battle/2.gif' },
  ]);

  // Inspect Chapter 140
  await page.locator('[data-testid="bulk-chapter-inspect"]').click();
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('✓ Đạt');
  await expect(page.locator('[data-testid="bulk-preview-page"]')).toHaveCount(2);

  // Delete first page
  await page.locator('.bulk-btn-delete').first().click();

  // Page count decreases
  await expect(page.locator('[data-testid="bulk-preview-page"]')).toHaveCount(1);
  await expect(page.locator('#bulk-inspector-pages')).toHaveText('1');

  // Preflight status becomes unchecked and upload is disabled
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');
  await expect(page.locator('#bulk-inspector-status')).toHaveText('Chưa kiểm tra');
  await expect(page.locator('#bulk-start-upload')).toBeDisabled();

  // Re-inspect to pass
  await page.locator('#bulk-inspector-run').click();
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('✓ Đạt');
  await expect(page.locator('#bulk-inspector-status')).toHaveText('✓ Đạt');
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();
});

test('admin can open lightbox preview, navigate pages and close with Escape', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Series/Ch.01/1.gif' },
    { name: '2.gif', relativePath: 'Series/Ch.01/2.gif' },
  ]);

  await page.locator('[data-testid="bulk-chapter-inspect"]').click();

  // Click view icon on first page
  await page.locator('.bulk-btn-view').first().click();

  const lightbox = page.locator('#bulk-preflight-lightbox');
  await expect(lightbox).toBeVisible();
  await expect(page.locator('#bulk-lightbox-pos')).toHaveText('1 / 2');
  await expect(page.locator('#bulk-lightbox-title')).toHaveText('1.gif');

  // Next page via ArrowRight
  await page.keyboard.press('ArrowRight');
  await expect(page.locator('#bulk-lightbox-pos')).toHaveText('2 / 2');
  await expect(page.locator('#bulk-lightbox-title')).toHaveText('2.gif');

  // Close via Escape
  await page.keyboard.press('Escape');
  await expect(lightbox).toBeHidden();
});

test('admin can replace page, add new pages, reorder and reset back to original', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Manga/Ch.10/1.gif' },
    { name: '2.gif', relativePath: 'Manga/Ch.10/2.gif' },
  ]);

  await page.locator('[data-testid="bulk-chapter-inspect"]').click();
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('✓ Đạt');

  // Test REORDER via down button on first page
  await page.locator('.bulk-btn-down').first().click();
  await expect(page.locator('.bulk-preview-page-meta strong').first()).toContainText('2.gif');
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');

  // Test ADD pages
  const validGifBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
  await page.locator('#bulk-inspector-add-input').setInputFiles([
    {
      name: '3.gif',
      mimeType: 'image/gif',
      buffer: Buffer.from(validGifBase64, 'base64'),
    },
  ]);
  await expect(page.locator('[data-testid="bulk-preview-page"]')).toHaveCount(3);
  await expect(page.locator('#bulk-inspector-pages')).toHaveText('3');
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');

  // Test REPLACE
  await page.locator('.bulk-btn-replace').first().click();
  await page.locator('#bulk-inspector-replace-input').setInputFiles({
    name: 'replaced.gif',
    mimeType: 'image/gif',
    buffer: Buffer.from(validGifBase64, 'base64'),
  });
  await expect(page.locator('.bulk-preview-page-meta strong').first()).toContainText('replaced.gif');
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');

  // Test RESET
  await page.locator('#bulk-inspector-reset').click();
  await expect(page.locator('[data-testid="bulk-preview-page"]')).toHaveCount(2);
  await expect(page.locator('.bulk-preview-page-meta strong').first()).toContainText('1.gif');
  await expect(page.locator('.bulk-preview-page-meta strong').nth(1)).toContainText('2.gif');
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');
});

test('upload failure shows detailed error panel with chapter, batch, HTTP 422 and retry button', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Series/Ch.901/1.gif' },
  ]);

  // Inspect to enable upload
  await page.locator('[data-testid="bulk-chapter-inspect"]').click();
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();

  // Intercept backend bulk upload endpoint to return 422 on chunk
  await page.route('**/admin/comics/*/chapters', async (route) => {
    const postData = route.request().postData() || '';
    if (route.request().method() === 'POST' && postData.includes('chunk')) {
      await route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Checksum không khớp' }),
      });
    } else {
      await route.continue();
    }
  });

  await page.locator('#bulk-start-upload').click();

  // Assert error diagnostics panel is shown
  const errorPanel = page.locator('#bulk-error-panel');
  await expect(errorPanel).toBeVisible();
  await expect(page.locator('#bulk-error-chapter')).toContainText('901');
  await expect(page.locator('#bulk-error-batch')).toContainText('1 / 1');
  await expect(page.locator('#bulk-error-http')).toHaveText('422');
  await expect(page.locator('#bulk-error-message-text')).toContainText('Checksum không khớp');
  await expect(page.locator('#bulk-error-retry-btn')).toBeVisible();

  // Assert row in table shows failed batch
  await expect(page.locator('[data-testid="bulk-chapter-status"]')).toContainText('❌ Lỗi batch');

  // Assert upload button offers retry with context
  await expect(page.locator('#bulk-start-upload')).toContainText('↻ Thử lại');
});

test('upload handles HTTP 500 and network disconnection cleanly without JS crash', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  const pageErrors = [];
  page.on('pageerror', (err) => pageErrors.push(err.message));

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Series/Ch.902/1.gif' },
  ]);

  await page.locator('[data-testid="bulk-chapter-inspect"]').click();

  // Mock 500 error on chunk
  await page.route('**/admin/comics/*/chapters', async (route) => {
    const postData = route.request().postData() || '';
    if (route.request().method() === 'POST' && postData.includes('chunk')) {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Lỗi server khi ghi storage' }),
      });
    } else {
      await route.continue();
    }
  });

  await page.locator('#bulk-start-upload').click();

  const errorPanel = page.locator('#bulk-error-panel');
  await expect(errorPanel).toBeVisible();
  await expect(page.locator('#bulk-error-http')).toHaveText('500');
  await expect(page.locator('#bulk-error-message-text')).toContainText('Lỗi server khi ghi storage');

  // Test network failure
  await page.unroute('**/admin/comics/*/chapters');
  await page.route('**/admin/comics/*/chapters', async (route) => {
    const postData = route.request().postData() || '';
    if (route.request().method() === 'POST' && postData.includes('chunk')) {
      await route.abort('failed');
    } else {
      await route.continue();
    }
  });

  await page.locator('#bulk-error-retry-btn').click();
  await expect(page.locator('#bulk-error-message-text')).toContainText('Mất kết nối hoặc server không phản hồi');

  expect(pageErrors).toEqual([]);
});

test('retry continues from failed chapter without resetting already completed chapters', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name.includes('mobile'), 'Large directory picking is a desktop admin workflow.');

  await openBulkUploader(page);

  await setDirectoryFiles(page, [
    { name: '1.gif', relativePath: 'Series/Ch.920/1.gif' },
    { name: '1.gif', relativePath: 'Series/Ch.921/1.gif' },
  ]);

  await page.locator('#bulk-check-all').click();
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();

  let ch2ChunkFailedOnce = true;

  await page.route('**/admin/comics/*/chapters', async (route) => {
    const postData = route.request().postData() || '';
    if (route.request().method() === 'POST' && postData.includes('chapter-0001') && postData.includes('chunk') && ch2ChunkFailedOnce) {
      ch2ChunkFailedOnce = false;
      await route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Lỗi upload Ch.921 lần đầu' }),
      });
      return;
    }
    await route.continue();
  });

  await page.locator('#bulk-start-upload').click();

  // Chapter 1 is completed
  await expect(page.locator('[data-testid="bulk-chapter-status"]').first()).toContainText('✓ Xong');

  // Chapter 2 failed
  await expect(page.locator('[data-testid="bulk-chapter-status"]').nth(1)).toContainText('❌ Lỗi batch');

  // Error panel displays Chapter 921
  await expect(page.locator('#bulk-error-panel')).toBeVisible();
  await expect(page.locator('#bulk-error-chapter')).toContainText('921');
  await expect(page.locator('#bulk-start-upload')).toContainText('↻ Thử lại từ Chapter 921');

  // Retry upload
  await page.locator('#bulk-error-retry-btn').click();

  // Now both chapters are done! Chapter 1 was not re-done or reset to pending
  await expect(page.locator('[data-testid="bulk-chapter-status"]').first()).toContainText('✓ Xong');
  await expect(page.locator('[data-testid="bulk-chapter-status"]').nth(1)).toContainText('✓ Xong');
  await expect(page.locator('#bulk-start-upload')).toHaveText('✅ Upload hoàn tất');
});



