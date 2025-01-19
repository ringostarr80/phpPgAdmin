<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, Website, WebsiteComponents};

class Servers extends Website
{
    public function __construct()
    {
        parent::__construct();

        $this->title = _('Servers');
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom));
        $body->appendChild(WebsiteComponents::buildRootTabs($dom, 'servers'));

        $table = $dom->createElement('table');
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
        $table->appendChild($tr);

        $servers = Config::getServers();
        foreach ($servers as $index => $server) {
            $tr = $dom->createElement('tr');
            $tr->setAttribute('class', 'data' . ($index + 1));
            $td = $dom->createElement('td');
            $a = $dom->createElement('a', $server['desc']);
            $redirectUrlParams = [
                'subject' => 'server',
                'server' => $server['host'] . ':' . $server['port'] . ':' . $server['sslmode']
            ];
            $redirectUrl = 'redirect.php?' . http_build_query($redirectUrlParams);
            $a->setAttribute('href', $redirectUrl);
            $td->appendChild($a);
            $tr->appendChild($td);
            $td = $dom->createElement('td', $server['host']);
            $tr->appendChild($td);
            $td = $dom->createElement('td', (string)$server['port']);
            $tr->appendChild($td);
            $td = $dom->createElement('td');
            $tr->appendChild($td);
            $td = $dom->createElement('td');
            $tr->appendChild($td);
            $table->appendChild($tr);
        }

        $body->appendChild($table);

        $body->appendChild(WebsiteComponents::buildBackToTopLink($dom));

        return $body;
    }
}
