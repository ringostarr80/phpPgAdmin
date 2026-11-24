<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Website\Roles;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, RunningServer, TestEnvironment};

#[CoversMethod(Roles::class, 'buildRolesTable')]
final class RolesTest extends TestCase
{
    /**
     * With an &, which the list shows as it is.
     */
    private const LISTED_ROLE = 'ppa_test_R&D_listed_role';

    /**
     * With other attributes than the listed role.
     */
    private const OTHER_ROLE = 'ppa_test_R&D_other_role';

    public function testTheListTakesTheWholeWidth(): void
    {
        self::assertSame('width: 100%;', self::rolesList()->getAttribute('style'));
    }

    public function testTheListHasAColumnForEachAttributeOfARoleAndTwoForTheActions(): void
    {
        $headings = PageBody::query(self::rolesList(), './thead/tr/th');

        self::assertSame(
            [
                'Role',
                'Superuser?',
                'Create DB?',
                'Can create role?',
                'Inherits privileges?',
                'Can login?',
                'Connection limit',
                'Expires',
                'Actions',
            ],
            array_map(static fn (\DOMElement $th): string => $th->textContent, $headings),
        );
        self::assertSame(
            array_fill(0, 9, 'data'),
            array_map(static fn (\DOMElement $th): string => $th->getAttribute('class'), $headings),
        );
        self::assertSame(
            ['', '', '', '', '', '', '', '', '2'],
            array_map(static fn (\DOMElement $th): string => $th->getAttribute('colspan'), $headings),
        );
    }

    public function testTheListShowsTheAttributesOfARole(): void
    {
        // PostgreSQL gives the time back in the time zone of the session, in which it takes it.
        $role = self::createRole(
            self::LISTED_ROLE,
            "SUPERUSER NOCREATEDB CREATEROLE NOINHERIT LOGIN CONNECTION LIMIT 7 VALID UNTIL '2031-02-03 04:05'",
        );

        $texts = self::textsOfTheCells(self::rowOfTheRole($role));

        self::assertMatchesRegularExpression('/\A2031-02-03T04:05:00[+-]\d{2}:\d{2}\z/', $texts[7]);
        $texts[7] = 'expiry';
        self::assertSame([$role, 'Yes', 'No', 'Yes', 'No', 'Yes', '7', 'expiry', 'Alter', 'Drop'], $texts);
    }

    public function testTheListShowsTheOtherAttributesOfARoleWithoutALimitAndAnExpiry(): void
    {
        $role = self::createRole(self::OTHER_ROLE, 'NOSUPERUSER CREATEDB NOCREATEROLE INHERIT NOLOGIN');

        self::assertSame(
            [$role, 'No', 'Yes', 'No', 'Yes', 'No', 'No limit', '', 'Alter', 'Drop'],
            self::textsOfTheCells(self::rowOfTheRole($role)),
        );
    }

    public function testTheListCentersTheAnswersAndNothingElse(): void
    {
        $role = self::createRole(self::LISTED_ROLE, "CONNECTION LIMIT 7 VALID UNTIL '2031-02-03 04:05'");

        $cells = PageBody::query(self::rowOfTheRole($role), './td');

        self::assertSame(
            ['', ...array_fill(0, 5, 'text-align: center;'), '', '', '', ''],
            array_map(static fn (\DOMElement $cell): string => $cell->getAttribute('style'), $cells),
        );
        // The columns of the role and its attributes.
        self::assertSame(
            array_fill(0, 8, 'data'),
            array_map(static fn (\DOMElement $cell): string => $cell->getAttribute('class'), array_slice($cells, 0, 8)),
        );
    }

    public function testTheNameOfARoleLinksToItsProperties(): void
    {
        $role = self::createRole(self::LISTED_ROLE);

        $links = PageBody::query(self::rowOfTheRole($role), './td[1]/a');

        self::assertCount(1, $links);
        self::assertSame(
            'roles.php?' . http_build_query([
                'action' => 'properties',
                'rolename' => $role,
                'server' => RunningServer::id(),
            ]),
            $links[0]->getAttribute('href'),
        );
    }

