import { test, expect } from '@playwright/test';
import {
  LOGIN_FORM_SELECTOR,
  NOT_RUNNING_SERVER_DESC,
  NOT_RUNNING_SERVER_HOST,
  NOT_RUNNING_SERVER_PORT,
  detailFrame,
  login,
  passwordFieldName,
  serverTreeLink,
} from './support/environment';

test('Login > fails for a server that is not running', async ({ page }) => {
  await page.goto('/');
  await serverTreeLink(page, NOT_RUNNING_SERVER_HOST, NOT_RUNNING_SERVER_PORT).click();

  const loginForm = detailFrame(page).locator(LOGIN_FORM_SELECTOR);
  const usernameField = loginForm.locator('input[name="loginUsername"]');
  const passwordField = loginForm.locator(`input[name="${passwordFieldName(NOT_RUNNING_SERVER_DESC)}"]`);
  await expect(usernameField).toHaveValue('');
  await expect(passwordField).toHaveValue('');

  await usernameField.fill('postgres');
  await passwordField.fill('wrongpassword');
  await loginForm.locator('#loginSubmit').click();

  await expect(detailFrame(page).getByText('Login failed')).toBeVisible();
});

test('Login > succeeds for the running server', async ({ page }) => {
  await login(page);
});
