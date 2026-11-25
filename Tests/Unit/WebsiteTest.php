<?php

declare(strict_types=1);

namespace Tests\Unit;

use PhpPgAdmin\{Session, Website};
use PhpPgAdmin\Website\{
    AllDb,
    AllDbExport,
    AlterDb,
    AlterRole,
    AlterTablespace,
    Browser,
    CreateDb,
    CreateRole,
    CreateTablespace,
    DbExport,
    DropDb,
    DropRole,
    DropTablespace,
    History,
    HistoryClear,
    HistoryDelete,
    HistoryDownload,
    Index,
    Intro,
    Languages,
    Login,
    Redirect,
    Roles,
    ServerLogout,
    Servers,
    SqlEdit,
    Tablespaces,
};
use PHPUnit\Framework\Attributes\{CoversMethod, DataProvider};
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(Website::class, 'buildHtmlDocument')]
#[CoversMethod(Website::class, 'buildHtmlHead')]
#[CoversMethod(Website::class, 'requiresServerLogin')]
#[CoversMethod(Website::class, 'title')]
#[CoversMethod(AllDb::class, 'title')]
#[CoversMethod(AllDbExport::class, 'title')]
#[CoversMethod(AlterDb::class, 'title')]
#[CoversMethod(Browser::class, 'requiresServerLogin')]
#[CoversMethod(CreateDb::class, 'title')]
#[CoversMethod(CreateRole::class, 'title')]
#[CoversMethod(CreateTablespace::class, 'title')]
#[CoversMethod(DropDb::class, 'title')]
#[CoversMethod(DropRole::class, 'title')]
#[CoversMethod(DropTablespace::class, 'title')]
#[CoversMethod(History::class, 'title')]
#[CoversMethod(HistoryClear::class, 'title')]
#[CoversMethod(HistoryDelete::class, 'title')]
#[CoversMethod(Index::class, 'buildHtmlHead')]
#[CoversMethod(Index::class, 'requiresServerLogin')]
#[CoversMethod(Intro::class, 'requiresServerLogin')]
#[CoversMethod(Languages::class, 'title')]
#[CoversMethod(Login::class, 'requiresServerLogin')]
#[CoversMethod(Login::class, 'title')]
#[CoversMethod(Redirect::class, 'requiresServerLogin')]
#[CoversMethod(Roles::class, 'title')]
#[CoversMethod(ServerLogout::class, 'requiresServerLogin')]
#[CoversMethod(Servers::class, 'requiresServerLogin')]
#[CoversMethod(Servers::class, 'title')]
#[CoversMethod(SqlEdit::class, 'title')]
#[CoversMethod(Tablespaces::class, 'title')]
final class WebsiteTest extends TestCase
{
    public function testDocument(): void
    {
        $dom = self::page()->buildHtmlDocument();

        self::assertSame('html', $dom->doctype?->name);

        $html = $dom->documentElement;
        self::assertInstanceOf(\DOMElement::class, $html);
        self::assertSame('html', $html->tagName);
        // The locale en_US of the test configuration, as a language tag.
        self::assertSame('en-US', $html->getAttribute('lang'));
        self::assertSame(['head', 'body'], self::childElementNames($html));
    }

    public function testHead(): void
    {
        self::assertSame(
            [
                ['meta', ['content' => 'text/html; charset=utf-8', 'http-equiv' => 'Content-Type']],
                ['title', []],
                [
                    'link',
                    [
                        'href' => 'themes/default/global.css',
                        'id' => 'csstheme',
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                    ],
                ],
                [
                    'link',
                    [
                        'href' => 'images/themes/default/Favicon.ico',
                        'rel' => 'shortcut icon',
                        'type' => 'image/vnd.microsoft.icon',
                    ],
                ],
                [
                    'link',
                    [
                        'href' => 'images/themes/default/Introduction.png',
                        'rel' => 'icon',
                        'type' => 'image/png',
                    ],
                ],
                ['script', ['src' => 'libraries/js/jquery-3.7.1.min.js', 'type' => 'text/javascript']],
            ],
            self::childElements(self::head(self::page())),
        );
    }

