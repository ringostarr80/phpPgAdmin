import { test, expect } from '@playwright/test';
import { login } from './support/environment';

test('All Databases > tabs after login', async ({ page }) => {
  const detail = await login(page);

  const tabs = detail.locator('table.tabs tbody tr td');
  await expect(tabs).toHaveCount(4);
  await expect(tabs.locator('span.label')).toHaveText(['Databases', 'Roles', 'Tablespaces', 'Export']);

  await expect(tabs.nth(0)).toHaveClass('tab active');
  await expect(tabs.nth(1)).toHaveClass('tab');
  await expect(tabs.nth(2)).toHaveClass('tab');
  await expect(tabs.nth(3)).toHaveClass('tab');
});
