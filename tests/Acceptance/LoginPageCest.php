<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\{AcceptanceTester, MyConfigExtension};

final class LoginPageCest
{
    private const LOGIN_FORM_SELECTOR = 'form[name="login_form"]';

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
                'loginPassword_' . hash('sha256', MyConfigExtension::NOT_RUNNING_SERVER_DESC) => '',
            ]
        );

        $i->submitForm(self::LOGIN_FORM_SELECTOR, [
            'loginUsername' => 'postgres',
            'loginPassword_' . hash('sha256', MyConfigExtension::NOT_RUNNING_SERVER_DESC) => 'wrongpassword',
        ]);

        $i->waitForText('Login failed', timeout: 180);
    }

    public function tryToTestLoginSuccessful(AcceptanceTester $i): void
    {
        $i->amOnPage('/');

        $i->switchToIframe('browser');
        $i->waitForText(MyConfigExtension::RUNNING_SERVER_DESC);
        $i->click(MyConfigExtension::RUNNING_SERVER_DESC);

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $loginUsername = $_ENV['PHPPGADMIN_TEST_SERVER_USERNAME'] ?? 'postgres';
        $loginPassword = $_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD'] ?? '';
        error_log('$_ENV: ' . serialize($_ENV));
        error_log('getenv(PHPPGADMIN_TEST_SERVER_PASSWORD): ' .  serialize(getenv('PHPPGADMIN_TEST_SERVER_PASSWORD')));
        $i->submitForm(self::LOGIN_FORM_SELECTOR, [
            'loginUsername' => $loginUsername,
            'loginPassword_' . hash('sha256', MyConfigExtension::RUNNING_SERVER_DESC) => $loginPassword,
        ]);

        $i->waitForText("You are logged in as user \"{$loginUsername}\"");
    }
}
