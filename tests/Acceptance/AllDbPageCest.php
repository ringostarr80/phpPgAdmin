<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class AllDbPageCest
{
    public const LOGIN_FORM_SELECTOR = 'form[name="login_form"]';

    public function tryToTestAllDbPage(AcceptanceTester $i): void
    {
        $i->login();

        $i->seeNumberOfElements('table.tabs tbody tr td', 4);
        $i->see('Databases', 'table.tabs tbody tr td:nth-child(1) span.label');
        $i->see('Roles', 'table.tabs tbody tr td:nth-child(2) span.label');
        $i->see('Tablespaces', 'table.tabs tbody tr td:nth-child(3) span.label');
        $i->see('Export', 'table.tabs tbody tr td:nth-child(4) span.label');

        $i->seeElement('table.tabs tbody tr td:nth-child(1)', ['class' => 'tab active']);
        $i->seeElement('table.tabs tbody tr td:nth-child(2)', ['class' => 'tab']);
        $i->seeElement('table.tabs tbody tr td:nth-child(3)', ['class' => 'tab']);
        $i->seeElement('table.tabs tbody tr td:nth-child(4)', ['class' => 'tab']);
    }
}
