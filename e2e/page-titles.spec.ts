import { test, expect, type Page } from '@playwright/test';
import {
  RUNNING_SERVER_PORT,
  detailFrame,
  login,
  runningServerHost,
  runningServerId,
  serverTreeLink,
  submitLoginForm,
  testUsername,
} from './support/environment';

const DATABASE = 'postgres';
const TABLESPACE = 'pg_default';

// The pages that come from the same legacy page share its title, only sqledit.php has one for each of its actions. In
// French, as the titles are English without a translation (only "SQL" and "Tablespaces" are the same word in both).
const pagesByTitle: Record<string, Record<string, Record<string, string>>> = {
  'Bases de données': {
    'alter_db.php': { database: DATABASE },
    'create_db.php': {},
    'drop_db.php': { database: DATABASE },
  },
  Historique: {
    'history.php': {},
    'history_clear.php': { database: DATABASE },
    'history_delete.php': { database: DATABASE, queryid: 'none' },
  },
  Rechercher: {
    'sqledit.php': { action: 'find' },
  },
  Rôles: {
    'account.php': {},
    'alter_role.php': { rolename: testUsername() },
    'create_role.php': {},
    'drop_role.php': { rolename: testUsername() },
    'roles.php': {},
  },
  SQL: {
    'sqledit.php': { action: 'sql' },
  },
  Tablespaces: {
    'alter_tablespace.php': { tablespace: TABLESPACE },
    'create_tablespace.php': {},
    'drop_tablespace.php': { tablespace: TABLESPACE },
    'tablespaces.php': {},
  },
};

for (const [title, pages] of Object.entries(pagesByTitle)) {
  test(`Page titles > the pages titled "${title}" have their title in the language of the user`, async ({ page }) => {
    await login(page);

    for (const [path, params] of Object.entries(pages)) {
      const searchParams = new URLSearchParams({ ...params, server: runningServerId(), language: 'french' });
      await page.goto(`/${path}?${searchParams}`);

      await expect.soft(page, path).toHaveTitle(`phpPgAdmin - ${title}`);
    }
  });
}

// Pages whose title the window can't take over: it can't read the one of another site.
const pagesWithoutReadableTitle: Record<string, { body: string; url: string }> = {
  'a page of another site': { body: '<title>Another site</title>', url: 'https://another-site.example/' },
  'a page without a title': { body: '<p>No title</p>', url: '/without-title.html' },
};

async function openInFrame(page: Page, name: 'browser' | 'detail', url: string): Promise<void> {
  const frame = page.frame({ name });
  expect(frame).not.toBeNull();

  await frame?.goto(url);
}

test('Page titles > the window has the title of the page in the detail frame', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle('phpPgAdmin');

  await detailFrame(page).locator('table.tabs span.label', { hasText: /^Servers$/ }).click();
  await expect(page).toHaveTitle('phpPgAdmin - Servers');

  // A link of the browser tree, which opens in the detail frame.
  await serverTreeLink(page, runningServerHost(), RUNNING_SERVER_PORT).click();
  await expect(page).toHaveTitle('phpPgAdmin - Login');

  // The login also reloads the browser tree, which has no title of its own.
  await submitLoginForm(detailFrame(page));
  await expect(page).toHaveTitle('phpPgAdmin - Databases');
});

test('Page titles > the window has the title of a legacy page in the detail frame', async ({ page }) => {
  await login(page);

  const searchParams = new URLSearchParams({ database: DATABASE, server: runningServerId(), subject: 'database' });
  await openInFrame(page, 'detail', `/schemas.php?${searchParams}`);

  await expect(page).toHaveTitle('phpPgAdmin - Schemas');
});

test('Page titles > the window has the title of the page that going back leads to', async ({ page }) => {
  await page.goto('/');
  const tabs = detailFrame(page).locator('table.tabs');
  await tabs.locator('span.label', { hasText: /^Servers$/ }).click();
  await expect(page).toHaveTitle('phpPgAdmin - Servers');
  await tabs.locator('span.label', { hasText: /^Introduction$/ }).click();
  await expect(page).toHaveTitle('phpPgAdmin');

  // Not page.goBack(), which waits for the window to load another page.
  await page.evaluate(() => window.history.back());

  await expect(page).toHaveTitle('phpPgAdmin - Servers');
});

test('Page titles > the browser tree leaves the window with the title of the page', async ({ page }) => {
  await page.goto('/');
  await openInFrame(page, 'detail', '/servers.php');

  await openInFrame(page, 'browser', '/browser.php');

  await expect(page).toHaveTitle('phpPgAdmin - Servers');
});

for (const [description, { body, url }] of Object.entries(pagesWithoutReadableTitle)) {
  test(`Page titles > ${description} in the detail frame leaves the window with its own title`, async ({ page }) => {
    await page.route(url, (route) => route.fulfill({ body, contentType: 'text/html' }));
    await page.goto('/');
    await openInFrame(page, 'detail', '/servers.php');
    await expect(page).toHaveTitle('phpPgAdmin - Servers');

    await openInFrame(page, 'detail', url);

    await expect(page).toHaveTitle('phpPgAdmin');
  });
}
