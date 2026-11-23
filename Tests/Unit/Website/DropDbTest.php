<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\DropDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(DropDb::class, 'buildHtmlBody')]
final class DropDbTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    private const DATABASE = 'ppa_test_database';

    private const OTHER_DATABASE = 'ppa_test_other_database';

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(
            1,
            PageBody::query($body, './/div[@class="trail"]//a[@href="all_db.php?server=127.0.0.1%3A5432%3Aallow"]'),
        );
    }

    public function testTheHeadingLinksToTheHelpOnDroppingADatabase(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Drop', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.database.drop&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormAsksWhetherToDropTheDatabase(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame(
            'Are you sure you want to drop the database "' . self::DATABASE . '"?',
            PageBody::onlyElement($form, 'p')->textContent,
        );
    }

    public function testTheFormAsksWhetherToDropEachOfSeveralDatabases(): void
    {
        $_GET['database'] = [self::DATABASE, self::OTHER_DATABASE];

        self::assertSame(
            [
                'Are you sure you want to drop the database "' . self::DATABASE . '"?',
                'Are you sure you want to drop the database "' . self::OTHER_DATABASE . '"?',
            ],
            array_map(
                static fn (\DOMElement $question): string => $question->textContent,
                PageBody::query(self::form(), './p'),
            ),
        );
    }

    public function testTheFormPostsTheDatabaseAndTheServer(): void
    {
        self::assertSame(
            [['database[]', self::DATABASE], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    public function testTheFormPostsEachOfSeveralDatabases(): void
    {
        $_GET['database'] = [self::DATABASE, self::OTHER_DATABASE];

        self::assertSame(
            [['database[]', self::DATABASE], ['database[]', self::OTHER_DATABASE], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    public function testTheFormLeavesOutAnEmptyDatabase(): void
    {
        $_GET['database'] = ['', self::DATABASE];

        self::assertSame(
            [['database[]', self::DATABASE], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    public function testTheFormIsSentByTheDropButton(): void
    {
        $submits = PageBody::query(self::form(), './/input[@type="submit"]');

        self::assertCount(1, $submits);
        self::assertSame('action', $submits[0]->getAttribute('name'));
        self::assertSame('Drop', $submits[0]->getAttribute('value'));
    }

    public function testCancelLeadsBackToTheListOfTheDatabases(): void
    {
        // Kept in a variable, as the link has no siblings any more once nothing refers to the form.
        $form = self::form();
        $cancel = PageBody::onlyElement($form, 'a');

        self::assertSame('Cancel', $cancel->textContent);
        self::assertSame('button', $cancel->getAttribute('class'));
        self::assertSame(
            'all_db.php?server=127.0.0.1%3A5432%3Aallow&subject=server',
            $cancel->getAttribute('href'),
        );
        // A space that does not break keeps it apart from the Drop button.
        self::assertInstanceOf(\DOMEntityReference::class, $cancel->previousSibling);
        self::assertSame('nbsp', $cancel->previousSibling->nodeName);
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
        return PageBody::of(DropDb::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }
}
