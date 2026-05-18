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
  // Last <td> = chosen_images_count
  await expect(openRow.locator('td').last()).toHaveText('2');
});
