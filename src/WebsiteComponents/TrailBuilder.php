<?php

declare(strict_types=1);

namespace PhpPgAdmin\WebsiteComponents;

use PhpPgAdmin\{Config, TrailSubject, WebsiteComponents};
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

abstract class TrailBuilder
{
    public static function buildTrailFor(TrailSubject $subject, \DOMDocument $dom): \DOMElement
    {
        $td = $dom->createElement('td');
        $td->setAttribute('class', 'crumb');

        $a = self::buildLinkFor($subject, $dom);

        $iconUrl = match ($subject) {
            TrailSubject::Database => Config::getIcon('Database'),
            TrailSubject::Role => Config::getIcon('Roles'),
            TrailSubject::Server => Config::getIcon('Server'),
            TrailSubject::Tablespace => Config::getIcon('Tablespaces'),
        };

        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', $iconUrl);
        $imgIcon->setAttribute('alt', self::buildLinkTitleFor($subject));
        $spanIcon->appendChild($imgIcon);
        $a->appendChild($spanIcon);

        $spanLabel = $dom->createElement('span');
        $spanLabel->appendChild($dom->createTextNode(self::buildLabelTextFor($subject)));
        $spanLabel->setAttribute('class', 'label');
        $a->appendChild($spanLabel);

        $td->appendChild($a);

        $td->appendChild(self::buildHelpLinkFor($subject, $dom));
        $td->appendChild($dom->createTextNode(': '));

        return $td;
    }

    private static function buildHelpLinkFor(TrailSubject $subject, \DOMDocument $dom): \DOMElement
    {
        $serverId = RequestParameter::getString('server') ?? '';
        $helpUrlParam = match ($subject) {
            TrailSubject::Database => 'pg.database',
            TrailSubject::Role => 'pg.role',
            TrailSubject::Server => 'pg.server',
            TrailSubject::Tablespace => 'pg.tablespace',
        };

        return WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => $helpUrlParam,
                'server' => $serverId,
            ],
        );
    }

    private static function buildLabelTextFor(TrailSubject $subject): string
    {
        $serverId = RequestParameter::getString('server') ?? '';

        return match ($subject) {
            TrailSubject::Database => RequestParameter::getString('database') ?? '',
            TrailSubject::Role => RequestParameter::getString('rolename') ?? '',
            TrailSubject::Server => (string)Logins::serverSession($serverId, Config::getServers())?->Name,
            TrailSubject::Tablespace => RequestParameter::getString('tablespace') ?? '',
        };
    }

    private static function buildLinkFor(TrailSubject $subject, \DOMDocument $dom): \DOMElement
    {
        $a = $dom->createElement('a');
        $url = self::buildUrlFor($subject);

        if (!is_null($url)) {
            $a->setAttribute('href', $url);
        }

        $a->setAttribute('title', self::buildLinkTitleFor($subject));

        return $a;
    }

    private static function buildLinkTitleFor(TrailSubject $subject): string
    {
        return match ($subject) {
            TrailSubject::Database => _('Database'),
            TrailSubject::Role => _('Role'),
            TrailSubject::Server => _('Server'),
            TrailSubject::Tablespace => _('Tablespace'),
        };
    }

    private static function buildUrlFor(TrailSubject $subject): ?string
    {
        $serverId = RequestParameter::getString('server') ?? '';

        return match ($subject) {
            // Like the legacy Misc::getTrail(): redirect.php opens the database tab that was used last.
            TrailSubject::Database => 'redirect.php?' . http_build_query([
                'database' => RequestParameter::getString('database') ?? '',
                'server' => $serverId,
                'subject' => 'database',
            ]),
            TrailSubject::Role => 'roles.php?' . http_build_query([
                'action' => 'properties',
                'rolename' => RequestParameter::getString('rolename') ?? '',
                'server' => $serverId,
            ]),
            TrailSubject::Server => 'all_db.php?' . http_build_query([
                'server' => $serverId,
            ]),
            TrailSubject::Tablespace => null,
        };
    }
}
