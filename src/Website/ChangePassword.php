<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

/**
 * Changes the password of the own role of the login, like the legacy roles.php?action=confchangepassword.
 */
final class ChangePassword extends Website
{
    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            self::redirectTo($this->handlePostRequest());
        }
    }

    #[\Override]
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        // Like the account page, without the role.
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server]));

        $serverId = RequestParameter::getString('server') ?? '';

        $h2 = $dom->createElement('h2');
        $h2->appendChild($dom->createTextNode(_('Change password')));
        $h2->appendChild(WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.role.alter',
                'server' => $serverId,
            ],
        ));
        $body->appendChild($h2);

        if ($this->message !== '') {
            $body->appendChild(WebsiteComponents::buildMessage($dom, $this->message));
        }

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'post');
        $form->appendChild(self::buildPasswordTable($dom));
        $form->appendChild(self::buildButtons($dom, $serverId));
        $body->appendChild($form);

        return $body;
    }

    #[\Override]
    protected function title(): string
    {
        // Like the other pages that come from the legacy roles.php.
        return _('Roles');
    }

    /**
     * Leaves leading to the next page to the constructor, whose die would end the tests.
     *
     * @return ?string The page to lead to, or null to show this page again.
     */
    private function handlePostRequest(): ?string
    {
        $serverId = RequestParameter::getString('server') ?? '';

        // Like the legacy page, which changed the password only for its OK button.
        if (is_null(RequestParameter::getString('ok'))) {
            return 'account.php?' . http_build_query(['server' => $serverId]);
        }

        $password = RequestParameter::getString(Role::FORM_ID_PASSWORD) ?? '';

        // In characters, where the legacy page counted the bytes, in which an umlaut counts twice.
        if (mb_strlen($password) < Config::minPasswordLength()) {
            $this->message = _('Password is too short.');

            return null;
        }

        if ($password !== RequestParameter::getString(Role::FORM_ID_PASSWORD_CONFIRMATION)) {
            $this->message = _('Password does not match confirmation.');

            return null;
        }

        $serverSession = Logins::serverSession($serverId, Config::getServers());

        // The constructor of Website leads to the login without one.
        if (is_null($serverSession)) {
            $this->message = _('Failed to change password.');

            return null;
        }

        try {
            // The own role of the login: only a superuser may change the password of another role.
            PhpPgAdminConnection::createFromServerSession($serverSession)?->changePassword(
                (string)$serverSession->Username,
                $password,
            );
        } catch (\PDOException $e) {
            $this->message = _('Failed to change password.');
            $this->pdoException = $e;

            return null;
        }

        Logins::changePassword($serverId, $password);

        return 'account.php?' . http_build_query(['message' => _('Password changed.'), 'server' => $serverId]);
    }

    private static function buildButtons(\DOMDocument $dom, string $serverId): \DOMElement
    {
        $inputServer = $dom->createElement('input');
        $inputServer->setAttribute('type', 'hidden');
        $inputServer->setAttribute('name', 'server');
        $inputServer->setAttribute('value', $serverId);
        $inputOk = $dom->createElement('input');
        $inputOk->setAttribute('type', 'submit');
        $inputOk->setAttribute('name', 'ok');
        $inputOk->setAttribute('value', _('OK'));
        $inputCancel = $dom->createElement('input');
        $inputCancel->setAttribute('type', 'submit');
        $inputCancel->setAttribute('name', 'cancel');
        $inputCancel->setAttribute('value', _('Cancel'));

        $p = $dom->createElement('p');
        $p->appendChild($inputServer);
        $p->appendChild($inputOk);
        $p->appendChild($dom->createEntityReference('nbsp'));
        $p->appendChild($inputCancel);

        return $p;
    }

    private static function buildPasswordTable(\DOMDocument $dom): \DOMElement
    {
        $passwordSpecs = [
            'id' => Role::FORM_ID_PASSWORD,
            'label-text' => _('Password'),
            'value' => [
                // After a failed change, like the legacy page, which left only the confirmation to be entered again.
                'content' => RequestParameter::getString(Role::FORM_ID_PASSWORD) ?? '',
                'required' => true,
                'type' => 'password',
            ],
        ];
        $confirmationSpecs = [
            'id' => Role::FORM_ID_PASSWORD_CONFIRMATION,
            'label-text' => _('Confirm'),
            'value' => [
                'required' => true,
                'type' => 'password',
            ],
        ];

        $tBody = $dom->createElement('tbody');
        $tBody->appendChild(WebsiteComponents::buildTableRowForInputFormular($dom, $passwordSpecs));
        $tBody->appendChild(WebsiteComponents::buildTableRowForInputFormular($dom, $confirmationSpecs));

        $table = $dom->createElement('table');
        $table->setAttribute('class', 'form-table');
        $table->appendChild($tBody);

        return $table;
    }
}
