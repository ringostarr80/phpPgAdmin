<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, WebsiteComponents};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

final class AlterRole extends CreateRole
{
    //private string $message = '';

    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            //$this->handlePostRequest();
        }
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = $dom->createElement('body');

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server]));

        $serverId = RequestParameter::getString('server') ?? '';
        $rolename = RequestParameter::getString('rolename');

        $h2 = $dom->createElement('h2');
        $h2->appendChild($dom->createTextNode(_('Alter')));
        $helpLink = WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.role.alter',
                'server' => $serverId,
            ],
        );
        $h2->appendChild($helpLink);
        $body->appendChild($h2);

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'post');

        $role = null;

        if (!is_null($rolename)) {
            $serverSession = ServerSession::fromServerId($serverId);
            $db = $serverSession?->getDatabaseConnection();
            $role = $db?->getRole($rolename);
        }

        $form->appendChild(self::buildCreateOrEditRoleTable($dom, $serverId, $role));
        $form->appendChild(self::buildCreateOrEditFormParagraphButtonsTable($dom, $serverId, $role));

        $body->append($form);

        return $body;
    }
}
