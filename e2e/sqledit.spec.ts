import { test, expect } from '@playwright/test';
import { login, runningServerId } from './support/environment';

const DATABASE = 'postgres';
const QUERY_FIELD = '#query';

test('SQL Editor > choosing a database keeps the line break the query starts with', async ({ page }) => {
  await login(page);
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await page.locator(QUERY_FIELD).fill('\nSELECT 1');
  // js/sqledit.js opens the editor again for the chosen database, with the entered query.
  await page.locator('#database').selectOption(DATABASE);

  await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
  await expect(page.locator(QUERY_FIELD)).toHaveValue('\nSELECT 1');
});
