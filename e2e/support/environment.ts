import { createHash } from 'node:crypto';
import { expect, type FrameLocator, type Page } from '@playwright/test';

// Mirrors Tests/Support/TestEnvironment.php, which writes the server configuration the app runs with.
export const NOT_RUNNING_SERVER_DESC = 'Not Running Server';
export const NOT_RUNNING_SERVER_HOST = '127.0.0.1';
export const NOT_RUNNING_SERVER_PORT = 1;
export const RUNNING_SERVER_DESC = 'Running Server';
export const RUNNING_SERVER_PORT = 5432;
const SERVER_SSL_MODE = 'allow';

export const LOGIN_FORM_SELECTOR = 'form[name="login_form"]';

export function runningServerHost(): string {
  return process.env.PHPPGADMIN_TEST_SERVER_HOSTNAME || '127.0.0.1';
}

export function testUsername(): string {
  return process.env.PHPPGADMIN_TEST_SERVER_USERNAME || 'postgres';
}

export function testPassword(): string {
  return process.env.PHPPGADMIN_TEST_SERVER_PASSWORD ?? '';
}

export function browserFrame(page: Page): FrameLocator {
  return page.locator('iframe#browser').contentFrame();
}

export function detailFrame(page: Page): FrameLocator {
  return page.locator('iframe#detail').contentFrame();
}

/** The server entry in the browser tree; its tooltip is the server id `host:port:sslmode`. */
export function serverTreeLink(page: Page, host: string, port: number) {
  return browserFrame(page).locator(`a[title="${host}:${port}:${SERVER_SSL_MODE}"]`);
}

/** The password field name contains a hash of the server description. */
export function passwordFieldName(serverDesc: string): string {
  return `loginPassword_${createHash('sha256').update(serverDesc).digest('hex')}`;
}

export async function login(page: Page, username = testUsername(), password = testPassword()): Promise<FrameLocator> {
  await page.goto('/');
  await serverTreeLink(page, runningServerHost(), RUNNING_SERVER_PORT).click();

  const detail = detailFrame(page);
  const loginForm = detail.locator(LOGIN_FORM_SELECTOR);
  await loginForm.locator('input[name="loginUsername"]').fill(username);
  await loginForm.locator(`input[name="${passwordFieldName(RUNNING_SERVER_DESC)}"]`).fill(password);
  await loginForm.locator('#loginSubmit').click();

  await expect(detail.getByText(`You are logged in as user "${username}"`)).toBeVisible();

  return detail;
}
