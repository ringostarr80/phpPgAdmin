<?php

declare(strict_types=1);

namespace PhpPgAdmin;

use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\DDD\ValueObjects\{DbSize, TrailSubject};

abstract class WebsiteComponents
{
    public static function buildBackToTopLink(\DOMDocument $dom): \DOMElement
    {
        $a = $dom->createElement('a', _('back to top'));
        $a->setAttribute('href', '#');
        $a->setAttribute('class', 'bottom_link');

        return $a;
    }

    public static function buildDatabasesTable(\DOMDocument $dom, ?ServerSession $serverSession): \DOMElement
    {
        $db = $serverSession?->getDatabaseConnection();

        $table = $dom->createElement('table');
        $table->setAttribute('style', 'width: 100%;');

        $tHead = $dom->createElement('thead');
        $trHead = $dom->createElement('tr');
        $thEmpty = $dom->createElement('th');
        $thDatabase = $dom->createElement('th', _('Database'));
        $thDatabase->setAttribute('class', 'data');
        $thOwner = $dom->createElement('th', _('Owner'));
        $thOwner->setAttribute('class', 'data');
        $thEncoding = $dom->createElement('th', _('Encoding'));
        $thEncoding->setAttribute('class', 'data');
        $thCollation = $dom->createElement('th', _('Collation'));
        $thCollation->setAttribute('class', 'data');
        $thCharacterType = $dom->createElement('th', _('Character Type'));
        $thCharacterType->setAttribute('class', 'data');
        $thTablespace = $dom->createElement('th', _('Tablespace'));
        $thTablespace->setAttribute('class', 'data');
        $thSize = $dom->createElement('th', _('Size'));
        $thSize->setAttribute('class', 'data');
        $thActions = $dom->createElement('th', _('Actions'));
        $thActions->setAttribute('class', 'data');
        $thActions->setAttribute('colspan', '3');
        $thComment = $dom->createElement('th', _('Comment'));
        $thComment->setAttribute('class', 'data');
        $trHead->appendChild($thEmpty);
        $trHead->appendChild($thDatabase);
        $trHead->appendChild($thOwner);
        $trHead->appendChild($thEncoding);
        $trHead->appendChild($thCollation);
        $trHead->appendChild($thCharacterType);
        $trHead->appendChild($thTablespace);
        $trHead->appendChild($thSize);
        $trHead->appendChild($thActions);
        $trHead->appendChild($thComment);
        $tHead->appendChild($trHead);

        $tBody = $dom->createElement('tbody');

        $dbs = $db?->getDatabases();
        if ($dbs instanceof \ADORecordSet) {
            $dbCounter = 0;
            while (!$dbs->EOF) {
                $dbCounter++;

                $tr = $dom->createElement('tr');
                $tr->setAttribute('class', "data{$dbCounter}");

                $tdCheckbox = $dom->createElement('td');
                $inputCheckbox = $dom->createElement('input');
                $inputCheckbox->setAttribute('type', 'checkbox');
                $inputCheckbox->setAttribute('name', 'ma[]');

                $dbName = '';
                $dbOwner = '';
                $dbEncoding = '';
                $dbCollation = '';
                $dbCharacterType = '';
                $dbTablespace = '';
                $dbSize = new DbSize(0);
                $dbComment = '';
                $checkboxValue = '';
                if (is_array($dbs->fields)) {
                    if (isset($dbs->fields['datname']) && is_string($dbs->fields['datname'])) {
                        $dbName = $dbs->fields['datname'];
                        $checkboxValue = serialize(['database' => $dbName]);
                    }
                    if (isset($dbs->fields['datowner']) && is_string($dbs->fields['datowner'])) {
                        $dbOwner = $dbs->fields['datowner'];
                    }
                    if (isset($dbs->fields['datencoding']) && is_string($dbs->fields['datencoding'])) {
                        $dbEncoding = $dbs->fields['datencoding'];
                    }
                    if (isset($dbs->fields['datcollate']) && is_string($dbs->fields['datcollate'])) {
                        $dbCollation = $dbs->fields['datcollate'];
                    }
                    if (isset($dbs->fields['datctype']) && is_string($dbs->fields['datctype'])) {
                        $dbCharacterType = $dbs->fields['datctype'];
                    }
                    if (isset($dbs->fields['tablespace']) && is_string($dbs->fields['tablespace'])) {
                        $dbTablespace = $dbs->fields['tablespace'];
                    }
                    if (isset($dbs->fields['dbsize']) && is_numeric($dbs->fields['dbsize'])) {
                        $dbSize = new DbSize((int)$dbs->fields['dbsize']);
                    }
                    if (isset($dbs->fields['datcomment']) && is_string($dbs->fields['datcomment'])) {
                        $dbComment = $dbs->fields['datcomment'];
                    }
                }
                $inputCheckbox->setAttribute('value', $checkboxValue);
                $tdCheckbox->appendChild($inputCheckbox);

                $tdDatabase = $dom->createElement('td');
                $aDatabase = $dom->createElement('a', $dbName);
                $dbUrl = 'redirect.php';
                $dbUrlParams = [
                    'subject' => 'database',
                    'server' => $serverSession?->id() ?? '',
                    'database' => $dbName
                ];
                $aDatabase->setAttribute('href', $dbUrl . '?' . http_build_query($dbUrlParams));
                $tdDatabase->appendChild($aDatabase);

                $tdOwner = $dom->createElement('td', $dbOwner);
                $tdEncoding = $dom->createElement('td', $dbEncoding);
                $tdCollation = $dom->createElement('td', $dbCollation);
                $tdCharacterType = $dom->createElement('td', $dbCharacterType);
                $tdTablespace = $dom->createElement('td', $dbTablespace);
                $tdSize = $dom->createElement('td', $dbSize->prettyFormat());

                $tdActions = $dom->createElement('td');
                $tdActions->setAttribute('colspan', '3');

                $tdComment = $dom->createElement('td', $dbComment);

                $tr->appendChild($tdCheckbox);
                $tr->appendChild($tdDatabase);
                $tr->appendChild($tdOwner);
                $tr->appendChild($tdEncoding);
                $tr->appendChild($tdCollation);
                $tr->appendChild($tdCharacterType);
                $tr->appendChild($tdTablespace);
                $tr->appendChild($tdSize);
                $tr->appendChild($tdActions);
                $tr->appendChild($tdComment);

                $tBody->appendChild($tr);

                $dbs->MoveNext();
            }
        }

        $table->appendChild($tHead);
        $table->appendChild($tBody);

        return $table;
    }

