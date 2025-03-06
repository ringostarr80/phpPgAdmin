<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class IndexPageCest
{
    // @phpcs:disable
    public function _before(AcceptanceTester $i): void
    {
        // Code here will be executed before each test.
    }

    public function tryToTestIndexPage(AcceptanceTester $i): void
    {
        $i->amOnPage('/');
        $i->seeElement('iframe', ['src' => 'browser.php', 'name' => 'browser']);
        $i->seeElement('iframe', ['src' => 'intro.php', 'name' => 'detail']);

        $i->switchToIframe('detail');
        $i->see('Introduction', 'table.tabs tr:first-child td:first-child span');
        $i->see('Server', 'table.tabs tr:first-child td:nth-child(2) span');
    }
}
