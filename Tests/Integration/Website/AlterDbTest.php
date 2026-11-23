<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\AlterDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer, TestEnvironment};

#[CoversMethod(AlterDb::class, 'buildHtmlBody')]
final class AlterDbTest extends TestCase
{
    /**
     * With a bare & and an entity reference, which the form shows as they are.
     */
    private const COMMENT = 'R&D, written R&amp;D in HTML';

    public function testTheFormShowsTheNameTheOwnerAndTheCommentOfTheDatabase(): void
    {
        $form = self::form();

        self::assertSame(self::database(), PageBody::fieldValue($form, 'newname'));
        self::assertSame([self::owner()], PageBody::selectedValues($form, 'owner'));
        self::assertSame(self::COMMENT, PageBody::fieldValue($form, 'dbcomment'));
    }

    public function testTheFormOffersTheUsersOfTheServerAsOwnersInTheOrderOfTheirNames(): void
    {
        $options = PageBody::query(self::form(), './/select[@id="owner"]/option');
        $values = array_map(static fn (\DOMElement $option): string => $option->getAttribute('value'), $options);
        $texts = array_map(static fn (\DOMElement $option): string => $option->textContent, $options);
        $sortedValues = $values;
        sort($sortedValues, SORT_STRING);

        // Besides the owner of the database, the server has the users of the tests running at the same time.
        self::assertContains(self::owner(), $values);
        self::assertSame($sortedValues, $values);
        self::assertSame($values, $texts);
    }

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        RunningServer::logIn();
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        self::dropTheDatabase();

        // The owners to choose from are the roles that can log in.
        $connection->exec('CREATE ROLE "' . self::owner() . '" LOGIN');
        // template0 takes no connections, so the tests running at the same time do not keep it from being copied.
        $connection->exec(
            'CREATE DATABASE "' . self::database() . '" TEMPLATE template0 OWNER "' . self::owner() . '"',
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
        $_GET = [
            'database' => self::database(),
            'server' => RunningServer::id(),
        ];
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
     * Like its owner, named with an &, which the form shows as it is. Every dropped database forces a checkpoint,
     * which the parallel threads of Infection wait for in turn, so the tests share one database.
     */
    private static function database(): string
    {
        return TestEnvironment::perThread('ppa_test_R&D_altered_database');
    }

    private static function owner(): string
    {
        return TestEnvironment::perThread('ppa_test_R&D_altered_owner');
    }

    private static function dropTheDatabase(): void
    {
        $connection = RunningServer::connection();

        $connection->exec('DROP DATABASE IF EXISTS "' . self::database() . '"');
        $connection->exec('DROP ROLE IF EXISTS "' . self::owner() . '"');
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(PageBody::of(AlterDb::class), 'form');
    }
}
