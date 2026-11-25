<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\Redirect;
use PhpPgAdmin\WebsiteComponents;
use PHPUnit\Framework\Attributes\{CoversMethod, DataProvider};
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(Redirect::class, 'buildRootUrl')]
#[CoversMethod(Redirect::class, 'buildServerUrl')]
final class RedirectTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    #[DataProvider('rootTabs')]
    public function testALinkToTheRootOpensItsTabThatWasUsedLast(string $tab, string $url): void
    {
        $_SESSION['webdbLastTab'] = ['root' => $tab, 'server' => 'roles'];

        self::assertSame($url, Redirect::buildRootUrl());
    }

    #[DataProvider('noTabs')]
    public function testALinkToTheRootOpensItsFirstTabWithoutALastOne(?string $noTab): void
    {
        $_SESSION['webdbLastTab'] = ['root' => $noTab, 'server' => 'roles'];

        self::assertSame('./intro.php', Redirect::buildRootUrl());
    }

    #[DataProvider('serverTabs')]
    public function testALinkToAServerOpensItsTabThatWasUsedLast(string $tab, string $url): void
    {
        $_SESSION['webdbLastTab'] = ['root' => 'servers', 'server' => $tab];

        self::assertSame($url, Redirect::buildServerUrl(self::SERVER_ID));
    }

    #[DataProvider('noTabs')]
    public function testALinkToAServerOpensItsFirstTabWithoutALastOne(?string $noTab): void
    {
        $_SESSION['webdbLastTab'] = ['root' => 'servers', 'server' => $noTab];

        self::assertSame('./all_db.php?server=127.0.0.1%3A5432%3Aallow', Redirect::buildServerUrl(self::SERVER_ID));
    }

    /**
     * So that a new tab is not forgotten in the redirect.
     */
    public function testEachTabOfTheRootCanBeOpenedAgain(): void
    {
        $dom = new \DOMDocument();

        self::assertSame(
            self::pagesOfTheTabs(WebsiteComponents::buildRootTabs($dom, 'intro')),
            self::pages(self::rootTabs()),
        );
    }

    /**
     * So that a new tab is not forgotten in the redirect.
     */
    public function testEachTabOfAServerCanBeOpenedAgain(): void
    {
        $dom = new \DOMDocument();

        self::assertSame(
            self::pagesOfTheTabs(WebsiteComponents::buildServerDatabasesTabs($dom, self::SERVER_ID, 'databases')),
            self::pages(self::serverTabs()),
        );
    }

    /**
     * The tabs of the root in their order and where they lead to.
     *
     * @return list<array{string, string}>
     */
    public static function rootTabs(): array
    {
        return [
            ['intro', './intro.php'],
            ['servers', './servers.php'],
        ];
    }

    /**
     * The tabs of a server in their order and where they lead to.
     *
     * @return list<array{string, string}>
     */
    public static function serverTabs(): array
    {
        return [
            ['databases', './all_db.php?server=127.0.0.1%3A5432%3Aallow'],
            ['roles', './roles.php?server=127.0.0.1%3A5432%3Aallow'],
            ['tablespaces', './tablespaces.php?server=127.0.0.1%3A5432%3Aallow'],
            ['export', './all_db_export.php?server=127.0.0.1%3A5432%3Aallow'],
        ];
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function noTabs(): iterable
    {
        yield 'none' => [null];
        yield 'of a legacy page' => ['users'];
    }

    #[\Override]
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * @param list<array{string, string}> $tabs
     * @return list<string>
     */
    private static function pages(array $tabs): array
    {
        return array_map(
            static fn (array $tab): string => basename((string)parse_url($tab[1], PHP_URL_PATH)),
            $tabs,
        );
    }

    /**
     * @return list<string>
     */
    private static function pagesOfTheTabs(\DOMElement $tabs): array
    {
        return array_map(
            static fn (\DOMElement $link): string => (string)parse_url($link->getAttribute('href'), PHP_URL_PATH),
            PageBody::query($tabs, './/td/a[not(@class="help")]'),
        );
    }
}
