<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Codeception\Attribute\Depends;
use Tests\Support\{AcceptanceTester, MyConfigExtension};

#[Depends('Tests\Acceptance\LoginPageCest:tryToTestLoginSuccessful')]
final class AllDbPageCest
{
    public function tryToTestAllDbPage(AcceptanceTester $i): void
    {
        $loginUsername = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres';
        $loginPassword = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '';

        $i->login($loginUsername, $loginPassword);

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
