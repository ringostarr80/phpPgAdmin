<?php

declare(strict_types=1);

namespace PhpPgAdmin\Api\Servers;

use PhpPgAdmin\{Config, RequestParameter, Session};
use PhpPgAdmin\DDD\Entities\ServerSession;

class Tree
{
    public function __construct()
    {
        Session::start();
    }

    public function outputXmlTree(): void
    {
        $domImpl = new \DOMImplementation();
        $dom = $domImpl->createDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->encoding = 'utf-8';

        $logins = isset($_SESSION['webdbLogin']) && is_array($_SESSION['webdbLogin']) ? $_SESSION['webdbLogin'] : [];
        $root = $dom->createElement('tree');

        $serverIdParam = RequestParameter::getString('server');
        $serverSession = !is_null($serverIdParam) ? ServerSession::fromServerId($serverIdParam) : null;
        if (!is_null($serverSession)) {
            $db = $serverSession->getDatabaseConnection();
            $dbs = $db->getDatabases();
            if ($dbs instanceof \ADORecordSet) {
                while (!$dbs->EOF) {
                    if (
                        is_array($dbs->fields) &&
                        isset($dbs->fields['datname']) &&
                        is_string($dbs->fields['datname'])
                    ) {
                        $actionUrl = 'redirect.php';
                        $actionUrlParams = [
                            'subject' => 'database',
                            'server' => $serverSession->id(),
                            'database' => $dbs->fields['datname']
                        ];
                        $srcUrl = 'database.php';
                        $srcUrlParams = $actionUrlParams;
                        $srcUrlParams['action'] = 'tree';

                        $tree = $dom->createElement('tree');
                        $tree->setAttribute('text', $dbs->fields['datname']);
                        $tree->setAttribute('action', $actionUrl . '?' . http_build_query($actionUrlParams));
                        $tree->setAttribute('src', $srcUrl . '?' . http_build_query($srcUrlParams));
                        $tree->setAttribute('icon', Config::getIcon('Database'));
                        $tree->setAttribute('openicon', Config::getIcon('Database'));
                        if (isset($dbs->fields['datcomment']) && is_string($dbs->fields['datcomment'])) {
                            $tree->setAttribute('tooltip', $dbs->fields['datcomment']);
                        }

                        $root->appendChild($tree);
                    }

                    $dbs->MoveNext();
                }
            }
        } else {
            $configuredServers = Config::getServers();
            foreach ($configuredServers as $configuredServer) {
                $tree = $dom->createElement('tree');
                $tree->setAttribute('text', (string)$configuredServer->Name);
                $serverId = $configuredServer->id();
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
                        'server' => $serverId
                    ];
                    $srcUrl = 'servers-tree.php?' . http_build_query($srcUrlParams);
                    $tree->setAttribute('src', $srcUrl);
                }

                $iconName = $username !== '' ? 'Server' : 'DisconnectedServer';
                $tree->setAttribute('icon', Config::getIcon($iconName));
                $tree->setAttribute('openicon', Config::getIcon($iconName));
                $tree->setAttribute('tooltip', $serverId);
                $root->appendChild($tree);
            }
        }

        $dom->appendChild($root);

        header('Content-Type: text/xml');
        echo $dom->saveXML();
    }
}
