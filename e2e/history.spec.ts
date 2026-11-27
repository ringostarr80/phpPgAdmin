import { test, expect } from '@playwright/test';
import { login, RUNNING_SERVER_DESC, runningServerId } from './support/environment';

test('History > the server list only has the servers the user is logged in to', async ({ page }) => {
  await login(page);

  await page.goto(`/history.php?${new URLSearchParams({ server: runningServerId() })}`);

  // The test configuration also has the Not Running Server, which nobody can log in to.
  await expect(page.locator('#server option')).toHaveText([`${RUNNING_SERVER_DESC} (${runningServerId()})`]);
});

// Like the legacy page, the window takes the name of its server, under which js/topbar.js opens the History link.
test('History > the window takes the name of its server', async ({ page }) => {
  await login(page);

  await page.goto(`/history.php?${new URLSearchParams({ server: runningServerId() })}`);

  expect(await page.evaluate(() => window.name)).toBe(`history:${runningServerId()}`);
});

test('History > in the detail frame the page keeps the name of the frame', async ({ page }) => {
  // The login leads the detail frame to all_db.php, which has the top bar.
  const detail = await login(page);

  // js/topbar.js leaves the link to the browser when the popup is blocked. Without a target, it opens in the frame.
  await detail.locator('body').evaluate(() => {
    window.open = () => null;
  });
  await detail.locator('#toplink_history').click();

  await expect(detail.locator('script[src="js/history.js"]')).toHaveCount(1);
  // The links of the other pages open in the frame by its name, e.g. those of the browser tree.
  expect(await detail.locator('body').evaluate(() => window.name)).toBe('detail');
});