    public function testTheListLeadsFromARoleToItsAlterAndItsDrop(): void
    {
        $role = self::createRole(self::LISTED_ROLE);

        // Styled as buttons, like the actions of the legacy list.
        $links = PageBody::query(self::rowOfTheRole($role), './td[starts-with(@class, "opbutton")]/a');

        self::assertSame(
            ['Alter', 'Drop'],
            array_map(static fn (\DOMElement $link): string => $link->textContent, $links),
        );
        self::assertSame(
            [
                'alter_role.php?' . http_build_query(['rolename' => $role, 'server' => RunningServer::id()]),
                'drop_role.php?' . http_build_query(['rolename' => $role, 'server' => RunningServer::id()]),
            ],
            array_map(static fn (\DOMElement $link): string => $link->getAttribute('href'), $links),
        );
    }

    public function testTheListAlternatesTheClassOfTheRows(): void
    {
        $classes = array_map(
            static fn (\DOMElement $row): string => $row->getAttribute('class'),
            PageBody::query(self::rolesList(), './tbody/tr'),
        );

        // Besides the login of the tests, the server has its predefined roles, like pg_monitor.
        self::assertGreaterThanOrEqual(3, count($classes));
        self::assertSame(
            array_map(static fn (int $index): string => $index % 2 === 0 ? 'data1' : 'data2', array_keys($classes)),
            $classes,
        );
    }

    public function testTheListGivesTheActionsOfARowTheNumberOfTheRowClass(): void
    {
        // Like in the legacy list, as the themes style opbutton1 in the rows with data1 and opbutton2 in those with
        // data2.
        $classes = array_map(
            static fn (\DOMElement $row): array => array_map(
                static fn (\DOMElement $cell): string => $cell->getAttribute('class'),
                // The columns of the actions.
                PageBody::query($row, './td[position() >= 9]'),
            ),
            PageBody::query(self::rolesList(), './tbody/tr'),
        );

        // Besides the login of the tests, the server has its predefined roles, like pg_monitor.
        self::assertGreaterThanOrEqual(3, count($classes));
        self::assertSame(
            array_map(
                static fn (int $index): array => array_fill(0, 2, $index % 2 === 0 ? 'opbutton1' : 'opbutton2'),
                array_keys($classes),
            ),
            $classes,
        );
    }

    public function testForAServerThatIsNotConfiguredTheListHasNoRoles(): void
    {
        $_GET['server'] = 'ppa-test-unknown-host:5432:allow';

        self::assertSame([], PageBody::query(self::rolesList(), './tbody/tr'));
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
        foreach ([self::LISTED_ROLE, self::OTHER_ROLE] as $role) {
            RunningServer::connection()->exec('DROP ROLE IF EXISTS "' . TestEnvironment::perThread($role) . '"');
        }

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * @return string The name of the role in this thread.
     */
    private static function createRole(string $role, string $options = ''): string
    {
        $connection = RunningServer::connection();
        $role = TestEnvironment::perThread($role);

        // Removes what an aborted run of this thread left behind.
        $connection->exec("DROP ROLE IF EXISTS \"{$role}\"");
        $connection->exec("CREATE ROLE \"{$role}\" {$options}");

        return $role;
    }

    private static function rolesList(): \DOMElement
    {
        $tables = PageBody::query(PageBody::of(Roles::class), './/table[thead/tr/th[1] = "Role"]');

        self::assertCount(1, $tables);

        return $tables[0];
    }

    private static function rowOfTheRole(string $role): \DOMElement
    {
        $rows = PageBody::query(self::rolesList(), './tbody/tr[td[1] = "' . $role . '"]');

        self::assertCount(1, $rows);

        return $rows[0];
    }

    /**
     * @return list<string>
     */
    private static function textsOfTheCells(\DOMElement $row): array
    {
        return array_map(static fn (\DOMElement $cell): string => $cell->textContent, PageBody::query($row, './td'));
    }
}
