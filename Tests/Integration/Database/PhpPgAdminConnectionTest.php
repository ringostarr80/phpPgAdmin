<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\Entities\{Server, ServerSession};
use PhpPgAdmin\DDD\ValueObjects\Server\{DatabaseName, Host, Name, Port};
use PhpPgAdmin\DDD\ValueObjects\ServerSession\{Password, Username};
use PhpPgAdmin\DDD\ValueObjects\{Role, Tablespace};
use PhpPgAdmin\DDD\ValueObjects\Tablespace\{Comment, Location, Name as TablespaceName, Owner};
use PHPUnit\Framework\Attributes\{CoversClass, TestWith};
use PHPUnit\Framework\TestCase;
use Tests\Support\{NameZero, RunningServer, TestEnvironment};

#[CoversClass(PhpPgAdminConnection::class)]
final class PhpPgAdminConnectionTest extends TestCase
{
    private const USER_PASSWORD = 'ppa_test_user';

    private const ROLE_PASSWORD = 'ppa_test_role';

    private const COMMENT_OF_THE_DATABASE = 'The database of the user';

    private const COMMENT_WITH_QUOTES = 'It\'s "quoted"';

    /**
     * PostgreSQL stores a comment as it is given, with the blanks and line breaks around it.
     */
    private const COMMENT_WITH_BLANKS = "  Old data\n";

    /**
     * Browsers post every line break of a textarea as CRLF, whatever line breaks the comment had before.
     */
    private const COMMENT_AS_POSTED = "Line 1\r\nLine 2";

    private const COMMENT_WITH_LF = "Line 1\nLine 2";

    /**
     * PostgreSQL rejects a location with a single quote itself, with this SQLSTATE (invalid_name).
     */
    private const SQLSTATE_INVALID_NAME = '42602';

    /**
     * The SQLSTATE of a missing directory (undefined_file).
     */
    private const SQLSTATE_UNDEFINED_FILE = '58P01';

    /**
     * The SQLSTATE of a name that is taken (duplicate_object).
     */
    private const SQLSTATE_DUPLICATE_OBJECT = '42710';

    /**
     * The SQLSTATE of a role that does not exist (undefined_object).
     */
    private const SQLSTATE_UNDEFINED_OBJECT = '42704';

    private const MISSING_ROLE = 'ppa_test_missing_role';

    /**
     * The connection that created the user, which drops it again after the test.
     */
    private ?PhpPgAdminConnection $administrator = null;

    /**
     * Whether the test created tablespaces, which are dropped again after it.
     */
    private bool $createsTablespaces = false;

    /**
     * Whether the test created roles, which are dropped again after it.
     */
    private bool $createsRoles = false;

    private ?NameZero $nameZero = null;

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

    public function testLoginDataIsValidForARoleWithoutADatabaseOfItsOwnName(): void
    {
        // A connection that names no database goes to the database named like the role, which most roles do not have.
        $this->createTheUser();

        self::assertTrue(self::loginDataIsValid(
            self::configuredServer(TestEnvironment::RUNNING_SERVER_DESC),
            self::user(),
            self::USER_PASSWORD,
        ));
    }

