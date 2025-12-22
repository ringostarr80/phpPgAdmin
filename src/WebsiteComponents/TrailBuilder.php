<?php

declare(strict_types=1);

namespace PhpPgAdmin\WebsiteComponents;

use PhpPgAdmin\{Config, TrailSubject};
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\{RequestParameter, WebsiteComponents};

abstract class TrailBuilder
{
    public static function buildTrailFor(TrailSubject $subject, \DOMDocument $dom): \DOMElement
    {
        $td = $dom->createElement('td');
        $td->setAttribute('class', 'crumb');

        $a = self::buildLinkFor($subject, $dom);

        $iconUrl = match ($subject) {
            TrailSubject::Role => Config::getIcon('Roles'),
            TrailSubject::Server => Config::getIcon('Servers'),
        };

        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', $iconUrl);
        $imgIcon->setAttribute('alt', self::buildLinkTitleFor($subject));
        $spanIcon->appendChild($imgIcon);
        $a->appendChild($spanIcon);

        $spanLabel = $dom->createElement('span', self::buildLabelTextFor($subject));
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
            TrailSubject::Role => 'pg.role',
            TrailSubject::Server => 'pg.server',
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
        if ($subject === TrailSubject::Server) {
            $serverId = RequestParameter::getString('server') ?? '';
            $serverSession = ServerSession::fromServerId($serverId);

            if (!is_null($serverSession)) {
                return (string)$serverSession->Name;
            }
        } elseif ($subject === TrailSubject::Role) {
            return RequestParameter::getString('rolename') ?? '';
        }

        return '';
    }

    private static function buildLinkFor(TrailSubject $subject, \DOMDocument $dom): \DOMElement
    {
        $a = $dom->createElement('a');
        $a->setAttribute('href', self::buildUrlFor($subject));
        $a->setAttribute('title', self::buildLinkTitleFor($subject));

        return $a;
    }

    private static function buildLinkTitleFor(TrailSubject $subject): string
    {
        return match ($subject) {
            TrailSubject::Role => _('Role'),
            TrailSubject::Server => _('Server'),
        };
    }

    private static function buildUrlFor(TrailSubject $subject): string
    {
        $serverId = RequestParameter::getString('server') ?? '';

        $script = match ($subject) {
            TrailSubject::Role => 'roles.php',
            TrailSubject::Server => 'all_db.php',
        };
        $scriptParams = match ($subject) {
            TrailSubject::Role => [
                'action' => 'properties',
                'rolename' => RequestParameter::getString('rolename') ?? '',
                'server' => $serverId,
            ],
            TrailSubject::Server => [
                'server' => $serverId,
            ],
        };

        return $script . '?' . http_build_query($scriptParams);
    }
}
