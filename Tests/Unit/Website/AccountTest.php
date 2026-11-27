<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\Account;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversClass(Account::class)]
final class AccountTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServerWithoutTheRole(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));
        self::assertSame([], PageBody::query($body, './/div[@class="trail"]//a[@title="Role"]'));
    }

    public function testThePageHasTheTabsOfTheServer(): void
    {
        $tabs = PageBody::query(self::body(), './/table[@class="tabs"]//td/a[1]');

        self::assertContains(
            'all_db.php?server=127.0.0.1%3A5432%3Aallow&subject=server',
            array_map(static fn (\DOMElement $link): string => $link->getAttribute('href'), $tabs),
        );
    }

    public function testThePageShowsTheMessageOfTheRequest(): void
    {
        $_GET['message'] = 'Password changed.';

        self::assertSame(['Password changed.'], PageBody::messages(self::body()));
    }

    public function testWithoutAMessageThePageShowsNone(): void
    {
        self::assertSame([], PageBody::messages(self::body()));
    }

    public function testThePageLinksToTheChangeOfThePassword(): void
    {
        $links = PageBody::query(self::body(), './/ul[@class="navlink"]/li/a');

        self::assertCount(1, $links);
        self::assertSame('Change password', $links[0]->textContent);
        self::assertSame('change_password.php?server=127.0.0.1%3A5432%3Aallow', $links[0]->getAttribute('href'));
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = ['server' => self::SERVER_ID];
        $_POST = [];
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        // Without a login, the page reads no role from the server.
        return PageBody::of(Account::class);
    }
}
