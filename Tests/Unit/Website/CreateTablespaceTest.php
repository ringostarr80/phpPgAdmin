<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Website\CreateTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(CreateTablespace::class, 'buildHtmlBody')]
#[CoversMethod(CreateTablespace::class, 'handlePostRequest')]
final class CreateTablespaceTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    /**
     * @var array<mixed>
     */
    private array $server = [];

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(
            1,
            PageBody::query($body, './/div[@class="trail"]//a[@href="all_db.php?server=127.0.0.1%3A5432%3Aallow"]'),
        );
    }

    public function testTheHeadingLinksToTheHelpOnCreatingATablespace(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Create tablespace', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.tablespace.create&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndStartsEmpty(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame('', PageBody::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame('', PageBody::fieldValue($form, Tablespace::FORM_ID_LOCATION));
        self::assertSame('', PageBody::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testAfterAFailedCreateTheFormShowsTheEnteredValues(): void
    {
        self::postTheForm();

        $form = self::form();

        self::assertSame('archive', PageBody::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame('/srv/archive', PageBody::fieldValue($form, Tablespace::FORM_ID_LOCATION));
        self::assertSame('Old data', PageBody::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testAfterAFailedCreateTheLocationCanStillBeChanged(): void
    {
        // A wrong location is a common reason for the create to fail.
        self::postTheForm();

        self::assertFalse(PageBody::field(self::form(), Tablespace::FORM_ID_LOCATION)->hasAttribute('readonly'));
    }

    public function testAfterACreateWithoutANameThePageAsksForOne(): void
    {
        // The browser only requires the field to be filled, so blanks get past it.
        self::postTheForm(name: ' ');

        self::assertSame(
            ['You must give a name for your tablespace.'],
            PageBody::messages(PageBody::afterPost(CreateTablespace::class)),
        );
    }

    public function testAfterACreateWithoutANameTheFormKeepsTheEnteredValues(): void
    {
        self::postTheForm(name: ' ');

        $form = PageBody::onlyElement(PageBody::afterPost(CreateTablespace::class), 'form');

        self::assertSame('/srv/archive', PageBody::fieldValue($form, Tablespace::FORM_ID_LOCATION));
        self::assertSame('Old data', PageBody::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_GET = ['server' => self::SERVER_ID];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        // Without a login, the form has no owners to choose from, which these tests do not check.
        return PageBody::of(CreateTablespace::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }

    /**
     * The request after a failed create: the constructor redirects to the tablespaces after a successful one, so the
     * page only shows a posted form when the create failed.
     */
    private static function postTheForm(string $name = 'archive'): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            Tablespace::FORM_ID_COMMENT => 'Old data',
            Tablespace::FORM_ID_LOCATION => '/srv/archive',
            Tablespace::FORM_ID_NAME => $name,
        ];
    }
}
