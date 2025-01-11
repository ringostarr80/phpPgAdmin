<?php

declare(strict_types=1);

namespace PhpPgAdmin\Api\Servers;

use PhpPgAdmin\Config;

class Tree
{
    public function outputXmlTree(): void
    {
        $domImpl = new \DOMImplementation();
        $doctype = $domImpl->createDocumentType('tree', '', '');
        $dom = $domImpl->createDocument('', '', $doctype);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElement('tree');
        $configuredServers = Config::getServers();
        foreach ($configuredServers as $configuredServer) {
            $tree = $dom->createElement('tree');
            $tree->setAttribute('text', $configuredServer['desc']);
            $tooltip = $configuredServer['host'] . ':' .
                $configuredServer['port'] . ':' .
                $configuredServer['sslmode'];
            $actionUrlParams = [
                'subject' => 'server',
                'server' => $tooltip
            ];
            $actionUrl = 'redirect.php?' . http_build_query($actionUrlParams);
            $tree->setAttribute('action', $actionUrl);
            $tree->setAttribute('icon', Config::getIcon('DisconnectedServer'));
            $tree->setAttribute('openicon', Config::getIcon('DisconnectedServer'));
            $tree->setAttribute('tooltip', $tooltip);
            $root->appendChild($tree);
        }
        $dom->appendChild($root);

        header('Content-Type: text/xml');
        echo $dom->saveXML();
    }
}