    public function testAPageLoadsItsOwnScriptsAfterTheOneOfAllPages(): void
    {
        $head = self::head(
            self::page(
                scripts: [
                    'classic' => ['src' => 'js/classic.js'],
                    'module' => ['src' => 'js/module.js', 'type' => 'module'],
                ],
            ),
        );

        self::assertSame(
            [
                ['script', ['src' => 'libraries/js/jquery-3.7.1.min.js', 'type' => 'text/javascript']],
                ['script', ['src' => 'js/classic.js', 'type' => 'text/javascript']],
                ['script', ['src' => 'js/module.js', 'type' => 'module']],
            ],
            array_slice(self::childElements($head), -3),
        );
    }

    public function testThePageOfTheFramesAddsTheScriptOfItsSeparatorToTheHead(): void
    {
        $elements = self::childElements(self::head(self::pageOf(Index::class)));

        self::assertSame(
            [
                ...self::childElements(self::head(self::page())),
                ['script', ['src' => 'js/main.js', 'type' => 'text/javascript']],
            ],
            $elements,
        );
    }

    public function testTheTitleOfTheWindowEndsWithTheTitleOfThePage(): void
    {
        self::assertSame('phpPgAdmin - R&D <1>', self::windowTitle(self::page('R&D <1>')));
    }

    /**
     * @param class-string<Website> $page
     */
    #[DataProvider('pagesWithATitle')]
    public function testAPageHasTheTitleOfItsLegacyPage(string $page, string $title): void
    {
        self::assertSame("phpPgAdmin - {$title}", self::windowTitle(self::pageOf($page)));
    }

    #[DataProvider('titlesOfTheSqlWindow')]
    public function testTheSqlWindowHasTheTitleOfItsAction(string $action, string $title): void
    {
        $_GET = ['action' => $action];

        self::assertSame("phpPgAdmin - {$title}", self::windowTitle(self::pageOf(SqlEdit::class)));
    }

    public function testTheTitleOfTheWindowIsTheNameOfTheApplicationForAPageWithoutATitle(): void
    {
        self::assertSame('phpPgAdmin', self::windowTitle(self::page()));
    }

    public function testAPageReloadsTheBrowserTreeWhenAnEarlierRequestAskedForIt(): void
    {
        Session::requestBrowserReload();

        $body = self::body(self::page()->buildHtmlDocument());

        // At the end of the page, like the legacy Misc::printFooter().
        self::assertSame(['p', 'script'], self::childElementNames($body));
        self::assertSame('parent.frames.browser.location.reload();', $body->lastElementChild?->textContent);
    }

    public function testOnlyTheNextPageReloadsTheBrowserTree(): void
    {
        Session::requestBrowserReload();
        self::page()->buildHtmlDocument();

        self::assertSame(['p'], self::childElementNames(self::body(self::page()->buildHtmlDocument())));
    }

    public function testWithoutARequestAPageDoesNotReloadTheBrowserTree(): void
    {
        self::assertSame(['p'], self::childElementNames(self::body(self::page()->buildHtmlDocument())));
    }

    /**
     * @param class-string<Website> $page
     */
    #[DataProvider('pagesOfAServer')]
    public function testAPageOfAServerIsOnlyShownWhileOneIsLoggedInToTheServer(string $page): void
    {
        self::assertTrue($page::requiresServerLogin());
    }

    /**
     * @param class-string<Website> $page
     */
    #[DataProvider('pagesWithoutAServer')]
    public function testAPageWithoutAServerIsShownWithoutALogin(string $page): void
    {
        self::assertFalse($page::requiresServerLogin());
    }

    /**
     * Like the legacy pages, which handed their title to Misc::printHeader().
     *
     * @return iterable<string, array{class-string<Website>, string}>
     */
    public static function pagesWithATitle(): iterable
    {
        $titles = [
            AllDb::class => 'Databases',
            AllDbExport::class => 'Databases',
            AlterDb::class => 'Databases',
            AlterRole::class => 'Roles',
            AlterTablespace::class => 'Tablespaces',
            CreateDb::class => 'Databases',
            CreateRole::class => 'Roles',
            CreateTablespace::class => 'Tablespaces',
            DropDb::class => 'Databases',
            DropRole::class => 'Roles',
            DropTablespace::class => 'Tablespaces',
            History::class => 'History',
            HistoryClear::class => 'History',
            HistoryDelete::class => 'History',
            Languages::class => 'Languages',
            Login::class => 'Login',
            Roles::class => 'Roles',
            Servers::class => 'Servers',
            Tablespaces::class => 'Tablespaces',
        ];

        foreach ($titles as $page => $title) {
            yield $page => [$page, $title];
        }
    }

