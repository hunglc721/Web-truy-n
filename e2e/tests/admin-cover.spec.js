const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

test.describe('Admin Comic Cover Update Flow', () => {
  // Desktop only since local file input is desktop admin workflow
  test('admin can upload new cover, preview, save, and verify rendered on edit and public pages', async ({ page }, testInfo) => {
    if (testInfo.project.name.includes('mobile')) {
      test.skip();
    }

    // 1. Login Admin
    await page.goto('/login');
    await page.locator('#login-email').fill('admin@webcomics.com');
    await page.locator('#login-password').fill('12345678');
    await page.getByRole('button', { name: /Đăng Nhập/i }).click();
    await page.waitForURL((url) => !url.pathname.includes('/login'));

    // 2. Open Admin Comics List and click Edit on the first comic
    await page.goto('/admin/comics');
    await expect(page.locator('h1.admin-page-title')).toBeVisible();

    const editButton = page.locator('a[href*="/admin/comics/"][href*="/edit"]').first();
    await expect(editButton).toBeVisible();
    const editUrl = await editButton.getAttribute('href');
    await editButton.click();

    // Verify on edit page
    await expect(page.locator('#cover-preview-box')).toBeVisible();

    // Create a temporary PNG image for test
    const tempDir = path.join(__dirname, '..', 'temp');
    if (!fs.existsSync(tempDir)) {
      fs.mkdirSync(tempDir, { recursive: true });
    }
    const testImagePath = path.join(tempDir, 'test-cover.png');
    // Minimal valid 1x1 PNG
    const pngBuffer = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');
    fs.writeFileSync(testImagePath, pngBuffer);

    // 3. Select new cover image
    const fileInput = page.locator('input#cover_image');
    await fileInput.setInputFiles(testImagePath);

    // 4. Preview should update immediately via FileReader
    const previewImg = page.locator('#cover-img-preview');
    await expect(previewImg).toBeVisible();
    await expect(previewImg).toHaveAttribute('src', /^data:image\//);

    // 5. Submit form
    await page.getByRole('button', { name: /Lưu Thay Đổi/i }).click();

    // Should redirect back to admin comics list with success
    await page.waitForURL(/\/admin\/comics/);
    await expect(page.locator('.alert-success, .admin-alert-success, [style*="color:var(--admin-primary)"]').first()).toBeVisible();

    // 6. Reload/revisit edit page
    await page.goto(editUrl);
    await expect(page.locator('#cover-preview-box')).toBeVisible();

    // 7. Verify new image renders successfully
    const savedEditCover = page.locator('#cover-preview-box img#cover-img-preview');
    await expect(savedEditCover).toBeVisible();
    const editSrc = await savedEditCover.getAttribute('src');
    expect(editSrc).toMatch(/^\/storage\/comics\/covers\/.*\.png$/);

    // Verify image is loaded and not broken
    const isImageLoaded = await savedEditCover.evaluate((img) => img.complete && img.naturalWidth > 0);
    expect(isImageLoaded).toBe(true);

    // 8. Open public comic detail link from edit page
    const publicLink = page.locator('a[href*="/truyen/"]').first();
    const publicHref = await publicLink.getAttribute('href');
    await page.goto(publicHref);

    // 9. Verify public cover is also the new image and renders
    const publicCover = page.locator('.spotlight-cover img.cover-img');
    await expect(publicCover).toBeVisible();
    const publicSrc = await publicCover.getAttribute('src');
    expect(publicSrc).toBe(editSrc);

    // 10. Verify no broken image on public page
    const isPublicImageLoaded = await publicCover.evaluate((img) => img.complete && img.naturalWidth > 0);
    expect(isPublicImageLoaded).toBe(true);

    // Clean up temp file
    try {
      fs.unlinkSync(testImagePath);
    } catch (_) {}
  });
});
