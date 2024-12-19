<?php

declare(strict_types=1);

namespace PhpPgAdmin;

abstract class Website
{
    public function __construct()
    {
        putenv('LC_ALL=en_US.UTF-8');
        setlocale(LC_ALL, ['en_US.UTF-8', 'en_US', 'en']);
        bindtextdomain('messages', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'locale');
        textdomain('messages');
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        return $dom->createElement('body');
    }

    public function buildHtmlDocument(): \DOMDocument
    {
        $domImpl = new \DOMImplementation();
        $doctype = $domImpl->createDocumentType('html', '', '');
        $dom = $domImpl->createDocument('', '', $doctype);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElement('html');
        $root->setAttribute('lang', _('applocale'));
        $root->appendChild($this->buildHtmlHead($dom));
        $root->appendChild($this->buildHtmlBody($dom));
        $dom->appendChild($root);

        return $dom;
    }

    protected function buildHtmlHead(\DOMDocument $dom): \DOMElement
    {
        return $dom->createElement('head');
    }

    public function buildHtmlString(): string
    {
        $dom = $this->buildHtmlDocument();
        return $dom->saveHTML() ?: '';
    }
}
