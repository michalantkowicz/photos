import { test, expect } from '@playwright/test';
import { FIXTURE_WITH_PWD, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('protected gallery: shows password form on first visit', async ({ page }) => {
  await page.goto('/' + FIXTURE_WITH_PWD.url);

  await expect(page.locator('input[name="password"]')).toBeVisible();
  await expect(page.getByRole('heading', { name: /Sesja:/ })).toContainText(FIXTURE_WITH_PWD.name);
  // Gallery checkboxes must NOT be visible behind the gate.
  await expect(page.locator('input[name="chosenImages[]"]')).toHaveCount(0);
});

test('protected gallery: wrong password keeps form with invalid feedback', async ({ page }) => {
  await page.goto('/' + FIXTURE_WITH_PWD.url);
  await page.fill('input[name="password"]', 'nope-nope');
  await page.click('button[type="submit"]');

  await expect(page.locator('input.is-invalid[name="password"]')).toBeVisible();
  await expect(page.locator('input[name="chosenImages[]"]')).toHaveCount(0);
});

test('protected gallery: correct password unlocks the photos', async ({ page }) => {
  await page.goto('/' + FIXTURE_WITH_PWD.url);
  await page.fill('input[name="password"]', FIXTURE_WITH_PWD.password);
  await page.click('button[type="submit"]');

  await expect(page.locator('input[name="chosenImages[]"]')).toHaveCount(6);
  await expect(page.locator('#chosenImagesCount')).toHaveText('0');
});
