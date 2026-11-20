<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\DropRole;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{NameZero, PageRedirect, RunningServer, TestEnvironment};

#[CoversMethod(DropRole::class, 'handlePostRequest')]
final class DropRoleTest extends TestCase
{
    private const MISSING_ROLE = 'ppa_test_missing_role';

    private ?NameZero $nameZero = null;

    public function testADropRemovesTheRoleAndLeadsToTheListWithAMessage(): void
    {
        self::createTheRole();
        $_POST['rolename'] = self::role();

        $redirect = PageRedirect::afterPost(DropRole::class);

        self::assertFalse(self::exists(self::role()));
        self::assertSame(self::theList('Role dropped.'), $redirect);
    }

    public function testADropRemovesTheRoleZero(): void
    {
        $this->nameZero = NameZero::take();
        $this->nameZero->createRole();
        $_POST['rolename'] = NameZero::NAME;

        PageRedirect::afterPost(DropRole::class);

        self::assertFalse(self::exists(NameZero::NAME));
    }

    public function testAFailedDropLeadsToTheListWithTheErrorOfPostgreSql(): void
    {
        $_POST['rolename'] = self::MISSING_ROLE;

        self::assertSame(
            self::theList('Role drop failed. - ' . self::errorOfDropping(self::MISSING_ROLE)),
            PageRedirect::afterPost(DropRole::class),
        );
    }

    public function testWithoutARoleThePageShowsItselfAgain(): void
    {
        $_POST['rolename'] = '';

        self::assertNull(PageRedirect::afterPost(DropRole::class));
    }

    protected function setUp(): void
    {
        $_GET = [];
        $_POST = ['server' => RunningServer::id()];
        RunningServer::logIn();
    }

    protected function tearDown(): void
    {
        // The role is left behind when the page did not drop it.
        RunningServer::connection()->exec('DROP ROLE IF EXISTS "' . self::role() . '"');
        $this->nameZero?->release();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function role(): string
    {
        return TestEnvironment::perThread('ppa_test_dropped_role');
    }

    private static function createTheRole(): void
    {
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        $connection->exec('DROP ROLE IF EXISTS "' . self::role() . '"');
        $connection->exec('CREATE ROLE "' . self::role() . '"');
    }

    private static function exists(string $role): bool
    {
        $statement = RunningServer::connection()->prepare('SELECT 1 FROM pg_catalog.pg_roles WHERE rolname = :role');
        $statement->execute(['role' => $role]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * The error that PostgreSQL gives for the drop, in the language of the server.
     */
    private static function errorOfDropping(string $role): string
    {
        try {
            RunningServer::connection()->exec('DROP ROLE "' . $role . '"');
        } catch (\PDOException $e) {
            return $e->getMessage();
        }

        self::fail("PostgreSQL dropped the role {$role}.");
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function theList(string $message): array
    {
        return ['roles.php', ['message' => $message, 'server' => RunningServer::id(), 'subject' => 'server']];
    }
}
