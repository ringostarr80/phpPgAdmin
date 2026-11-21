import { test, expect, type Page } from '@playwright/test';
import { login, runningServerId } from './support/environment';

const ROLE = 'ppa_e2e_altered_role';

function serverParams(params: Record<string, string> = {}): string {
  return new URLSearchParams({ server: runningServerId(), ...params }).toString();
}

/** Drops the role through the app, which leads to the roles with a message when there is none. */
async function dropRole(page: Page): Promise<void> {
  await page.request.post(`/drop_role.php?${serverParams()}`, { form: { rolename: ROLE } });
}

test.afterEach(async ({ page }) => {
  await dropRole(page);
});

test('Alter Role > posting the form runs no create first', async ({ page }) => {
  await login(page);
  // Removes what an aborted run left behind.
  await dropRole(page);
  await page.goto(`/create_role.php?${serverParams()}`);
  await page.locator('#name').fill(ROLE);
  await page.locator('input[name="create"]').click();
  await expect(page.locator('p.message')).toHaveText('Role created.');

  await page.goto(`/alter_role.php?${serverParams({ rolename: ROLE })}`);
  // The role is dropped while its form is open, as from another tab.
  await dropRole(page);
  await page.locator('input[name="alter"]').click();

  // The alter of a role that is gone fails, so the page shows its form again. A create would lead to the roles.
  await expect(page).toHaveURL(/\/alter_role\.php\?/);
  await page.goto(`/roles.php?${serverParams()}`);
  await expect(page.getByRole('link', { name: ROLE, exact: true })).toHaveCount(0);
});
