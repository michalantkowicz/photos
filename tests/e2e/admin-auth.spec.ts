import { test, expect } from '@playwright/test';
import { ADMIN_PASSWORD, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('admin: unauthenticated visit shows password form', async ({ page }) => {
  await page.goto('/admin');
  await expect(page.locator('input[name="password"]')).toBeVisible();
  // Dashboard markers must NOT be present yet.
  await expect(page.locator('a.navbar-brand')).toHaveCount(0);
});

test('admin: wrong password keeps form with invalid feedback', async ({ page }) => {
  await page.goto('/admin');
  await page.fill('input[name="password"]', 'definitely-wrong');
  await page.click('button[type="submit"]');
  await expect(page.locator('input.is-invalid[name="password"]')).toBeVisible();
});

test('admin: correct password reveals dashboard', async ({ page }) => {
  await page.goto('/admin');
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');

  // Dashboard markers: navbar header, sessions table, "Dodaj nową sesję" accordion
  await expect(page.locator('a.navbar-brand')).toContainText('Panel administratora');
  await expect(page.getByRole('button', { name: /Sesje zdjęciowe/ })).toBeVisible();
  await expect(page.getByRole('button', { name: /Dodaj nową sesję/ })).toBeVisible();
});

test('admin: rate-limiting returns 429 after 5 failed attempts', async ({ page }) => {
  // Make 5 failed attempts directly via POST (faster than browser nav)
  for (let i = 0; i < 5; i++) {
    await page.request.post('/admin.php', { form: { password: 'wrong' } });
  }
  // The 6th attempt must be blocked
  const r = await page.request.post('/admin.php', { form: { password: 'wrong' } });
  expect(r.status()).toBe(429);
});

test('admin: logout clears authentication', async ({ page }) => {
  await page.goto('/admin');
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  await expect(page.locator('a.navbar-brand')).toBeVisible();

  await page.goto('/admin?logout');
  await expect(page.locator('input[name="password"]')).toBeVisible();
  await expect(page.locator('a.navbar-brand')).toHaveCount(0);

  // Reopening /admin must still require re-login (session was destroyed).
  await page.goto('/admin');
  await expect(page.locator('input[name="password"]')).toBeVisible();
});
