<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\Tablespaces;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversClass(Tablespaces::class)]
final class TablespacesTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));
    }

    public function testTheTabOfTheTablespacesIsActive(): void
    {
        // The tab links to its page with its first link, the second one leads to the help.
        $activeTabs = PageBody::query(self::body(), './/table[@class="tabs"]//td[contains(@class, "active")]/a[1]');

        self::assertCount(1, $activeTabs);
        self::assertSame(
            'tablespaces.php?server=127.0.0.1%3A5432%3Aallow&subject=server',
            $activeTabs[0]->getAttribute('href'),
        );
    }

    public function testThePageShowsTheMessageItIsLedToWith(): void
    {
        // The pages to create, alter and drop a tablespace lead to this page with a message.
        $_GET['message'] = 'Tablespace dropped.';

        self::assertSame(['Tablespace dropped.'], PageBody::messages(self::body()));
    }

    public function testWithoutAMessageThePageShowsNone(): void
    {
        self::assertSame([], PageBody::messages(self::body()));
    }

    public function testWithoutTablespacesThePageSaysSo(): void
    {
        $texts = array_map(
            static fn (\DOMElement $p): string => $p->textContent,
            PageBody::query(self::body(), './p'),
        );

        self::assertContains('No tablespaces found.', $texts);
    }

    public function testTheNavLinkLeadsToCreatingATablespace(): void
    {
        $navLinks = PageBody::query(self::body(), './/ul[@class="navlink"]//a');

        self::assertCount(1, $navLinks);
        self::assertSame('Create tablespace', $navLinks[0]->textContent);
        self::assertSame('create_tablespace.php?server=127.0.0.1%3A5432%3Aallow', $navLinks[0]->getAttribute('href'));
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
        // Without a login, the page reads no tablespaces from the server.
        return PageBody::of(Tablespaces::class);
    }
}
