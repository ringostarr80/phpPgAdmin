<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website, WebsiteComponents};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

class AllDb extends Website
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
        $actionParam = RequestParameter::getString('action');
        $tabLinks = [
            [
                'active' => is_null($actionParam),
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => $serverId,
                    ],
                ],
                'icon' => 'Databases',
                'label' => _('Databases'),
                'url' => 'all_db.php',
                'url-params' => [
                    'server' => $serverId,
                    'subject' => 'server',
                ],
            ],
            [
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => $serverId,
                    ],
                ],
                'icon' => 'Roles',
                'label' => _('Roles'),
                'url' => 'roles.php',
                'url-params' => [
                    'server' => $serverId,
                    'subject' => 'server',
                ],
            ],
            [
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.tablespace',
                        'server' => $serverId,
                    ],
                ],
                'icon' => 'Tablespaces',
                'label' => _('Tablespaces'),
                'url' => 'tablespaces.php',
                'url-params' => [
                    'server' => $serverId,
                    'subject' => 'server',
                ],
            ],
            [
                'active' => $actionParam === 'export',
                'icon' => 'Export',
                'label' => _('Export'),
                'url' => 'all_db_export.php',
                'url-params' => [
                    'server' => $serverId,
                ],
            ],
        ];
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $tabLinks));

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
