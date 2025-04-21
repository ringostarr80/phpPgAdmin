<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Codeception\Attribute\Depends;
use Tests\Support\{AcceptanceTester, MyConfigExtension};

#[Depends('Tests\Acceptance\IndexPageCest:tryToTestIndexPage')]
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
        $loginUsername = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres';
        $loginPassword = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '';

        $i->login($loginUsername, $loginPassword);
    }
}
