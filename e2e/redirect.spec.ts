import { test, expect, type Page } from '@playwright/test';
import { login, runningServerId } from './support/environment';

function serverParams(): string {
  return `${new URLSearchParams({ server: runningServerId() })}`;
}

/** Like the links to a server, e.g. in the browser tree. */
async function openServer(page: Page): Promise<void> {
  await page.goto(`/redirect.php?${serverParams()}&subject=server`);
}

/** Like the first link of the trail. */
async function openRoot(page: Page): Promise<void> {
  await page.goto('/redirect.php?subject=root');
}

test('Redirect > a link to a server opens the tab of the server that was used last', async ({ page }) => {
  await login(page);
  await openServer(page);
  await expect(page).toHaveURL(`/all_db.php?${serverParams()}`);

  await page.goto(`/roles.php?${serverParams()}`);
  // Has tabs of its own, none of which is one of the server.
  await page.goto(`/sqledit.php?${serverParams()}&action=sql`);
  await openServer(page);

  await expect(page).toHaveURL(`/roles.php?${serverParams()}`);
  await expect(page.locator('table.tabs td.tab.active span.label')).toHaveText(['Roles']);
});

test('Redirect > a link to the root opens the tab of the root that was used last', async ({ page }) => {
  await openRoot(page);
  await expect(page).toHaveURL('/intro.php');

  await page.goto('/servers.php');
  await openRoot(page);

  await expect(page).toHaveURL('/servers.php');
  await expect(page.locator('table.tabs td.tab.active span.label')).toHaveText(['Servers']);
});
