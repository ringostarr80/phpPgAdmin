<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\Website\CreateDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(CreateDb::class, 'buildHtmlBody')]
final class CreateDbTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));
    }

    public function testTheHeadingLinksToTheHelpOnCreatingADatabase(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Create database', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.database.create&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndStartsEmpty(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame('', PageBody::fieldValue($form, 'db-name'));
        self::assertSame([], PageBody::selectedValues($form, 'db-encoding'));
        self::assertSame('', PageBody::fieldValue($form, 'db-comment'));
    }

    public function testEachRowOfTheFormLabelsItsFieldAndMarksTheRequiredOnes(): void
    {
        $rows = array_map(
            static fn (\DOMElement $row): array => [
                PageBody::onlyElement($row, 'th')->getAttribute('class'),
                PageBody::onlyElement($row, 'label')->textContent,
                PageBody::onlyElement($row, 'label')->getAttribute('for'),
                PageBody::onlyElement($row, 'td')->getAttribute('class'),
                PageBody::query($row, './td/*')[0]->getAttribute('id'),
                PageBody::query($row, './td/*')[0]->getAttribute('name'),
            ],
            PageBody::query(self::form(), './table/tbody/tr'),
        );

        self::assertSame(
            [
                ['data left required', 'Name', 'db-name', 'data1', 'db-name', 'formName'],
                ['data left required', 'Template', 'db-template', 'data1', 'db-template', 'formTemplate'],
                ['data left required', 'Encoding', 'db-encoding', 'data1', 'db-encoding', 'formEncoding'],
                ['data left', 'Collation', 'db-collation', 'data1', 'db-collation', 'formCollate'],
                ['data left', 'Character Type', 'db-ctype', 'data1', 'db-ctype', 'formCType'],
                ['data left', 'Comment', 'db-comment', '', 'db-comment', 'formComment'],
            ],
            $rows,
        );
    }

    public function testTheNameFieldTakesNoLongerNameThanPostgreSql(): void
    {
        $nameField = PageBody::field(self::form(), 'db-name');

        self::assertSame('text', $nameField->getAttribute('type'));
        self::assertSame('32', $nameField->getAttribute('size'));
        self::assertSame((string)PhpPgAdminConnection::MAX_NAME_LENGTH, $nameField->getAttribute('maxlength'));
    }

    public function testTheCommentFieldHasThreeLinesOf32Characters(): void
    {
        $commentField = PageBody::field(self::form(), 'db-comment');

        self::assertSame('32', $commentField->getAttribute('cols'));
        self::assertSame('3', $commentField->getAttribute('rows'));
    }

    public function testTheEncodingsToChooseFromAreNoneAndThoseOfPostgreSql(): void
    {
        $options = PageBody::query(self::form(), './/select[@id="db-encoding"]/option');
        $encodings = ['', ...array_keys(PhpPgAdminConnection::CODEMAP)];

        self::assertSame(
            $encodings,
            array_map(static fn (\DOMElement $option): string => $option->getAttribute('value'), $options),
        );
        self::assertSame(
            $encodings,
            array_map(static fn (\DOMElement $option): string => $option->textContent, $options),
        );
    }

    public function testAfterAFailedCreateTheFormShowsTheChosenEncoding(): void
    {
        $_POST['formEncoding'] = 'LATIN1';

        self::assertSame(['LATIN1'], PageBody::selectedValues(self::form(), 'db-encoding'));
    }

    public function testAfterACreateWithoutANameThePageAsksForOne(): void
    {
        $_POST = ['formName' => ''];

        self::assertSame(
            ['You must give a name for your database.'],
            PageBody::messages(PageBody::afterPost(CreateDb::class)),
        );
    }

    public function testWithoutACreateThePageShowsNoMessage(): void
    {
        self::assertSame([], PageBody::messages(self::body()));
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    public function testTheFormIsSentWithCreateAndLeftWithCancelForTheListOfDatabases(): void
    {
        $form = self::form();
        $submits = PageBody::query($form, './/input[@type="submit"]');
        $cancel = PageBody::onlyElement($form, 'a');

        self::assertCount(1, $submits);
        self::assertSame('Create', $submits[0]->getAttribute('value'));
        // A non-breaking space keeps the button and the link apart.
        self::assertSame('nbsp', $submits[0]->nextSibling?->nodeName);
        self::assertSame($cancel, $submits[0]->nextSibling->nextSibling);
        self::assertSame('Cancel', $cancel->textContent);
        self::assertSame('button', $cancel->getAttribute('class'));
        self::assertSame(
            'all_db.php?server=127.0.0.1%3A5432%3Aallow&subject=server',
            $cancel->getAttribute('href'),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = ['server' => self::SERVER_ID];
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
        // Without a login, the form has no templates and no locales to choose from, which the integration tests check.
        return PageBody::of(CreateDb::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }
}
