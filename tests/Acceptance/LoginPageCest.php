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
        $serverHost = '127.0.0.1';
        if (isset($_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME']) && is_string($_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME'])) {
            $serverHost = $_ENV['PHPPGADMIN_TEST_SERVER_HOSTNAME'];
        } else {
            $getEnvServerHostname = getenv('PHPPGADMIN_TEST_SERVER_HOSTNAME');
            if (is_string($getEnvServerHostname)) {
                $serverHost = $getEnvServerHostname;
            }
        }
        $serverPort = 5432;
        $serverSslMode = 'allow';
        $servertLinkTitle = "{$serverHost}:{$serverPort}:{$serverSslMode}";
        $i->click('a[title="' . $servertLinkTitle . '"]');

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $i->seeInFormFields(
            self::LOGIN_FORM_SELECTOR,
            [
                'loginUsername' => '',
                'loginPassword_' . hash('sha256', MyConfigExtension::RUNNING_SERVER_DESC) => '',
            ]
        );

        $loginUsername = $_ENV['PHPPGADMIN_TEST_SERVER_USERNAME'] ?? 'postgres';
        $loginPassword = '';
        if (isset($_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD']) && is_string($_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD'])) {
            $loginPassword = $_ENV['PHPPGADMIN_TEST_SERVER_PASSWORD'];
        } else {
            $envPassword = getenv('PHPPGADMIN_TEST_SERVER_PASSWORD');
            if (is_string($envPassword)) {
                $loginPassword = $envPassword;
            }
        }

        $i->submitForm(
            self::LOGIN_FORM_SELECTOR,
            [
                'loginUsername' => $loginUsername,
                'loginPassword_' . hash('sha256', MyConfigExtension::RUNNING_SERVER_DESC) => $loginPassword,
            ],
            'loginSubmit'
        );

        $i->waitForText("{$loginUsername}");
    }
}
