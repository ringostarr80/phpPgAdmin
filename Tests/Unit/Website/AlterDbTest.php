<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\Website\AlterDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(AlterDb::class, 'buildHtmlBody')]
final class AlterDbTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    private const DATABASE = 'ppa_test_database';

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(
            1,
            PageBody::query($body, './/div[@class="trail"]//a[@href="all_db.php?server=127.0.0.1%3A5432%3Aallow"]'),
        );
    }

    public function testTheHeadingLinksToTheHelpOnAlteringADatabase(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Alter', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.database.alter&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndShowsTheNameOfTheDatabase(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame(self::DATABASE, PageBody::fieldValue($form, 'newname'));
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
                ['data left required', 'Name', 'newname', 'data1', 'newname', 'newname'],
                ['data left required', 'Owner', 'owner', 'data1', 'owner', 'owner'],
                ['data left', 'Comment', 'dbcomment', 'data1', 'dbcomment', 'dbcomment'],
            ],
            $rows,
        );
    }

    public function testTheNameFieldTakesNoLongerNameThanPostgreSql(): void
    {
        $nameField = PageBody::field(self::form(), 'newname');

        self::assertSame('text', $nameField->getAttribute('type'));
        self::assertSame('32', $nameField->getAttribute('size'));
        self::assertSame((string)PhpPgAdminConnection::MAX_NAME_LENGTH, $nameField->getAttribute('maxlength'));
    }

    public function testTheCommentFieldHasThreeLinesOf32Characters(): void
    {
        $commentField = PageBody::field(self::form(), 'dbcomment');

        self::assertSame('32', $commentField->getAttribute('cols'));
        self::assertSame('3', $commentField->getAttribute('rows'));
    }

    public function testWithoutAnAlterThePageShowsNoMessage(): void
    {
        self::assertSame([], PageBody::messages(self::body()));
    }

    public function testTheHiddenFieldsOfTheFormAreTheServerAndTheOldNameOfTheDatabase(): void
    {
        self::assertSame(
            [['server', self::SERVER_ID], ['oldname', self::DATABASE]],
            PageBody::hiddenFields(self::form()),
        );
    }

    public function testTheFormIsSentWithAlterAndLeftWithCancelForTheListOfDatabases(): void
    {
        $form = self::form();
        $submits = PageBody::query($form, './/input[@type="submit"]');
        $cancel = PageBody::onlyElement($form, 'a');

        self::assertCount(1, $submits);
        self::assertSame('Alter', $submits[0]->getAttribute('value'));
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
        $_GET = [
            'database' => self::DATABASE,
            'server' => self::SERVER_ID,
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
        // Without a login, the form has no owners to choose from and no comment, which the integration tests check.
        return PageBody::of(AlterDb::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }
}