    /**
     * @param array{'url': string, 'url-params'?: array<string, string>, 'label': string}[] $navLinks
     */
    public static function buildNavLinks(\DOMDocument $dom, array $navLinks): \DOMElement
    {
        $ul = $dom->createElement('ul');
        $ul->setAttribute('class', 'navlink');

        foreach ($navLinks as $navLink) {
            $li = $dom->createElement('li');
            $a = $dom->createElement('a', $navLink['label']);
            $href = $navLink['url'];
            if (isset($navLink['url-params'])) {
                $href .= '?' . http_build_query($navLink['url-params']);
            }
            $a->setAttribute('href', $href);
            $li->appendChild($a);
            $ul->appendChild($li);
        }

        return $ul;
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

    /**
     * @param array{
     *  'url': string,
     *  'url-params'?: array<string, string>,
     *  'label': string,
     *  'icon': string,
     *  'active'?: bool,
     *  'help'?: array{
     *      'url': string,
     *      'url-params'?: array<string, string>
     *  }
     * }[] $tabLinks
     */
    public static function buildServerDatabasesTabs(\DOMDocument $dom, array $tabLinks): \DOMElement
    {
        $table = $dom->createElement('table');
        $table->setAttribute('class', 'tabs');

        $tBody = $dom->createElement('tbody');
        $tr = $dom->createElement('tr');

        foreach ($tabLinks as $tabLink) {
            $td = $dom->createElement('td');
            $tdClass = 'tab';
            if (isset($tabLink['active']) && $tabLink['active']) {
                $tdClass .= ' active';
            }
            $td->setAttribute('class', $tdClass);
            $td->setAttribute('style', 'width: 20%');

            $href = $tabLink['url'];
            if (isset($tabLink['url-params'])) {
                $href .= '?' . http_build_query($tabLink['url-params']);
            }
            $a = $dom->createElement('a');
            $a->setAttribute('href', $href);
            $spanIcon = $dom->createElement('span');
            $spanIcon->setAttribute('class', 'icon');
            $imgIcon = $dom->createElement('img');
            $imgIcon->setAttribute('src', Config::getIcon($tabLink['icon']));
            $imgIcon->setAttribute('alt', $tabLink['label']);
            $spanIcon->appendChild($imgIcon);
            $spanLabel = $dom->createElement('span', $tabLink['label']);
            $spanLabel->setAttribute('class', 'label');
            $a->appendChild($spanIcon);
            $a->appendChild($spanLabel);
            $td->appendChild($a);

            if (isset($tabLink['help'])) {
                $aHelp = $dom->createElement('a', '?');
                $helpHref = $tabLink['help']['url'];
                if (isset($tabLink['help']['url-params'])) {
                    $helpHref .= '?' . http_build_query($tabLink['help']['url-params']);
                }
                $aHelp->setAttribute('href', $helpHref);
                $aHelp->setAttribute('class', 'help');
                $aHelp->setAttribute('title', _('Help'));
                $aHelp->setAttribute('target', 'phppgadminhelp');
                $td->appendChild($aHelp);
            }

            $tr->appendChild($td);
        }

        $tBody->appendChild($tr);

        $table->appendChild($tBody);

        return $table;
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
