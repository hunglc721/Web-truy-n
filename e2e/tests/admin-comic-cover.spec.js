const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

async function loginAsAdmin(page) {
  await page.goto('/login');
  await page.locator('#login-email').fill('admin@webcomics.com');
  await page.locator('#login-password').fill('12345678');
  await page.getByRole('button', { name: /Đăng Nhập/i }).click();
  await page.waitForURL((url) => !url.pathname.includes('/login'));
}

test.describe('Admin comic cover management', () => {
  test('admin can upload new cover image, view preview, save, see in list with HTTP 200, and reopen edit', async ({ page, request }, testInfo) => {
    test.skip(testInfo.project.name.includes('mobile'), 'Admin cover management is covered on desktop Chromium.');

    await loginAsAdmin(page);

    // 1. Tạo một bộ truyện test mới để không ảnh hưởng dữ liệu truyện thật
    const testTitle = `Cover E2E Test ${Date.now()}`;
    await page.goto('/admin/comics/create');
    await page.locator('#title').fill(testTitle);
    await page.locator('input[name="genre_ids[]"]').first().check();
    await page.getByRole('button', { name: /Đăng Bộ Truyện/i }).click();
    await page.waitForURL(/\/admin\/comics/);

    // 2. Tìm truyện vừa tạo và click Sửa
    const row = page.locator('table.admin-table tbody tr', { hasText: testTitle });
    await expect(row).toBeVisible();
    await row.getByRole('link', { name: /Sửa/i }).click();
    await page.waitForURL(/\/admin\/comics\/\d+\/edit/);

    // 3. Chuẩn bị file ảnh fixture (1x1 PNG hợp lệ)
    const fixtureDir = path.join(__dirname, '..', 'fixtures');
    if (!fs.existsSync(fixtureDir)) {
      fs.mkdirSync(fixtureDir, { recursive: true });
    }
    const fixturePath = path.join(fixtureDir, 'test-cover.png');
    const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    fs.writeFileSync(fixturePath, Buffer.from(pngBase64, 'base64'));

    // 4. Upload fixture ảnh mới
    await page.locator('#cover_image').setInputFiles(fixturePath);

    // 5. Kiểm tra preview xuất hiện (FileReader đã load)
    const previewImg = page.locator('#cover-preview-box img#cover-img-preview');
    await expect(previewImg).toBeVisible();
    await expect(previewImg).toHaveAttribute('src', /^data:image\//);

    // 6. Bấm Lưu Thay Đổi
    await page.getByRole('button', { name: /Lưu Thay Đổi/i }).click();

    // 7. Quay về danh sách
    await page.waitForURL(/\/admin\/comics/);

    // 8. Kiểm tra ảnh có src hợp lệ (chứa /storage/comics/covers/)
    const updatedRow = page.locator('table.admin-table tbody tr', { hasText: testTitle });
    await expect(updatedRow).toBeVisible();
    const coverImgInList = updatedRow.locator('td img');
    await expect(coverImgInList).toBeVisible();
    const imgSrc = await coverImgInList.getAttribute('src');
    expect(imgSrc).toContain('/storage/comics/covers/');
    expect(imgSrc).not.toContain('/storage/storage/');

    // 9. Request ảnh trả HTTP 200
    const imgResponse = await request.get(imgSrc);
    expect(imgResponse.status()).toBe(200);

    // 10. Mở edit lại -> cover mới vẫn hiển thị
    await updatedRow.getByRole('link', { name: /Sửa/i }).click();
    await page.waitForURL(/\/admin\/comics\/\d+\/edit/);

    const editReopenedImg = page.locator('#cover-preview-box img#cover-img-preview');
    await expect(editReopenedImg).toBeVisible();
    const editImgSrc = await editReopenedImg.getAttribute('src');
    expect(editImgSrc).toContain('/storage/comics/covers/');

    // Cleanup: Xóa truyện test vừa tạo
    await page.goto('/admin/comics');
    const rowToDelete = page.locator('table.admin-table tbody tr', { hasText: testTitle });
    if (await rowToDelete.count() > 0) {
      page.once('dialog', dialog => dialog.accept());
      await rowToDelete.getByRole('button', { name: /Xóa/i }).click();
    }
  });
});
