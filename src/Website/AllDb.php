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
                'url' => 'all_db.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => $serverId
                ],
                'label' => _('Databases'),
                'icon' => 'Databases',
                'active' => is_null($actionParam),
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => $serverId
                    ]
                ]
            ],
            [
                'url' => 'roles.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => $serverId
                ],
                'label' => _('Roles'),
                'icon' => 'Roles',
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => $serverId
                    ]
                ]
            ],
            [
                'url' => 'tablespaces.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => $serverId
                ],
                'label' => _('Tablespaces'),
                'icon' => 'Tablespaces',
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.tablespace',
                        'server' => $serverId
                    ]
                ]
            ],
            [
                'url' => 'all_db.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => $serverId,
                    'action' => 'export'
                ],
                'label' => _('Export'),
                'icon' => 'Export',
                'active' => $actionParam === 'export'
            ]
        ];
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $tabLinks));

        $form = $dom->createElement('form');
        $form->setAttribute('id', 'multi_form');
        $form->setAttribute('action', 'all_db.php');
        $form->setAttribute('method', 'post');
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->appendChild(WebsiteComponents::buildDatabasesTable($dom, ServerSession::fromServerId($serverId)));
        $body->appendChild($form);

        $navLinks = [
            [
                'url' => 'all_db.php',
                'url-params' => [
                    'action' => 'create',
                    'server' => RequestParameter::getString('server') ?? ''
                ],
                'label' => _('Create database')
            ]
        ];
        $body->appendChild(WebsiteComponents::buildNavLinks($dom, $navLinks));

        return $body;
    }
}
