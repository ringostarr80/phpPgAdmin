<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\AllDb;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageRedirect;

#[CoversMethod(AllDb::class, 'handlePostRequest')]
final class AllDbTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    private const DATABASE = 'ppa_test_database';

    private const OTHER_DATABASE = 'ppa_test_other_database';

    public function testDeleteLeadsToTheDropOfTheCheckedDatabases(): void
    {
        $_POST['ma'] = [self::DATABASE, self::OTHER_DATABASE];

        self::assertSame(
            ['drop_db.php', ['database' => [self::DATABASE, self::OTHER_DATABASE], 'server' => self::SERVER_ID]],
            PageRedirect::afterPost(AllDb::class),
        );
    }

    public function testDeleteLeavesOutAnEmptyDatabase(): void
    {
        $_POST['ma'] = ['', self::DATABASE];

        self::assertSame(
            ['drop_db.php', ['database' => [self::DATABASE], 'server' => self::SERVER_ID]],
            PageRedirect::afterPost(AllDb::class),
        );
    }

    public function testDeleteWithoutACheckedDatabaseLeadsToTheListWithAMessage(): void
    {
        self::assertSame(
            [
                'all_db.php',
                [
                    'message' => 'You must specify at least one database to drop.',
                    'server' => self::SERVER_ID,
                    'subject' => 'server',
                ],
            ],
            PageRedirect::afterPost(AllDb::class),
        );
    }

    public function testWithoutAnActionThePageShowsItselfAgain(): void
    {
        // The first option of the actions, which chooses none.
        $_POST['action'] = '';
        $_POST['ma'] = [self::DATABASE];

        self::assertNull(PageRedirect::afterPost(AllDb::class));
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [];
        // As the form of the actions on multiple lines posts Delete.
        $_POST = [
            'action' => 'confirm_drop',
            'server' => self::SERVER_ID,
        ];
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }
}
