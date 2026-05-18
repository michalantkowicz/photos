import { test, expect } from '@playwright/test';
import { FIXTURE_NO_PWD, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('public gallery: opens without password and renders 6 photos', async ({ page }) => {
  await page.goto('/' + FIXTURE_NO_PWD.url);

  // No password gate
  await expect(page.locator('input[name="password"]')).toHaveCount(0);

  // Session name visible in navbar
  await expect(page.locator('a.navbar-brand')).toContainText(FIXTURE_NO_PWD.name);

  // 6 photo cards with checkboxes
  const checkboxes = page.locator('input[name="chosenImages[]"]');
  await expect(checkboxes).toHaveCount(6);
  // None should be pre-checked on a fresh DB
  await expect(checkboxes.first()).not.toBeChecked();

  // Counter shows 0
  await expect(page.locator('#chosenImagesCount')).toHaveText('0');
});

test('public gallery: unknown session prints error message', async ({ page }) => {
  // Current behavior: the bare /sesja/X handler die()s with a message when
  // no row matches, so the response is 200 + plaintext body. Captured as a
  // regression so any future change is intentional.
  const response = await page.goto('/sesja/this-session-does-not-exist');
  expect(response?.status()).toBe(200);
  await expect(page.locator('body')).toContainText('Unexpected error');
});
