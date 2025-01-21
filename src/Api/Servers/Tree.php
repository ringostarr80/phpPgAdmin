<?php

declare(strict_types=1);

namespace PhpPgAdmin\Api\Servers;

use PhpPgAdmin\{Config, Session};

class Tree
{
    public function __construct()
    {
        Session::start();
    }

    public function outputXmlTree(): void
    {
        $domImpl = new \DOMImplementation();
        $doctype = $domImpl->createDocumentType('tree', '', '');
        $dom = $domImpl->createDocument('', '', $doctype);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $logins = isset($_SESSION['webdbLogin']) && is_array($_SESSION['webdbLogin']) ? $_SESSION['webdbLogin'] : [];
        $root = $dom->createElement('tree');
        $configuredServers = Config::getServers();
        foreach ($configuredServers as $configuredServer) {
            $tree = $dom->createElement('tree');
            $tree->setAttribute('text', $configuredServer['desc']);
            $serverId = $configuredServer['host'] . ':' .
                $configuredServer['port'] . ':' .
                $configuredServer['sslmode'];
            $actionUrlParams = [
                'subject' => 'server',
                'server' => $serverId
            ];
            $actionUrl = 'redirect.php?' . http_build_query($actionUrlParams);
            $tree->setAttribute('action', $actionUrl);

            $username = '';
            if (
                isset($logins[$serverId]) &&
                is_array($logins[$serverId]) &&
                isset($logins[$serverId]['username']) &&
                is_string($logins[$serverId]['username'])
            ) {
                $username = $logins[$serverId]['username'];

                $srcUrlParams = [
                    'action' => 'tree',
                    'subject' => 'server',
                    'server' => $serverId
                ];
                $srcUrl = 'all_db.php?' . http_build_query($srcUrlParams);
                $tree->setAttribute('src', $srcUrl);
            }

            $iconName = $username !== '' ? 'Server' : 'DisconnectedServer';
            $tree->setAttribute('icon', Config::getIcon($iconName));
            $tree->setAttribute('openicon', Config::getIcon($iconName));
            $tree->setAttribute('tooltip', $serverId);
            $root->appendChild($tree);
        }
        $dom->appendChild($root);

        header('Content-Type: text/xml');
        echo $dom->saveXML();
    }
}
