<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Website, WebsiteComponents};
use PhpPgAdmin\DDD\ValueObjects\TrailSubject;

class AllDb extends Website
{
    public function __construct()
    {
        $this->title = _('Databases');

        parent::__construct();
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, TrailSubject::Server));

        return $body;
    }
}
