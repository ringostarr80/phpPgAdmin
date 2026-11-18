<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Infrastructure\Session\Logins;
use PhpPgAdmin\Website\AlterTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, TestEnvironment};

#[CoversMethod(AlterTablespace::class, 'buildHtmlBody')]
final class AlterTablespaceTest extends TestCase
{
    /**
     * Every database cluster has it, so the tests do not need to create a tablespace, which needs an empty directory
     * on the database server.
     */
    private const TABLESPACE = 'pg_default';

    public function testTheFormShowsTheNameAndTheOwnerOfTheTablespace(): void
    {
        $form = self::form();

        self::assertSame(self::TABLESPACE, PageBody::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame([self::bootstrapSuperuser()], PageBody::selectedValues($form, Tablespace::FORM_ID_OWNER));
    }

    public function testTheFormAltersTheTablespaceInsteadOfCreatingOne(): void
    {
        $buttons = array_map(
            static fn (\DOMElement $input): array => [$input->getAttribute('name'), $input->getAttribute('value')],
            PageBody::query(self::form(), './/input[@type="submit"]'),
        );

        self::assertSame([['alter', 'Alter'], ['cancel', 'Cancel']], $buttons);
    }

    protected function setUp(): void
    {
        $serverId = self::runningServerId();

        $_GET = [
            'server' => $serverId,
            'tablespace' => self::TABLESPACE,
        ];
        $_POST = [];
        $_SESSION = [
            'webdbLogin' => [
                $serverId => [
                    'password' => TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
                    'username' => TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(PageBody::of(AlterTablespace::class), 'form');
    }

    /**
     * The superuser that created the database cluster, who owns pg_default. Its name differs between installations.
     */
    private static function bootstrapSuperuser(): string
    {
        // PostgreSQL gives the bootstrap superuser the fixed oid 10.
        $statement = self::connection()->query('SELECT rolname FROM pg_catalog.pg_roles WHERE oid = 10');
        self::assertNotFalse($statement);
        $rolname = $statement->fetchColumn();
        self::assertIsString($rolname);

        return $rolname;
    }

    /**
     * A connection as the user the page logs in as.
     */
    private static function connection(): PhpPgAdminConnection
    {
        $connection = PhpPgAdminConnection::createFromServerSession(
            Logins::serverSession(self::runningServerId(), Config::getServers()),
        );
        self::assertNotNull($connection);

        return $connection;
    }

    private static function runningServerId(): string
    {
        foreach (Config::getServers() as $server) {
            if ((string)$server->Name === TestEnvironment::RUNNING_SERVER_DESC) {
                return $server->id();
            }
        }

        self::fail('No server "' . TestEnvironment::RUNNING_SERVER_DESC . '" in the test configuration.');
    }
}
