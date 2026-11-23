import { test, expect, type Page } from '@playwright/test';
import { login, runningServerId } from './support/environment';

// In the order of the list, which sorts the databases by their names.
const DATABASES = ['ppa_e2e_dropped_database_a', 'ppa_e2e_dropped_database_b'];

function serverParams(params: Record<string, string> = {}): string {
  return new URLSearchParams({ server: runningServerId(), ...params }).toString();
}

/** Drops the databases through the app, which leads to the list with a message when one is gone. */
async function dropDatabases(page: Page): Promise<void> {
  for (const database of DATABASES) {
    await page.request.post(`/drop_db.php?${serverParams()}`, { form: { database } });
  }
}

test.afterEach(async ({ page }) => {
  await dropDatabases(page);
});

test('Drop Database > the databases checked in the list are dropped after the confirmation', async ({ page }) => {
  await login(page);
  // Removes what an aborted run left behind.
  await dropDatabases(page);

  for (const database of DATABASES) {
    await page.request.post(`/create_db.php?${serverParams()}`, { form: { formName: database } });
  }

  await page.goto(`/all_db.php?${serverParams({ subject: 'server' })}`);

  for (const database of DATABASES) {
    await page.locator(`input[name="ma[]"][value="${database}"]`).check();
  }

  await page.locator('select[name="action"]').selectOption({ label: 'Delete' });
  await page.getByRole('button', { name: 'Execute' }).click();

  await expect(page).toHaveURL(/\/drop_db\.php\?/);
  await expect(page.getByText(/^Are you sure you want to drop the database /)).toHaveText(
    DATABASES.map((database) => `Are you sure you want to drop the database "${database}"?`),
  );
  await page.getByRole('button', { name: 'Drop' }).click();

  await expect(page).toHaveURL(/\/all_db\.php\?/);
  // The text as the browser shows it, with each database on a line of its own.
  await expect(page.locator('p.message')).toHaveJSProperty(
    'innerText',
    DATABASES.map((database) => `${database}: Database dropped.`).join('\n'),
  );

  for (const database of DATABASES) {
    await expect(page.locator(`input[name="ma[]"][value="${database}"]`)).toHaveCount(0);
  }
});
