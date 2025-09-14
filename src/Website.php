<?php

declare(strict_types=1);

namespace PhpPgAdmin;

abstract class Website
{
    public const APP_NAME = 'phpPgAdmin';
    public const APP_VERSION = '8.0.0-prealpha';

    /**
     * @var array<string, array{'src': string, 'type'?: 'text/javascript'|'module'}>
     */
    protected array $scripts = [
        'jquery' => [
            'src' => 'libraries/js/jquery-3.7.1.min.js',
        ],
    ];
    protected string $title = '';

    public function __construct()
    {
        if (!($this instanceof Website\Exception)) {
            set_exception_handler([Website\Exception::class, 'handle']);
        }

        Session::start();

        Language::setLocale(Config::locale());
        bindtextdomain('messages', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'locale');
    }

    public function buildHtmlDocument(): \DOMDocument
    {
        $domImpl = new \DOMImplementation();
        $doctype = $domImpl->createDocumentType('html', '', '');
        $dom = $domImpl->createDocument('', '', $doctype);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElement('html');
        $root->setAttribute('lang', str_replace('_', '-', Config::locale()));
        $root->appendChild($this->buildHtmlHead($dom));
        $root->appendChild($this->buildHtmlBody($dom));
        $dom->appendChild($root);

        return $dom;
    }

    public function buildHtmlString(): string
    {
        $dom = $this->buildHtmlDocument();

        return $dom->saveHTML() ?: '';
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        return $dom->createElement('body');
    }

    protected function buildHtmlHead(\DOMDocument $dom): \DOMElement
    {
        $head = $dom->createElement('head');

        $metaContentType = $dom->createElement('meta');
        $metaContentType->setAttribute('http-equiv', 'Content-Type');
        $metaContentType->setAttribute('content', 'text/html; charset=utf-8');
        $head->appendChild($metaContentType);

        $metaColorScheme = $dom->createElement('meta');
        $metaColorScheme->setAttribute('name', 'color-scheme');
        $metaColorScheme->setAttribute('content', 'light dark');
        $head->appendChild($metaColorScheme);

        $formatTitle = '';

        $formatTitle = !empty($this->title)
            ? self::APP_NAME . ' - ' . $this->title
            : self::APP_NAME;

        $title = $dom->createElement('title');
        $title->appendChild($dom->createTextNode($formatTitle));
        $head->appendChild($title);

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

        foreach ($this->scripts as $script) {
            $scriptElement = $dom->createElement('script');
            $scriptElement->setAttribute('src', $script['src']);
            $scriptElement->setAttribute('type', $script['type'] ?? 'text/javascript');
            $head->appendChild($scriptElement);
        }

        return $head;
    }

    protected function buildTitle(\DOMDocument $dom, string $title): \DOMElement
    {
        return $dom->createElement('h2', $title);
    }
}
