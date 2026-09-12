const { test, expect } = require('@playwright/test');

async function login(page) {
  await page.goto('/login');
  await page.locator('#login-email').fill('admin@webcomics.com');
  await page.locator('#login-password').fill('12345678');
  await page.getByRole('button', { name: /Đăng Nhập/i }).click();
  await page.waitForURL(/\/admin(?:\/|$)/);
}

async function selectFolder(page, start, count) {
  await page.evaluate(({ start, count }) => {
    const dt = new DataTransfer();
    const bytes = Uint8Array.from(atob('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), c => c.charCodeAt(0));
    for (let chapter = start; chapter < start + count; chapter++) {
      for (let n = 0; n < 13; n++) {
        const file = new File([bytes], `${n}.gif`, { type: 'image/gif' });
        Object.defineProperty(file, 'webkitRelativePath', { value: `Fixture/Ch.${chapter}.25/${n}.gif` });
        dt.items.add(file);
      }
    }
    const input = document.getElementById('bulk-folder-input');
    input.files = dt.files; input.dispatchEvent(new Event('change', { bubbles: true }));
  }, { start, count });
  await expect(page.locator('#bulk-validation-message')).not.toContainText('Đang kiểm tra chapter trùng');
  await page.locator('#bulk-check-all').click();
  await expect(page.locator('#bulk-start-upload')).toBeEnabled({ timeout: 30000 });
}

async function snapshot(page, id) {
  const response = await page.request.get(`/admin/upload-tasks/${id}`);
  expect(response.ok()).toBeTruthy();
  return (await response.json()).task;
}

async function startUpload(page, context, count) {
  await login(page);
  await page.goto('/admin/comics/1/chapters/create');
  await page.locator('[data-target="tab-bulk-folder"]').click();
  const start = 100000 + Math.floor(Math.random() * 800000);
  await selectFolder(page, start, count);
  // Delay real multipart requests, never fabricate backend progress or responses.
  await context.route('**/admin/upload-tasks/*/upload', async route => {
    await new Promise(resolve => setTimeout(resolve, 350));
    await route.continue().catch(() => {});
  });
  const workerPromise = context.waitForEvent('page');
  await page.locator('#bulk-start-upload').click();
  const worker = await workerPromise;
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-task-id', /.+/);
  const id = await page.locator('#upload-task-widget').getAttribute('data-task-id');
  await expect.poll(async () => (await snapshot(page, id)).uploaded_bytes).toBeGreaterThan(0);
  return { id, worker, start };
}

test.afterEach(async ({ page }) => {
  if (page.isClosed()) return;
  await page.evaluate(async () => {
    const task = window.ComicxUploads?.task;
    if (task && !['completed', 'cancelled'].includes(task.status)) await window.ComicxUploads.api(`/admin/upload-tasks/${task.id}/control`, { action: 'cancel' });
  }).catch(() => {});
});

test('real worker keeps uploading across admin public reader navigation, return, F5 and new tab', async ({ page, context }, info) => {
  test.skip(info.project.name.includes('mobile'), 'Folder upload is a desktop admin workflow.');
  test.setTimeout(150000);
  const errors = [];
  context.on('page', p => p.on('pageerror', e => errors.push(e.message)));
  page.on('pageerror', e => errors.push(e.message));
  const { id, worker } = await startUpload(page, context, 32);
  const evidence = [];
  for (const path of ['/admin/reports', '/admin/genres', '/', '/truyen/reader-performance', '/truyen/reader-performance/chapter-1']) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    const before = (await snapshot(page, id)).uploaded_bytes;
    await expect.poll(async () => (await snapshot(page, id)).uploaded_bytes, { timeout: 15000 }).toBeGreaterThan(before);
    const after = (await snapshot(page, id)).uploaded_bytes;
    await expect.poll(async () => Number(await page.locator('#upload-task-widget').getAttribute('data-uploaded-bytes'))).toBeGreaterThanOrEqual(after);
    evidence.push({ path, before, after });
  }
  const beforeReturn = (await snapshot(page, id)).uploaded_bytes;
  await page.goto('/admin/comics/1/chapters/create');
  await expect(page.locator('#bulk-progress-wrap')).toBeVisible();
  await expect(page.locator('#bulk-folder-input')).toBeDisabled();
  expect(await page.locator('#bulk-folder-input').evaluate(input => input.files.length)).toBe(0);
  expect((await snapshot(page, id)).uploaded_bytes).toBeGreaterThanOrEqual(beforeReturn);
  await page.reload();
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-task-id', id);
  await expect(page.locator('#bulk-progress-text')).not.toHaveText('0%');
  const tab = await context.newPage();
  await tab.goto('/admin/comics/1/chapters/create');
  await expect(tab.locator('#upload-task-widget')).toHaveAttribute('data-task-id', id);
  await expect(tab.locator('#bulk-folder-input')).toBeDisabled();
  expect((await (await tab.request.get('/admin/upload-tasks/active?comic_id=1')).json()).task.id).toBe(id);
  expect(context.pages().filter(p => p.url().includes('/admin/upload-worker'))).toHaveLength(1);
  await page.goto('/truyen/reader-performance/chapter-1');
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-status', 'completed', { timeout: 100000 });
  const complete = await snapshot(page, id);
  expect(complete.completed_chapters).toBe(32);
  expect(complete.uploaded_files).toBe(32 * 13);
  expect(complete.uploaded_bytes).toBe(complete.total_bytes);
  const notifications = (await (await page.request.get('/user/notifications/header')).json()).notifications;
  expect(notifications.filter(n => n.data.task_id === id)).toHaveLength(1);
  expect(errors).toEqual([]);
  await info.attach('server-progress-after-navigation', { body: JSON.stringify({ id, evidence, complete }, null, 2), contentType: 'application/json' });
  console.log('SERVER_PROGRESS_AFTER_NAVIGATION', JSON.stringify(evidence));
  await worker.close();
});

test('closed worker retains receipts and resumes only missing files after reselect', async ({ page, context }, info) => {
  test.skip(info.project.name.includes('mobile'), 'Folder upload is a desktop admin workflow.');
  test.setTimeout(90000);
  const { id, worker, start } = await startUpload(page, context, 6);
  await worker.close();
  await expect.poll(async () => (await snapshot(page, id)).status, { timeout: 45000 }).toBe('waiting_for_client');
  const paused = await snapshot(page, id);
  expect(paused.uploaded_bytes).toBeGreaterThan(0);
  await page.goto('/admin/comics/1/chapters/create');
  await expect(page.locator('#bulk-validation-message')).toContainText('Chọn lại folder');
  expect((await snapshot(page, id)).uploaded_bytes).toBe(paused.uploaded_bytes);
  await selectFolder(page, start, 6);
  let uploadedFiles = 0;
  context.on('request', request => {
    if (request.url().includes(`/upload-tasks/${id}/upload`)) uploadedFiles += (request.postData()?.match(/name="files\[\]"/g) || []).length;
  });
  await page.locator('#bulk-start-upload').click();
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-status', 'completed', { timeout: 45000 });
  const complete = await snapshot(page, id);
  expect(complete.completed_chapters).toBe(6);
  expect(complete.uploaded_files).toBe(78);
  expect(uploadedFiles).toBe(78 - paused.uploaded_files);
});

test('worker receives final edited files and skips existing corrupt chapter without preflight', async ({ page, context }, info) => {
  test.skip(info.project.name.includes('mobile'), 'Folder upload is a desktop admin workflow.');
  test.setTimeout(60000);
  await login(page);
  await page.goto('/admin/comics/1/chapters/create');
  await page.locator('[data-target="tab-bulk-folder"]').click();
  const number = 100000 + Math.floor(Math.random() * 800000);
  await selectFolder(page, number, 1);
  await page.locator('[data-testid="bulk-chapter-inspect"]').click();
  await page.locator('.bulk-btn-down').first().click();
  await page.locator('.bulk-btn-replace').first().click();
  const bytes = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64');
  const edited = Buffer.concat([bytes, Buffer.from('edited-original-bytes')]);
  await page.locator('#bulk-inspector-replace-input').setInputFiles({ name: 'edited.gif', mimeType: 'image/gif', buffer: edited });
  await page.locator('#bulk-inspector-add-input').setInputFiles({ name: 'added.gif', mimeType: 'image/gif', buffer: bytes });
  await page.locator('.bulk-btn-delete').last().click();
  await page.locator('#bulk-inspector-close').click();
  await page.locator('#bulk-check-all').click();
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();
  let chunksSent = 0;
  context.on('request', request => {
    if (request.url().includes('/upload-tasks/') && request.url().endsWith('/upload') && request.postData()?.includes('chunk')) chunksSent++;
  });
  await page.locator('#bulk-start-upload').click();
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-status', 'completed', { timeout: 20000 });
  const id = await page.locator('#upload-task-widget').getAttribute('data-task-id');
  const done = await snapshot(page, id);
  expect(done.uploaded_bytes).toBe(bytes.length * 12 + edited.length);
  await page.goto('/truyen/' + done.comic.slug + '/chapter-' + number + '.25');
  const originals = await page.locator('.comic-page-img').evaluateAll(images => images.map(image => image.dataset.originalSrc));
  expect(originals).toHaveLength(13);
  expect(await (await page.request.get(originals[0])).body()).toEqual(edited);
  for (const url of originals.slice(1)) expect(await (await page.request.get(url)).body()).toEqual(bytes);
  await page.goto('/admin/comics/1/chapters/create');
  // Re-select a chapter which now exists, with deliberately invalid image data.
  await page.reload();
  await page.locator('[data-target="tab-bulk-folder"]').click();
  await page.evaluate(number => {
    const input = document.getElementById('bulk-folder-input'), dt = new DataTransfer();
    const file = new File([], 'invalid.gif', { type: 'image/gif' });
    Object.defineProperty(file, 'webkitRelativePath', { value: `Fixture/Ch.${number}.25/invalid.gif` });
    dt.items.add(file); input.files = dt.files; input.dispatchEvent(new Event('change', { bubbles: true }));
  }, number);
  await expect(page.locator('#bulk-start-upload')).toBeEnabled();
  await expect(page.locator('[data-testid="bulk-preflight-state"]')).toHaveText('Chưa kiểm tra');
  const before = chunksSent;
  await page.locator('#bulk-start-upload').click();
  await expect(page.locator('#upload-task-widget')).toHaveAttribute('data-status', 'completed');
  const skipped = await snapshot(page, await page.locator('#upload-task-widget').getAttribute('data-task-id'));
  expect(skipped.skipped_chapters).toBe(1);
  expect(skipped.total_bytes).toBe(0);
  expect(chunksSent).toBe(before);
});

test('blocked popup reports explicit retry without starting a page-owned upload', async ({ page }, info) => {
  test.skip(info.project.name.includes('mobile'), 'Folder upload is a desktop admin workflow.');
  await login(page);
  await page.goto('/admin/comics/1/chapters/create');
  await page.locator('[data-target="tab-bulk-folder"]').click();
  await selectFolder(page, 100000 + Math.floor(Math.random() * 800000), 1);
  await page.evaluate(() => { window.open = () => null; });
  await page.locator('#bulk-start-upload').click();
  await expect(page.locator('#bulk-validation-message')).toContainText('Popup bị chặn');
  await expect(page.locator('#bulk-start-upload')).toHaveText('Mở trình upload nền');
  expect((await (await page.request.get('/admin/upload-tasks/active')).json()).task).toBeNull();
});
