import { test, expect, type Page } from '@playwright/test';
import { login, RUNNING_SERVER_DESC, runningServerId, UPLOAD_MAX_FILESIZE_MB } from './support/environment';

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

/** Executes the query from the SQL window, whereupon sql.php stores it in the session. */
async function executeQuery(page: Page, query: string): Promise<void> {
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', database: DATABASE, server: runningServerId() })}`);
  await page.locator(QUERY_FIELD).fill(query);
  // The form shows the result in the window named detail, which is a new one outside of the frames.
  const result = page.waitForEvent('popup');
  await page.locator('input[name="execute"]').click();
  await (await result).waitForLoadState();
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

// Like the legacy page, the window takes the name of its server, under which js/topbar.js opens SQL and Find.
for (const action of Object.keys(TABS)) {
  test(`SQL Editor > the window takes the name of its server (${action})`, async ({ page }) => {
    await login(page);

    await page.goto(`/sqledit.php?${new URLSearchParams({ action, server: runningServerId() })}`);

    expect(await page.evaluate(() => window.name)).toBe(`sqledit:${runningServerId()}`);
  });
}

// Like the legacy page, the window puts the cursor in the field of its form, so one can type right away.
for (const [action, { field }] of Object.entries(TABS)) {
  test(`SQL Editor > the cursor starts in the field of the form (${action})`, async ({ page }) => {
    await login(page);

    await page.goto(`/sqledit.php?${new URLSearchParams({ action, server: runningServerId() })}`);

    await expect(page.locator(field)).toBeFocused();
  });

  test(`SQL Editor > choosing a database puts the cursor back in the form (${action})`, async ({ page }) => {
    await login(page);
    await page.goto(`/sqledit.php?${new URLSearchParams({ action, server: runningServerId() })}`);

    // js/sqledit.js opens the window again for the chosen database, which the legacy page focused like any other load.
    await page.locator('#database').selectOption(DATABASE);

    await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
    await expect(page.locator(field)).toBeFocused();
  });
}

// Like the legacy page, the window shows the SQL form for any action other than find.
for (const [description, params] of Object.entries({ 'an unknown action': { action: 'unknown' }, 'no action': {} })) {
  test(`SQL Editor > the window shows the SQL form for ${description}`, async ({ page }) => {
    await login(page);

    await page.goto(`/sqledit.php?${new URLSearchParams({ ...params, server: runningServerId() })}`);

    await expectForm(page, 'sql');
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

test('SQL Editor > the server list only has the servers the user is logged in to', async ({ page }) => {
  await login(page);

  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  // The test configuration also has the Not Running Server, which nobody can log in to.
  await expect(page.locator('#server option')).toHaveText([`${RUNNING_SERVER_DESC} (${runningServerId()})`]);
});

test('SQL Editor > the upload takes scripts as large as PHP accepts', async ({ page }) => {
  await login(page);

  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await expect(page.locator('#script')).toBeVisible();
  // PHP rejects a larger file than the form field MAX_FILE_SIZE, which comes before the file field.
  await expect(page.locator('input[name="MAX_FILE_SIZE"]')).toHaveValue(String(UPLOAD_MAX_FILESIZE_MB * 1024 * 1024));
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

test('SQL Editor > the query field starts with the query executed last', async ({ page }) => {
  await login(page);
  await executeQuery(page, 'SELECT 1');

  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await expect(page.locator(QUERY_FIELD)).toHaveValue('SELECT 1');
});

test('SQL Editor > choosing a database keeps the cleared query field', async ({ page }) => {
  await login(page);
  await executeQuery(page, 'SELECT 1');
  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'sql', server: runningServerId() })}`);

  await page.locator(QUERY_FIELD).fill('');
  // js/sqledit.js opens the editor again for the chosen database, with the empty query, not the one executed last.
  await page.locator('#database').selectOption(DATABASE);

  await expect(page).toHaveURL(new RegExp(`[?&]database=${DATABASE}(&|$)`));
  await expect(page.locator(QUERY_FIELD)).toHaveValue('');
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

test('SQL Editor > the Find form offers the advanced object types after the others', async ({ page }) => {
  await login(page);

  await page.goto(`/sqledit.php?${new URLSearchParams({ action: 'find', server: runningServerId() })}`);

  // show_advanced is on in the test configuration, which offers the object types after Views, as the legacy page did.
  await expect(page.locator(`${FILTER_FIELD} option`)).toHaveText([
    'All objects',
    'Columns',
    'Constraints',
    'Domains',
    'Functions',
    'Indexes',
    'Rules',
    'Schemas',
    'Sequences',
    'Tables',
    'Triggers',
    'Views',
    'Aggregates',
    'Conversions',
    'Languages',
    'Op Classes',
    'Operators',
    'Types',
  ]);
});

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
