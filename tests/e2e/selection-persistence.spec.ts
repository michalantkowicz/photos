import { test, expect } from '@playwright/test';
import { FIXTURE_NO_PWD, FIXTURE_WITH_PWD, db, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('select two photos, save, and verify they stick across reload', async ({ page }) => {
  await page.goto('/' + FIXTURE_NO_PWD.url);

  const checkboxes = page.locator('input[name="chosenImages[]"]');
  await expect(checkboxes).toHaveCount(6);

  // Check the first two photos
  await checkboxes.nth(0).check();
  await checkboxes.nth(1).check();
  await expect(page.locator('#chosenImagesCount')).toHaveText('2');

  // Submit the form. Use the visible submit button by id from gallery.php.
  await page.locator('#chooseImagesSubmitButton').click();
  // The handler issues a Location redirect back to the gallery URL.
  await page.waitForURL('**/' + FIXTURE_NO_PWD.url);

  // After redirect, the two boxes should be pre-checked and counter shows 2.
  const reloaded = page.locator('input[name="chosenImages[]"]');
  await expect(reloaded).toHaveCount(6);
  await expect(reloaded.nth(0)).toBeChecked();
  await expect(reloaded.nth(1)).toBeChecked();
  await expect(reloaded.nth(2)).not.toBeChecked();
  await expect(page.locator('#chosenImagesCount')).toHaveText('2');

  // DB confirms: 2 rows in choice + 1 snapshot
  const conn = await db();
  const [choices] = await conn.query<any[]>(
    'SELECT image FROM choice WHERE session_id = ?', [FIXTURE_NO_PWD.id]);
  expect(choices.length).toBe(2);

  const [snapshots] = await conn.query<any[]>(
    'SELECT images FROM choice_snapshot WHERE session_id = ?', [FIXTURE_NO_PWD.id]);
  expect(snapshots.length).toBe(1);
  await conn.end();
});

test('re-submitting replaces the previous selection', async ({ page }) => {
  // First submission: pick photo 0
  await page.goto('/' + FIXTURE_NO_PWD.url);
  await page.locator('input[name="chosenImages[]"]').nth(0).check();
  await page.locator('#chooseImagesSubmitButton').click();
  await page.waitForURL('**/' + FIXTURE_NO_PWD.url);

  // Second submission: replace with photo 2 only
  await page.locator('input[name="chosenImages[]"]').nth(0).uncheck();
  await page.locator('input[name="chosenImages[]"]').nth(2).check();
  await page.locator('#chooseImagesSubmitButton').click();
  await page.waitForURL('**/' + FIXTURE_NO_PWD.url);

  const after = page.locator('input[name="chosenImages[]"]');
  await expect(after.nth(0)).not.toBeChecked();
  await expect(after.nth(2)).toBeChecked();
  await expect(page.locator('#chosenImagesCount')).toHaveText('1');

  // DB shows only one current choice but two snapshots (history retained)
  const conn = await db();
  const [choices] = await conn.query<any[]>(
    'SELECT image FROM choice WHERE session_id = ?', [FIXTURE_NO_PWD.id]);
  expect(choices.length).toBe(1);
  const [snapshots] = await conn.query<any[]>(
    'SELECT session_id FROM choice_snapshot WHERE session_id = ?', [FIXTURE_NO_PWD.id]);
  expect(snapshots.length).toBe(2);
  await conn.end();
});

test('submit_choices.php returns 403 for a POST to a password-protected session that has not been unlocked', async ({ page }) => {
  // Visit the open session to initialise a PHP session and obtain a valid CSRF token.
  await page.goto('/' + FIXTURE_NO_PWD.url);
  const csrf = await page.locator('input[name="csrf"]').inputValue();

  // POST to the password-protected session using the same browser session (CSRF is
  // valid) but without having unlocked it — the server must reject this with 403.
  const response = await page.request.post('/submit_choices.php', {
    form: {
      csrf,
      session_id: FIXTURE_WITH_PWD.id,
      session_name: FIXTURE_WITH_PWD.name,
    },
  });
  expect(response.status()).toBe(403);
});
