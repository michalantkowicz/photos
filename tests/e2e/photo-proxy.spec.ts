import { test, expect } from '@playwright/test';
import {
  FIXTURE_NO_PWD,
  FIXTURE_WITH_PWD,
  resetMutableState,
} from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('photo.php: serves photos from an open session without auth', async ({ request }) => {
  const r = await request.get(`/photo.php?s=${FIXTURE_NO_PWD.id}&f=photo1.jpg`);
  expect(r.status()).toBe(200);
  expect(r.headers()['content-type']).toBe('image/jpeg');
  const buf = await r.body();
  expect(buf.length).toBeGreaterThan(100);
});

test('photo.php: 403 for a protected session that has not been unlocked', async ({ request }) => {
  const r = await request.get(`/photo.php?s=${FIXTURE_WITH_PWD.id}&f=photo1.jpg`);
  expect(r.status()).toBe(403);
});

test('photo.php: serves photos after the user unlocks the protected session', async ({ browser }) => {
  // Use a real browser context so the unlock state lives in the PHP session cookie.
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.goto('/' + FIXTURE_WITH_PWD.url);
  await page.fill('input[name="password"]', FIXTURE_WITH_PWD.password);
  await page.click('button[type="submit"]');

  const r = await page.request.get(`/photo.php?s=${FIXTURE_WITH_PWD.id}&f=photo1.jpg`);
  expect(r.status()).toBe(200);
  await ctx.close();
});

test('photo.php: 404 for an unknown session id', async ({ request }) => {
  const r = await request.get('/photo.php?s=ffffffff-ffff-4fff-8fff-ffffffffffff&f=photo1.jpg');
  expect(r.status()).toBe(404);
});

test('photo.php: 404 for a malformed session id (would-be SQL injection)', async ({ request }) => {
  const r = await request.get('/photo.php?s=not-a-uuid&f=photo1.jpg');
  expect(r.status()).toBe(404);
});

test('photo.php: 404 for path traversal in filename', async ({ request }) => {
  // basename() collapses ../ — the missing /etc/passwd.jpg is a 404, not a read.
  const r = await request.get(`/photo.php?s=${FIXTURE_NO_PWD.id}&f=../etc/passwd`);
  expect(r.status()).toBe(404);
});

test('photo.php: 404 for a disallowed extension', async ({ request }) => {
  const r = await request.get(`/photo.php?s=${FIXTURE_NO_PWD.id}&f=evil.php`);
  expect(r.status()).toBe(404);
});

test('photo.php: 404 for a non-existent filename', async ({ request }) => {
  const r = await request.get(`/photo.php?s=${FIXTURE_NO_PWD.id}&f=nope.jpg`);
  expect(r.status()).toBe(404);
});

test('direct webroot access to /data/ is blocked by Apache', async ({ request }) => {
  const r = await request.get(`/data/${FIXTURE_NO_PWD.id}/photo1.jpg`);
  expect(r.status()).toBe(403);
});

test('direct webroot access to old <sid>/ path no longer resolves', async ({ request }) => {
  const r = await request.get(`/${FIXTURE_NO_PWD.id}/photo1.jpg`);
  // No dir at webroot anymore — depending on Apache routing this is 404 or 403.
  expect([404, 403]).toContain(r.status());
});
