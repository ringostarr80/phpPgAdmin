<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\Entities\Server;
use PhpPgAdmin\DDD\ValueObjects\Server\DatabaseName;
use PhpPgAdmin\Infrastructure\Session\Logins;
use PhpPgAdmin\Website\CreateDb;
use PHPUnit\Framework\Attributes\{CoversMethod, DataProvider, TestWith};
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer, TestEnvironment};

#[CoversMethod(CreateDb::class, 'buildHtmlBody')]
#[CoversMethod(CreateDb::class, 'handlePostRequest')]
final class CreateDbTest extends TestCase
{
    /**
     * A collation of the database of the tests, which only exists there. The form offers only collations with a dot in
     * their names, like those of the locales of the operating system.
     */
    private const COLLATION = 'ppa_test.collation';

    /**
     * PostgreSQL does not create a database with the name of one that exists, and initdb creates this one in every
     * cluster.
     */
    private const EXISTING_DATABASE = 'postgres';

    /**
     * @var array<mixed>
     */
    private array $server = [];

    public function testAfterAFailedCreateThePageSaysSo(): void
    {
        self::assertSame(['Database creation failed.'], PageBody::messages(self::bodyAfterTheCreate()));
    }

    public function testAfterAFailedCreateThePageShowsTheErrorOfPostgreSql(): void
    {
        // The error names the database in every language.
        self::assertStringContainsString(self::EXISTING_DATABASE, self::errorOfPostgreSql());
    }

    public function testAfterAFailedCreateTheFormShowsTheEnteredValues(): void
    {
        $form = PageBody::onlyElement(self::bodyAfterTheCreate(), 'form');

        self::assertSame(self::EXISTING_DATABASE, PageBody::fieldValue($form, 'db-name'));
        self::assertSame('Old data', PageBody::fieldValue($form, 'db-comment'));
    }

    #[DataProvider('choicesPostgreSqlRejects')]
    public function testACreateHandsTheChoiceOnToPostgreSql(string $field, string $value): void
    {
        $_POST[$field] = $value;

        self::assertStringContainsString($value, self::errorOfPostgreSql());
    }

    public function testACreateHandsTheNameZeroOnToPostgreSql(): void
    {
        // PHP's empty() takes "0" for an empty string, PostgreSQL takes it as a name. A page that takes "0" for no
        // name says so instead of handing the create on to PostgreSQL.
        $_POST['formName'] = '0';
        // The page would end the tests with die after a create, so PostgreSQL has to reject this one. Each of them
        // keeps it from creating the database.
        $_POST['formTemplate'] = 'ppa_test_missing_template';
        $_POST['formTablespace'] = 'ppa_test_missing_tablespace';

        self::assertSame(['Database creation failed.'], PageBody::messages(self::bodyAfterTheCreate()));
    }

    #[TestWith(['formCollate'])]
    #[TestWith(['formCType'])]
    public function testACreateHandsTheChosenLocaleOnToPostgreSql(string $field): void
    {
        // PostgreSQL copies a template database other than template0 only with its own locales.
        $locale = self::aLocaleTheTemplateDoesNotUse();
        $_POST[$field] = $locale;

        self::assertStringContainsString($locale, self::errorOfPostgreSql());
    }

    public function testTheFormOffersTemplate0AndTemplate1BeforeTheDatabasesOfTheServer(): void
    {
        $options = self::optionsOf('db-template');
        $values = self::valuesOf($options);

        self::assertSame(['template0', 'template1'], array_slice($values, 0, 2));
        // Besides the database of the tests, the server has the databases of the tests running at the same time.
        self::assertContains(self::database(), array_slice($values, 2));
        self::assertSame($values, self::textsOf($options));
    }

    public function testWithoutAChosenTemplateTheFormChoosesTemplate1(): void
    {
        self::assertSame(['template1'], PageBody::selectedValues(self::form(), 'db-template'));
    }

    #[DataProvider('templates')]
    public function testAfterAFailedCreateTheFormShowsTheChosenTemplate(string $template): void
    {
        $_POST['formTemplate'] = $template;

        self::assertSame([$template], PageBody::selectedValues(self::form(), 'db-template'));
    }

    #[TestWith(['db-collation'])]
    #[TestWith(['db-ctype'])]
    public function testTheFormOffersNoLocaleAndTheCollationsOfTheDefaultDatabaseOfTheServer(string $select): void
    {
        self::makeTheDatabaseTheDefaultOfTheServer();

        $options = self::optionsOf($select);
        $locales = ['', ...self::connectionToTheDatabase()->getAvailableCollations()];

        self::assertContains(self::COLLATION, $locales);
        self::assertSame($locales, self::valuesOf($options));
        self::assertSame($locales, self::textsOf($options));
    }

    #[TestWith(['formCollate', 'db-collation'])]
    #[TestWith(['formCType', 'db-ctype'])]
    public function testAfterAFailedCreateTheFormShowsTheChosenLocale(string $field, string $select): void
    {
        self::makeTheDatabaseTheDefaultOfTheServer();
        $_POST[$field] = self::COLLATION;

        self::assertSame([self::COLLATION], PageBody::selectedValues(self::form(), $select));
    }

