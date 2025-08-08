<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website, WebsiteComponents};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

class DropDb extends Website
{
    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $database = RequestParameter::getString('database') ?? '';
            $serverId = RequestParameter::getString('server') ?? '';
            $serverSession = ServerSession::fromServerId($serverId);
            if (!is_null($serverSession) && !empty($database)) {
                $db = $serverSession->getDatabaseConnection();
                $dropResult = $db->dropDatabase($database);
                if (!headers_sent()) {
                    $redirectUrl = 'all_db.php';
                    $redirectUrlParams = [
                        'subject' => 'server',
                        'server' => $serverId
                    ];
                    $redirectUrlParams['message'] = ($dropResult === 0) ?
                        _('Database dropped.') :
                        _('Database drop failed.');

                    header('Location: ' . $redirectUrl . '?' . http_build_query($redirectUrlParams));
                    die();
                }
            }
        }
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, TrailSubject::Server));

        $database = RequestParameter::getString('database') ?? '';
        $serverId = RequestParameter::getString('server') ?? '';

        $h2 = $dom->createElement('h2', _('Drop'));
        $aHelp = WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.database.drop',
                'server' => $serverId
            ]
        );
        $h2->appendChild($aHelp);
        $body->appendChild($h2);

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'post');
        $p = $dom->createElement(
            'p',
            sprintf(_('Are you sure you want to drop the database "%s"?'), $database)
        );
        $inputHiddenDatabase = $dom->createElement('input');
        $inputHiddenDatabase->setAttribute('type', 'hidden');
        $inputHiddenDatabase->setAttribute('name', 'database');
        $inputHiddenDatabase->setAttribute('value', $database);
        $inputHiddenServer = $dom->createElement('input');
        $inputHiddenServer->setAttribute('type', 'hidden');
        $inputHiddenServer->setAttribute('name', 'server');
        $inputHiddenServer->setAttribute('value', $serverId);
        $inputSubmit = $dom->createElement('input');
        $inputSubmit->setAttribute('type', 'submit');
        $inputSubmit->setAttribute('name', 'action');
        $inputSubmit->setAttribute('value', _('Drop'));
        $aButtonCancel = $dom->createElement('a', _('Cancel'));
        $aButtonCancel->setAttribute('class', 'button');
        $cancelUrl = 'all_db.php';
        $cancelUrlParams = [
            'subject' => 'server',
            'server' => $serverId
        ];
        $aButtonCancel->setAttribute('href', $cancelUrl . '?' . http_build_query($cancelUrlParams));

        $form->appendChild($p);
        $form->appendChild($inputHiddenDatabase);
        $form->appendChild($inputHiddenServer);
        $form->appendChild($inputSubmit);
        $form->appendChild($dom->createEntityReference('nbsp'));
        $form->appendChild($aButtonCancel);

        $body->appendChild($form);

        return $body;
    }
}
