import { test, expect } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';
import { loginAdmin, adminCreateSession, db, resetMutableState } from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

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
