<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\DropTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{NameZero, PageRedirect, RunningServer, TestEnvironment};

#[CoversMethod(DropTablespace::class, 'handlePostRequest')]
final class DropTablespaceTest extends TestCase
{
    private const MISSING_TABLESPACE = 'ppa_test_missing_tablespace';

    private ?NameZero $nameZero = null;

    public function testADropRemovesTheTablespaceAndLeadsToTheListWithAMessage(): void
    {
        self::createTheTablespace();
        $_POST['tablespace'] = self::tablespace();

        $redirect = PageRedirect::afterPost(DropTablespace::class);

        self::assertFalse(self::exists(self::tablespace()));
        self::assertSame(self::theList('Tablespace dropped.'), $redirect);
    }

    public function testADropRemovesTheTablespaceZero(): void
    {
        $this->nameZero = NameZero::take();
        $this->nameZero->createTablespace();
        $_POST['tablespace'] = NameZero::NAME;

        PageRedirect::afterPost(DropTablespace::class);

        self::assertFalse(self::exists(NameZero::NAME));
    }

    public function testAFailedDropLeadsToTheListWithTheErrorOfPostgreSql(): void
    {
        $_POST['tablespace'] = self::MISSING_TABLESPACE;

        self::assertSame(
            self::theList('Tablespace drop failed. - ' . self::errorOfDropping(self::MISSING_TABLESPACE)),
            PageRedirect::afterPost(DropTablespace::class),
        );
    }

    public function testWithoutATablespaceThePageShowsItselfAgain(): void
    {
        $_POST['tablespace'] = '';

        self::assertNull(PageRedirect::afterPost(DropTablespace::class));
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = ['server' => RunningServer::id()];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        // The tablespace is left behind when the page did not drop it.
        RunningServer::connection()->exec('DROP TABLESPACE IF EXISTS "' . self::tablespace() . '"');
        $this->nameZero?->release();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function tablespace(): string
    {
        return TestEnvironment::perThread('ppa_test_dropped_tablespace');
    }

    /**
     * A tablespace needs an empty directory on the database server, unless it is an in-place tablespace, which
     * PostgreSQL keeps in its data directory. The tests use one, which takes PostgreSQL 15 or later.
     */
    private static function createTheTablespace(): void
    {
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        $connection->exec('DROP TABLESPACE IF EXISTS "' . self::tablespace() . '"');
        $connection->exec('SET allow_in_place_tablespaces = true');
        $connection->exec('CREATE TABLESPACE "' . self::tablespace() . '" LOCATION \'\'');
    }

    private static function exists(string $tablespace): bool
    {
        $statement = RunningServer::connection()->prepare(
            'SELECT 1 FROM pg_catalog.pg_tablespace WHERE spcname = :tablespace',
        );
        $statement->execute(['tablespace' => $tablespace]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * The error that PostgreSQL gives for the drop, in the language of the server.
     */
    private static function errorOfDropping(string $tablespace): string
    {
        try {
            RunningServer::connection()->exec('DROP TABLESPACE "' . $tablespace . '"');
        } catch (\PDOException $e) {
            return $e->getMessage();
        }

        self::fail("PostgreSQL dropped the tablespace {$tablespace}.");
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function theList(string $message): array
    {
        return ['tablespaces.php', ['message' => $message, 'server' => RunningServer::id(), 'subject' => 'server']];
    }
}
