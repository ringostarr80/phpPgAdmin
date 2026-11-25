<?php

declare(strict_types=1);

namespace Tests\Unit\WebsiteComponents;

use PhpPgAdmin\{Config, TrailSubject};
use PhpPgAdmin\DDD\Entities\Server;
use PhpPgAdmin\DDD\ValueObjects\Server\Name;
use PhpPgAdmin\WebsiteComponents\TrailBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

/**
 * testCrumb() builds the crumbs without request parameters (server, database, …), so the labels and the URL parameters
 * taken from them are empty there. The other tests give them values.
 */
#[CoversClass(TrailBuilder::class)]
final class TrailBuilderTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    /**
     * @param ?array<string, string> $urlParams Null for a crumb without a link target.
     */
    #[DataProvider('subjects')]
    public function testCrumb(
        TrailSubject $subject,
        string $title,
        string $icon,
        string $help,
        ?string $urlPath,
        ?array $urlParams,
    ): void {
        $dom = new \DOMDocument();
        $crumb = TrailBuilder::buildTrailFor($subject, $dom);
        $dom->appendChild($crumb);

        self::assertSame('td', $crumb->tagName);
        self::assertSame('crumb', $crumb->getAttribute('class'));

        $links = $crumb->getElementsByTagName('a');
        self::assertCount(2, $links);

        $link = $links->item(0);
        self::assertInstanceOf(\DOMElement::class, $link);
        self::assertSame($title, $link->getAttribute('title'));

        if (is_null($urlPath)) {
            self::assertFalse($link->hasAttribute('href'));
        } else {
            self::assertSame(['params' => $urlParams, 'path' => $urlPath], self::parseUrl($link->getAttribute('href')));
        }

        $spans = $link->getElementsByTagName('span');
        self::assertCount(2, $spans);

        $iconSpan = $spans->item(0);
        self::assertInstanceOf(\DOMElement::class, $iconSpan);
        self::assertSame('icon', $iconSpan->getAttribute('class'));
        self::assertSame('label', $spans->item(1)?->getAttribute('class'));

        $img = $iconSpan->getElementsByTagName('img')->item(0);
        self::assertInstanceOf(\DOMElement::class, $img);
        self::assertSame("images/themes/default/{$icon}.png", $img->getAttribute('src'));
        self::assertSame($title, $img->getAttribute('alt'));

        $helpLink = $links->item(1);
        self::assertInstanceOf(\DOMElement::class, $helpLink);
        self::assertSame('help', $helpLink->getAttribute('class'));
        self::assertSame('Help', $helpLink->getAttribute('title'));
        self::assertSame('phppgadminhelp', $helpLink->getAttribute('target'));
        self::assertSame(
            ['params' => ['help' => $help, 'server' => ''], 'path' => 'help.php'],
            self::parseUrl($helpLink->getAttribute('href')),
        );

        self::assertSame(': ', $crumb->lastChild?->textContent);
    }

    #[DataProvider('subjectsOfTheRequest')]
    public function testCrumbLabelNamesTheSubjectOfTheRequest(
        TrailSubject $subject,
        string $parameter,
        string $name,
    ): void {
        $_GET[$parameter] = $name;

        self::assertSame($name, self::label($subject));
    }

    #[DataProvider('linksWithTheValuesOfTheRequest')]
    public function testCrumbLinksCarryTheValuesOfTheRequest(TrailSubject $subject, ?string $url, string $helpUrl): void
    {
        $_GET = [
            'database' => 'ppa_test_database',
            'rolename' => 'ppa_test_role',
            'server' => self::SERVER_ID,
            'tablespace' => 'ppa_test_tablespace',
        ];
        $dom = new \DOMDocument();
        $crumb = TrailBuilder::buildTrailFor($subject, $dom);
        $dom->appendChild($crumb);
        $links = PageBody::query($crumb, './a');

        self::assertCount(2, $links);
        self::assertSame($url, $links[0]->hasAttribute('href') ? $links[0]->getAttribute('href') : null);
        self::assertSame($helpUrl, $links[1]->getAttribute('href'));
    }

    public function testCrumbLabelOfAServerNamesTheServerOfTheLogin(): void
    {
        // With an entity reference, which the label shows as it is.
        self::logInToAServerNamed('R&amp;D production');

        self::assertSame('R&amp;D production', self::label(TrailSubject::Server));
    }

    /**
     * @return iterable<string, array{TrailSubject, string, string, string, ?string, ?array<string, string>}>
     */
    public static function subjects(): iterable
    {
        yield 'database' => [
            TrailSubject::Database,
            'Database',
            'Database',
            'pg.database',
            // Like the legacy Misc::getTrail(): redirect.php opens the database tab that was used last.
            'redirect.php',
            ['database' => '', 'server' => '', 'subject' => 'database'],
        ];
        yield 'role' => [
            TrailSubject::Role,
            'Role',
            'Roles',
            'pg.role',
            'roles.php',
            ['action' => 'properties', 'rolename' => '', 'server' => ''],
        ];
        yield 'server' => [
            TrailSubject::Server,
            'Server',
            'Server',
            'pg.server',
            // Like the legacy Misc::getTrail(): redirect.php opens the server tab that was used last.
            'redirect.php',
            ['server' => '', 'subject' => 'server'],
        ];
        yield 'tablespace' => [
            TrailSubject::Tablespace,
            'Tablespace',
            'Tablespaces',
            'pg.tablespace',
            null,
            null,
        ];
    }

    /**
     * The subjects whose labels are the values of request parameters, named with a bare & or an entity reference,
     * which the labels show as they are.
     *
     * @return iterable<string, array{TrailSubject, string, string}>
     */
    public static function subjectsOfTheRequest(): iterable
    {
        yield 'database' => [TrailSubject::Database, 'database', 'R&D database'];
        yield 'role' => [TrailSubject::Role, 'rolename', 'R&amp;D role'];
        yield 'tablespace' => [TrailSubject::Tablespace, 'tablespace', 'R&D tablespace'];
    }

    /**
     * The link of the crumb, null for one without a link target, and its help link.
     *
     * @return iterable<string, array{TrailSubject, ?string, string}>
     */
    public static function linksWithTheValuesOfTheRequest(): iterable
    {
        yield 'database' => [
            TrailSubject::Database,
            'redirect.php?database=ppa_test_database&server=127.0.0.1%3A5432%3Aallow&subject=database',
            'help.php?help=pg.database&server=127.0.0.1%3A5432%3Aallow',
        ];
        yield 'role' => [
            TrailSubject::Role,
            'roles.php?action=properties&rolename=ppa_test_role&server=127.0.0.1%3A5432%3Aallow',
            'help.php?help=pg.role&server=127.0.0.1%3A5432%3Aallow',
        ];
        yield 'server' => [
            TrailSubject::Server,
            'redirect.php?server=127.0.0.1%3A5432%3Aallow&subject=server',
            'help.php?help=pg.server&server=127.0.0.1%3A5432%3Aallow',
        ];
        yield 'tablespace' => [
            TrailSubject::Tablespace,
            null,
            'help.php?help=pg.tablespace&server=127.0.0.1%3A5432%3Aallow',
        ];
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
        // Config reads the configuration file again.
        (new \ReflectionProperty(Config::class, 'conf'))->setValue(null, null);
    }

    /**
     * Replaces the servers of the test configuration with one of the given name, so the label does not depend on the
     * names of the test configuration, and logs in to it. Config reads the configuration file only once, so this
     * changes what it read.
     */
    private static function logInToAServerNamed(string $name): void
    {
        $server = new Server(name: new Name($name));
        $conf = new \ReflectionProperty(Config::class, 'conf');
        // Reads the configuration file.
        Config::getServers();
        $configuration = $conf->getValue();
        self::assertIsArray($configuration);
        $conf->setValue(null, ['servers' => [$server]] + $configuration);

        $_GET['server'] = $server->id();
        $_SESSION['webdbLogin'] = [$server->id() => ['password' => '', 'username' => 'postgres']];
    }

    private static function label(TrailSubject $subject): string
    {
        $dom = new \DOMDocument();
        $crumb = TrailBuilder::buildTrailFor($subject, $dom);
        $dom->appendChild($crumb);
        $labels = PageBody::query($crumb, './a/span[@class="label"]');

        self::assertCount(1, $labels);

        return $labels[0]->textContent;
    }

    /**
     * @return array{'params': array<mixed>, 'path': string}
     */
    private static function parseUrl(string $url): array
    {
        $params = [];
        parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
        ksort($params);

        return [
            'params' => $params,
            'path' => (string)parse_url($url, PHP_URL_PATH),
        ];
    }
}
