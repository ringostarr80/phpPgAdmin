<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{RequestParameter, Website};

final class ServerLogout extends Website
{
    public function __construct()
    {
        parent::__construct();

        $serverId = RequestParameter::getString('id');

        if (
            !is_null($serverId) &&
            isset($_SESSION['webdbLogin']) &&
            is_array($_SESSION['webdbLogin']) &&
            isset($_SESSION['webdbLogin'][$serverId])
        ) {
            unset($_SESSION['webdbLogin'][$serverId]);
        }
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = $dom->createElement('body');

        $scriptElement = $dom->createElement('script');
        $scriptElement->setAttribute('type', 'text/javascript');
        $scriptElement->appendChild($dom->createCDATASection('parent.frames.browser.location.reload();'));
        $body->appendChild($scriptElement);

        return $body;
    }

    protected function buildHtmlHead(\DOMDocument $dom): \DOMElement
    {
        $head = parent::buildHtmlHead($dom);

        $redirectUrl = './servers.php';

        if (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
            $redirectUrl = $_SERVER['HTTP_REFERER'];
        }

        $meta = $dom->createElement('meta');
        $meta->setAttribute('http-equiv', 'refresh');
        $meta->setAttribute('content', '0; url=' . $redirectUrl);
        $head->appendChild($meta);

        return $head;
    }
}
