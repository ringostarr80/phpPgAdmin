import { test, expect } from '@playwright/test';
import {
  NOT_RUNNING_SERVER_DESC,
  NOT_RUNNING_SERVER_HOST,
  NOT_RUNNING_SERVER_PORT,
  RUNNING_SERVER_DESC,
  RUNNING_SERVER_PORT,
  browserFrame,
  detailFrame,
  runningServerHost,
} from './support/environment';

test('Landing Page > Introduction Tab (default)', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveTitle(/phpPgAdmin/);
  await expect(page.locator('html')).toHaveAttribute('lang', 'en-US');

  const browserIFrame = page.locator('iframe#browser');
  await expect(browserIFrame).toHaveAttribute('src', 'browser.php');
  await expect(browserIFrame).toHaveAttribute('name', 'browser');

  const detailIFrame = page.locator('iframe#detail');
  await expect(detailIFrame).toHaveAttribute('src', 'intro.php');
  await expect(detailIFrame).toHaveAttribute('name', 'detail');

  const serverLabels = browserFrame(page).locator('div.webfx-tree-item div.webfx-tree-row a.webfx-tree-item-label');
  await expect(serverLabels).toHaveText(['Servers', NOT_RUNNING_SERVER_DESC, RUNNING_SERVER_DESC]);

  const detail = detailFrame(page);
  await expect(detail.locator('table.tabs tr:first-child td:first-child.active span.label')).toHaveText('Introduction');
  await expect(detail.locator('table.tabs tr:first-child td:nth-child(2) span.label')).toHaveText('Server');

  await expect(detail.locator('select[name="language"] option')).toHaveCount(29);
  await expect(detail.locator('select[name="theme"] option')).toHaveCount(5);
  await expect(detail.locator('select[name="language"] option[value="english"]')).toHaveText('English');
  await expect(detail.locator('select[name="language"] option[value="german"]')).toHaveText('Deutsch');
});

test('Landing Page > Server Tab', async ({ page }) => {
  await page.goto('/');

  const detail = detailFrame(page);
  await detail.locator('table.tabs span.label', { hasText: /^Server$/ }).click();

  await expect(detail.locator('table.tabs tr:first-child td:nth-child(2).active span.label')).toHaveText('Server');
  await expect(detail.locator('table.tabs tr:first-child td:first-child span.label')).toHaveText('Introduction');

  const rows = detail.locator('table#server-list tbody tr');
  await expect(rows).toHaveCount(2);

  const notRunningServerCells = rows.nth(0).locator('td');
  await expect(notRunningServerCells.nth(0)).toHaveText(NOT_RUNNING_SERVER_DESC);
  await expect(notRunningServerCells.nth(1)).toHaveText(NOT_RUNNING_SERVER_HOST);
  await expect(notRunningServerCells.nth(2)).toHaveText(String(NOT_RUNNING_SERVER_PORT));

  const runningServerCells = rows.nth(1).locator('td');
  await expect(runningServerCells.nth(0)).toHaveText(RUNNING_SERVER_DESC);
  await expect(runningServerCells.nth(1)).toHaveText(runningServerHost());
  await expect(runningServerCells.nth(2)).toHaveText(String(RUNNING_SERVER_PORT));
});
