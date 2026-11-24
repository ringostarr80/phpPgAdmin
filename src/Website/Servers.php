<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, Website, WebsiteComponents};

final class Servers extends Website
{
    public function __construct()
    {
        parent::__construct();

        $this->title = _('Servers');
    }

    /**
     * The list of all servers, also the ones one is not logged in to.
     */
    #[\Override]
    public static function requiresServerLogin(): bool
    {
        return false;
    }

    #[\Override]
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom));
        $body->appendChild(WebsiteComponents::buildRootTabs($dom, 'servers'));

        $table = $dom->createElement('table');
        $table->setAttribute('id', 'server-list');

        $tHead = $dom->createElement('thead');
        $tr = $dom->createElement('tr');
        $th = $dom->createElement('th', _('Server'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $th = $dom->createElement('th', _('Host'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $th = $dom->createElement('th', _('Port'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $th = $dom->createElement('th', _('Username'));
        $th->setAttribute('class', 'data');
        $tr->appendChild($th);
        $th = $dom->createElement('th', _('Actions'));
        $th->setAttribute('class', 'data');
        $th->setAttribute('colspan', '1');
        $tr->appendChild($th);
        $tHead->appendChild($tr);
        $table->appendChild($tHead);

        $tBody = $dom->createElement('tbody');
        $logins = isset($_SESSION['webdbLogin']) && is_array($_SESSION['webdbLogin'])
            ? $_SESSION['webdbLogin']
            : [];
        $servers = Config::getServers();

        foreach ($servers as $index => $server) {
            // The themes style opbutton1 in the rows with data1 and opbutton2 in those with data2.
            $rowNumber = $index % 2 === 0
                ? '1'
                : '2';
            $tr = $dom->createElement('tr');
            $tr->setAttribute('class', 'data' . $rowNumber);
            $td = $dom->createElement('td');
            $a = $dom->createElement('a');
            $a->appendChild($dom->createTextNode((string)$server->Name));
            $serverId = $server->id();
            $redirectUrlParams = [
                'server' => $serverId,
                'subject' => 'server',
            ];
            $redirectUrl = 'redirect.php?' . http_build_query($redirectUrlParams);
            $a->setAttribute('href', $redirectUrl);
            $td->appendChild($a);
            $tr->appendChild($td);
            $td = $dom->createElement('td');
            $td->appendChild($dom->createTextNode((string)$server->Host));
            $tr->appendChild($td);
            $td = $dom->createElement('td', (string)$server->Port->Value);
            $tr->appendChild($td);
            $username = '';

            if (
                isset($logins[$serverId]) &&
                is_array($logins[$serverId]) &&
                isset($logins[$serverId]['username']) &&
                is_string($logins[$serverId]['username'])
            ) {
                $username = $logins[$serverId]['username'];
            }

            $td = $dom->createElement('td');
            $td->appendChild($dom->createTextNode($username));
            $tr->appendChild($td);
            $td = $dom->createElement('td');

            if ($username !== '') {
                $td->setAttribute('class', 'opbutton' . $rowNumber);
                $a = $dom->createElement('a', _('Logout'));
                $logoutUrlParams = [
                    'id' => $serverId,
                ];
                $logoutUrl = 'server-logout.php?' . http_build_query($logoutUrlParams);
                $a->setAttribute('href', $logoutUrl);
                $td->appendChild($a);
            }

            $tr->appendChild($td);
            $tBody->appendChild($tr);
        }

        $table->appendChild($tBody);

        $body->appendChild($table);

        $body->appendChild(WebsiteComponents::buildBackToTopLink($dom));

        return $body;
    }
}
