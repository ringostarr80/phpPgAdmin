<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class LoginPageCest
{
    // @phpcs:disable
    public function _before(AcceptanceTester $i): void
    {
        // Code here will be executed before each test.
    }

    public function tryToTestLoginFailed(AcceptanceTester $i): void
    {
        $i->amOnPage('/');

        $i->switchToIframe('browser');
        $i->waitForText('PostgreSQL Test');
        $i->click('PostgreSQL Test');

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $i->seeInFormFields(
            'form[name="login_form"]',
            [
                'loginUsername' => '',
                'loginPassword_' . hash('sha256', 'PostgreSQL Test') => '',
            ]
        );

        $i->submitForm('form[name="login_form"]', [
            'loginUsername' => 'admin',
            'loginPassword_' . hash('sha256', 'PostgreSQL Test') => 'wrongpassword',
        ]);

        $i->waitForText('Login failed', timeout: 180);
    }
}
