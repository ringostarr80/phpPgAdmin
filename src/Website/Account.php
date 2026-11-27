<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

/**
 * The own role of the login, like the legacy roles.php?action=account.
 */
final class Account extends Website
{
    #[\Override]
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        // Without the role, which the trail takes from the request, and whose link leads to its alter and its drop.
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server]));

        $serverId = RequestParameter::getString('server') ?? '';
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $serverId, 'account'));

        $role = self::roleOfTheLogin($serverId);

        if (is_null($role)) {
            $p = $dom->createElement('p');
            $p->appendChild($dom->createTextNode(_('No rows found.')));
            $body->appendChild($p);
        } else {
            $body->appendChild(self::buildRoleTable($dom, $role));
        }

        return $body;
    }

    #[\Override]
    protected function title(): string
    {
        // Like the other pages that come from the legacy roles.php.
        return _('Roles');
    }

    /**
     * One row with the attributes of the role, in the columns of the legacy page.
     */
    private static function buildRoleTable(\DOMDocument $dom, Role $role): \DOMElement
    {
        // Heading and value of each column. A list, because phpcbf sorts the keys of keyed arrays.
        $columns = [
            [_('Name'), $role->Name],
            [_('Superuser?'), $role->IsSuperuser ? _('Yes') : _('No')],
            [_('Create DB?'), $role->CanCreateDb ? _('Yes') : _('No')],
            [_('Can create role?'), $role->CanCreateRole ? _('Yes') : _('No')],
            [_('Inherits privileges?'), $role->CanInheritRights ? _('Yes') : _('No')],
            [_('Connection limit'), $role->ConnectionLimit === -1 ? _('No limit') : (string)$role->ConnectionLimit],
            [_('Expires'), $role->Expires?->format('c') ?? _('Never')],
        ];

        $tHeadRow = $dom->createElement('tr');
        $tBodyRow = $dom->createElement('tr');

        foreach ($columns as [$heading, $value]) {
            $th = $dom->createElement('th');
            $th->setAttribute('class', 'data');
            $th->appendChild($dom->createTextNode($heading));
            $tHeadRow->appendChild($th);

            $td = $dom->createElement('td');
            $td->setAttribute('class', 'data1');
            $td->appendChild($dom->createTextNode($value));
            $tBodyRow->appendChild($td);
        }

        $tHead = $dom->createElement('thead');
        $tHead->appendChild($tHeadRow);
        $tBody = $dom->createElement('tbody');
        $tBody->appendChild($tBodyRow);

        $table = $dom->createElement('table');
        $table->appendChild($tHead);
        $table->appendChild($tBody);

        return $table;
    }

    /**
     * Null without a login to the server.
     */
    private static function roleOfTheLogin(string $serverId): ?Role
    {
        $serverSession = Logins::serverSession($serverId, Config::getServers());
        $db = PhpPgAdminConnection::createFromServerSession($serverSession);

        return $db?->getRole((string)$serverSession?->Username);
    }
}
