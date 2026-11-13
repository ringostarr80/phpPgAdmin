import { test, expect, type Page } from '@playwright/test';
import { login } from './support/environment';

// server-logout.php ends the session, then a meta refresh sends the page back to where the logout was clicked.

/** The actions cell of the "Running Server" row on the server list (servers.php). */
function runningServerActions(page: Page) {
  return page.locator('#server-list tbody tr:nth-child(2) td:last-child');
}

test('Logout > via the top bar link', async ({ page }) => {
  const detail = await login(page);

  const logoutResponse = page.waitForResponse(/server-logout\.php/);
  await detail.locator('#toplink_logout').click();
  await logoutResponse;

  // Back on the previous page, which now shows no login.
  await expect(detail.locator('div.topbar span.appname')).toBeVisible();

  await page.goto('/servers.php');
  await expect(page.locator('#server-list')).toBeVisible();
  await expect(runningServerActions(page).getByText('Logout')).toHaveCount(0);
});

test('Logout > via the server list', async ({ page }) => {
  await login(page);
  await page.goto('/servers.php');

  // Wait for the redirect back instead of navigating ourselves, which would race the meta refresh.
  const backOnServerList = page.waitForEvent('framenavigated', {
    predicate: (frame) => frame === page.mainFrame() && frame.url().endsWith('/servers.php'),
  });
  await runningServerActions(page).getByText('Logout').click();
  await backOnServerList;

  await expect(page.locator('#server-list')).toBeVisible();
  await expect(runningServerActions(page).getByText('Logout')).toHaveCount(0);
});
