<?php

declare(strict_types=1);

namespace Tests\Support;

use Tests\Acceptance\LoginPageCest;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public const LOGIN_FORM_SELECTOR = 'form[name="login_form"]';

    /**
     * Define custom actions here
     */
    public function login(?string $name = null, ?string $password = null): void
    {
        if (is_null($name)) {
            $name = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_USERNAME') ?? 'postgres';
        }
        if (is_null($password)) {
            $password = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_PASSWORD') ?? '';
        }

        $i = $this;

        $i->amOnPage('/');

        $i->switchToIframe('browser');
        $i->waitForText(MyConfigExtension::RUNNING_SERVER_DESC);
        $serverHost = MyConfigExtension::getEnvVar('PHPPGADMIN_TEST_SERVER_HOSTNAME') ?? '127.0.0.1';
        $serverPort = 5432;
        $serverSslMode = 'allow';
        $servertLinkTitle = "{$serverHost}:{$serverPort}:{$serverSslMode}";
        $i->click('a[title="' . $servertLinkTitle . '"]');

        $i->switchToIframe();
        $i->switchToIframe('detail');

        $i->submitForm(
            self::LOGIN_FORM_SELECTOR,
            [
                'loginUsername' => $name,
                'loginPassword_' . hash('sha256', MyConfigExtension::RUNNING_SERVER_DESC) => $password,
            ],
            'loginSubmit'
        );

        $i->waitForText("You are logged in as user \"{$name}\"");
    }
}
