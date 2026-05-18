import { test, expect } from '@playwright/test';
import {
  FIXTURE_NO_PWD,
  loginAdmin,
  adminCreateSession,
  resetMutableState,
} from './helpers';

test.beforeEach(async () => {
  await resetMutableState();
});

test('user submits photo choices and the admin sees them in the listing', async ({ browser }) => {
  // ----- USER: pick 3 photos and submit -----
  const userCtx = await browser.newContext();
  const userPage = await userCtx.newPage();
  await userPage.goto('/' + FIXTURE_NO_PWD.url);

  const boxes = userPage.locator('input[name="chosenImages[]"]');
  await boxes.nth(0).check();
  await boxes.nth(1).check();
  await boxes.nth(2).check();
  await userPage.locator('#chooseImagesSubmitButton').click();
  await userPage.waitForURL('**/' + FIXTURE_NO_PWD.url);
  // Sanity: the redirect-rendered gallery shows the count
  await expect(userPage.locator('#chosenImagesCount')).toHaveText('3');
  await userCtx.close();

  // ----- ADMIN: log in and read the chosen-images column -----
  const adminCtx = await browser.newContext();
  const adminPage = await adminCtx.newPage();
  await loginAdmin(adminPage);

  const row = adminPage.locator('table tbody tr').filter({ hasText: FIXTURE_NO_PWD.name });
  await expect(row).toHaveCount(1);
  // nth(7) = "Wybrano" (nth(8) is the delete button)
  await expect(row.locator('td').nth(7)).toHaveText('3');
  await adminCtx.close();
});

test('admin creates a password-protected session and a user can open it with that password', async ({ browser }) => {
  const pwd = 'sup3r-secret';

  // ----- ADMIN: create the protected session -----
  const adminCtx = await browser.newContext();
  const adminPage = await adminCtx.newPage();
  await loginAdmin(adminPage);
  const created = await adminCreateSession(adminPage, {
    name: 'E2E Protected Created',
    slug: 'e2e-protected-created',
    password: pwd,
  });
  await adminCtx.close();

  // ----- USER: fresh browser, expect a password gate -----
  const userCtx = await browser.newContext();
  const userPage = await userCtx.newPage();
  await userPage.goto(created.url);

  await expect(userPage.locator('input[name="password"]')).toBeVisible();
  await expect(userPage.locator('input[name="chosenImages[]"]')).toHaveCount(0);

  // Wrong password keeps the form
  await userPage.fill('input[name="password"]', 'definitely-not-it');
  await userPage.click('button[type="submit"]');
  await expect(userPage.locator('input.is-invalid[name="password"]')).toBeVisible();
  await expect(userPage.locator('input[name="chosenImages[]"]')).toHaveCount(0);

  // Correct password unlocks the gallery
  await userPage.fill('input[name="password"]', pwd);
  await userPage.click('button[type="submit"]');
  await expect(userPage.locator('a.navbar-brand')).toContainText('E2E Protected Created');
  // adminCreateSession uploads 1 photo
  await expect(userPage.locator('input[name="chosenImages[]"]')).toHaveCount(1);

  await userCtx.close();
});

test('admin creates an open session and a user opens it without any password', async ({ browser }) => {
  // ----- ADMIN: create the open session -----
  const adminCtx = await browser.newContext();
  const adminPage = await adminCtx.newPage();
  await loginAdmin(adminPage);
  const created = await adminCreateSession(adminPage, {
    name: 'E2E Open Created',
    slug: 'e2e-open-created',
  });
  await adminCtx.close();

  // ----- USER: fresh browser, should go straight to the gallery -----
  const userCtx = await browser.newContext();
  const userPage = await userCtx.newPage();
  await userPage.goto(created.url);

  await expect(userPage.locator('input[name="password"]')).toHaveCount(0);
  await expect(userPage.locator('a.navbar-brand')).toContainText('E2E Open Created');
  await expect(userPage.locator('input[name="chosenImages[]"]')).toHaveCount(1);

  await userCtx.close();
});
