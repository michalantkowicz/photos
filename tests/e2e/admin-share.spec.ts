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

const PROJECT_ROOT = path.join(__dirname, '..', '..');

/** Mirror of the PHP-side {url}/{password} substitution in security/admin.php. */
function renderTemplate(tpl: string, url: string, password: string): string {
  return tpl.split('{url}').join(url).split('{password}').join(password);
}

test.beforeEach(async () => {
  await resetMutableState();
});

test('the create-session form has a client e-mail input', async ({ page }) => {
  await loginAdmin(page);
  await expect(page.locator('#session_email')).toBeVisible();
});

test('creating a session with a client e-mail stores it on the session row', async ({ page }) => {
  await loginAdmin(page);
  const email = 'klient@example.com';
  const created = await adminCreateSession(page, {
    name: 'Email Stored Test',
    slug: 'email-stored-test',
    email,
  });

  const conn = await db();
  const [rows] = await conn.query<any[]>(
    'SELECT email FROM session WHERE id = ?', [created.sessionId]);
  await conn.end();
  expect(rows[0].email).toBe(email);
});

test('a session with a client e-mail gets an enabled share button', async ({ page }) => {
  await loginAdmin(page);
  const email = 'klient@example.com';
  await adminCreateSession(page, {
    name: 'Share Enabled Test',
    slug: 'share-enabled-test',
    email,
  });

  await page.goto('/admin');
  await page.locator('#dt-date-range').waitFor();
  const row = page.locator('table tbody tr').filter({ hasText: 'Share Enabled Test' });
  const shareLink = row.locator('a[href^="mailto:"]');
  await expect(shareLink).toHaveCount(1);

  const href = (await shareLink.getAttribute('href')) ?? '';
  expect(decodeURIComponent(href)).toContain(email);
});

test('a session without a client e-mail gets a disabled share button', async ({ page }) => {
  await loginAdmin(page);
  await page.locator('#dt-date-range').waitFor();
  const row = page.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });

  // The fixture session has no e-mail -> the share control is a disabled
  // button, never a mailto link.
  await expect(row.locator('a[href^="mailto:"]')).toHaveCount(0);
  await expect(row.locator('button[disabled]')).toHaveCount(1);
});

test('the share mailto link is built from the templates with url and password filled', async ({ page }) => {
  await loginAdmin(page);
  const email = 'klient@example.com';
  const password = 'sharetest-pwd-42';
  await adminCreateSession(page, {
    name: 'Share Template Test',
    slug: 'share-template-test',
    password,
    email,
  });

  await page.goto('/admin');
  await page.locator('#dt-date-range').waitFor();
  const row = page.locator('table tbody tr').filter({ hasText: 'Share Template Test' });
  const href = (await row.locator('a[href^="mailto:"]').getAttribute('href')) ?? '';
  const decoded = decodeURIComponent(href);

  const fullUrl = 'http://localhost:8080/sesja/share-template-test';
  const bodyTpl  = fs.readFileSync(path.join(PROJECT_ROOT, 'mail_template'), 'utf8');
  const topicTpl = fs.readFileSync(path.join(PROJECT_ROOT, 'mail_topic_template'), 'utf8');

  // Recipient, templated subject, and templated body are all in the link,
  // with {url} and {password} substituted.
  expect(decoded).toContain('mailto:' + email);
  expect(decoded).toContain('subject=' + renderTemplate(topicTpl, fullUrl, password).trim());
  expect(decoded).toContain('body=' + renderTemplate(bodyTpl, fullUrl, password));
});
