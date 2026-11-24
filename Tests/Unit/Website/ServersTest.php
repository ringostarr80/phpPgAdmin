<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\Entities\Server;
use PhpPgAdmin\DDD\ValueObjects\Server\{Host, Name, Port, SslMode};
use PhpPgAdmin\Website\Servers;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(Servers::class, 'buildHtmlBody')]
final class ServersTest extends TestCase
{
    /**
     * With a bare &, which the list shows as it is.
     */
    private const USERNAME = 'R&D admin';

    public function testThePageHasTheTopBarAndATrailToTheRootOnly(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertSame(
            ['redirect.php?subject=root'],
            array_map(
                static fn (\DOMElement $link): string => $link->getAttribute('href'),
                PageBody::query($body, './/div[@class="trail"]//a'),
            ),
        );
    }

    public function testTheTabOfTheServersIsActive(): void
    {
        $activeTabs = PageBody::query(self::body(), './/table[@class="tabs"]//td[contains(@class, "active")]/a');

        self::assertCount(1, $activeTabs);
        self::assertSame('servers.php', $activeTabs[0]->getAttribute('href'));
    }

    public function testTheListHasAColumnForTheServerTheHostThePortTheUsernameAndTheActions(): void
    {
        $headings = PageBody::query(self::serverList(), './thead/tr/th');

        self::assertSame(
            ['Server', 'Host', 'Port', 'Username', 'Actions'],
            array_map(static fn (\DOMElement $th): string => $th->textContent, $headings),
        );
        self::assertSame(
            ['data', 'data', 'data', 'data', 'data'],
            array_map(static fn (\DOMElement $th): string => $th->getAttribute('class'), $headings),
        );
        self::assertSame('1', $headings[4]->getAttribute('colspan'));
    }

    public function testEachServerHasARowWithItsNameHostPortAndTheUsernameOfItsLogin(): void
    {
        self::assertSame(
            [
                ['R&amp;D local', '/run/R&D/postgresql', '5432', '', ''],
                ['R&D production', 'db.example.org', '5433', self::USERNAME, 'Logout'],
            ],
            self::textsOfTheRows(),
        );
    }

    public function testWithoutALoginNoServerShowsAUsernameOrALinkToLogOut(): void
    {
        $_SESSION = [];

        self::assertSame(
            [
                ['R&amp;D local', '/run/R&D/postgresql', '5432', '', ''],
                ['R&D production', 'db.example.org', '5433', '', ''],
            ],
            self::textsOfTheRows(),
        );
    }

    public function testTheListAlternatesTheClassOfTheRows(): void
    {
        self::configureThreeServersWithALogin();

        self::assertSame(
            ['data1', 'data2', 'data1'],
            array_map(static fn (\DOMElement $row): string => $row->getAttribute('class'), self::rows()),
        );
    }

    public function testTheListGivesTheActionOfARowTheNumberOfTheRowClass(): void
    {
        self::configureThreeServersWithALogin();

        // Like in the legacy list, as the themes style opbutton1 in the rows with data1 and opbutton2 in those with
        // data2.
        self::assertSame(
            ['opbutton1', 'opbutton2', 'opbutton1'],
            array_map(
                static fn (\DOMElement $cell): string => $cell->getAttribute('class'),
                PageBody::query(self::serverList(), './tbody/tr/td[5]'),
            ),
        );
    }

    public function testTheNameOfAServerLinksToTheServer(): void
    {
        self::assertSame(
            [
                'redirect.php?server=%2Frun%2FR%26D%2Fpostgresql%3A5432%3Aallow&subject=server',
                'redirect.php?server=db.example.org%3A5433%3Arequire&subject=server',
            ],
            array_map(
                static fn (\DOMElement $link): string => $link->getAttribute('href'),
                PageBody::query(self::serverList(), './tbody/tr/td[1]/a'),
            ),
        );
    }

