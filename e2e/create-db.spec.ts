import { test, expect } from '@playwright/test';
import { login, runningServerId } from './support/environment';

const COMMENT_FIELD = '#db-comment';
const NAME_FIELD = '#db-name';

test('Create Database > the form keeps the line break the entered comment starts with', async ({ page }) => {
  await login(page);
  await page.goto(`/create_db.php?${new URLSearchParams({ server: runningServerId() })}`);

  // Without a name the create fails before it reaches the database, and the page shows the entered comment again.
  await page.locator(COMMENT_FIELD).fill('\nEntered comment');
  await page.locator('input[type="submit"]').click();

  await expect(page.locator('p.message')).toHaveText('You must give a name for your database.');
  await expect(page.locator(COMMENT_FIELD)).toHaveValue('\nEntered comment');
});

test('Create Database > a create that PostgreSQL rejects shows its error and keeps the entered name', async ({ page }) => {
  await login(page);
  await page.goto(`/create_db.php?${new URLSearchParams({ server: runningServerId() })}`);

  // initdb creates this database in every cluster, so PostgreSQL does not create another one with its name.
  await page.locator(NAME_FIELD).fill('postgres');
  await page.locator('input[type="submit"]').click();

  await expect(page.locator('p.message')).toHaveText('Database creation failed.');
  // The error names the database in every language.
  await expect(page.locator('pre.error')).toContainText('postgres');
  await expect(page.locator(NAME_FIELD)).toHaveValue('postgres');
});
