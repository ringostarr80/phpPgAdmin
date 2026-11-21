<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Website\CreateTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{OtherOwner, PageBody, RunningServer, TestEnvironment};

#[CoversMethod(CreateTablespace::class, 'buildHtmlBody')]
#[CoversMethod(CreateTablespace::class, 'handlePostRequest')]
final class CreateTablespaceTest extends TestCase
{
    /**
     * PostgreSQL creates a tablespace only in a directory that exists on the database server, and this one does not.
     */
    private const MISSING_LOCATION = '/ppa_test_missing/tablespace';

    /**
     * @var array<mixed>
     */
    private array $server = [];

    private ?OtherOwner $otherOwner = null;

    public function testAfterAFailedCreateThePageSaysSo(): void
    {
        self::assertSame(['Tablespace creation failed.'], PageBody::messages(self::bodyAfterTheCreate()));
    }

    public function testAfterAFailedCreateThePageStillShowsTheErrorOfPostgreSql(): void
    {
        $errors = PageBody::query(self::bodyAfterTheCreate(), './/pre[@class="error"]');

        self::assertCount(1, $errors);
        // The error names the directory in every language.
        self::assertStringContainsString(self::MISSING_LOCATION, $errors[0]->textContent);
    }

    public function testACreateLeavesOutTheBlanksAroundTheEnteredName(): void
    {
        // Blanks around a typed name are mostly slips. PostgreSQL would keep them, and with them it takes the name,
        // while it refuses the reserved pg_default before it looks for the directory.
        $_POST[Tablespace::FORM_ID_NAME] = ' pg_default ';

        $errors = PageBody::query(self::bodyAfterTheCreate(), './/pre[@class="error"]');

        self::assertCount(1, $errors);
        // The error names the tablespace in every language.
        self::assertStringContainsString('pg_default', $errors[0]->textContent);
    }

    public function testAfterACreateWithoutANameTheFormKeepsTheEnteredOwner(): void
    {
        // Without an entered owner, the form selects the user of the tests.
        $this->otherOwner = OtherOwner::create();
        $_POST[Tablespace::FORM_ID_NAME] = ' ';
        $_POST[Tablespace::FORM_ID_OWNER] = OtherOwner::name();

        $form = PageBody::onlyElement(self::bodyAfterTheCreate(), 'form');

        self::assertSame([OtherOwner::name()], PageBody::selectedValues($form, Tablespace::FORM_ID_OWNER));
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['server' => RunningServer::id()];
        $_POST = [
            Tablespace::FORM_ID_LOCATION => self::MISSING_LOCATION,
            Tablespace::FORM_ID_NAME => TestEnvironment::perThread('ppa_test_tablespace'),
        ];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->otherOwner?->drop();

        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function bodyAfterTheCreate(): \DOMElement
    {
        return PageBody::afterPost(CreateTablespace::class);
    }
}
