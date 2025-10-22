<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website, WebsiteComponents};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

final class AllDb extends Website
{
    public function __construct()
    {
        $this->title = _('Databases');
        $this->scripts['multiactionform'] = ['src' => 'multiactionform.js'];
        $this->scripts['all_db'] = ['src' => 'js/all_db.js'];

        parent::__construct();
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, TrailSubject::Server));

        $serverId = RequestParameter::getString('server') ?? '';
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $serverId, 'databases'));

        $message = RequestParameter::getString('message') ?? '';

        if (!empty($message)) {
            $body->appendChild(WebsiteComponents::buildMessage($dom, $message));
        }

        $serverSession = ServerSession::fromServerId($serverId);
        $form = $dom->createElement('form');
        $form->setAttribute('id', 'multi_form');
        $form->setAttribute('action', 'all_db.php');
        $form->setAttribute('method', 'post');
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->appendChild(WebsiteComponents::buildDatabasesTable($dom, $serverSession));
        $form->appendChild($dom->createElement('br'));
        $form->appendChild(WebsiteComponents::buildMultipleActionsTableForDatabases($dom, $serverSession));
        $body->appendChild($form);

        $navLinks = [
            [
                'label' => _('Create database'),
                'url' => 'create_db.php',
                'url-params' => [
                    'server' => RequestParameter::getString('server') ?? '',
                ],
            ],
        ];
        $body->appendChild(WebsiteComponents::buildNavLinks($dom, $navLinks));

        return $body;
    }
}