    public function testOnlyAServerWithALoginHasALinkToLogOut(): void
    {
        [$localActions, $productionActions] = array_map(
            static fn (\DOMElement $row): \DOMElement => PageBody::query($row, './td[5]')[0],
            self::rows(),
        );

        self::assertFalse($localActions->hasAttribute('class'));
        self::assertNull($localActions->firstChild);
        // Styled as a button like the actions of the legacy list, with the number of the row.
        self::assertSame('opbutton2', $productionActions->getAttribute('class'));
        self::assertSame(
            'server-logout.php?id=db.example.org%3A5433%3Arequire',
            PageBody::onlyElement($productionActions, 'a')->getAttribute('href'),
        );
    }

    public function testThePageEndsWithALinkBackToTheTop(): void
    {
        $lastElement = self::body()->lastElementChild;

        self::assertInstanceOf(\DOMElement::class, $lastElement);
        self::assertSame('a', $lastElement->tagName);
        self::assertSame('bottom_link', $lastElement->getAttribute('class'));
        self::assertSame('#', $lastElement->getAttribute('href'));
    }

    #[\Override]
    protected function setUp(): void
    {
        self::configureTheServers(self::local(), self::production());

        $_GET = [];
        $_POST = [];
        $_SESSION = [
            'webdbLogin' => [
                self::production()->id() => ['password' => 'secret', 'username' => self::USERNAME],
            ],
        ];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        // Config reads the configuration file again.
        (new \ReflectionProperty(Config::class, 'conf'))->setValue(null, null);
    }

    /**
     * A server on the same machine, reached through the socket in the directory given as its host. Its name holds an
     * entity reference and its host a bare &, which the list shows as they are.
     */
    private static function local(): Server
    {
        return new Server(name: new Name('R&amp;D local'), host: new Host('/run/R&D/postgresql'));
    }

    /**
     * Its name holds a bare &, which the list shows as it is.
     */
    private static function production(): Server
    {
        return new Server(
            name: new Name('R&D production'),
            host: new Host('db.example.org'),
            port: new Port(5_433),
            sslMode: SslMode::REQUIRE,
        );
    }

    /**
     * Replaces the servers of the test configuration. Config reads the configuration file only once, so this changes
     * what it read.
     */
    private static function configureTheServers(Server ...$servers): void
    {
        $conf = new \ReflectionProperty(Config::class, 'conf');
        // Reads the configuration file.
        Config::getServers();
        $configuration = $conf->getValue();
        self::assertIsArray($configuration);
        $conf->setValue(null, ['servers' => $servers] + $configuration);
    }

    /**
     * Only a third server tells whether the list alternates the classes or counts them up.
     */
    private static function configureThreeServersWithALogin(): void
    {
        $servers = array_map(
            static fn (int $port): Server => new Server(
                name: new Name("Server on port {$port}"),
                host: new Host('db.example.org'),
                port: new Port($port),
            ),
            [5_433, 5_434, 5_435],
        );
        self::configureTheServers(...$servers);

        $_SESSION = [
            'webdbLogin' => array_fill_keys(
                array_map(static fn (Server $server): string => $server->id(), $servers),
                ['password' => 'secret', 'username' => self::USERNAME],
            ),
        ];
    }

    private static function body(): \DOMElement
    {
        return PageBody::of(Servers::class);
    }

    private static function serverList(): \DOMElement
    {
        $tables = PageBody::query(self::body(), './/table[@id="server-list"]');

        self::assertCount(1, $tables);

        return $tables[0];
    }

    /**
     * @return list<\DOMElement>
     */
    private static function rows(): array
    {
        return PageBody::query(self::serverList(), './tbody/tr');
    }

    /**
     * @return list<list<string>>
     */
    private static function textsOfTheRows(): array
    {
        return array_map(
            static fn (\DOMElement $row): array => array_map(
                static fn (\DOMElement $cell): string => $cell->textContent,
                PageBody::query($row, './td'),
            ),
            self::rows(),
        );
    }
}
