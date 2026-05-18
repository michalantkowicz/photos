import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import {
  FIXTURE_NO_PWD,
  adminCreateSession,
  db,
  loginAdmin,
  resetMutableState,
} from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('delete button opens modal with the correct session name', async ({ page }) => {
  await loginAdmin(page);
  await adminCreateSession(page, { name: 'Modal Name Test', slug: 'modal-name-test' });
  await page.goto('/admin');

  const row = page.locator('table tbody tr').filter({ hasText: 'Modal Name Test' });
  await row.locator('.delete-session-btn').click();

  await expect(page.locator('#deleteModal')).toBeVisible();
  await expect(page.locator('#deleteModalSessionName')).toHaveText('Modal Name Test');
});

test('cancelling the delete modal leaves the session intact', async ({ page }) => {
  await loginAdmin(page);
  await adminCreateSession(page, { name: 'Cancel Delete Test', slug: 'cancel-delete-test' });
  await page.goto('/admin');

  const row = page.locator('table tbody tr').filter({ hasText: 'Cancel Delete Test' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();

  await page.locator('#deleteModal .btn-secondary').click();
  await expect(page.locator('#deleteModal')).not.toBeVisible();

  await expect(page.locator('table tbody tr').filter({ hasText: 'Cancel Delete Test' })).toHaveCount(1);
});

test('confirming delete removes the row, DB record, and photo directory', async ({ page }) => {
  await loginAdmin(page);
  const created = await adminCreateSession(page, { name: 'Delete Me Fully', slug: 'delete-me-fully' });
  await page.goto('/admin');

  const row = page.locator('table tbody tr').filter({ hasText: 'Delete Me Fully' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();
  await page.locator('#deleteModal button[type="submit"]').click();
  await page.waitForURL(/\/admin/);

  await expect(page.locator('table tbody tr').filter({ hasText: 'Delete Me Fully' })).toHaveCount(0);

  const conn = await db();
  const [rows] = await conn.query<any[]>('SELECT id FROM session WHERE id = ?', [created.sessionId]);
  await conn.end();
  expect(rows).toHaveLength(0);

  const dataDir = path.join(__dirname, '..', '..', 'data', created.sessionId);
  expect(fs.existsSync(dataDir)).toBe(false);
});

test('deleting a session also removes its choices and snapshots', async ({ page }) => {
  await loginAdmin(page);
  const created = await adminCreateSession(page, { name: 'Delete With Choices', slug: 'delete-with-choices' });

  const conn = await db();
  await conn.query(
    `INSERT INTO choice (session_id, image, timestamp) VALUES (?, ?, NOW())`,
    [created.sessionId, 'aW1nMQ=='],
  );
  await conn.query(
    `INSERT INTO choice_snapshot (session_id, session_name, images, timestamp) VALUES (?, ?, ?, NOW())`,
    [created.sessionId, 'Delete With Choices', 'aW1nMQ==\n'],
  );
  await conn.end();

  await page.goto('/admin');
  const row = page.locator('table tbody tr').filter({ hasText: 'Delete With Choices' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();
  await page.locator('#deleteModal button[type="submit"]').click();
  await page.waitForURL(/\/admin/);

  const conn2 = await db();
  const [choices]   = await conn2.query<any[]>('SELECT 1 FROM choice          WHERE session_id = ?', [created.sessionId]);
  const [snapshots] = await conn2.query<any[]>('SELECT 1 FROM choice_snapshot WHERE session_id = ?', [created.sessionId]);
  await conn2.end();
  expect(choices).toHaveLength(0);
  expect(snapshots).toHaveLength(0);
});

test('unauthenticated POST to delete_session.php returns 403', async ({ page }) => {
  const response = await page.request.post('/delete_session.php', {
    form: { session_id: FIXTURE_NO_PWD.id, csrf: 'forged' },
  });
  expect(response.status()).toBe(403);
});

// ── Filter persistence across delete ─────────────────────────────────────────

/** Wait for DataTables to finish its initComplete (sentinel input is rendered). */
async function waitForDT(page: Parameters<typeof loginAdmin>[0]) {
  await page.locator('#dt-date-range').waitFor();
}

/** Column-1 (Nazwa) filter input injected by DataTables initComplete. */
function nameFilterInput(page: Parameters<typeof loginAdmin>[0]) {
  return page.locator('#sessions-table thead th').nth(1).locator('input');
}

test('column search filter is restored after session delete', async ({ page }) => {
  await loginAdmin(page);
  await adminCreateSession(page, { name: 'Filter Persist Delete', slug: 'filter-persist-delete' });
  await page.goto('/admin');
  await waitForDT(page);

  await nameFilterInput(page).fill('Filter Persist Delete');

  const row = page.locator('table tbody tr').filter({ hasText: 'Filter Persist Delete' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();
  await page.locator('#deleteModal button[type="submit"]').click();
  await page.waitForURL(/\/admin/);
  await waitForDT(page);

  await expect(nameFilterInput(page)).toHaveValue('Filter Persist Delete');
});

test('date range filter is restored after session delete', async ({ page }) => {
  await loginAdmin(page);
  await adminCreateSession(page, { name: 'Date Range Persist Delete', slug: 'date-range-persist-delete' });
  await page.goto('/admin');
  await waitForDT(page);

  // Wide range covering both fixture sessions (2024) and the newly created one (today).
  await page.evaluate(() => {
    // Pass Date objects, not strings — Flatpickr's dateFormat 'y/m/d' can't parse ISO strings.
    (document.getElementById('dt-date-range') as any)._flatpickr.setDate([new Date(2024, 0, 1), new Date(2026, 11, 31)]);
  });

  const row = page.locator('table tbody tr').filter({ hasText: 'Date Range Persist Delete' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();
  await page.locator('#deleteModal button[type="submit"]').click();
  await page.waitForURL(/\/admin/);
  await waitForDT(page);

  await expect(page.locator('#dt-date-range')).toHaveValue('24/01/01 - 26/12/31');
});

test('both text and date filters are restored together after session delete', async ({ page }) => {
  await loginAdmin(page);
  await adminCreateSession(page, { name: 'Multi Filter Persist', slug: 'multi-filter-persist' });
  await page.goto('/admin');
  await waitForDT(page);

  await nameFilterInput(page).fill('Multi Filter Persist');
  await page.evaluate(() => {
    // Pass Date objects, not strings — Flatpickr's dateFormat 'y/m/d' can't parse ISO strings.
    (document.getElementById('dt-date-range') as any)._flatpickr.setDate([new Date(2024, 0, 1), new Date(2026, 11, 31)]);
  });

  const row = page.locator('table tbody tr').filter({ hasText: 'Multi Filter Persist' });
  await row.locator('.delete-session-btn').click();
  await expect(page.locator('#deleteModal')).toBeVisible();
  await page.locator('#deleteModal button[type="submit"]').click();
  await page.waitForURL(/\/admin/);
  await waitForDT(page);

  await expect(nameFilterInput(page)).toHaveValue('Multi Filter Persist');
  await expect(page.locator('#dt-date-range')).toHaveValue('24/01/01 - 26/12/31');
});

test('filters are not restored on a plain page reload', async ({ page }) => {
  await loginAdmin(page);
  await waitForDT(page);

  await nameFilterInput(page).fill('something');
  await page.evaluate(() => {
    (document.getElementById('dt-date-range') as any)._flatpickr.setDate([new Date(2024, 0, 1), new Date(2024, 5, 30)]);
  });

  // Navigate away and back without going through the delete form.
  await page.goto('/admin');
  await waitForDT(page);

  await expect(nameFilterInput(page)).toHaveValue('');
  await expect(page.locator('#dt-date-range')).toHaveValue('');
});
