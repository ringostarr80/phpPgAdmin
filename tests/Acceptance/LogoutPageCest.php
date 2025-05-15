<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class LogoutPageCest
{
    public function tryToTestLoginAndLogout(AcceptanceTester $i): void
    {
        $i->login();

        $i->click('#toplink_logout');

        $i->waitForText(text: 'Logout', selector: '#server-list tbody tr:nth-child(2) td:last-child');
        $i->click('Logout', '#server-list tbody tr:nth-child(2) td:last-child');

        $i->waitForElement('#server-list');
    }
}
