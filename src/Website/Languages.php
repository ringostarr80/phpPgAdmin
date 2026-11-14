<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use Locr\Lib\HTTP\StatusCode;
use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\Entities\ServerSession;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;

/**
 * The languages of a database.
 */
final class Languages extends Website
{
    public function __construct()
    {
        parent::__construct();

        // After the parent constructor, which sets the locale for the translation.
        $this->title = _('Languages');
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $serverId = RequestParameter::getString('server') ?? '';
        $database = RequestParameter::getString('database') ?? '';

        if ($database === '') {
            throw new \InvalidArgumentException('Missing required parameter: database', StatusCode::BadRequest->value);
        }

        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom, $database));
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server, TrailSubject::Database]));
        $body->appendChild(WebsiteComponents::buildDatabaseTabs($dom, $serverId, $database, 'languages'));

        $serverSession = ServerSession::fromServerId($serverId, Config::getServers());
        $languages = PhpPgAdminConnection::createFromServerSession($serverSession, $database)?->getLanguages() ?? [];

        if (empty($languages)) {
            $p = $dom->createElement('p');
            $p->appendChild($dom->createTextNode(_('No languages found.')));
            $body->appendChild($p);
        } else {
            $body->appendChild(self::buildLanguagesTable($dom, $languages));
        }

        return $body;
    }

    /**
     * @param array<\PhpPgAdmin\DDD\ValueObjects\Language> $languages
     */
    private static function buildLanguagesTable(\DOMDocument $dom, array $languages): \DOMElement
    {
        $table = $dom->createElement('table');

        $tHead = $dom->createElement('thead');
        $tHeadRow = $dom->createElement('tr');

        foreach ([_('Name'), _('Trusted'), _('Function')] as $title) {
            $th = $dom->createElement('th');
            $th->setAttribute('class', 'data');
            $th->appendChild($dom->createTextNode($title));
            $tHeadRow->appendChild($th);
        }

        $tHead->appendChild($tHeadRow);
        $table->appendChild($tHead);

        $tBody = $dom->createElement('tbody');

        foreach ($languages as $index => $language) {
            $tr = $dom->createElement('tr');
            $tr->setAttribute('class', $index % 2 === 0 ? 'data1' : 'data2');

            $cells = [
                $language->Name,
                $language->IsTrusted ? _('Yes') : _('No'),
                $language->CallHandler,
            ];

            foreach ($cells as $cell) {
                $td = $dom->createElement('td');
                $td->appendChild($dom->createTextNode($cell));
                $tr->appendChild($td);
            }

            $tBody->appendChild($tr);
        }

        $table->appendChild($tBody);

        return $table;
    }
}
