<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, Website};

class Index extends Website
{
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);
        $body->setAttribute('style', 'position: absolute; top: 0; bottom: 0; left: 0; right: 0;');

        $div = $dom->createElement('div');
        $div->setAttribute('style', 'display: flex; height: 100%;');

        $leftWidth = Config::leftWidth();
        $browserIFrame = $dom->createElement('iframe');
        $browserIFrame->setAttribute('src', 'browser.php');
        $browserIFrame->setAttribute('style', "width: {$leftWidth}px;");
        $browserIFrame->setAttribute('title', 'browser');
        $browserIFrame->setAttribute('name', 'browser');
        $browserIFrame->setAttribute('id', 'browser');
        $browserIFrame->setAttribute('frameborder', '0');
        $div->appendChild($browserIFrame);

        $separator = $dom->createElement('div');
        $separator->setAttribute('style', 'width: 3px; background-color: #AAA; cursor: ew-resize;');
        $separator->setAttribute('id', 'separator');
        $div->appendChild($separator);

        $detailIFrame = $dom->createElement('iframe');
        $detailIFrame->setAttribute('src', 'intro.php');
        $detailIFrame->setAttribute('style', 'width: 100%;');
        $detailIFrame->setAttribute('name', 'detail');
        $detailIFrame->setAttribute('id', 'detail');
        $detailIFrame->setAttribute('frameborder', '0');
        $div->appendChild($detailIFrame);

        $body->appendChild($div);

        return $body;
    }

    protected function buildHtmlHead(\DOMDocument $dom): \DOMElement
    {
        $head = parent::buildHtmlHead($dom);

        $meta = $dom->createElement('meta');
        $meta->setAttribute('http-equiv', 'Content-Type');
        $meta->setAttribute('content', 'text/html; charset=utf-8');
        $head->appendChild($meta);

        $theme = Config::theme();

        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'stylesheet');
        $link->setAttribute('href', "themes/{$theme}/global.css");
        $link->setAttribute('type', 'text/css');
        $link->setAttribute('id', 'csstheme');
        $head->appendChild($link);

        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'shortcut icon');
        $link->setAttribute('href', "images/themes/{$theme}/Favicon.ico");
        $link->setAttribute('type', 'image/vnd.microsoft.icon');
        $head->appendChild($link);

        $link = $dom->createElement('link');
        $link->setAttribute('rel', 'icon');
        $link->setAttribute('type', 'image/png');
        $link->setAttribute('href', "images/themes/{$theme}/Introduction.png");
        $head->appendChild($link);

        $script = $dom->createElement('script');
        $script->setAttribute('type', 'text/javascript');
        $script->setAttribute('src', 'libraries/js/jquery.js');
        $head->appendChild($script);

        $script = $dom->createElement('script');
        $script->setAttribute('type', 'text/javascript');
        $script->setAttribute('src', 'js/main.js');
        $head->appendChild($script);

        $formatTitle = '';
        if (!empty($this->title)) {
            $formatTitle = self::APP_NAME . ' - ' . $this->title;
        } else {
            $formatTitle = self::APP_NAME;
        }
        $title = $dom->createElement('title');
        $title->appendChild($dom->createTextNode($formatTitle));
        $head->appendChild($title);

        return $head;
    }
}
