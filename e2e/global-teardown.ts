import { execFileSync } from 'node:child_process';

// The matching setup runs as part of the web server command in playwright.config.ts,
// because Playwright starts the web server before globalSetup.
export default function globalTeardown(): void {
  execFileSync('php', ['Tests/test-environment.php', 'teardown'], { stdio: 'inherit' });
}
