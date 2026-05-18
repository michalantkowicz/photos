import { test, expect } from '@playwright/test';
import {
  FIXTURE_NO_PWD,
  FIXTURE_WITH_PWD,
  adminCreateSession,
  db,
  loginAdmin,
  resetMutableState,
} from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('admin listing shows both fixture sessions with file counts', async ({ page }) => {
  await loginAdmin(page);

  const rows = page.locator('table tbody tr');
  await expect(rows).toHaveCount(2);

  // Row for the open session
  const openRow = rows.filter({ hasText: FIXTURE_NO_PWD.name });
  await expect(openRow).toHaveCount(1);
  await expect(openRow.locator('td').nth(2)).toHaveText('6'); // Ilość plików

  // Row for the protected session – password is rendered in plain text (today's behavior)
  const protectedRow = rows.filter({ hasText: FIXTURE_WITH_PWD.name });
  await expect(protectedRow).toHaveCount(1);
  await expect(protectedRow.locator('td').nth(5)).toContainText('test123');
});

test('admin listing displays a newly created session password in plain text', async ({ page }) => {
  await loginAdmin(page);
  const pwd = 'share-with-client-123';
  await adminCreateSession(page, {
    name: 'Password Visibility Test',
    slug: 'pwd-visibility',
    password: pwd,
  });

  // Reload the dashboard so the new row is rendered.
  await page.goto('/admin');
  const row = page.locator('table tbody tr').filter({ hasText: 'Password Visibility Test' });
  await expect(row).toHaveCount(1);
  await expect(row.locator('td').nth(5)).toContainText(pwd);
});

test('session count badge shows number of sessions and updates after creating one', async ({ page }) => {
  await loginAdmin(page);

  const badge = page.locator('#session-count-badge');
  await expect(badge).toHaveText('2');

  await adminCreateSession(page, { name: 'Badge Count Test', slug: 'badge-count-test' });
  await page.goto('/admin');

  await expect(badge).toHaveText('3');
});

test('admin listing reflects chosen image counts after a client selection', async ({ page, context }) => {
  // Seed a couple of choices for the open session
  const conn = await db();
  await conn.query(
    `INSERT INTO choice (session_id, image, timestamp)
     VALUES (?, ?, NOW()), (?, ?, NOW())`,
    [FIXTURE_NO_PWD.id, 'aW1nMQ==', FIXTURE_NO_PWD.id, 'aW1nMg=='],
  );
  await conn.end();

  await loginAdmin(page);

  const openRow = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  // nth(6) = chosen_images_count (nth(7) is the delete button)
  await expect(openRow.locator('td').nth(6)).toHaveText('2');
});

test('ID badge shows full UUID in tooltip on hover', async ({ page }) => {
  await loginAdmin(page);
  const row = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  await row.locator('[data-bs-toggle="tooltip"]').first().hover();
  await expect(page.locator('.tooltip-inner')).toBeVisible();
  await expect(page.locator('.tooltip-inner')).toHaveText(FIXTURE_NO_PWD.id);
});

test('URL "open" link shows full URL in tooltip on hover', async ({ page }) => {
  await loginAdmin(page);
  const row = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  await row.locator('a', { hasText: 'open' }).hover();
  await expect(page.locator('.tooltip-inner')).toBeVisible();
  await expect(page.locator('.tooltip-inner')).toContainText('test-no-pwd');
});

test('ID copy button copies UUID to clipboard', async ({ page }) => {
  await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
  await loginAdmin(page);
  const row = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  await row.locator('.copy-btn').first().click();
  const text = await page.evaluate(() => navigator.clipboard.readText());
  expect(text).toBe(FIXTURE_NO_PWD.id);
});

test('URL copy button copies URL to clipboard', async ({ page }) => {
  await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
  await loginAdmin(page);
  const row = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  await row.locator('.copy-btn').nth(1).click();
  const text = await page.evaluate(() => navigator.clipboard.readText());
  expect(text).toContain('test-no-pwd');
});

test('rows with chosen photos get a green mark and highlight; rows without do not', async ({ page }) => {
  const conn = await db();
  await conn.query(
    `INSERT INTO choice (session_id, image, timestamp) VALUES (?, ?, NOW())`,
    [FIXTURE_NO_PWD.id, 'aW1nMQ=='],
  );
  await conn.end();

  await loginAdmin(page);

  const chosenRow = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  const unchosen  = page.locator('table tbody tr').filter({ hasText: FIXTURE_WITH_PWD.name });

  // Green checkmark icon present only on the chosen row
  await expect(chosenRow.locator('svg.bi-check-circle-fill')).toBeVisible();
  await expect(unchosen.locator('svg.bi-check-circle-fill')).toHaveCount(0);

  // Row background tinted green via the Bootstrap CSS variable
  const chosenBg = await chosenRow.evaluate(el => (el as HTMLElement).style.getPropertyValue('--bs-table-bg'));
  expect(chosenBg.trim()).toMatch(/rgba\(25,\s*135,\s*84,\s*0\.1\)/);

  // No inline background on the unchosen row
  const unchosenBg = await unchosen.evaluate(el => (el as HTMLElement).style.getPropertyValue('--bs-table-bg'));
  expect(unchosenBg.trim()).toBe('');
});
