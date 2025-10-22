<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website, WebsiteComponents};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

final class Roles extends Website
{
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, TrailSubject::Server));

        $serverId = RequestParameter::getString('server') ?? '';
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $serverId, 'roles'));
        $body->appendChild($this->buildRolesTable($dom, $serverId));

        $navLinks = [
            [
                'label' => _('Create role'),
                'url' => 'roles.php',
                'url-params' => [
                    'action' => 'create',
                    'server' => $serverId,
                ],
            ],
        ];
        $body->appendChild(WebsiteComponents::buildNavLinks($dom, $navLinks));

        return $body;
    }

    private function buildRolesTable(\DOMDocument $dom, string $serverId): \DOMElement
    {
        $table = $dom->createElement('table');
        $table->setAttribute('style', 'width: 100%;');

        $tHead = $dom->createElement('thead');
        $tHeadRow = $dom->createElement('tr');
        $columns = [
            _('Role'),
            _('Superuser?'),
            _('Create DB?'),
            _('Can create role?'),
            _('Inherits privileges?'),
            _('Can login?'),
            _('Connection limit'),
            _('Expires'),
            _('Actions'),
        ];

        foreach ($columns as $columnIndex => $columnName) {
            $th = $dom->createElement('th');
            $th->setAttribute('class', 'data');

            if ($columnIndex === count($columns) - 1) {
                $th->setAttribute('colspan', '2');
            }

            $th->appendChild($dom->createTextNode($columnName));
            $tHeadRow->appendChild($th);
        }

        $tHead->appendChild($tHeadRow);
        $table->appendChild($tHead);

        $serverSession = ServerSession::fromServerId($serverId);
        $db = $serverSession?->getDatabaseConnection();
        $roles = $db?->getRoles() ?? [];

        $tBody = $dom->createElement('tbody');

        foreach ($roles as $roleIndex => $role) {
            $tr = $dom->createElement('tr');
            $tr->setAttribute('class', 'data' . ($roleIndex % 2 ? '2' : '1'));

            // <a href="redirect.php?subject=role&amp;action=properties&amp;server=127.0.0.1%3A5432%3Aallow&amp;rolename=pg_checkpoint&amp;">pg_checkpoint</a>
            $rolenameUrl = 'redirect.php';
            $rolenameUrlParams = [
                'action' => 'properties',
                'rolename' => $role->Name,
                'server' => $serverId,
                'subject' => 'role',
            ];
            $roleLink = $dom->createElement('a');
            $roleLink->setAttribute('href', $rolenameUrl . '?' . http_build_query($rolenameUrlParams));
            $roleLink->appendChild($dom->createTextNode($role->Name));
            $nameTd = $dom->createElement('td');
            $nameTd->setAttribute('class', 'data');
            $nameTd->appendChild($roleLink);
            $tr->appendChild($nameTd);

            $fields = [
                $role->IsSuperuser ? _('Yes') : _('No'),
                $role->CanCreateDb ? _('Yes') : _('No'),
                $role->CanCreateRole ? _('Yes') : _('No'),
                $role->CanInheritRights ? _('Yes') : _('No'),
                $role->CanLogin ? _('Yes') : _('No'),
                $role->ConnectionLimit === -1 ? _('No limit') : (string)$role->ConnectionLimit,
                $role->Expires?->format('Y-m-d') ?? '',
            ];

            foreach ($fields as $fieldValue) {
                $td = $dom->createElement('td');
                $td->setAttribute('class', 'data');

                if ($fieldValue === _('Yes') || $fieldValue === _('No')) {
                    $td->setAttribute('style', 'text-align: center;');
                }

                $td->appendChild($dom->createTextNode($fieldValue));
                $tr->appendChild($td);
            }

            $alterTd = $dom->createElement('td');
            $alterTd->setAttribute('class', 'opbutton1');
            $alterLink = $dom->createElement('a');
            $alterUrl = 'roles.php';
            $alterUrlParams = [
                'action' => 'alter',
                'rolename' => $role->Name,
                'server' => $serverId,
            ];
            $alterLink->setAttribute('href', $alterUrl . '?' . http_build_query($alterUrlParams));
            $alterLink->appendChild($dom->createTextNode(_('Alter')));
            $alterTd->appendChild($alterLink);
            $tr->appendChild($alterTd);

            $dropTd = $dom->createElement('td');
            $dropTd->setAttribute('class', 'opbutton1');
            $dropLink = $dom->createElement('a');
            $dropUrl = 'roles.php';
            $dropUrlParams = [
                'action' => 'confirm_drop',
                'rolename' => $role->Name,
                'server' => $serverId,
            ];
            $dropLink->setAttribute('href', $dropUrl . '?' . http_build_query($dropUrlParams));
            $dropLink->appendChild($dom->createTextNode(_('Drop')));
            $dropTd->appendChild($dropLink);
            $tr->appendChild($dropTd);

            $tBody->appendChild($tr);
        }

        $table->appendChild($tBody);

        return $table;
    }
}
