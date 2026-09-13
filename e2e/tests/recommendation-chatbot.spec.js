const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, baseURL }) => {
  // Covers/fonts/analytics must not make this test depend on the internet.
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin
    ? route.continue() : route.abort());
  await page.goto('/about');
  await expect(page.getByRole('button', { name: 'Mở trợ lý tìm truyện' })).toContainText('Tìm truyện cho tôi');
  await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
});

test('natural request shows loading, excludes Harem, and opens the real comic', async ({ page }) => {
  const panel = page.getByRole('dialog', { name: 'Trợ lý tìm truyện' });
  const message = 'T muốn fantasy main bá không harem';
  let release;
  const gate = new Promise(resolve => { release = resolve; });
  // Forward to the real backend, holding delivery only until loading is asserted.
  await page.route('**/api/recommendation/chat', async route => {
    const response = await route.fetch();
    await gate;
    await route.fulfill({ response });
  });
  await panel.getByLabel('Bạn muốn đọc truyện gì?').fill(message);
  const reply = page.waitForResponse('**/api/recommendation/chat');
  await panel.getByRole('button', { name: 'Gửi', exact: true }).click();
  try {
    await expect(panel.getByRole('status')).toBeVisible();
    await expect(panel.getByRole('button', { name: 'Gửi', exact: true })).toBeDisabled();
    await expect(panel.getByText(message, { exact: true })).toHaveCount(1);
  } finally { release(); }
  const response = await reply;
  expect(response.ok()).toBe(true);
  const body = await response.json();
  expect(body.type).toBe('recommendations');
  expect(body.preferences.exclude).toEqual(['Harem']);
  await expect(panel.getByRole('status')).toBeHidden();
  await expect(panel.getByText(message, { exact: true })).toHaveCount(1);
  const card = panel.getByRole('article').filter({ has: page.getByRole('heading', { name: 'Chatbot Comic A' }) });
  await expect(card).toBeVisible();
  await expect(panel.getByRole('heading', { name: 'Chatbot Comic B' })).toHaveCount(0);
  await expect(card.getByText(/Fantasy/)).toBeVisible();
  await expect.poll(() => card.locator('img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
  await card.getByRole('link', { name: 'Xem truyện' }).click();
  await expect(page).toHaveURL(/\/truyen\/chatbot-comic-a$/);
  await expect(page).toHaveTitle(/Chatbot Comic A/);
});

test('question, quick reply and reload reuse the guest conversation', async ({ page }) => {
  const panel = page.getByRole('dialog', { name: 'Trợ lý tìm truyện' });
  await panel.getByLabel('Bạn muốn đọc truyện gì?').fill('tìm truyện hay');
  let reply = page.waitForResponse('**/api/recommendation/chat');
  await panel.getByRole('button', { name: 'Gửi', exact: true }).click();
  const question = await (await reply).json();
  expect(question.type).toBe('question');
  await expect(panel.getByText(question.message, { exact: true })).toBeVisible();
  reply = page.waitForResponse('**/api/recommendation/chat');
  await panel.getByRole('button', { name: 'Fantasy', exact: true }).click();
  expect((await (await reply).json()).preferences.genres).toEqual(['Fantasy']);
  await expect(panel.getByRole('status')).toBeHidden();
  await page.reload();
  await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
  reply = page.waitForResponse('**/api/recommendation/chat');
  await panel.getByRole('button', { name: 'Main OP', exact: true }).click();
  const response = await reply;
  expect(response.request().postDataJSON()).toEqual({ quick_reply: 'Main OP', conversation_token: question.conversation_token });
  const body = await response.json();
  expect(body.conversation_token).toBe(question.conversation_token);
  expect(body.preferences.genres).toEqual(['Fantasy']);
  expect(body.preferences.character_traits).toEqual(['Overpowered MC']);
  await expect(panel.getByRole('heading', { name: 'Chatbot Comic A' })).toBeVisible();
});
