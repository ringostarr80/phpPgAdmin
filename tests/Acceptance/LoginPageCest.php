<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\{AcceptanceTester, MyConfigExtension};

final class LoginPageCest
{
    public const LOGIN_FORM_SELECTOR = 'form[name="login_form"]';

    public function tryToTestLoginFailed(AcceptanceTester $i): void
    {
        $i->amOnPage('/');

        $i->switchToIframe('browser');
        $i->waitForText(MyConfigExtension::NOT_RUNNING_SERVER_DESC);
        $i->click(MyConfigExtension::NOT_RUNNING_SERVER_DESC);

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $i->seeInFormFields(
            self::LOGIN_FORM_SELECTOR,
            [
                'loginUsername' => '',
                'loginPassword_' . hash('sha256', MyConfigExtension::NOT_RUNNING_SERVER_DESC) => ''
            ]
        );

        $i->submitForm(self::LOGIN_FORM_SELECTOR, [
            'loginUsername' => 'postgres',
            'loginPassword_' . hash('sha256', MyConfigExtension::NOT_RUNNING_SERVER_DESC) => 'wrongpassword'
        ]);

        $i->waitForText('Login failed', timeout: 180);
    }

    public function tryToTestLoginSuccessful(AcceptanceTester $i): void
    {
        $i->login();
    }
}