    /**
     * Like the legacy page, where doFind() and doDefault() each handed a title of their own to Misc::printHeader().
     *
     * @return iterable<string, array{string, string}>
     */
    public static function titlesOfTheSqlWindow(): iterable
    {
        yield 'find' => ['find', 'Find'];
        yield 'sql' => ['sql', 'SQL'];
    }

    /**
     * Like the legacy pages, which need a login unless they set $_no_db_connection (see libraries/lib.inc.php).
     *
     * @return iterable<string, array{class-string<Website>}>
     */
    public static function pagesOfAServer(): iterable
    {
        $pages = [
            AllDb::class,
            AllDbExport::class,
            AlterDb::class,
            AlterRole::class,
            AlterTablespace::class,
            CreateDb::class,
            CreateRole::class,
            CreateTablespace::class,
            DbExport::class,
            DropDb::class,
            DropRole::class,
            DropTablespace::class,
            History::class,
            HistoryClear::class,
            HistoryDelete::class,
            HistoryDownload::class,
            Languages::class,
            Roles::class,
            SqlEdit::class,
            Tablespaces::class,
        ];

        foreach ($pages as $page) {
            yield $page => [$page];
        }
    }

    /**
     * Like the legacy pages index.php, intro.php, browser.php, servers.php (also for the logout) and redirect.php,
     * which set $_no_db_connection, and the login form, which is shown while one is not logged in.
     *
     * @return iterable<string, array{class-string<Website>}>
     */
    public static function pagesWithoutAServer(): iterable
    {
        $pages = [
            Browser::class,
            Index::class,
            Intro::class,
            Login::class,
            Redirect::class,
            ServerLogout::class,
            Servers::class,
        ];

        foreach ($pages as $page) {
            yield $page => [$page];
        }
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    /**
     * A page with one paragraph as its content.
     *
     * @param array<string, array{'src': string, 'type'?: 'text/javascript'|'module'}> $scripts
     */
    private static function page(string $title = '', array $scripts = []): Website
    {
        return new class ($title, $scripts) extends Website {
            /**
             * @param array<string, array{'src': string, 'type'?: 'text/javascript'|'module'}> $scripts
             */
            public function __construct(private readonly string $pageTitle, array $scripts)
            {
                // Skips the constructor of Website, which starts a PHP session: the tests set $_SESSION themselves.
                $this->scripts = [...$this->scripts, ...$scripts];
            }

            #[\Override]
            protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
            {
                $body = parent::buildHtmlBody($dom);
                $body->appendChild($dom->createElement('p', 'Content'));

                return $body;
            }

            #[\Override]
            protected function title(): string
            {
                return $this->pageTitle;
            }
        };
    }

    /**
     * Without the constructor of the page, which starts a PHP session and needs a login.
     *
     * @param class-string<Website> $page
     */
    private static function pageOf(string $page): Website
    {
        return (new \ReflectionClass($page))->newInstanceWithoutConstructor();
    }

    private static function windowTitle(Website $page): string
    {
        return PageBody::onlyElement(self::head($page), 'title')->textContent;
    }

    /**
     * Without the body of the page, which may need a request with parameters.
     */
    private static function head(Website $page): \DOMElement
    {
        $head = (new \ReflectionMethod($page, 'buildHtmlHead'))->invoke($page, new \DOMDocument());
        self::assertInstanceOf(\DOMElement::class, $head);

        return $head;
    }

    private static function body(\DOMDocument $dom): \DOMElement
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        self::assertInstanceOf(\DOMElement::class, $body);

        return $body;
    }

    /**
     * @return list<string>
     */
    private static function childElementNames(\DOMElement $element): array
    {
        return array_column(self::childElements($element), 0);
    }

    /**
     * The child elements in their order, each with its name and its attributes, which are sorted by name.
     *
     * @return list<array{string, array<string, string>}>
     */
    private static function childElements(\DOMElement $element): array
    {
        $elements = [];

        foreach ($element->childNodes as $child) {
            if (!($child instanceof \DOMElement)) {
                continue;
            }

            $attributes = [];

            foreach ($child->attributes as $attribute) {
                $attributes[$attribute->nodeName] = (string)$attribute->nodeValue;
            }

            ksort($attributes);
            $elements[] = [$child->tagName, $attributes];
        }

        return $elements;
    }
}
