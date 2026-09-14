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
  expect(body.message).toContain('Harem');
  expect(body.quick_replies).not.toContain('Main OP');
  await expect(panel.getByText(body.follow_up_message, { exact: true })).toBeVisible();
  const order = await panel.locator('#recommendation-chat-messages').evaluate(log => [...log.children].map(node => node.dataset.type || (node.tagName === 'ARTICLE' ? 'card' : 'greeting')));
  expect(order.indexOf('card')).toBeGreaterThan(order.indexOf('text', 2));
  expect(order.indexOf('follow_up')).toBeGreaterThan(order.lastIndexOf('card'));
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
  await expect(panel.getByText('tìm truyện hay', { exact: true })).toHaveCount(1);
  await expect(panel.getByText(question.message, { exact: true })).toHaveCount(1);
  reply = page.waitForResponse('**/api/recommendation/chat');
  await panel.getByRole('button', { name: 'Main OP', exact: true }).click();
  const response = await reply;
  expect(response.request().postDataJSON()).toEqual({ quick_reply: 'Main OP', conversation_token: question.conversation_token });
  const body = await response.json();
  expect(body.conversation_token).toBe(question.conversation_token);
  expect(body.preferences.genres).toEqual(['Fantasy']);
  expect(body.preferences.character_traits).toEqual(['Overpowered MC']);
  await expect(panel.getByRole('heading', { name: 'Chatbot Comic A' })).toBeVisible();
  await expect(panel.getByText('Main OP', { exact: true })).toHaveCount(1);
  await expect(panel.getByRole('button', { name: 'Main OP', exact: true })).toHaveCount(0);
  await page.reload();
  await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
  await expect(panel.getByRole('heading', { name: 'Chatbot Comic A' })).toHaveCount(1);
  await expect(panel.getByText(body.message, { exact: true })).toHaveCount(1);
});

test('user and response HTML remain inert text after reload', async ({ page }) => {
  const payload = '<img src=x onerror=alert(1)>';
  await page.route('**/api/recommendation/chat', route => route.fulfill({ json: {
    type: 'question', conversation_token: 'a'.repeat(64), message: payload,
    recommendations: [], follow_up_message: '<script>alert(1)</script>', quick_replies: [],
  } }));
  await page.locator('#recommendation-chat-input').fill(payload);
  await page.locator('#recommendation-chat-input').press('Enter');
  await expect(page.locator('#recommendation-chat-messages').getByText(payload, { exact: true })).toHaveCount(2);
  await expect(page.locator('#recommendation-chat-messages img, #recommendation-chat-messages script')).toHaveCount(0);
  await page.reload();
  await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
  await expect(page.locator('#recommendation-chat-messages').getByText(payload, { exact: true })).toHaveCount(2);
  await expect(page.locator('#recommendation-chat-messages img, #recommendation-chat-messages script')).toHaveCount(0);
});

test('chat stays usable below the real header on short viewports', async ({ page }) => {
  const panel = page.getByRole('dialog', { name: 'Trợ lý tìm truyện' });
  for (const viewport of [{ width: 667, height: 375 }, { width: 393, height: 350 }]) {
    await page.setViewportSize(viewport);
    const header = await page.locator('#site-header').boundingBox();
    const box = await panel.boundingBox();
    expect(box.y).toBeGreaterThanOrEqual(header.y + header.height);
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(viewport.width);
    await panel.getByRole('button', { name: 'Đóng trợ lý tìm truyện' }).click();
    await expect(panel).toBeHidden();
    await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
    await panel.getByLabel('Bạn muốn đọc truyện gì?').fill('fantasy không harem');
    const response = page.waitForResponse('**/api/recommendation/chat');
    await panel.getByLabel('Bạn muốn đọc truyện gì?').press('Enter');
    expect((await response).ok()).toBe(true);
    await expect(panel.getByRole('status')).toBeHidden();
    await expect(panel.getByRole('heading', { name: 'Chatbot Comic A' }).last()).toBeVisible();
    await panel.getByRole('button', { name: 'Đóng trợ lý tìm truyện' }).click();
    await expect(panel).toBeHidden();
    await page.getByRole('button', { name: 'Mở trợ lý tìm truyện' }).click();
  }
});
