<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Session;

use PhpPgAdmin\Infrastructure\Session\LastTabs;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;

#[CoversClass(LastTabs::class)]
final class LastTabsTest extends TestCase
{
    public function testTheTabOfASectionIsTheOneThatWasUsedLast(): void
    {
        LastTabs::remember('database', 'languages');
        LastTabs::remember('server', 'roles');

        self::assertSame('languages', LastTabs::of('database'));
        self::assertSame('roles', LastTabs::of('server'));
    }

    public function testASectionWhoseTabsWereNotUsedHasNoLastTab(): void
    {
        LastTabs::remember('database', 'languages');

        self::assertNull(LastTabs::of('server'));
    }

    #[DataProvider('noLastTabs')]
    public function testWithoutLastTabsASectionHasNoLastTab(mixed $noLastTabs): void
    {
        $_SESSION['webdbLastTab'] = $noLastTabs;

        self::assertNull(LastTabs::of('database'));
    }

    public function testALastTabThatIsNoTextIsNone(): void
    {
        $_SESSION['webdbLastTab'] = ['database' => ['languages']];

        self::assertNull(LastTabs::of('database'));
    }

    public function testTheTabOfASectionReplacesTheOneBefore(): void
    {
        LastTabs::remember('database', 'schemas');
        LastTabs::remember('database', 'languages');

        self::assertSame(['webdbLastTab' => ['database' => 'languages']], $_SESSION);
    }

    public function testTheTabsOfOtherSectionsAreKept(): void
    {
        // Like the legacy Misc::printTabs() remembers the tabs of the pages that are not migrated yet.
        $_SESSION['webdbLastTab'] = ['schema' => 'tables', 'table' => 'indexes'];

        LastTabs::remember('database', 'languages');

        self::assertSame(
            ['database' => 'languages', 'schema' => 'tables', 'table' => 'indexes'],
            self::sortedLastTabs(),
        );
    }

    #[DataProvider('noLastTabs')]
    public function testWithoutLastTabsOnlyTheTabOfTheSectionIsRemembered(mixed $noLastTabs): void
    {
        $_SESSION['webdbLastTab'] = $noLastTabs;

        LastTabs::remember('database', 'languages');

        self::assertSame(['webdbLastTab' => ['database' => 'languages']], $_SESSION);
    }

    public function testTheRestOfTheSessionIsLeftAlone(): void
    {
        $_SESSION['webdbLogin'] = ['127.0.0.1:5432:allow' => ['username' => 'postgres']];

        LastTabs::remember('database', 'languages');

        self::assertSame(
            [
                'webdbLastTab' => ['database' => 'languages'],
                'webdbLogin' => ['127.0.0.1:5432:allow' => ['username' => 'postgres']],
            ],
            self::sortedSession(),
        );
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function noLastTabs(): iterable
    {
        yield 'none' => [null];
        yield 'no array' => ['languages'];
    }

    #[\Override]
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * @return array<mixed>
     */
    private static function sortedLastTabs(): array
    {
        $lastTabs = $_SESSION['webdbLastTab'] ?? null;
        self::assertIsArray($lastTabs);
        ksort($lastTabs);

        return $lastTabs;
    }

    /**
     * @return array<mixed>
     */
    private static function sortedSession(): array
    {
        $session = $_SESSION;
        ksort($session);

        return $session;
    }
}
