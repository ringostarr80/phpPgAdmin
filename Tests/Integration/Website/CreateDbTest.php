<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\CreateDb;
use PHPUnit\Framework\Attributes\{CoversMethod, DataProvider, TestWith};
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer};

#[CoversMethod(CreateDb::class, 'handlePostRequest')]
final class CreateDbTest extends TestCase
{
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

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function bodyAfterTheCreate(): \DOMElement
    {
        return PageBody::afterPost(CreateDb::class);
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
