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
     * @param string $section A section of the legacy Misc::getNavTabs(), such as 'database'.
     */
    public static function remember(string $section, string $tab): void
    {
        $lastTabs = isset($_SESSION['webdbLastTab']) && is_array($_SESSION['webdbLastTab'])
            ? $_SESSION['webdbLastTab']
            : [];
        $lastTabs[$section] = $tab;
        $_SESSION['webdbLastTab'] = $lastTabs;
    }
}
