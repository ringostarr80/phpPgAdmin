<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\CreateDb;
use PHPUnit\Framework\Attributes\CoversMethod;
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
        $errors = PageBody::query(self::bodyAfterTheCreate(), './/pre[@class="error"]');

        self::assertCount(1, $errors);
        // The error names the database in every language.
        self::assertStringContainsString(self::EXISTING_DATABASE, $errors[0]->textContent);
    }

    public function testAfterAFailedCreateTheFormShowsTheEnteredValues(): void
    {
        $form = PageBody::onlyElement(self::bodyAfterTheCreate(), 'form');

        self::assertSame(self::EXISTING_DATABASE, PageBody::fieldValue($form, 'db-name'));
        self::assertSame('Old data', PageBody::fieldValue($form, 'db-comment'));
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
}
