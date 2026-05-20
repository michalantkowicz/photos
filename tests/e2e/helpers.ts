import { Page, expect } from '@playwright/test';
import mysql from 'mysql2/promise';
import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

export const FIXTURE_PHOTO = path.join(__dirname, '..', 'fixtures', 'upload-photo.jpg');

export const FIXTURE_NO_PWD = {
  id: '00000000-0000-4000-8000-000000000001',
  url: 'sesja/test-no-pwd',
  name: 'Test Session No Password',
};

export const FIXTURE_WITH_PWD = {
  id: '00000000-0000-4000-8000-000000000002',
  url: 'sesja/test-with-pwd',
  name: 'Test Session With Password',
  password: 'test123',
};

export const ADMIN_PASSWORD = 'admin';

export async function db() {
  return mysql.createConnection({
    host: '127.0.0.1',
    port: 3307,
    user: 'root',
    password: 'root',
    database: 'sessions',
    multipleStatements: true,
  });
}

/** Reset mutable state (choices + ad-hoc admin-created sessions) so tests
 *  start from the same baseline. Fixture sessions in init.sql remain.
 *  Session dirs are discovered via the DB so we never touch unrelated
 *  user data sitting in the project root. */
export async function resetMutableState() {
  const c = await db();
  const [nonFixture] = await c.query<any[]>(
    'SELECT id FROM session WHERE id NOT IN (?, ?)',
    [FIXTURE_NO_PWD.id, FIXTURE_WITH_PWD.id],
  );
  await c.query('DELETE FROM choice');
  await c.query('DELETE FROM choice_snapshot');
  await c.query('DELETE FROM login_attempts');
  await c.query(
    'DELETE FROM session WHERE id NOT IN (?, ?)',
    [FIXTURE_NO_PWD.id, FIXTURE_WITH_PWD.id],
  );
  await c.end();

  const dataRoot = path.join(__dirname, '..', '..', 'data');
  for (const row of nonFixture) {
    const dir = path.join(dataRoot, row.id);
    if (!fs.existsSync(dir)) continue;
    try {
      fs.rmSync(dir, { recursive: true, force: true });
    } catch (err) {
      // In CI the upload dirs are created by the app container (www-data),
      // so the runner user can't unlink files inside them. Fall back to a
      // privileged remove — GitHub runners allow passwordless sudo. Locally
      // (no CI env) this rethrows so a real permission bug stays visible.
      if (!process.env.CI) throw err;
      execSync('sudo rm -rf ' + JSON.stringify(dir));
    }
  }
}

export async function loginAdmin(page: Page) {
  await page.goto('/admin');
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  // navbar-brand is only rendered inside security/admin.php (the logged-in dashboard)
  await expect(page.locator('a.navbar-brand')).toBeVisible();
}

export async function unlockProtectedGallery(page: Page) {
  await page.goto('/' + FIXTURE_WITH_PWD.url);
  await page.fill('input[name="password"]', FIXTURE_WITH_PWD.password);
  await page.click('button[type="submit"]');
}

export interface CreatedSession {
  sessionId: string;
  slug: string;
  url: string;          // path-only, e.g. "/sesja/foo"
  password?: string;
  email?: string;
}

/** Driven through the admin UI: opens the accordion, fills the form, uploads
 *  one fixture photo, and waits for the post-submit redirect back to /admin.
 *  The session_id is generated server-side now; we read it back by URL. */
export async function adminCreateSession(
  page: Page,
  opts: { name: string; slug: string; password?: string; email?: string },
): Promise<CreatedSession> {
  const formPanel = page.locator('#panelsStayOpen-collapseTwo');
  if (!await formPanel.isVisible()) {
    await page.locator('button[data-bs-target="#panelsStayOpen-collapseTwo"]').click();
    await formPanel.waitFor({ state: 'visible' });
  }

  await page.fill('input[name="session_name"]', opts.name);

  await page.evaluate((slug) => {
    const u = document.getElementById('session_url') as HTMLInputElement;
    u.removeAttribute('readonly');
    u.value = 'http://localhost:8080/sesja/' + slug;
  }, opts.slug);

  // The password field is pre-filled with a random suggestion, so always set
  // it explicitly — the requested value, or empty for an open session.
  await page.fill('input[name="session_password"]', opts.password ?? '');

  if (opts.email) {
    await page.fill('input[name="session_email"]', opts.email);
  }

  await page.fill('textarea[name="session_description"]', 'created by playwright');
  await page.setInputFiles('input[name="session_files[]"]', FIXTURE_PHOTO);

  await page.click('form[action="submit.php"] button[type="submit"]');
  await page.waitForURL(/\/admin/);

  const conn = await db();
  const fullUrl = 'http://localhost:8080/sesja/' + opts.slug;
  const [rows] = await conn.query<any[]>('SELECT id FROM session WHERE url = ?', [fullUrl]);
  await conn.end();
  if (rows.length !== 1) {
    throw new Error(`Expected one session at ${fullUrl}, got ${rows.length}`);
  }

  return {
    sessionId: rows[0].id,
    slug: opts.slug,
    url: '/sesja/' + opts.slug,
    password: opts.password,
    email: opts.email,
  };
}
