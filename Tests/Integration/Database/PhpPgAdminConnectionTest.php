<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\Entities\{Server, ServerSession};
use PhpPgAdmin\DDD\ValueObjects\Server\{Host, Name, Port};
use PhpPgAdmin\DDD\ValueObjects\ServerSession\{Password, Username};
use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\DDD\ValueObjects\Tablespace\{Comment, Location, Name as TablespaceName, Owner};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestEnvironment;

#[CoversClass(PhpPgAdminConnection::class)]
final class PhpPgAdminConnectionTest extends TestCase
{
    private const USER_PASSWORD = 'ppa_test_user';

    private const COMMENT_OF_THE_DATABASE = 'The database of the user';

    private const COMMENT_WITH_QUOTES = 'It\'s "quoted"';

    /**
     * PostgreSQL rejects a location with a single quote itself, with this SQLSTATE (invalid_name).
     */
    private const SQLSTATE_INVALID_NAME = '42602';

    /**
     * The SQLSTATE of a missing directory (undefined_file).
     */
    private const SQLSTATE_UNDEFINED_FILE = '58P01';

    /**
     * The connection that created the user, which drops it again after the test.
     */
    private ?PhpPgAdminConnection $administrator = null;

    /**
     * Whether the test created tablespaces, which are dropped again after it.
     */
    private bool $createsTablespaces = false;

    public function testLoginDataIsValidForTheRunningServer(): void
    {
        self::assertTrue(self::loginDataIsValid(self::configuredServer(TestEnvironment::RUNNING_SERVER_DESC)));
    }

    public function testLoginDataIsInvalidForTheNotRunningServer(): void
    {
        $server = self::configuredServer(TestEnvironment::NOT_RUNNING_SERVER_DESC);

        // loginDataIsValid() logs the connection error.
        $this->expectErrorLog();

        self::assertFalse(self::loginDataIsValid($server));
    }

    public function testCreateFromServerSessionConnectsToPostgresByDefault(): void
    {
        self::assertSame('postgres', self::currentDatabase(self::connection()));
    }

    public function testCreateFromServerSessionConnectsToTheGivenDatabase(): void
    {
        self::assertSame('template1', self::currentDatabase(self::connection('template1')));
    }

    public function testCreateFromServerSessionConfiguresTheConnection(): void
    {
        $connection = self::connection('template1');

        self::assertSame('UTF8', self::queryValue($connection, 'SHOW client_encoding'));
        self::assertSame('escape', self::queryValue($connection, 'SHOW bytea_output'));

        $statement = $connection->query('SELECT 1 AS one');
        self::assertNotFalse($statement);
        self::assertSame(['one' => 1], $statement->fetch(), 'Rows are fetched as associative arrays.');
    }

    public function testGetLanguagesListsTheProceduralLanguages(): void
    {
        $languages = self::languagesByName(self::connection()->getLanguages());

        self::assertArrayHasKey('plpgsql', $languages);
        self::assertTrue($languages['plpgsql']->IsTrusted);
        self::assertSame('plpgsql_call_handler', $languages['plpgsql']->CallHandler);

        // show_system is off in the test configuration, which hides the built-in languages.
        self::assertArrayNotHasKey('c', $languages);
        self::assertArrayNotHasKey('internal', $languages);
        self::assertArrayNotHasKey('sql', $languages);
    }

    public function testGetLanguagesWithAllListsTheBuiltInLanguagesToo(): void
    {
        $languages = self::languagesByName(self::connection()->getLanguages(all: true));

        foreach (['c', 'internal', 'plpgsql', 'sql'] as $name) {
            self::assertArrayHasKey($name, $languages);
        }

        self::assertFalse($languages['c']->IsTrusted);
        self::assertTrue($languages['sql']->IsTrusted);
        // regproc shows the missing call handler of a built-in language as "-".
        self::assertSame('-', $languages['sql']->CallHandler);

        $names = array_keys($languages);
        $sortedNames = $names;
        sort($sortedNames, SORT_STRING);
        self::assertSame($sortedNames, $names, 'Languages are sorted by name.');
    }

