<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, RequestParameter, Website, WebsiteComponents};
use PhpPgAdmin\Database\Connection;

class Login extends Website
{
    private string $message = '';

    public function __construct()
    {
        parent::__construct();

        $this->title = _('Login');

        if (
            isset($_SERVER['REQUEST_METHOD']) &&
            is_string($_SERVER['REQUEST_METHOD']) &&
            $_SERVER['REQUEST_METHOD'] === 'POST'
        ) {
            $loginServer = RequestParameter::getString('loginServer');
            if (is_null($loginServer)) {
                throw new \InvalidArgumentException('Parameter "loginServer" is required');
            }
            $loginUsername = RequestParameter::getString('loginUsername');
            if (is_null($loginUsername)) {
                throw new \InvalidArgumentException('Parameter "loginUsername" is required');
            }

            $server = Config::getServerById($loginServer);
            if (is_null($server)) {
                throw new \InvalidArgumentException('Server not found');
            }
            $loginPassword = RequestParameter::getString('loginPassword_' . hash('sha256', (string)$server->Name));
            if (is_null($loginPassword)) {
                throw new \InvalidArgumentException('Parameter "loginPassword" is required');
            }

            try {
                new Connection(
                    (string)$server->Host,
                    $server->Port->Value,
                    $server->SslMode->value,
                    $loginUsername,
                    $loginPassword,
                    (string)$server->DefaultDb
                );
            } catch (\Exception) {
                $this->message = _('Login failed');
            }
        }
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom));

        $serverId = RequestParameter::getString('server');
        if (is_null($serverId)) {
            throw new \InvalidArgumentException('Parameter "server" is required');
        }

        $server = Config::getServerById($serverId);
        if (is_null($server)) {
            throw new \InvalidArgumentException('Server not found');
        }

        $body->appendChild($this->buildTitle($dom, sprintf(_('Login to %s'), (string)$server->Name)));

        if (!empty($this->message)) {
            $p = $dom->createElement('p', $this->message);
            $p->setAttribute('class', 'message');
            $body->appendChild($p);
        }

        $form = $dom->createElement('form');
        $form->setAttribute('id', 'login_form');
        $loginFormAction = '';
        if (isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME'])) {
            $loginFormAction = $_SERVER['SCRIPT_NAME'];
        }
        $form->setAttribute('action', $loginFormAction);
        $form->setAttribute('method', 'post');
        $form->setAttribute('name', 'login_form');
        $inputTypeHiddenSubject = $dom->createElement('input');
        $inputTypeHiddenSubject->setAttribute('type', 'hidden');
        $inputTypeHiddenSubject->setAttribute('name', 'subject');
        $inputTypeHiddenSubject->setAttribute('value', 'server');
        $form->appendChild($inputTypeHiddenSubject);
        $inputTypeHiddenServer = $dom->createElement('input');
        $inputTypeHiddenServer->setAttribute('type', 'hidden');
        $inputTypeHiddenServer->setAttribute('name', 'server');
        $inputTypeHiddenServer->setAttribute('value', $serverId);
        $form->appendChild($inputTypeHiddenServer);
        $inputTypeHiddenLoginServer = $dom->createElement('input');
        $inputTypeHiddenLoginServer->setAttribute('type', 'hidden');
        $inputTypeHiddenLoginServer->setAttribute('name', 'loginServer');
        $inputTypeHiddenLoginServer->setAttribute('value', $serverId);
        $form->appendChild($inputTypeHiddenLoginServer);
        $table = $dom->createElement('table');
        $table->setAttribute('class', 'navbar');
        $table->setAttribute('border', '0');
        $table->setAttribute('cellpadding', '5');
        $table->setAttribute('cellspacing', '3');
        $tr = $dom->createElement('tr');
        $td = $dom->createElement('td', _('Username'));
        $tr->appendChild($td);
        $td = $dom->createElement('td');
        $username = RequestParameter::getString('loginUsername') ?? '';
        $inputTypeTextLoginUsername = $dom->createElement('input');
        $inputTypeTextLoginUsername->setAttribute('type', 'text');
        $inputTypeTextLoginUsername->setAttribute('name', 'loginUsername');
        $inputTypeTextLoginUsername->setAttribute('value', $username);
        $inputTypeTextLoginUsername->setAttribute('size', '24');
        $td->appendChild($inputTypeTextLoginUsername);
        $tr->appendChild($td);
        $table->appendChild($tr);
        $tr = $dom->createElement('tr');
        $td = $dom->createElement('td', _('Password'));
        $tr->appendChild($td);
        $td = $dom->createElement('td');
        $inputTypePasswordLoginPassword = $dom->createElement('input');
        $inputTypePasswordLoginPassword->setAttribute('id', 'loginPassword');
        $inputTypePasswordLoginPassword->setAttribute('type', 'password');
        $inputPasswordName = 'loginPassword_' . hash('sha256', (string)$server->Name);
        $inputTypePasswordLoginPassword->setAttribute('name', $inputPasswordName);
        $inputTypePasswordLoginPassword->setAttribute('size', '24');
        $td->appendChild($inputTypePasswordLoginPassword);
        $tr->appendChild($td);
        $table->appendChild($tr);
        $form->appendChild($table);
        $p = $dom->createElement('p');
        $inputTypeSubmitLoginSubmit = $dom->createElement('input');
        $inputTypeSubmitLoginSubmit->setAttribute('type', 'submit');
        $inputTypeSubmitLoginSubmit->setAttribute('name', 'loginSubmit');
        $inputTypeSubmitLoginSubmit->setAttribute('value', _('Login'));
        $p->appendChild($inputTypeSubmitLoginSubmit);
        $form->appendChild($p);
        $body->appendChild($form);

        return $body;
    }
}
