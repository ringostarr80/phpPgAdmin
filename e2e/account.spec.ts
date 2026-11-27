import { test, expect, type Page } from '@playwright/test';
import { login, runningServerId, submitLoginForm } from './support/environment';

// Without the superuser attribute, whose logins have the tab of the account in place of the one of the roles. The
// passwords are at least as long as the minimum length of the configuration.
const ROLE = 'ppa_e2e_account_role';
const OLD_PASSWORD = 'ppa_e2e_old_password';
const NEW_PASSWORD = 'ppa_e2e_new_password';

function serverParams(): string {
  return new URLSearchParams({ server: runningServerId() }).toString();
}

/** Logs in as the user of the tests in a session of its own and drops the role through the app. */
async function dropRole(page: Page): Promise<void> {
  await page.context().clearCookies();
  await login(page);
  await page.request.post(`/drop_role.php?${serverParams()}`, { form: { rolename: ROLE } });
}

/** Logs in as the role in a session of its own, from a link to the server like the ones of the browser tree. */
async function logInAsTheRole(page: Page, password: string): Promise<void> {
  await page.context().clearCookies();
  await page.goto(`/redirect.php?${serverParams()}&subject=server`);
  await submitLoginForm(page, ROLE, password);
  await expect(page.getByText(`You are logged in as user "${ROLE}"`)).toBeVisible();
}

test.afterEach(async ({ page }) => {
  await dropRole(page);
});

test('Account > a login without the superuser attribute changes its own password', async ({ page }) => {
  // Removes what an aborted run left behind.
  await dropRole(page);
  const created = await page.request.post(`/create_role.php?${serverParams()}`, {
    form: { formCanLogin: 'on', name: ROLE, password: OLD_PASSWORD, password_confirmation: OLD_PASSWORD },
  });
  expect(new URL(created.url()).searchParams.get('message')).toBe('Role created.');
  await logInAsTheRole(page, OLD_PASSWORD);

  await expect(page.locator('table.tabs td.tab span.label')).toHaveText([
    'Databases',
    'Account',
    'Tablespaces',
    'Export',
  ]);
  await page.locator('table.tabs span.label', { hasText: /^Account$/ }).click();
  await page.getByRole('link', { name: 'Change password', exact: true }).click();
  await page.locator('#password').fill(NEW_PASSWORD);
  await page.locator('#password_confirmation').fill(NEW_PASSWORD);
  await page.locator('input[name="ok"]').click();

  await expect(page).toHaveURL(/\/account\.php\?/);
  await expect(page.locator('p.message')).toHaveText('Password changed.');
  await expect(page.locator('table.tabs td.tab.active span.label')).toHaveText(['Account']);
  // The page connects with the password of the session, which the change kept.
  await expect(page.getByRole('cell', { name: ROLE, exact: true })).toBeVisible();
  await logInAsTheRole(page, NEW_PASSWORD);
});
