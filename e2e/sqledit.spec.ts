import { test, expect, type Page } from '@playwright/test';
import { login, runningServerId } from './support/environment';

const DATABASE = 'postgres';
const FILTER_FIELD = '#filter';
const PAGINATE_FIELD = '#paginate';
const QUERY_FIELD = '#query';
const SCHEMA = 'public';
const SEARCH_PATH_FIELD = '#search_path';
const TERM_FIELD = '#term';

/** The tab of each action, and the field of its form. */
const TABS: Record<string, { field: string; label: string }> = {
  find: { field: TERM_FIELD, label: 'Find' },
  sql: { field: QUERY_FIELD, label: 'SQL' },
};

function tabLabels(page: Page) {
  return page.locator('table.tabs td.tab span.label');
}

async function expectForm(page: Page, action: string): Promise<void> {
  const { field, label } = TABS[action];

  await expect(page.locator(field)).toBeVisible();
  await expect(page.locator('table.tabs td.tab.active span.label')).toHaveText([label]);
}

for (const action of Object.keys(TABS)) {
  test(`SQL Editor > the window has tabs of its own (${action})`, async ({ page }) => {
    await login(page);

    await page.goto(`/sqledit.php?${new URLSearchParams({ action, server: runningServerId() })}`);

    await expectForm(page, action);
    await expect(tabLabels(page)).toHaveText(['SQL', 'Find']);
  });
}

test('SQL Editor > the tabs switch between the forms and keep the database and the schema', async ({ page }) => {
  const params = { action: 'sql', database: DATABASE, schema: SCHEMA, server: runningServerId() };
  await login(page);
  await page.goto(`/sqledit.php?${new URLSearchParams(params)}`);

  for (const action of ['find', 'sql']) {
    await tabLabels(page).getByText(TABS[action].label, { exact: true }).click();

    await expectForm(page, action);
    await expect(page.locator('#server')).toHaveValue(runningServerId());
    await expect(page.locator('#database')).toHaveValue(DATABASE);
    await expect(page).toHaveURL(new RegExp(`[?&]schema=${SCHEMA}(&|$)`));
  }
});

// The search path of the database is public. The legacy page put the schema in front of it, but only once.
for (const [schema, searchPath] of [['information_schema', 'information_schema,public'], ['public', 'public']]) {
  test(`SQL Editor > the search path starts with the schema the window is opened from (${schema})`, async ({ page }) => {
    await login(page);
    await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', database: DATABASE, schema, server: runningServerId() })}`);

    await expect(page.locator(SEARCH_PATH_FIELD)).toHaveValue(searchPath);
  });
}

test('SQL Editor > choosing a database keeps the entered search path', async ({ page }) => {
  await login(page);
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await page.locator(SEARCH_PATH_FIELD).fill('information_schema');
  // js/sqledit.js opens the editor again for the chosen database, with the entered search path instead of its own.
  await page.locator('#database').selectOption(DATABASE);

  await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
  await expect(page.locator(SEARCH_PATH_FIELD)).toHaveValue('information_schema');
});

test('SQL Editor > choosing a database keeps the line break the query starts with', async ({ page }) => {
  await login(page);
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await page.locator(QUERY_FIELD).fill('\nSELECT 1');
  // js/sqledit.js opens the editor again for the chosen database, with the entered query.
  await page.locator('#database').selectOption(DATABASE);

  await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
  await expect(page.locator(QUERY_FIELD)).toHaveValue('\nSELECT 1');
});

for (const checked of [true, false]) {
  test(`SQL Editor > choosing a database keeps "Paginate results" ${checked ? 'checked' : 'unchecked'}`, async ({ page }) => {
    await login(page);
    await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

    await page.locator(PAGINATE_FIELD).setChecked(checked);
    // js/sqledit.js opens the editor again for the chosen database, with paginate=on only if the box is checked.
    await page.locator('#database').selectOption(DATABASE);

    await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
    await expect(page.locator(PAGINATE_FIELD)).toBeChecked({ checked });
  });
}

test('SQL Editor > choosing a database keeps the term and the filter of the Find form', async ({ page }) => {
  await login(page);
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'find', server: runningServerId() })}`);

  await page.locator(TERM_FIELD).fill('pg_');
  await page.locator(FILTER_FIELD).selectOption('TABLE');
  // js/sqledit.js opens the window again for the chosen database, with the entered term and filter.
  await page.locator('#database').selectOption(DATABASE);

  await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
  await expectForm(page, 'find');
  await expect(page.locator(TERM_FIELD)).toHaveValue('pg_');
  await expect(page.locator(FILTER_FIELD)).toHaveValue('TABLE');
});
