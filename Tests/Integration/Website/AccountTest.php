<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\Account;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer, TestEnvironment};

#[CoversClass(Account::class)]
final class AccountTest extends TestCase
{
    /**
     * With an &, which the account shows as it is.
     */
    private const ROLE = 'ppa_test_R&D_account';

    private const PASSWORD = 'ppa_test_account';

    public function testTheAccountHasAColumnForEachAttributeOfTheRole(): void
    {
        self::logInAsTheRole();

        $headings = PageBody::query(self::account(), './thead/tr/th');

        self::assertSame(
            [
                'Name',
                'Superuser?',
                'Create DB?',
                'Can create role?',
                'Inherits privileges?',
                'Connection limit',
                'Expires',
            ],
            array_map(static fn (\DOMElement $th): string => $th->textContent, $headings),
        );
        self::assertSame(
            array_fill(0, 7, 'data'),
            array_map(static fn (\DOMElement $th): string => $th->getAttribute('class'), $headings),
        );
    }

    public function testTheAccountShowsTheAttributesOfTheRoleOfTheLogin(): void
    {
        // PostgreSQL gives the time back in the time zone of the session, in which it takes it.
        $role = self::logInAsTheRole(
            "SUPERUSER NOCREATEDB CREATEROLE NOINHERIT CONNECTION LIMIT 7 VALID UNTIL '2031-02-03 04:05'",
        );

        $texts = self::textsOfTheCells();

        self::assertMatchesRegularExpression('/\A2031-02-03T04:05:00[+-]\d{2}:\d{2}\z/', $texts[6]);
        $texts[6] = 'expiry';
        self::assertSame([$role, 'Yes', 'No', 'Yes', 'No', '7', 'expiry'], $texts);
    }

    public function testTheAccountShowsTheOtherAttributesOfARoleWithoutALimitAndAnExpiry(): void
    {
        $role = self::logInAsTheRole('NOSUPERUSER CREATEDB NOCREATEROLE INHERIT');

        self::assertSame([$role, 'No', 'Yes', 'No', 'Yes', 'No limit', 'Never'], self::textsOfTheCells());
    }

    public function testTheAccountShowsTheRoleInOneRow(): void
    {
        self::logInAsTheRole();

        $rows = PageBody::query(self::account(), './tbody/tr');

        self::assertCount(1, $rows);
        $cells = PageBody::query($rows[0], './td');
        // Like the legacy page.
        self::assertSame(
            array_fill(0, 7, 'data1'),
            array_map(static fn (\DOMElement $cell): string => $cell->getAttribute('class'), $cells),
        );
    }

    public function testWithoutALoginTheAccountShowsNoRows(): void
    {
        $_GET['server'] = 'ppa-test-unknown-host:5432:allow';

        $body = PageBody::of(Account::class);

        self::assertSame([], PageBody::query($body, './/table[thead/tr/th[1] = "Name"]'));
        self::assertCount(1, PageBody::query($body, './p[. = "No rows found."]'));
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = ['server' => RunningServer::id()];
        $_POST = [];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        // The test may have logged in as the role.
        RunningServer::logIn();
        RunningServer::connection()->exec('DROP ROLE IF EXISTS "' . TestEnvironment::perThread(self::ROLE) . '"');

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * Creates the role, which the account then shows, and logs in as it.
     *
     * @return string The name of the role in this thread.
     */
    private static function logInAsTheRole(string $options = ''): string
    {
        $connection = RunningServer::connection();
        $role = TestEnvironment::perThread(self::ROLE);

        // Removes what an aborted run of this thread left behind.
        $connection->exec("DROP ROLE IF EXISTS \"{$role}\"");
        $connection->exec("CREATE ROLE \"{$role}\" LOGIN PASSWORD '" . self::PASSWORD . "' {$options}");

        RunningServer::logInAs($role, self::PASSWORD);

        return $role;
    }

    private static function account(): \DOMElement
    {
        $tables = PageBody::query(PageBody::of(Account::class), './/table[thead/tr/th[1] = "Name"]');

        self::assertCount(1, $tables);

        return $tables[0];
    }

    /**
     * @return list<string>
     */
    private static function textsOfTheCells(): array
    {
        return array_map(
            static fn (\DOMElement $cell): string => $cell->textContent,
            PageBody::query(self::account(), './tbody/tr/td'),
        );
    }
}
