<?php

declare(strict_types=1);

namespace PhpPgAdmin\Infrastructure\Session;

/**
 * The tab of each section that was used last, which the session keeps under webdbLastTab. The legacy Misc::printTabs()
 * remembers its tabs there too, and redirect.php leads back to them (see Misc::getLastTabURL()).
 */
final class LastTabs
{
    /**
     * The tab of the section that was used last, null when none was.
     *
     * @param string $section A section of the legacy Misc::getNavTabs(), such as 'database'.
     */
    public static function of(string $section): ?string
    {
        $lastTab = self::all()[$section] ?? null;

        return is_string($lastTab)
            ? $lastTab
            : null;
    }

    /**
     * @param string $section A section of the legacy Misc::getNavTabs(), such as 'database'.
     */
    public static function remember(string $section, string $tab): void
    {
        $lastTabs = self::all();
        $lastTabs[$section] = $tab;
        $_SESSION['webdbLastTab'] = $lastTabs;
    }

    /**
     * @return array<mixed>
     */
    private static function all(): array
    {
        return isset($_SESSION['webdbLastTab']) && is_array($_SESSION['webdbLastTab'])
            ? $_SESSION['webdbLastTab']
            : [];
    }
}
