<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Website\{AlterTablespace, CreateTablespace};
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(AlterTablespace::class, 'buildHtmlBody')]
#[CoversMethod(CreateTablespace::class, 'buildCreateOrEditTablespaceTable')]
final class AlterTablespaceTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServerAndTheTablespace(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));

        $tablespaceLabels = PageBody::query(
            $body,
            './/div[@class="trail"]//a[@title="Tablespace"]/span[@class="label"]',
        );
        self::assertCount(1, $tablespaceLabels);
        self::assertSame('archive', $tablespaceLabels[0]->textContent);
    }

    public function testTheHeadingLinksToTheHelpOnAlteringATablespace(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Alter', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.tablespace.alter&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndItsLocationCannotBeChanged(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        // PostgreSQL cannot move a tablespace.
        self::assertTrue(PageBody::field($form, Tablespace::FORM_ID_LOCATION)->hasAttribute('readonly'));
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionTheServerAndTheTablespace(): void
    {
        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID], ['tablespace', 'archive']],
            PageBody::hiddenFields(self::form()),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [
            'server' => self::SERVER_ID,
            'tablespace' => 'archive',
        ];
        $_POST = [];
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        // Without a login, the page cannot load the tablespace, so the form shows none of its values and no owners to
        // choose from, which these tests do not check.
        return PageBody::of(AlterTablespace::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }
}