    public function testGetDatabasesWithOwnedOnlyListsOnlyTheDatabasesOfTheUser(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser();
        self::turnOn('owned_only');
        $connection = self::logInAs(self::user(), self::USER_PASSWORD);

        self::assertSame(
            [
                [
                    'datcollate' => 'C',
                    'datcomment' => self::COMMENT_OF_THE_DATABASE,
                    'datctype' => 'C',
                    'datencoding' => 'UTF8',
                    'datname' => self::databaseOfTheUser(),
                    'datowner' => self::user(),
                    'dbsize' => $this->sizeOfTheDatabaseOfTheUser(),
                    'tablespace' => 'pg_default',
                ],
            ],
            $connection->getDatabases(),
        );

        $this->makeTheDatabaseOfTheUserATemplate();

        // show_system is off in the test configuration, which hides the template.
        self::assertSame([], $connection->getDatabases());
    }

    public function testGetDatabasesWithoutOwnedOnlyListsTheDatabasesOfOtherUsersToo(): void
    {
        // owned_only is off in the test configuration.
        $this->createTheUser();
        $connection = self::logInAs(self::user(), self::USER_PASSWORD);

        $databases = array_column($connection->getDatabases(), 'datname');

        self::assertContains('postgres', $databases);
    }

    public function testGetDatabasesWithShowSystemListsTheTemplatesThatTakeConnections(): void
    {
        self::turnOn('show_system');

        $databases = array_column(self::connection()->getDatabases(), 'datname');

        self::assertContains('postgres', $databases);
        self::assertContains('template1', $databases);
        self::assertNotContains('template0', $databases);
    }

    public function testIsSuperUserIsTrueForASuperuser(): void
    {
        // The tests connect as a superuser, as they create roles and databases.
        self::assertTrue(self::connection()->isSuperUser());
    }

    public function testIsSuperUserIsFalseForAUserWhoIsNoSuperuser(): void
    {
        $this->createTheUser();

        self::assertFalse(self::logInAs(self::user(), self::USER_PASSWORD)->isSuperUser());
    }

