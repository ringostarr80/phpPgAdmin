<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website, WebsiteComponents};
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

        $actionParam = RequestParameter::getString('action');
        $tabLinks = [
            [
                'url' => 'all_db.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => RequestParameter::getString('server') ?? ''
                ],
                'label' => _('Databases'),
                'icon' => 'Databases',
                'active' => is_null($actionParam),
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => RequestParameter::getString('server') ?? ''
                    ]
                ]
            ],
            [
                'url' => 'roles.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => RequestParameter::getString('server') ?? ''
                ],
                'label' => _('Roles'),
                'icon' => 'Roles',
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.role',
                        'server' => RequestParameter::getString('server') ?? ''
                    ]
                ]
            ],
            [
                'url' => 'tablespaces.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => RequestParameter::getString('server') ?? ''
                ],
                'label' => _('Tablespaces'),
                'icon' => 'Tablespaces',
                'help' => [
                    'url' => 'help.php',
                    'url-params' => [
                        'help' => 'pg.tablespace',
                        'server' => RequestParameter::getString('server') ?? ''
                    ]
                ]
            ],
            [
                'url' => 'all_db.php',
                'url-params' => [
                    'subject' => 'server',
                    'server' => RequestParameter::getString('server') ?? '',
                    'action' => 'export'
                ],
                'label' => _('Export'),
                'icon' => 'Export',
                'active' => $actionParam === 'export'
            ]
        ];
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $tabLinks));

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
