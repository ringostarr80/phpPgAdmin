<?php

declare(strict_types=1);

namespace Tests\Integration;

use PhpPgAdmin\{Config, WebsiteComponents};
use PhpPgAdmin\DDD\ValueObjects\DbSize;
use PhpPgAdmin\Infrastructure\Session\Logins;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer, TestEnvironment};

#[CoversMethod(WebsiteComponents::class, 'buildDatabasesTable')]
final class WebsiteComponentsTest extends TestCase
{
    /**
     * With a bare & and an entity reference, which the list shows as they are.
     */
    private const COMMENT = 'R&D, written R&amp;D in HTML';

    public function testDatabasesTableShowsTheValuesOfADatabase(): void
    {
        self::assertSame(
            [
                // The cell of the checkbox.
                '',
                self::database(),
                self::owner(),
                'UTF8',
                'C',
                'en_US.UTF-8',
                self::tablespace(),
                (new DbSize(self::sizeOfTheDatabase()))->prettyFormat(),
                'Delete',
                'Privileges',
                'Alter',
                self::COMMENT,
            ],
            array_map(
                static fn (\DOMElement $cell): string => $cell->textContent,
                PageBody::query(self::rowOfTheDatabase(), './td'),
            ),
        );
    }

    public function testDatabasesTableNamesADatabaseInTheValueOfItsCheckbox(): void
    {
        $checkbox = PageBody::onlyElement(self::rowOfTheDatabase(), 'input');

        self::assertSame('checkbox', $checkbox->getAttribute('type'));
        // The actions on multiple lines take the checked databases from it, like in the legacy list.
        self::assertSame('ma[]', $checkbox->getAttribute('name'));
        self::assertSame(serialize(['database' => self::database()]), $checkbox->getAttribute('value'));
    }

    public function testDatabasesTableLinksTheNameOfADatabaseToTheDatabase(): void
    {
        $links = PageBody::query(self::rowOfTheDatabase(), './td[2]/a');

        self::assertCount(1, $links);
        self::assertSame(
            'redirect.php?' . http_build_query([
                'database' => self::database(),
                'server' => RunningServer::id(),
                'subject' => 'database',
            ]),
            $links[0]->getAttribute('href'),
        );
    }

    public function testDatabasesTableLeadsFromADatabaseToItsDropItsPrivilegesAndItsAlter(): void
    {
        // Styled as buttons, like the actions of the legacy list.
        $links = PageBody::query(self::rowOfTheDatabase(), './td[starts-with(@class, "opbutton")]/a');

        self::assertSame(
            ['Delete', 'Privileges', 'Alter'],
            array_map(static fn (\DOMElement $link): string => $link->textContent, $links),
        );
        self::assertSame(
            'drop_db.php?' . http_build_query(['database' => self::database(), 'server' => RunningServer::id()]),
            $links[0]->getAttribute('href'),
        );

        $privilegesUrl = $links[1]->getAttribute('href');
        $privilegesParams = [];
        parse_str((string)parse_url($privilegesUrl, PHP_URL_QUERY), $privilegesParams);

        self::assertSame('privileges.php', parse_url($privilegesUrl, PHP_URL_PATH));
        self::assertSame(RunningServer::id(), $privilegesParams['server'] ?? null);
        self::assertSame('database', $privilegesParams['subject'] ?? null);

        self::assertSame(
            'alter_db.php?' . http_build_query(['database' => self::database(), 'server' => RunningServer::id()]),
            $links[2]->getAttribute('href'),
        );
    }

    public function testDatabasesTableAlternatesTheClassOfTheRows(): void
    {
        $classes = array_map(
            static fn (\DOMElement $row): string => $row->getAttribute('class'),
            PageBody::query(self::databasesTable(), './tbody/tr'),
        );

        // Besides the database of the tests, the server has at least the database postgres.
        self::assertGreaterThanOrEqual(2, count($classes));
        self::assertSame(
            array_map(static fn (int $index): string => $index % 2 === 0 ? 'data1' : 'data2', array_keys($classes)),
            $classes,
        );
    }

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        RunningServer::logIn();
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        self::dropTheDatabase();

        $connection->exec('CREATE ROLE "' . self::owner() . '"');
        // A tablespace in the data directory of the server, which needs no directory of its own.
        $connection->exec('SET allow_in_place_tablespaces = true');
        $connection->exec('CREATE TABLESPACE "' . self::tablespace() . '" LOCATION \'\'');
        // template0 takes no connections, so the tests running at the same time do not keep it from being copied.
        // Two different locales tell the column of the collation from the one of the character type. Not POSIX, which
        // PostgreSQL on Linux keeps as C.
        $connection->exec(
            'CREATE DATABASE "' . self::database() . '" TEMPLATE template0 OWNER "' . self::owner() . '"'
            . ' TABLESPACE "' . self::tablespace() . '" ENCODING \'UTF8\' LOCALE_PROVIDER libc'
            . ' LC_COLLATE \'C\' LC_CTYPE \'en_US.UTF-8\'',
        );
        $connection->exec('COMMENT ON DATABASE "' . self::database() . '" IS \'' . self::COMMENT . '\'');

        $_SESSION = [];
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        RunningServer::logIn();
        self::dropTheDatabase();

        $_SESSION = [];
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * Like its owner and its tablespace, named with an &, which the list shows as it is.
     */
    private static function database(): string
    {
        return TestEnvironment::perThread('ppa_test_R&D_database');
    }

    private static function owner(): string
    {
        return TestEnvironment::perThread('ppa_test_R&D_owner');
    }

    private static function tablespace(): string
    {
        return TestEnvironment::perThread('ppa_test_R&D_tablespace');
    }

    /**
     * Every dropped database forces a checkpoint, which the parallel threads of Infection wait for in turn, so the
     * tests share one database.
     */
    private static function dropTheDatabase(): void
    {
        $connection = RunningServer::connection();

        $connection->exec('DROP DATABASE IF EXISTS "' . self::database() . '"');
        $connection->exec('DROP TABLESPACE IF EXISTS "' . self::tablespace() . '"');
        $connection->exec('DROP ROLE IF EXISTS "' . self::owner() . '"');
    }

    private static function sizeOfTheDatabase(): int
    {
        $statement = RunningServer::connection()->prepare('SELECT pg_catalog.pg_database_size(:database)');
        $statement->execute(['database' => self::database()]);
        $size = $statement->fetchColumn();

        self::assertIsInt($size);

        return $size;
    }

    private static function databasesTable(): \DOMElement
    {
        $dom = new \DOMDocument();
        $table = WebsiteComponents::buildDatabasesTable(
            $dom,
            Logins::serverSession(RunningServer::id(), Config::getServers()),
        );
        $dom->appendChild($table);

        return $table;
    }

    private static function rowOfTheDatabase(): \DOMElement
    {
        $rows = PageBody::query(self::databasesTable(), './tbody/tr[td[2] = "' . self::database() . '"]');

        self::assertCount(1, $rows);

        return $rows[0];
    }
}