    public function testCreateTablespaceKeepsQuotesInTheNameAndTheComment(): void
    {
        $connection = $this->connectionForTablespaces();

        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_QUOTES));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_QUOTES, (string)$tablespace->Comment);
    }

    public function testCreateTablespaceGivesItTheOwnerThatIsGiven(): void
    {
        $connection = $this->connectionForTablespaces();
        $this->createTheUser();

        $connection->createTablespace(self::tablespace(self::tablespaceName(), owner: self::user()));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::user(), (string)$tablespace->Owner);
    }

    public function testCreateTablespaceWithoutAnOwnerGivesItToTheConnectedUser(): void
    {
        $connection = $this->connectionForTablespaces();

        $connection->createTablespace(self::tablespace(self::tablespaceName(), owner: ''));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::queryValue($connection, 'SELECT current_user'), (string)$tablespace->Owner);
    }

    public function testUpdateTablespaceKeepsQuotesInTheNamesAndTheComment(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        $connection->updateTablespace(
            self::tablespace(self::renamedTablespaceName(), comment: self::COMMENT_WITH_QUOTES),
            self::tablespaceName(),
        );

        self::assertNull($connection->getTablespace(self::tablespaceName()));
        $tablespace = $connection->getTablespace(self::renamedTablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_QUOTES, (string)$tablespace->Comment);
    }

    public function testUpdateTablespaceThatKeepsTheNameRunsNoRename(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        // PostgreSQL refuses to rename a tablespace to its own name, as the name is taken.
        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_QUOTES),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_QUOTES, (string)$tablespace->Comment);
    }

    public function testSetCommentForTablespaceWithAnEmptyCommentRemovesTheComment(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_QUOTES));

        $connection->setCommentForTablespace(self::tablespaceName(), '');

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame('', (string)$tablespace->Comment);
    }

    public function testCreateTablespaceLeavesASingleQuoteInTheLocationToPostgreSql(): void
    {
        $exception = $this->exceptionOfCreatingTheTablespaceAt("/ppa_test_missing/it's");

        self::assertSame(self::SQLSTATE_INVALID_NAME, $exception->errorInfo[0] ?? null);
    }

    public function testCreateTablespaceLooksForTheLocationAsItIsGiven(): void
    {
        $location = '/ppa_test_missing/"quoted"';

        $exception = $this->exceptionOfCreatingTheTablespaceAt($location);

        self::assertSame(self::SQLSTATE_UNDEFINED_FILE, $exception->errorInfo[0] ?? null);
        // The message names the directory in every language.
        self::assertStringContainsString($location, $exception->getMessage());
    }

    protected function tearDown(): void
    {
        // Config reads the configuration file again.
        (new \ReflectionProperty(Config::class, 'conf'))->setValue(null, null);

        if (!is_null($this->administrator)) {
            self::dropTheUser($this->administrator);
        }

        // A failed statement can leave the connection of the test in an aborted transaction, so a new one drops them.
        if ($this->createsTablespaces) {
            self::dropTheTablespaces(self::connection());
        }
    }

    /**
     * A role that is no superuser, so owned_only applies to it.
     */
    private function createTheUser(): void
    {
        $administrator = self::connection();
        $this->administrator = $administrator;

        // Removes what an aborted run of this thread left behind.
        self::dropTheUser($administrator);

        $administrator->exec('CREATE ROLE "' . self::user() . '" LOGIN PASSWORD \'' . self::USER_PASSWORD . '\'');
    }

    /**
     * Every dropped database forces a checkpoint, which the parallel threads of Infection wait for in turn, so the
     * tests create as few databases as they can.
     */
    private function createADatabaseOfTheUser(): void
    {
        self::assertNotNull($this->administrator, 'Create the user first.');

        $database = self::databaseOfTheUser();
        // template1 cannot be copied while another test is connected to it, template0 takes no connections.
        // The servers differ in their locale, so the database gets its own.
        $this->administrator->exec(
            'CREATE DATABASE "' . $database . '" OWNER "' . self::user() . '" TEMPLATE template0'
                . " ENCODING 'UTF8' LOCALE_PROVIDER libc LOCALE 'C'",
        );
        $this->administrator->exec(
            'COMMENT ON DATABASE "' . $database . '" IS \'' . self::COMMENT_OF_THE_DATABASE . '\'',
        );
    }

    private function sizeOfTheDatabaseOfTheUser(): int
    {
        self::assertNotNull($this->administrator, 'Create the user first.');

        $size = self::queryValue(
            $this->administrator,
            "SELECT pg_database_size('" . self::databaseOfTheUser() . "')",
        );

        self::assertIsInt($size);

        return $size;
    }

    private function makeTheDatabaseOfTheUserATemplate(): void
    {
        self::assertNotNull($this->administrator, 'Create the user first.');

        $this->administrator->exec('ALTER DATABASE "' . self::databaseOfTheUser() . '" IS_TEMPLATE true');
    }

    /**
     * A tablespace needs an empty directory on the database server, unless it is an in-place tablespace, which
     * PostgreSQL keeps in its data directory. The tests use those, which takes PostgreSQL 15 or later.
     */
    private function connectionForTablespaces(): PhpPgAdminConnection
    {
        $connection = self::connection();
        $this->createsTablespaces = true;

        // Removes what an aborted run of this thread left behind.
        self::dropTheTablespaces($connection);

        $connection->exec('SET allow_in_place_tablespaces = true');

        return $connection;
    }

    private function exceptionOfCreatingTheTablespaceAt(string $location): \PDOException
    {
        $connection = $this->connectionForTablespaces();

        try {
            $connection->createTablespace(self::tablespace(self::tablespaceName(), location: $location));
        } catch (\PDOException $e) {
            return $e;
        }

        self::fail("The tablespace was created at {$location}.");
    }

    private static function connection(?string $database = null): PhpPgAdminConnection
    {
        $serverSession = self::serverSession(
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
        );

        $connection = is_null($database)
            ? PhpPgAdminConnection::createFromServerSession($serverSession)
            : PhpPgAdminConnection::createFromServerSession($serverSession, $database);

        self::assertNotNull($connection);

        return $connection;
    }

    /**
     * The quote shows that getDatabases() keeps the name of the user out of its SQL.
     */
    private static function user(): string
    {
        return TestEnvironment::perThread("ppa_test_o'user");
    }

    private static function databaseOfTheUser(): string
    {
        return TestEnvironment::perThread('ppa_test_user_database');
    }

    private static function dropTheUser(PhpPgAdminConnection $administrator): void
    {
        // The user cannot be dropped while it owns a tablespace.
        self::dropTheTablespaces($administrator);

        $database = self::databaseOfTheUser();
        $isTemplate = self::queryValue(
            $administrator,
            "SELECT datistemplate FROM pg_database WHERE datname = '{$database}'",
        );

        // A template cannot be dropped.
        if ($isTemplate === true) {
            $administrator->exec('ALTER DATABASE "' . $database . '" IS_TEMPLATE false');
        }

        $administrator->exec('DROP DATABASE IF EXISTS "' . $database . '"');
        $administrator->exec('DROP ROLE IF EXISTS "' . self::user() . '"');
    }

    private static function tablespaceName(): string
    {
        return TestEnvironment::perThread('ppa_test_"tablespace"');
    }

    private static function renamedTablespaceName(): string
    {
        return TestEnvironment::perThread('ppa_test_"renamed"');
    }

    /**
     * An empty location makes an in-place tablespace. Unless another owner is given, the user of the tests owns it.
     * An empty owner names none.
     */
    private static function tablespace(
        string $name,
        string $location = '',
        string $comment = '',
        ?string $owner = null,
    ): Tablespace {
        return new Tablespace(
            name: new TablespaceName($name),
            owner: new Owner($owner ?? TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres'),
            location: new Location($location),
            comment: new Comment($comment),
        );
    }

    private static function dropTheTablespaces(PhpPgAdminConnection $administrator): void
    {
        foreach ([self::tablespaceName(), self::renamedTablespaceName()] as $name) {
            $administrator->exec('DROP TABLESPACE IF EXISTS "' . str_replace('"', '""', $name) . '"');
        }
    }

    /**
     * Config reads the configuration file only once, so this changes what it read.
     */
    private static function turnOn(string $setting): void
    {
        $conf = new \ReflectionProperty(Config::class, 'conf');
        // Reads the configuration file.
        Config::ownedOnly();
        $configuration = $conf->getValue();
        self::assertIsArray($configuration);
        $conf->setValue(null, [$setting => true] + $configuration);
    }

    private static function logInAs(string $username, string $password): PhpPgAdminConnection
    {
        $connection = PhpPgAdminConnection::createFromServerSession(self::serverSession($username, $password));

        self::assertNotNull($connection);

        return $connection;
    }

    private static function serverSession(string $username, string $password): ServerSession
    {
        return new ServerSession(
            username: new Username($username),
            password: new Password($password),
            name: new Name(TestEnvironment::RUNNING_SERVER_DESC),
            host: new Host(
                TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_HOSTNAME')
                    ?? TestEnvironment::RUNNING_SERVER_DEFAULT_HOST,
            ),
            port: new Port(TestEnvironment::RUNNING_SERVER_PORT),
        );
    }

    private static function currentDatabase(PhpPgAdminConnection $connection): mixed
    {
        return self::queryValue($connection, 'SELECT current_database()');
    }

    /**
     * @param array<\PhpPgAdmin\DDD\ValueObjects\Language> $languages
     * @return array<string, \PhpPgAdmin\DDD\ValueObjects\Language>
     */
    private static function languagesByName(array $languages): array
    {
        $languagesByName = [];

        foreach ($languages as $language) {
            $languagesByName[$language->Name] = $language;
        }

        return $languagesByName;
    }

    private static function configuredServer(string $description): Server
    {
        foreach (Config::getServers() as $server) {
            if ((string)$server->Name === $description) {
                return $server;
            }
        }

        self::fail("No server \"{$description}\" in the test configuration.");
    }

    private static function loginDataIsValid(Server $server): bool
    {
        return PhpPgAdminConnection::loginDataIsValid(
            host: (string)$server->Host,
            port: $server->Port->Value,
            sslmode: $server->SslMode,
            user: TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
            password: TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
        );
    }

    private static function queryValue(PhpPgAdminConnection $connection, string $sql): mixed
    {
        $statement = $connection->query($sql);

        self::assertNotFalse($statement);

        return $statement->fetchColumn();
    }
}
