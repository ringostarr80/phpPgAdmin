import { test, expect } from '@playwright/test';
import { login, runningServerId, testUsername } from './support/environment';

const DATABASE = 'postgres';
const TABLESPACE = 'pg_default';

// The pages that come from the same legacy page share its title. In French, as the titles are English without a
// translation (only "Tablespaces" is the same word in both).
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
  Rôles: {
    'alter_role.php': { rolename: testUsername() },
    'create_role.php': {},
    'drop_role.php': { rolename: testUsername() },
    'roles.php': {},
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
