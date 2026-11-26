import { test, expect } from '@playwright/test';
import { login, RUNNING_SERVER_DESC, runningServerId } from './support/environment';

test('History > the server list only has the servers the user is logged in to', async ({ page }) => {
  await login(page);

  await page.goto(`/history.php?${new URLSearchParams({ server: runningServerId() })}`);

  // The test configuration also has the Not Running Server, which nobody can log in to.
  await expect(page.locator('#server option')).toHaveText([`${RUNNING_SERVER_DESC} (${runningServerId()})`]);
});