    /**
     * PostgreSQL checks these choices before it looks for a database with the name, and its error names the choice it
     * rejects.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function choicesPostgreSqlRejects(): iterable
    {
        yield 'a template that does not exist' => ['formTemplate', 'ppa_test_missing_template'];
        yield 'a tablespace that does not exist' => ['formTablespace', 'ppa_test_missing_tablespace'];
        // The template database of the test servers uses UTF-8, and PostgreSQL copies it into no other encoding.
        yield 'an encoding other than that of the template' => ['formEncoding', 'LATIN1'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function templates(): iterable
    {
        yield 'template0' => ['template0'];
        yield 'template1' => ['template1'];
        yield 'a database of the server' => [self::database()];
    }

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        RunningServer::logIn();
        $connection = RunningServer::connection();

        // Removes what an aborted run of this thread left behind.
        self::dropTheDatabase();

        // template0 takes no connections, so the tests running at the same time do not keep it from being copied.
        $connection->exec('CREATE DATABASE "' . self::database() . '" TEMPLATE template0');
        self::connectionToTheDatabase()->exec(
            'CREATE COLLATION "' . self::COLLATION . '" (PROVIDER = libc, LOCALE = \'C\')',
        );

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
        $this->server = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['server' => RunningServer::id()];
        $_POST = [
            'formComment' => 'Old data',
            'formName' => self::EXISTING_DATABASE,
        ];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        // Config reads the configuration file again.
        (new \ReflectionProperty(Config::class, 'conf'))->setValue(null, null);
    }

    /**
     * The database of the tests, which the form offers as a template. Every dropped database forces a checkpoint,
     * which the parallel threads of Infection wait for in turn, so the tests share one database.
     */
    private static function database(): string
    {
        return TestEnvironment::perThread('ppa_test_default_database');
    }

    private static function dropTheDatabase(): void
    {
        RunningServer::connection()->exec('DROP DATABASE IF EXISTS "' . self::database() . '"');
    }

    private static function connectionToTheDatabase(): PhpPgAdminConnection
    {
        $connection = PhpPgAdminConnection::createFromServerSession(
            Logins::serverSession(RunningServer::id(), Config::getServers()),
            self::database(),
        );
        self::assertNotNull($connection);

        return $connection;
    }

    /**
     * The page reads the collations from the default database of the server. The one of the test configuration is
     * template1, which the tests leave as it is, because new databases copy it. Config reads the configuration file
     * only once, so this changes what it read.
     */
    private static function makeTheDatabaseTheDefaultOfTheServer(): void
    {
        $conf = new \ReflectionProperty(Config::class, 'conf');
        $servers = array_map(
            static fn (Server $server): Server => $server->id() === RunningServer::id()
                ? new Server(
                    name: $server->Name,
                    host: $server->Host,
                    port: $server->Port,
                    sslMode: $server->SslMode,
                    defaultDb: new DatabaseName(self::database()),
                )
                : $server,
            // Reads the configuration file.
            Config::getServers(),
        );
        $configuration = $conf->getValue();
        self::assertIsArray($configuration);
        $conf->setValue(null, ['servers' => $servers] + $configuration);
    }

    private static function bodyAfterTheCreate(): \DOMElement
    {
        return PageBody::afterPost(CreateDb::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(PageBody::of(CreateDb::class), 'form');
    }

    /**
     * @return list<\DOMElement>
     */
    private static function optionsOf(string $select): array
    {
        return PageBody::query(self::form(), ".//select[@id=\"{$select}\"]/option");
    }

    /**
     * @param list<\DOMElement> $options
     * @return list<string>
     */
    private static function valuesOf(array $options): array
    {
        return array_map(static fn (\DOMElement $option): string => $option->getAttribute('value'), $options);
    }

    /**
     * @param list<\DOMElement> $options
     * @return list<string>
     */
    private static function textsOf(array $options): array
    {
        return array_map(static fn (\DOMElement $option): string => $option->textContent, $options);
    }

    private static function errorOfPostgreSql(): string
    {
        $errors = PageBody::query(self::bodyAfterTheCreate(), './/pre[@class="error"]');

        self::assertCount(1, $errors);

        return $errors[0]->textContent;
    }

    /**
     * One of the locales that the form offers, other than those of template1, the template of a create without one.
     */
    private static function aLocaleTheTemplateDoesNotUse(): string
    {
        $connection = RunningServer::connection();
        $statement = $connection->prepare(
            'SELECT datcollate, datctype FROM pg_catalog.pg_database WHERE datname = :template',
        );
        $statement->execute(['template' => 'template1']);
        $templateRow = $statement->fetch(\PDO::FETCH_NUM);
        self::assertIsArray($templateRow);
        $templateLocales = array_filter($templateRow, is_string(...));

        $locales = array_values(array_diff($connection->getAvailableCollations(), $templateLocales));
        self::assertNotSame([], $locales);

        return $locales[0];
    }
}
