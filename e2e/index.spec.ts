import { test, expect } from '@playwright/test';

test('Landing Page > Introduction Tab (default)', async ({ page }) => {
  await page.goto('http://localhost:9876/');

  await expect(page).toHaveTitle(/phpPgAdmin/);

  await expect(page.getAttribute('html', 'lang')).resolves.toBe('en-US');

  const browserIFrame = page.locator('iframe#browser');
  await expect(browserIFrame).toHaveAttribute('src', 'browser.php');
  await expect(browserIFrame).toHaveAttribute('name', 'browser');

  const detailIFrame = page.locator('iframe#detail');
  await expect(detailIFrame).toHaveAttribute('src', 'intro.php');
  await expect(detailIFrame).toHaveAttribute('name', 'detail');

  const detailContentFrame = detailIFrame.contentFrame();

  await expect(detailContentFrame.locator('table.tabs tr:first-child td:first-child.active span.label')).toHaveText('Introduction');

  const languageOptions = detailContentFrame.locator('select[name="language"] option');
  await expect(languageOptions).toHaveCount(29);
  const themeOptions = detailContentFrame.locator('select[name="theme"] option');
  await expect(themeOptions).toHaveCount(5);

  await expect(detailContentFrame.locator('select[name="language"] option[value="english"]')).toHaveText('English');
  await expect(detailContentFrame.locator('select[name="language"] option[value="german"]')).toHaveText('Deutsch');
});
