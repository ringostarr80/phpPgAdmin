<?php

declare(strict_types=1);

namespace PhpPgAdmin;

use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

abstract class WebsiteComponents
{
    public static function buildBackToTopLink(\DOMDocument $dom): \DOMElement
    {
        $a = $dom->createElement('a', _('back to top'));
        $a->setAttribute('href', '#');
        $a->setAttribute('class', 'bottom_link');

        return $a;
    }

    /**
     * @param string $activeTab 'intro'|'servers'
     */
    public static function buildRootTabs(\DOMDocument $dom, string $activeTab): \DOMElement
    {
        $_SESSION['webdbLastTab'] = ['root' => $activeTab];

        $tableTabs = $dom->createElement('table');
        $tableTabs->setAttribute('class', 'tabs');
        $trTabs = $dom->createElement('tr');
        $tdTab = $dom->createElement('td');
        $tdTab->setAttribute('style', 'width: 50%');
        $tdTab->setAttribute('class', $activeTab === 'intro' ? 'tab active' : 'tab');
        $aTab = $dom->createElement('a');
        $aTab->setAttribute('href', 'intro.php');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Introduction'));
        $imgIcon->setAttribute('alt', _('Introduction'));
        $spanIcon->appendChild($imgIcon);
        $aTab->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', _('Introduction'));
        $spanLabel->setAttribute('class', 'label');
        $aTab->appendChild($spanLabel);
        $tdTab->appendChild($aTab);
        $trTabs->appendChild($tdTab);
        $tdTab = $dom->createElement('td');
        $tdTab->setAttribute('style', 'width: 50%');
        $tdTab->setAttribute('class', $activeTab === 'servers' ? 'tab active' : 'tab');
        $aTab = $dom->createElement('a');
        $aTab->setAttribute('href', 'servers.php');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Servers'));
        $imgIcon->setAttribute('alt', _('Server'));
        $spanIcon->appendChild($imgIcon);
        $aTab->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', _('Server'));
        $spanLabel->setAttribute('class', 'label');
        $aTab->appendChild($spanLabel);
        $tdTab->appendChild($aTab);
        $trTabs->appendChild($tdTab);
        $tableTabs->appendChild($trTabs);

        return $tableTabs;
    }

    public static function buildTopBar(\DOMDocument $dom): \DOMElement
    {
        $divWrapper = $dom->createElement('div');

        if (!Config::extraSessionSecurity()) {
            $divAlertBanner = $dom->createElement('div');
            $divAlertBanner->setAttribute('class', 'alert-banner');
            $pAlertBanner = $dom->createElement('p');
            $aAlertBanner = $dom->createElement('a');
            $aAlertBanner->setAttribute(
                'href',
                'https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite'
            );
            $aAlertBanner->setAttribute('target', '_blank');
            $aAlertBanner->setAttribute('rel', 'noopener noreferrer');
            $aAlertBanner->appendChild($dom->createTextNode(
                _('You are running phpPgAdmin with session security disabled. This is a potential security risk!')
            ));
            $pAlertBanner->appendChild($aAlertBanner);
            $divAlertBanner->appendChild($pAlertBanner);

            $divWrapper->appendChild($divAlertBanner);
        }

        $divTopbar = $dom->createElement('div');
        $divTopbar->setAttribute('class', 'topbar');
        $tableTopbar = $dom->createElement('table');
        $tableTopbar->setAttribute('style', 'width: 100%');
        $trTopbar = $dom->createElement('tr');

        $serverSession = ServerSession::fromRequestParameter();
        if (!is_null($serverSession)) {
            $topLeftContent = sprintf(
                _("%s running on %s:%s -- You are logged in as user \"%s\""),
                '<span class="platform">' . htmlspecialchars((string)$serverSession->Platform) . '</span>',
                '<span class="host">' . htmlspecialchars((string)$serverSession->Host) . '</span>',
                '<span class="port">' . $serverSession->Port->Value . '</span>',
                '<span class="username">' . htmlspecialchars((string)$serverSession->Username) . '</span>'
            );
            $fragment = $dom->createDocumentFragment();
            $fragment->appendXML($topLeftContent);
            $tdTopbar = $dom->createElement('td');
            $tdTopbar->appendChild($fragment);
            $trTopbar->appendChild($tdTopbar);

            $trTopbar->appendChild(self::buildTopBarLinks($dom, $serverSession));
        } else {
            $tdTopbar = $dom->createElement('td');
            $spanAppname = $dom->createElement('span', Website::APP_NAME);
            $spanAppname->setAttribute('class', 'appname');
            $spanVersion = $dom->createElement('span', Website::APP_VERSION);
            $spanVersion->setAttribute('class', 'version');
            $tdTopbar->appendChild($spanAppname);
            $tdTopbar->appendChild($dom->createTextNode(' '));
            $tdTopbar->appendChild($spanVersion);
            $trTopbar->appendChild($tdTopbar);
        }

        $tableTopbar->appendChild($trTopbar);
        $divTopbar->appendChild($tableTopbar);

        $divWrapper->appendChild($divTopbar);

        return $divWrapper;
    }

