<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\DropDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{NameZero, PageRedirect, RunningServer, TestEnvironment};

#[CoversMethod(DropDb::class, 'handlePostRequest')]
final class DropDbTest extends TestCase
{
    private const MISSING_DATABASE = 'ppa_test_missing_database';

    private ?NameZero $nameZero = null;

    public function testADropRemovesTheDatabaseAndLeadsToTheListWithAMessage(): void
    {
        self::createTheDatabase();
        $_POST['database'] = self::database();

        $redirect = PageRedirect::afterPost(DropDb::class);

        self::assertFalse(self::exists(self::database()));
        self::assertSame(self::theList('Database dropped.'), $redirect);
    }

    public function testADropRemovesTheDatabaseZero(): void
    {
        $this->nameZero = NameZero::take();
        $this->nameZero->createDatabase();
        $_POST['database'] = NameZero::NAME;

        PageRedirect::afterPost(DropDb::class);

        self::assertFalse(self::exists(NameZero::NAME));
    }

    public function testAFailedDropLeadsToTheListWithTheErrorOfPostgreSql(): void
    {
        $_POST['database'] = self::MISSING_DATABASE;

        self::assertSame(
            self::theList('Database drop failed. - ' . self::errorOfDropping(self::MISSING_DATABASE)),
            PageRedirect::afterPost(DropDb::class),
        );
    }

    public function testWithoutADatabaseThePageShowsItselfAgain(): void
    {
        $_POST['database'] = '';

        self::assertNull(PageRedirect::afterPost(DropDb::class));
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
        // The database is left behind when the page did not drop it.
        RunningServer::connection()->exec('DROP DATABASE IF EXISTS "' . self::database() . '"');
        $this->nameZero?->release();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * Every dropped database forces a checkpoint, which the parallel threads of Infection wait for in turn, so the
     * tests drop as few databases as they can.
     */
    private static function database(): string
    {
        return TestEnvironment::perThread('ppa_test_dropped_database');
    }

    private static function createTheDatabase(): void
    {
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        $connection->exec('DROP DATABASE IF EXISTS "' . self::database() . '"');
        // template1 cannot be copied while another test is connected to it, template0 takes no connections.
        $connection->exec('CREATE DATABASE "' . self::database() . '" TEMPLATE template0');
    }

    private static function exists(string $database): bool
    {
        $statement = RunningServer::connection()->prepare(
            'SELECT 1 FROM pg_catalog.pg_database WHERE datname = :database',
        );
        $statement->execute(['database' => $database]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * The error that PostgreSQL gives for the drop, in the language of the server.
     */
    private static function errorOfDropping(string $database): string
    {
        try {
            RunningServer::connection()->exec('DROP DATABASE "' . $database . '"');
        } catch (\PDOException $e) {
            return $e->getMessage();
        }

        self::fail("PostgreSQL dropped the database {$database}.");
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function theList(string $message): array
    {
        return ['all_db.php', ['message' => $message, 'server' => RunningServer::id(), 'subject' => 'server']];
    }
}
