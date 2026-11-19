import { test, expect } from '@playwright/test';
import { login, runningServerId } from './support/environment';

// Every database cluster has it, so the tests do not need to create a tablespace, which needs an empty directory on
// the database server.
const TABLESPACE = 'pg_default';

const NAME_FIELD = '#formSpcname';

function alterTablespaceUrl(): string {
  return `/alter_tablespace.php?${new URLSearchParams({ server: runningServerId(), tablespace: TABLESPACE })}`;
}

test('Alter Tablespace > posting the form runs no create first', async ({ page }) => {
  await login(page);
  await page.goto(alterTablespaceUrl());

  // A blank name passes the required check of the browser. Neither the create nor the alter handling reaches the
  // database with it, so pg_default stays as it is, but each of them answers with its own message.
  await page.locator(NAME_FIELD).fill(' ');
  await page.locator('input[name="alter"]').click();

  await expect(page.locator('p.message')).toHaveText('Tablespace alteration failed.');
  // The alter page shows its form again, with the loaded name, as a tablespace needs one.
  await expect(page.locator(NAME_FIELD)).toHaveValue(TABLESPACE);
});
