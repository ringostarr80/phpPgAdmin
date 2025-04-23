<?php

declare(strict_types=1);

namespace Tests\Unit;

use PhpPgAdmin\Config;
use Tests\Support\UnitTester;

final class LocalesCest
{
    // @phpcs:disable
    public function _before(UnitTester $I): void
    {
        // Code here will be executed before each test.
    }

    public function tryToTestLocales(UnitTester $i): void
    {
        $locales = Config::getAvailableLocales();
        $i->assertNotEmpty($locales, 'No locales found');
        $i->assertEquals(28, count($locales), 'Expected 28 locales');
        $i->assertContains('de_DE', $locales, 'Locale de_DE should be present');
        $i->assertContains('en_US', $locales, 'Locale en_US should be present');
    }
}
