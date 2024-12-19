<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, Website};

class Index extends Website
{
    protected function buildHtmlHead(\DOMDocument $dom): \DOMElement
    {
        $head = parent::buildHtmlHead($dom);

        $meta = $dom->createElement('meta');
        $meta->setAttribute('http-equiv', 'Content-Type');
        $meta->setAttribute('content', 'text/html; charset=utf-8');
        $head->appendChild($meta);

        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'stylesheet');
        $theme = Config::theme();
        $link->setAttribute('href', "themes/{$theme}/global.css");
        $link->setAttribute('type', 'text/css');
        $link->setAttribute('id', 'csstheme');
        $head->appendChild($link);

        return $head;
    }
}