    private static function buildTopBarLinks(\DOMDocument $dom, ServerSession $serverSession): \DOMElement
    {
        $tableCell = $dom->createElement('td');
        $tableCell->setAttribute('style', 'text-align: right;');

        $ulTopLinks = $dom->createElement('ul');
        $ulTopLinks->setAttribute('class', 'toplink');

        $topLinks = [
            'sql' => [
                'text' => _('SQL'),
                'url' => 'sqledit.php',
                'url-params' => [
                    'subject' => 'table',
                    'server' => $serverSession->id(),
                    'action' => 'sql'
                ],
                'target' => 'sqledit'
            ],
            'history' => [
                'text' => _('History'),
                'url' => 'history.php',
                'url-params' => [
                    'subject' => 'table',
                    'server' => $serverSession->id(),
                    'action' => 'pophistory'
                ],
            ],
            'find' => [
                'text' => _('Find'),
                'url' => 'sqledit.php',
                'url-params' => [
                    'subject' => 'table',
                    'server' => $serverSession->id(),
                    'action' => 'find'
                ],
                'target' => 'sqledit'
            ],
            'logout' => [
                'text' => _('Logout'),
                'url' => 'server-logout.php',
                'url-params' => [
                    'id' => $serverSession->id()
                ]
            ]
        ];

        foreach ($topLinks as $key => $link) {
            $li = $dom->createElement('li');
            $a = $dom->createElement('a', $link['text']);
            $a->setAttribute('href', $link['url'] . '?' . http_build_query($link['url-params']));
            if (isset($link['target'])) {
                $a->setAttribute('target', $link['target']);
            }
            $a->setAttribute('id', 'toplink_' . $key);
            $li->appendChild($a);
            $ulTopLinks->appendChild($li);
        }

        $tableCell->appendChild($ulTopLinks);

        return $tableCell;
    }

    public static function buildTrail(\DOMDocument $dom, ?TrailSubject $subject = null): \DOMElement
    {
        $divTrail = $dom->createElement('div');
        $divTrail->setAttribute('class', 'trail');
        $tableTrail = $dom->createElement('table');
        $trTrail = $dom->createElement('tr');
        $tdTrail = $dom->createElement('td');
        $tdTrail->setAttribute('class', 'crumb');
        $aTrail = $dom->createElement('a');
        $aTrail->setAttribute('href', 'redirect.php?subject=root');
        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Introduction'));
        $imgIcon->setAttribute('alt', 'Database Root');
        $spanIcon->appendChild($imgIcon);
        $aTrail->appendChild($spanIcon);
        $spanLabel = $dom->createElement('span', Website::APP_NAME);
        $spanLabel->setAttribute('class', 'label');
        $aTrail->appendChild($spanLabel);
        $aTrail->appendChild($dom->createTextNode(':'));
        $tdTrail->appendChild($aTrail);
        $trTrail->appendChild($tdTrail);
        $tableTrail->appendChild($trTrail);
        $divTrail->appendChild($tableTrail);

        $subTrail = match ($subject) {
            TrailSubject::Server => self::buildTrailForServer($dom),
            default => null
        };

        if (!is_null($subTrail)) {
            $trTrail->appendChild($subTrail);
        }

        return $divTrail;
    }

    private static function buildTrailForServer(\DOMDocument $dom): \DOMElement
    {
        $td = $dom->createElement('td');
        $td->setAttribute('class', 'crumb');

        $serverId = RequestParameter::getString('server') ?? '';
        $serverSession = ServerSession::fromServerId($serverId);
        $link = 'all_db.php';
        $linkParams = [
            'server' => $serverId
        ];
        $a = $dom->createElement('a');
        $a->setAttribute('href', $link . '?' . http_build_query($linkParams));
        $a->setAttribute('title', _('Server'));

        $spanIcon = $dom->createElement('span');
        $spanIcon->setAttribute('class', 'icon');
        $imgIcon = $dom->createElement('img');
        $imgIcon->setAttribute('src', Config::getIcon('Servers'));
        $imgIcon->setAttribute('alt', _('Server'));
        $spanIcon->appendChild($imgIcon);
        $a->appendChild($spanIcon);
        if (!is_null($serverSession)) {
            $spanLabel = $dom->createElement('span', (string)$serverSession->Name);
            $spanLabel->setAttribute('class', 'label');
            $a->appendChild($spanLabel);
        }

        $td->appendChild($a);

        $helpUrl = 'help.php';
        $helpUrlParams = [
            'help' => 'pg.server',
            'server' => $serverId
        ];
        $aHelp = $dom->createElement('a', '?');
        $aHelp->setAttribute('href', $helpUrl . '?' . http_build_query($helpUrlParams));
        $aHelp->setAttribute('class', 'help');
        $aHelp->setAttribute('title', _('Help'));
        $aHelp->setAttribute('target', 'phppgadminhelp');

        $td->appendChild($aHelp);
        $td->appendChild($dom->createTextNode(': '));

        return $td;
    }
}
