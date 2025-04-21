<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class PhpInfoCest
{
    public function tryToTestPhpInfo(AcceptanceTester $i): void
    {
        $i->amOnPage('/phpinfo.php');
        $i->see('Foo');
    }
}