    public function testCreateFromServerSessionConnectsToTheDefaultDatabaseOfTheServerByDefault(): void
    {
        // Like the legacy pages and the login check, so a user who may not connect to postgres can use the pages.
        self::assertSame('template1', self::currentDatabase(self::connection(defaultDb: 'template1')));
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

    public function testCreateFromServerSessionConnectsAsTheRoleWithTheBlanksAroundItsName(): void
    {
        // The login checks the name with its blanks, so the pages connect with them too.
        $this->createThePaddedUser();

        $connection = self::logInAs(self::paddedUserName(), self::USER_PASSWORD);

        self::assertSame(self::paddedUserName(), self::queryValue($connection, 'SELECT current_user'));
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

    public function testIsSuperUserWithTheNameOfASuperuserIsTrueForAUserWhoIsNone(): void
    {
        $administrator = self::queryValue(self::connection(), 'SELECT current_user');
        self::assertIsString($administrator);
        $this->createTheUser();

        self::assertTrue(self::logInAs(self::user(), self::USER_PASSWORD)->isSuperUser($administrator));
    }

    public function testIsSuperUserWithTheNameOfAUserWhoIsNoSuperuserIsFalseForASuperuser(): void
    {
        $this->createTheUser();

        self::assertFalse(self::connection()->isSuperUser(self::user()));
    }

    public function testIsSuperUserWithTheNameOfARoleThatDoesNotExistIsFalse(): void
    {
        self::assertFalse(self::connection()->isSuperUser(self::MISSING_ROLE));
    }

    public function testIsSuperUserWithTheNameZeroAsksForTheUserZero(): void
    {
        // PHP's empty() takes "0" for an empty string, PostgreSQL takes it as a name.
        RunningServer::logIn();
        $this->nameZero = NameZero::take();
        $this->nameZero->createUser();

        // The user of the tests is a superuser, the user "0" is none.
        self::assertFalse(self::connection()->isSuperUser(NameZero::NAME));
    }

    public function testAlterDatabaseCommitsTheNewNameAndTheComment(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser();
        $connection = self::connection();

        $connection->alterDatabase(
            dbName: self::databaseOfTheUser(),
            newName: self::renamedDatabaseOfTheUser(),
            comment: self::COMMENT_WITH_QUOTES,
        );

        // Another connection sees only what was committed.
        $comments = array_column(self::connection()->getDatabases(), 'datcomment', 'datname');
        self::assertArrayNotHasKey(self::databaseOfTheUser(), $comments);
        self::assertSame(self::COMMENT_WITH_QUOTES, $comments[self::renamedDatabaseOfTheUser()] ?? null);
    }

    public function testAlterDatabaseThatKeepsTheNameChangesOnlyTheOwnerAndTheComment(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser();
        $connection = self::connection();
        $administrator = self::queryValue($connection, 'SELECT current_user');
        self::assertIsString($administrator);

        // PostgreSQL refuses to rename a database to its own name, as the name is taken. A comment of null removes it.
        $connection->alterDatabase(
            dbName: self::databaseOfTheUser(),
            newName: self::databaseOfTheUser(),
            newOwner: $administrator,
            comment: null,
        );

        $database = array_column($connection->getDatabases(), null, 'datname')[self::databaseOfTheUser()] ?? null;
        self::assertNotNull($database);
        self::assertSame($administrator, $database['datowner']);
        self::assertSame('', $database['datcomment']);
    }

    #[TestWith([self::COMMENT_WITH_LF])]
    #[TestWith(["Line 1\rLine 2"])]
    public function testAlterDatabaseKeepsTheLineBreaksOfAnUnchangedComment(string $storedComment): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser($storedComment);
        $connection = self::connection();

        $connection->alterDatabase(
            dbName: self::databaseOfTheUser(),
            newName: self::databaseOfTheUser(),
            comment: self::COMMENT_AS_POSTED,
        );

        self::assertSame($storedComment, $connection->getDatabaseComment(self::databaseOfTheUser()));
    }

    public function testAlterDatabaseThatRenamesItKeepsTheLineBreaksOfAnUnchangedComment(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser(self::COMMENT_WITH_LF);
        $connection = self::connection();

        $connection->alterDatabase(
            dbName: self::databaseOfTheUser(),
            newName: self::renamedDatabaseOfTheUser(),
            comment: self::COMMENT_AS_POSTED,
        );

        self::assertSame(self::COMMENT_WITH_LF, $connection->getDatabaseComment(self::renamedDatabaseOfTheUser()));
    }

    public function testAlterDatabaseStoresAnEditedCommentWithTheLineBreaksItIsGiven(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser(self::COMMENT_WITH_LF);
        $connection = self::connection();

        $connection->alterDatabase(
            dbName: self::databaseOfTheUser(),
            newName: self::databaseOfTheUser(),
            comment: "Line 1\r\nLine 3",
        );

        self::assertSame("Line 1\r\nLine 3", $connection->getDatabaseComment(self::databaseOfTheUser()));
    }

    public function testAlterDatabaseThatFailsUndoesWhatItChangedAndKeepsTheConnectionUsable(): void
    {
        $this->createTheUser();
        $this->createADatabaseOfTheUser();
        $connection = self::connection();

        // The rename works, then the owner fails, as there is no such role.
        try {
            $connection->alterDatabase(
                dbName: self::databaseOfTheUser(),
                newName: self::renamedDatabaseOfTheUser(),
                newOwner: self::MISSING_ROLE,
            );
            self::fail('The database got an owner that does not exist.');
        } catch (\PDOException $e) {
            self::assertSame(self::SQLSTATE_UNDEFINED_OBJECT, $e->errorInfo[0] ?? null);
        }

        // An aborted transaction would refuse this query.
        $databases = array_column($connection->getDatabases(), 'datname');
        self::assertContains(self::databaseOfTheUser(), $databases);
        self::assertNotContains(self::renamedDatabaseOfTheUser(), $databases);
    }

    public function testCreateTablespaceKeepsQuotesInTheNameAndTheComment(): void
    {
        $connection = $this->connectionForTablespaces();

        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_QUOTES));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_QUOTES, (string)$tablespace->Comment);
    }

    public function testCreateTablespaceKeepsTheBlanksAroundTheComment(): void
    {
        $connection = $this->connectionForTablespaces();

        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_BLANKS));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_BLANKS, (string)$tablespace->Comment);
    }

    public function testGetTablespaceKeepsTheBlanksAroundTheName(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::paddedTablespaceName()));

        $tablespace = $connection->getTablespace(self::paddedTablespaceName());

        self::assertNotNull($tablespace);
        self::assertSame(self::paddedTablespaceName(), (string)$tablespace->Name);
    }

    public function testGetTablespacesKeepsTheBlanksAroundTheNames(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::paddedTablespaceName()));

        $names = array_map(
            static fn (Tablespace $tablespace): string => (string)$tablespace->Name,
            $connection->getTablespaces(),
        );

        self::assertContains(self::paddedTablespaceName(), $names);
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

    public function testCreateTablespaceKeepsTheBlanksAroundTheOwner(): void
    {
        $connection = $this->connectionForTablespaces();
        $this->createThePaddedOwner($connection);

        $connection->createTablespace(self::tablespace(self::tablespaceName(), owner: self::paddedOwnerName()));

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::paddedOwnerName(), (string)$tablespace->Owner);
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

    public function testUpdateTablespaceKeepsTheBlanksAroundTheComment(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_BLANKS),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_BLANKS, (string)$tablespace->Comment);
    }

    #[TestWith([self::COMMENT_WITH_LF])]
    #[TestWith(["Line 1\rLine 2"])]
    public function testUpdateTablespaceKeepsTheLineBreaksOfAnUnchangedComment(string $storedComment): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: $storedComment));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), comment: self::COMMENT_AS_POSTED),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame($storedComment, (string)$tablespace->Comment);
    }

    public function testUpdateTablespaceThatRenamesItKeepsTheLineBreaksOfAnUnchangedComment(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_LF));

        $connection->updateTablespace(
            self::tablespace(self::renamedTablespaceName(), comment: self::COMMENT_AS_POSTED),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::renamedTablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_LF, (string)$tablespace->Comment);
    }

    public function testUpdateTablespaceStoresAnEditedCommentWithTheLineBreaksItIsGiven(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_LF));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), comment: "Line 1\r\nLine 3"),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame("Line 1\r\nLine 3", (string)$tablespace->Comment);
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

    public function testUpdateTablespaceGivesItTheOwnerThatIsGiven(): void
    {
        $connection = $this->connectionForTablespaces();
        $this->createTheUser();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), owner: self::user()),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::user(), (string)$tablespace->Owner);
    }

    public function testUpdateTablespaceKeepsTheBlanksAroundTheOwner(): void
    {
        $connection = $this->connectionForTablespaces();
        $this->createThePaddedOwner($connection);
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), owner: self::paddedOwnerName()),
            self::tablespaceName(),
        );

        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::paddedOwnerName(), (string)$tablespace->Owner);
    }

    public function testUpdateTablespaceCommitsWhatItChanged(): void
    {
        $connection = $this->connectionForTablespaces();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));

        $connection->updateTablespace(
            self::tablespace(self::tablespaceName(), comment: self::COMMENT_WITH_QUOTES),
            self::tablespaceName(),
        );

        // Another connection sees only what was committed.
        $tablespace = self::connection()->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::COMMENT_WITH_QUOTES, (string)$tablespace->Comment);
    }

    public function testUpdateTablespaceThatFailsUndoesWhatItChangedAndKeepsTheConnectionUsable(): void
    {
        $connection = $this->connectionForTablespaces();
        $this->createTheUser();
        $connection->createTablespace(self::tablespace(self::tablespaceName()));
        $connection->createTablespace(self::tablespace(self::renamedTablespaceName()));

        // The new name is taken, so the rename fails after the owner has changed.
        try {
            $connection->updateTablespace(
                self::tablespace(self::renamedTablespaceName(), owner: self::user()),
                self::tablespaceName(),
            );
            self::fail('The tablespace was renamed to a name that is taken.');
        } catch (\PDOException $e) {
            self::assertSame(self::SQLSTATE_DUPLICATE_OBJECT, $e->errorInfo[0] ?? null);
        }

        // An aborted transaction would refuse these queries.
        $tablespace = $connection->getTablespace(self::tablespaceName());
        self::assertNotNull($tablespace);
        self::assertSame(self::queryValue($connection, 'SELECT current_user'), (string)$tablespace->Owner);
    }

    public function testUpdateTablespaceThatDoesNotExistLeavesTheErrorToPostgreSql(): void
    {
        $connection = $this->connectionForTablespaces();

        // The alter page shows the error of PostgreSQL, which names the tablespace.
        try {
            $connection->updateTablespace(self::tablespace(self::tablespaceName()), self::tablespaceName());
            self::fail('A tablespace that does not exist was altered.');
        } catch (\PDOException $e) {
            self::assertSame(self::SQLSTATE_UNDEFINED_OBJECT, $e->errorInfo[0] ?? null);
        }
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

    public function testCreateRoleKeepsQuotesInTheNamesOfTheRolesItIsGiven(): void
    {
        $connection = $this->connectionForRoles();

        $connection->createRole(
            role: new Role(self::roleName()),
            password: '',
            memberOf: [self::groupName()],
            members: [self::memberName()],
            adminMembers: [self::adminMemberName()],
        );

        self::assertSame([self::groupName()], $connection->getMemberOf(self::roleName()));
        self::assertSame([self::memberName()], $connection->getMembers(self::roleName()));
        self::assertSame([self::adminMemberName()], $connection->getMembers(self::roleName(), onlyAdminMembers: true));
    }

    public function testUpdateRoleKeepsQuotesInTheNamesOfTheRoles(): void
    {
        $connection = $this->connectionForRoles();
        $connection->exec('CREATE ROLE ' . self::quoteIdentifier(self::roleName()) . ' NOCREATEDB');

        $connection->updateRole(
            role: new Role(self::roleName(), canCreateDb: true),
            newMemberOf: [self::groupName()],
            newMembers: [self::memberName()],
            newAdminMembers: [self::adminMemberName()],
        );

        $role = $connection->getRole(self::roleName());
        self::assertNotNull($role);
        self::assertTrue($role->CanCreateDb);
        // Seen from the group, which lists a member with the admin option among its admin members instead.
        self::assertSame([self::roleName()], $connection->getMembers(self::groupName()));
        self::assertSame([self::memberName()], $connection->getMembers(self::roleName()));
        self::assertSame([self::adminMemberName()], $connection->getMembers(self::roleName(), onlyAdminMembers: true));
    }

    public function testCreateRoleGivesItTheAttributesAndThePasswordThatAreGiven(): void
    {
        $connection = $this->connectionForRoles();
        $role = self::roleThatDiffersFromTheDefaults();

        // The form passes no roles as empty lists.
        $connection->createRole($role, self::ROLE_PASSWORD, memberOf: [], members: [], adminMembers: []);

        self::assertEquals($role, $connection->getRole(self::roleName()));
        self::assertSame(self::encryptedRolePassword($connection), self::passwordOfTheRole($connection));
    }

    public function testUpdateRoleGivesItTheAttributesAndThePasswordThatAreGiven(): void
    {
        $connection = $this->connectionForRoles();
        $connection->exec('CREATE ROLE ' . self::quoteIdentifier(self::roleName()));
        $role = self::roleThatDiffersFromTheDefaults();

        $connection->updateRole($role, self::ROLE_PASSWORD);

        self::assertEquals($role, $connection->getRole(self::roleName()));
        self::assertSame(self::encryptedRolePassword($connection), self::passwordOfTheRole($connection));
    }

    public function testUpdateRoleKeepsTheRolesThatAreGivenAgain(): void
    {
        $connection = $this->connectionForRoles();
        self::createTheRoleWithItsRoles($connection);

        $connection->updateRole(
            role: new Role(self::roleName()),
            newMemberOf: [self::groupName()],
            newMembers: [self::memberName()],
            newAdminMembers: [self::adminMemberName()],
        );

        self::assertSame([self::groupName()], $connection->getMemberOf(self::roleName()));
        self::assertSame([self::memberName()], $connection->getMembers(self::roleName()));
        self::assertSame([self::adminMemberName()], $connection->getMembers(self::roleName(), onlyAdminMembers: true));
    }

    public function testUpdateRoleRemovesTheRolesThatAreNotGivenAnyMore(): void
    {
        $connection = $this->connectionForRoles();
        self::createTheRoleWithItsRoles($connection);

        $connection->updateRole(new Role(self::roleName()));

        self::assertSame([], $connection->getMemberOf(self::roleName()));
        // An admin member that is removed loses only the admin option and stays a member.
        self::assertSame([self::adminMemberName()], $connection->getMembers(self::roleName()));
        self::assertSame([], $connection->getMembers(self::roleName(), onlyAdminMembers: true));
    }

    public function testUpdateRoleCommitsWhatItChanged(): void
    {
        $connection = $this->connectionForRoles();
        $connection->exec('CREATE ROLE ' . self::quoteIdentifier(self::roleName()));

        $connection->updateRole(
            role: new Role(self::roleName(), canCreateDb: true),
            newMemberOf: [self::groupName()],
        );

        // Another connection sees only what was committed.
        $administrator = self::connection();
        $role = $administrator->getRole(self::roleName());
        self::assertNotNull($role);
        self::assertTrue($role->CanCreateDb);
        self::assertSame([self::groupName()], $administrator->getMemberOf(self::roleName()));
    }

    public function testUpdateRoleThatFailsUndoesWhatItChangedAndKeepsTheConnectionUsable(): void
    {
        $connection = $this->connectionForRoles();
        self::createTheRoleWithItsRoles($connection);

        // The attributes change and the group is revoked, then the grant fails, as there is no such role.
        try {
            $connection->updateRole(
                role: new Role(self::roleName(), canCreateDb: true),
                newMembers: [self::MISSING_ROLE],
            );
            self::fail('The role got a member that does not exist.');
        } catch (\PDOException $e) {
            self::assertSame(self::SQLSTATE_UNDEFINED_OBJECT, $e->errorInfo[0] ?? null);
        }

        // An aborted transaction would refuse these queries.
        $role = $connection->getRole(self::roleName());
        self::assertNotNull($role);
        self::assertFalse($role->CanCreateDb);
        self::assertSame([self::groupName()], $connection->getMemberOf(self::roleName()));
    }

    public function testGetRolesLeavesOutTheRoleThatIsGiven(): void
    {
        $connection = $this->connectionForRoles();

        $rolenames = array_map(static fn (Role $role): string => $role->Name, $connection->getRoles(self::groupName()));

        self::assertNotContains(self::groupName(), $rolenames);
        self::assertContains(self::memberName(), $rolenames);
    }

    #[\Override]
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

        if ($this->createsRoles) {
            self::dropTheRoles(self::connection());
        }

        $this->nameZero?->release();
        // NameZero connects with the login of the session.
        $_SESSION = [];
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
    private function createADatabaseOfTheUser(string $comment = self::COMMENT_OF_THE_DATABASE): void
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
            'COMMENT ON DATABASE "' . $database . '" IS ' . $this->administrator->quote($comment),
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

    /**
     * Comes after connectionForTablespaces(), as a role cannot be dropped while it owns a tablespace that an aborted
     * run of this thread left behind.
     */
    private function createThePaddedOwner(PhpPgAdminConnection $connection): void
    {
        $this->createsRoles = true;
        $quotedName = self::quoteIdentifier(self::paddedOwnerName());

        // Removes what an aborted run of this thread left behind.
        $connection->exec("DROP ROLE IF EXISTS {$quotedName}");
        $connection->exec("CREATE ROLE {$quotedName}");
    }

    private function createThePaddedUser(): void
    {
        $this->createsRoles = true;
        $administrator = self::connection();
        $quotedName = self::quoteIdentifier(self::paddedUserName());

        // Removes what an aborted run of this thread left behind.
        $administrator->exec("DROP ROLE IF EXISTS {$quotedName}");
        $administrator->exec("CREATE ROLE {$quotedName} LOGIN PASSWORD '" . self::USER_PASSWORD . "'");
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

    /**
     * Creates the roles that the role of the tests is given, which are dropped again after the test.
     */
    private function connectionForRoles(): PhpPgAdminConnection
    {
        $connection = self::connection();
        $this->createsRoles = true;

        // Removes what an aborted run of this thread left behind.
        self::dropTheRoles($connection);

        foreach ([self::groupName(), self::memberName(), self::adminMemberName()] as $name) {
            $connection->exec('CREATE ROLE ' . self::quoteIdentifier($name));
        }

        return $connection;
    }

    private static function connection(?string $database = null, string $defaultDb = 'postgres'): PhpPgAdminConnection
    {
        $serverSession = self::serverSession(
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres',
            TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '',
            $defaultDb,
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

    private static function renamedDatabaseOfTheUser(): string
    {
        return TestEnvironment::perThread('ppa_test_renamed_database');
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
        // The user cannot be dropped either while it owns a renamed database.
        $administrator->exec('DROP DATABASE IF EXISTS "' . self::renamedDatabaseOfTheUser() . '"');
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
     * PostgreSQL keeps blanks around a quoted name.
     */
    private static function paddedTablespaceName(): string
    {
        return ' ' . TestEnvironment::perThread('ppa_test_"padded"') . ' ';
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
        foreach ([self::tablespaceName(), self::renamedTablespaceName(), self::paddedTablespaceName()] as $name) {
            $administrator->exec('DROP TABLESPACE IF EXISTS "' . str_replace('"', '""', $name) . '"');
        }
    }

    /**
     * A role belongs to no database, so the tests create roles without the checkpoint that a dropped database forces.
     */
    private static function roleName(): string
    {
        return TestEnvironment::perThread('ppa_test_"role"');
    }

    private static function groupName(): string
    {
        return TestEnvironment::perThread('ppa_test_"group"');
    }

    private static function memberName(): string
    {
        return TestEnvironment::perThread('ppa_test_"member"');
    }

    private static function adminMemberName(): string
    {
        return TestEnvironment::perThread('ppa_test_"admin"');
    }

    /**
     * PostgreSQL keeps blanks around a quoted role name.
     */
    private static function paddedOwnerName(): string
    {
        return ' ' . TestEnvironment::perThread('ppa_test_"owner"') . ' ';
    }

    /**
     * PostgreSQL keeps blanks around a quoted role name.
     */
    private static function paddedUserName(): string
    {
        return ' ' . TestEnvironment::perThread('ppa_test_"login"') . ' ';
    }

    /**
     * Every attribute differs from the defaults of PostgreSQL, but the role is no superuser.
     */
    private static function roleThatDiffersFromTheDefaults(): Role
    {
        return new Role(
            name: self::roleName(),
            canCreateDb: true,
            canCreateRole: true,
            canInheritRights: false,
            canLogin: true,
            connectionLimit: 3,
        );
    }

    private static function createTheRoleWithItsRoles(PhpPgAdminConnection $administrator): void
    {
        $administrator->exec(
            'CREATE ROLE ' . self::quoteIdentifier(self::roleName())
                . ' IN ROLE ' . self::quoteIdentifier(self::groupName())
                . ' ROLE ' . self::quoteIdentifier(self::memberName())
                . ' ADMIN ' . self::quoteIdentifier(self::adminMemberName()),
        );
    }

    /**
     * PostgreSQL stores a password that it gets as an MD5 hash as it is.
     * The hash that is expected is built with the md5() of PostgreSQL.
     */
    private static function encryptedRolePassword(PhpPgAdminConnection $administrator): mixed
    {
        return self::queryValue(
            $administrator,
            "SELECT 'md5' || md5(" . $administrator->quote(self::ROLE_PASSWORD . self::roleName()) . ')',
        );
    }

    private static function passwordOfTheRole(PhpPgAdminConnection $administrator): mixed
    {
        return self::queryValue(
            $administrator,
            'SELECT rolpassword FROM pg_catalog.pg_authid WHERE rolname = ' . $administrator->quote(self::roleName()),
        );
    }

    private static function dropTheRoles(PhpPgAdminConnection $administrator): void
    {
        $names = [
            self::roleName(),
            self::groupName(),
            self::memberName(),
            self::adminMemberName(),
            self::paddedOwnerName(),
            self::paddedUserName(),
        ];

        foreach ($names as $name) {
            $administrator->exec('DROP ROLE IF EXISTS ' . self::quoteIdentifier($name));
        }
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
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

    /**
     * By default for postgres, so the tests do not leave anything in template1, which new databases copy.
     */
    private static function serverSession(
        string $username,
        string $password,
        string $defaultDb = 'postgres',
    ): ServerSession {
        return new ServerSession(
            username: new Username($username),
            password: new Password($password),
            name: new Name(TestEnvironment::RUNNING_SERVER_DESC),
            host: new Host(
                TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_HOSTNAME')
                    ?? TestEnvironment::RUNNING_SERVER_DEFAULT_HOST,
            ),
            port: new Port(TestEnvironment::RUNNING_SERVER_PORT),
            defaultDb: new DatabaseName($defaultDb),
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

    /**
     * Without a user, the login data of the superuser that runs the tests.
     */
    private static function loginDataIsValid(Server $server, ?string $user = null, string $password = ''): bool
    {
        if (is_null($user)) {
            $user = TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres';
            $password = TestEnvironment::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '';
        }

        return PhpPgAdminConnection::loginDataIsValid(
            host: (string)$server->Host,
            port: $server->Port->Value,
            sslmode: $server->SslMode,
            user: $user,
            password: $password,
            database: (string)$server->DefaultDb,
        );
    }

    private static function queryValue(PhpPgAdminConnection $connection, string $sql): mixed
    {
        $statement = $connection->query($sql);

        self::assertNotFalse($statement);

        return $statement->fetchColumn();
    }
}
