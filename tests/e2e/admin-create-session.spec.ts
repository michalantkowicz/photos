import { test, expect } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';
import { FIXTURE_PHOTO, loginAdmin, adminCreateSession, db, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

// ── "Zapamiętaj opis sesji" checkbox ─────────────────────────────────────────

/** The inline <script> at end of body wires up the IIFE that attaches the
 *  remember-description listeners. loginAdmin only waits for navbar visibility,
 *  which can paint before that script finishes. #dt-date-range is created by
 *  DataTables initComplete, which runs after the IIFE — waiting for it
 *  guarantees the listeners are attached before we interact with the form. */
async function waitForRememberReady(page: Parameters<typeof loginAdmin>[0]) {
  await page.locator('#dt-date-range').waitFor();
}

test('remember-description checkbox is unchecked and description is empty by default', async ({ page }) => {
  await loginAdmin(page);
  await waitForRememberReady(page);
  await expect(page.locator('#remember_description')).not.toBeChecked();
  await expect(page.locator('#session_description')).toHaveValue('');
});

test('checking the box persists description across page navigations', async ({ page }) => {
  await loginAdmin(page);
  await waitForRememberReady(page);
  // Fill first, then check — the change handler snapshots the current value into localStorage.
  await page.locator('#session_description').fill('my remembered description');
  await page.locator('#remember_description').check();

  await page.goto('/admin');
  await waitForRememberReady(page);

  await expect(page.locator('#remember_description')).toBeChecked();
  await expect(page.locator('#session_description')).toHaveValue('my remembered description');
});

test('description survives a form submit when checkbox is checked', async ({ page }) => {
  await loginAdmin(page);
  await waitForRememberReady(page);
  await page.locator('#remember_description').check();
  // Fill after checking — the input event keeps localStorage in sync.
  await page.locator('#session_description').fill('i will be remembered');
  await page.locator('#session_name').fill('Remember Submit Test');
  await page.setInputFiles('input[name="session_files[]"]', FIXTURE_PHOTO);
  await page.locator('form[action="submit.php"] button[type="submit"]').click();
  await page.waitForURL(/\/admin/);
  await waitForRememberReady(page);

  await expect(page.locator('#session_description')).toHaveValue('i will be remembered');
  await expect(page.locator('#remember_description')).toBeChecked();
});

test('unchecking the box clears persistence; next load starts fresh', async ({ page }) => {
  await loginAdmin(page);
  await waitForRememberReady(page);
  await page.locator('#session_description').fill('to be forgotten');
  await page.locator('#remember_description').check();
  // Verify it was actually saved by reloading.
  await page.goto('/admin');
  await waitForRememberReady(page);
  await expect(page.locator('#session_description')).toHaveValue('to be forgotten');

  // Now uncheck — change handler removes both localStorage keys.
  await page.locator('#remember_description').uncheck();
  await page.goto('/admin');
  await waitForRememberReady(page);

  await expect(page.locator('#remember_description')).not.toBeChecked();
  await expect(page.locator('#session_description')).toHaveValue('');
});

test('stored description updates live as the user types with the box checked', async ({ page }) => {
  await loginAdmin(page);
  await waitForRememberReady(page);
  await page.locator('#remember_description').check();
  await page.locator('#session_description').fill('version one');
  await page.goto('/admin');
  await waitForRememberReady(page);
  await expect(page.locator('#session_description')).toHaveValue('version one');

  // Overwrite the value — input event updates localStorage again.
  await page.locator('#session_description').fill('version two');
  await page.goto('/admin');
  await waitForRememberReady(page);
  await expect(page.locator('#session_description')).toHaveValue('version two');
});

// ── Session creation ──────────────────────────────────────────────────────────

test('admin can create a new session and the photo lands on disk + DB', async ({ page }) => {
  await loginAdmin(page);
  const created = await adminCreateSession(page, {
    name: 'E2E Created Session',
    slug: 'e2e-created',
  });

  // DB row exists
  const conn = await db();
  const [rows] = await conn.query<any[]>('SELECT id, name FROM session WHERE id = ?', [created.sessionId]);
  expect(rows.length).toBe(1);
  expect(rows[0].name).toBe('E2E Created Session');
  await conn.end();

  // File landed on disk under data/
  const dataRoot = path.join(__dirname, '..', '..', 'data');
  const uploaded = path.join(dataRoot, created.sessionId, 'upload-photo.jpg');
  expect(fs.existsSync(uploaded)).toBe(true);
});
