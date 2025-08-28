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
        $dbConnection = $serverSession?->getDatabaseConnection();

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

        $dbs = $dbConnection?->getDatabases();

        if (is_iterable($dbs)) {
            $dbCounter = 0;

            foreach ($dbs as $db) {
                $dbCounter++;

                $tr = $dom->createElement('tr');
                $tr->setAttribute('class', "data" . ($dbCounter % 2 !== 0 ? '1' : '2'));

                $tdCheckbox = $dom->createElement('td');
                $inputCheckbox = $dom->createElement('input');
                $inputCheckbox->setAttribute('type', 'checkbox');
                $inputCheckbox->setAttribute('name', 'ma[]');

                $dbName = $db['datname'];
                $dbSize = new DbSize($db['dbsize']);
                $checkboxValue = serialize(['database' => $dbName]);

                $inputCheckbox->setAttribute('value', $checkboxValue);
                $tdCheckbox->appendChild($inputCheckbox);

                $tdDatabase = $dom->createElement('td');
                $aDatabase = $dom->createElement('a', $dbName);
                $dbUrl = 'redirect.php';
                $dbUrlParams = [
                    'database' => $dbName,
                    'server' => $serverSession?->id() ?? '',
                    'subject' => 'database',
                ];
                $aDatabase->setAttribute('href', $dbUrl . '?' . http_build_query($dbUrlParams));
                $tdDatabase->appendChild($aDatabase);

                $tdOwner = $dom->createElement('td', $db['datowner']);
                $tdEncoding = $dom->createElement('td', $db['datencoding']);
                $tdCollation = $dom->createElement('td', $db['datcollate']);
                $tdCharacterType = $dom->createElement('td', $db['datctype']);
                $tdTablespace = $dom->createElement('td', $db['tablespace']);
                $tdSize = $dom->createElement('td', $dbSize->prettyFormat());

                $tdActionDelete = $dom->createElement('td');
                $tdActionDelete->setAttribute('class', 'opbutton1');
                $aDelete = $dom->createElement('a');
                $deleteUrl = 'drop_db.php';
                $deleteUrlParams = [
                    'database' => $dbName,
                    'server' => $serverSession?->id() ?? '',
                ];
                $aDelete->setAttribute('href', $deleteUrl . '?' . http_build_query($deleteUrlParams));
                $aDelete->appendChild($dom->createTextNode(_('Delete')));
                $tdActionDelete->appendChild($aDelete);

                $tdActionPrivileges = $dom->createElement('td');
                $tdActionPrivileges->setAttribute('class', 'opbutton1');
                $aPrivileges = $dom->createElement('a');
                $privilegesUrl = 'privileges.php';
                $privilegesUrlParams = [
                    'dropdatabase' => $dbName,
                    'server' => $serverSession?->id() ?? '',
                    'subject' => 'database',
                ];
                $aPrivileges->setAttribute('href', $privilegesUrl . '?' . http_build_query($privilegesUrlParams));
                $aPrivileges->appendChild($dom->createTextNode(_('Privileges')));
                $tdActionPrivileges->appendChild($aPrivileges);

                $tdActionAlter = $dom->createElement('td');
                $tdActionAlter->setAttribute('class', 'opbutton1');
                $aAlter = $dom->createElement('a');
                $alterUrl = 'alter_db.php';
                $alterUrlParams = [
                    'database' => $dbName,
                    'server' => $serverSession?->id() ?? '',
                ];
                $aAlter->setAttribute('href', $alterUrl . '?' . http_build_query($alterUrlParams));
                $aAlter->appendChild($dom->createTextNode(_('Alter')));
                $tdActionAlter->appendChild($aAlter);

                $tdComment = $dom->createElement('td', $db['datcomment']);

                $tr->appendChild($tdCheckbox);
                $tr->appendChild($tdDatabase);
                $tr->appendChild($tdOwner);
                $tr->appendChild($tdEncoding);
                $tr->appendChild($tdCollation);
                $tr->appendChild($tdCharacterType);
                $tr->appendChild($tdTablespace);
                $tr->appendChild($tdSize);
                $tr->appendChild($tdActionDelete);
                $tr->appendChild($tdActionPrivileges);
                $tr->appendChild($tdActionAlter);
                $tr->appendChild($tdComment);

                $tBody->appendChild($tr);
            }
        }

        $table->appendChild($tHead);
        $table->appendChild($tBody);

        return $table;
    }

    /**
     * @param array<mixed> $urlParams
     */
    public static function buildHelpLink(\DOMDocument $dom, string $url, ?array $urlParams = null): \DOMElement
    {
        $a = $dom->createElement('a', '?');
        $href = $url;

        if (!is_null($urlParams)) {
            $href .= '?' . http_build_query($urlParams);
        }

        $a->setAttribute('href', $href);
        $a->setAttribute('class', 'help');
        $a->setAttribute('title', _('Help'));
        $a->setAttribute('target', 'phppgadminhelp');

        return $a;
    }

    public static function buildMessage(\DOMDocument $dom, string $message): \DOMElement
    {
        $pMessage = $dom->createElement('p');
        $pMessage->setAttribute('class', 'message');
        $pMessage->appendChild($dom->createTextNode($message));

        return $pMessage;
    }

    public static function buildMultipleActionsTableForDatabases(
        \DOMDocument $dom,
        ?ServerSession $serverSession,
    ): \DOMElement {
        $table = $dom->createElement('table');

        $tHead = $dom->createElement('thead');
        $trHead = $dom->createElement('tr');
        $tHead->appendChild($trHead);
        $thAction = $dom->createElement('th');
        $thAction->setAttribute('class', 'data');
        $thAction->setAttribute('colspan', '3');
        $thAction->appendChild($dom->createTextNode(_('Actions on multiple lines')));
        $tHead->appendChild($thAction);

        $tBody = $dom->createElement('tbody');
        $trRow1 = $dom->createElement('tr');
        $trRow1->setAttribute('class', 'row1');

        $tdCol1 = $dom->createElement('td');
        $aSelectAll = $dom->createElement('a');
        $aSelectAll->setAttribute('href', '#');
        $aSelectAll->setAttribute('onclick', 'javascript:checkAll(true);');
        $aSelectAll->appendChild($dom->createTextNode(_('Select all')));
        $aUnselectAll = $dom->createElement('a');
        $aUnselectAll->setAttribute('href', '#');
        $aUnselectAll->setAttribute('onclick', 'javascript:checkAll(false);');
        $aUnselectAll->appendChild($dom->createTextNode(_('Unselect all')));
        $tdCol1->appendChild($aSelectAll);
        $tdCol1->appendChild($dom->createTextNode(' / '));
        $tdCol1->appendChild($aUnselectAll);

        $tdCol2 = $dom->createElement('td', '&nbsp;---&gt;&nbsp;');

        $tdCol3 = $dom->createElement('td');
        $selectMultiAction = $dom->createElement('select');
        $selectMultiAction->setAttribute('name', 'action');
        $optionEmpty = $dom->createElement('option', '--');
        $optionEmpty->setAttribute('value', '');
        $optionDelete = $dom->createElement('option', _('Delete'));
        $optionDelete->setAttribute('value', 'confirm_drop');
        $selectMultiAction->appendChild($optionEmpty);
        $selectMultiAction->appendChild($optionDelete);
        $inputSubmit = $dom->createElement('input');
        $inputSubmit->setAttribute('type', 'submit');
        $inputSubmit->setAttribute('value', _('Execute'));
        $inputHiddenServer = $dom->createElement('input');
        $inputHiddenServer->setAttribute('type', 'hidden');
        $inputHiddenServer->setAttribute('name', 'server');
        $inputHiddenServer->setAttribute('value', $serverSession?->id() ?? '');
        $tdCol3->appendChild($selectMultiAction);
        $tdCol3->appendChild($inputSubmit);
        $tdCol3->appendChild($inputHiddenServer);

        $trRow1->appendChild($tdCol1);
        $trRow1->appendChild($tdCol2);
        $trRow1->appendChild($tdCol3);
        $tBody->appendChild($trRow1);

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
                $aHelp = self::buildHelpLink(
                    dom: $dom,
                    url: $tabLink['help']['url'],
                    urlParams: $tabLink['help']['url-params'] ?? [],
                );
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
                'https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-samesite',
            );
            $aAlertBanner->setAttribute('target', '_blank');
            $aAlertBanner->setAttribute('rel', 'noopener noreferrer');
            $aAlertBanner->appendChild($dom->createTextNode(
                _('You are running phpPgAdmin with session security disabled. This is a potential security risk!'),
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
                '<span class="username">' . htmlspecialchars((string)$serverSession->Username) . '</span>',
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

    private static function buildTopBarLinks(\DOMDocument $dom, ServerSession $serverSession): \DOMElement
    {
        $tableCell = $dom->createElement('td');
        $tableCell->setAttribute('style', 'text-align: right;');

        $ulTopLinks = $dom->createElement('ul');
        $ulTopLinks->setAttribute('class', 'toplink');

        $topLinks = [];
        $topLinks['sql'] = [
            'target' => 'sqledit',
            'text' => _('SQL'),
            'url' => 'sqledit.php',
            'url-params' => [
                'action' => 'sql',
                'server' => $serverSession->id(),
                'subject' => 'table',
            ],
        ];
        $topLinks['history'] = [
            'text' => _('History'),
            'url' => 'history.php',
            'url-params' => [
                'action' => 'pophistory',
                'server' => $serverSession->id(),
                'subject' => 'table',
            ],
        ];
        $topLinks['find'] = [
            'target' => 'sqledit',
            'text' => _('Find'),
            'url' => 'sqledit.php',
            'url-params' => [
                'action' => 'find',
                'server' => $serverSession->id(),
                'subject' => 'table',
            ],
        ];
        $topLinks['logout'] = [
            'text' => _('Logout'),
            'url' => 'server-logout.php',
            'url-params' => [
                'id' => $serverSession->id(),
            ],
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

    private static function buildTrailForServer(\DOMDocument $dom): \DOMElement
    {
        $td = $dom->createElement('td');
        $td->setAttribute('class', 'crumb');

        $serverId = RequestParameter::getString('server') ?? '';
        $serverSession = ServerSession::fromServerId($serverId);
        $link = 'all_db.php';
        $linkParams = [
            'server' => $serverId,
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

        $aHelp = self::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.server',
                'server' => $serverId,
            ],
        );
        $td->appendChild($aHelp);
        $td->appendChild($dom->createTextNode(': '));

        return $td;
    }
}
