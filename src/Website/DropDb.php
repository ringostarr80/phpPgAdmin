<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

final class DropDb extends Website
{
    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            self::redirectTo($this->handlePostRequest());
        }
    }

    #[\Override]
    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $body->appendChild(WebsiteComponents::buildTopBar($dom));
        $body->appendChild(WebsiteComponents::buildTrail($dom, [TrailSubject::Server]));

        $serverId = RequestParameter::getString('server') ?? '';

        $h2 = $dom->createElement('h2', _('Drop'));
        $aHelp = WebsiteComponents::buildHelpLink(
            dom: $dom,
            url: 'help.php',
            urlParams: [
                'help' => 'pg.database.drop',
                'server' => $serverId,
            ],
        );
        $h2->appendChild($aHelp);
        $body->appendChild($h2);

        $form = $dom->createElement('form');
        $form->setAttribute('method', 'post');

        foreach (self::databases() as $database) {
            $form->appendChild($dom->createElement(
                'p',
                sprintf(_('Are you sure you want to drop the database "%s"?'), $database),
            ));
            $inputHiddenDatabase = $dom->createElement('input');
            $inputHiddenDatabase->setAttribute('type', 'hidden');
            $inputHiddenDatabase->setAttribute('name', 'database[]');
            $inputHiddenDatabase->setAttribute('value', $database);
            $form->appendChild($inputHiddenDatabase);
        }

        $inputHiddenServer = $dom->createElement('input');
        $inputHiddenServer->setAttribute('type', 'hidden');
        $inputHiddenServer->setAttribute('name', 'server');
        $inputHiddenServer->setAttribute('value', $serverId);
        $inputSubmit = $dom->createElement('input');
        $inputSubmit->setAttribute('type', 'submit');
        $inputSubmit->setAttribute('name', 'action');
        $inputSubmit->setAttribute('value', _('Drop'));
        $aButtonCancel = $dom->createElement('a', _('Cancel'));
        $aButtonCancel->setAttribute('class', 'button');
        $cancelUrl = 'all_db.php';
        $cancelUrlParams = [
            'server' => $serverId,
            'subject' => 'server',
        ];
        $aButtonCancel->setAttribute('href', $cancelUrl . '?' . http_build_query($cancelUrlParams));

        $form->appendChild($inputHiddenServer);
        $form->appendChild($inputSubmit);
        $form->appendChild($dom->createEntityReference('nbsp'));
        $form->appendChild($aButtonCancel);

        $body->appendChild($form);

        return $body;
    }

    /**
     * Leaves leading to the next page to the constructor, whose die would end the tests.
     *
     * @return ?string The page to lead to, or null to show this page again.
     */
    private function handlePostRequest(): ?string
    {
        $databases = self::databases();
        $serverId = RequestParameter::getString('server') ?? '';
        $serverSession = Logins::serverSession($serverId, Config::getServers());

        $redirectUrl = 'all_db.php';
        $redirectUrlParams = [
            'server' => $serverId,
            'subject' => 'server',
        ];

        $db = PhpPgAdminConnection::createFromServerSession($serverSession);

        if (is_null($db) || $databases === []) {
            return null;
        }

        $messages = [];
        // Like the legacy list did for the checked databases.
        $namesEach = count($databases) > 1;

        foreach ($databases as $database) {
            $prefix = $namesEach
                ? $database . ': '
                : '';

            try {
                $db->dropDatabase($database);
                $messages[] = $prefix . _('Database dropped.');
            } catch (\Throwable $e) {
                $messages[] = $prefix . _('Database drop failed.') . ' - ' . $e->getMessage();

                // Like the legacy list, which dropped none after the first that failed.
                break;
            }
        }

        $redirectUrlParams['message'] = implode("\n", $messages);

        return $redirectUrl . '?' . http_build_query($redirectUrlParams);
    }

    /**
     * One from the link of a database in the list, or several from the actions on multiple lines of the list.
     *
     * @return list<string>
     */
    private static function databases(): array
    {
        $databases = RequestParameter::getArray('database') ?? [RequestParameter::getString('database') ?? ''];

        return array_values(
            array_filter(
                $databases,
                static fn (mixed $database): bool => is_string($database) && $database !== '',
            ),
        );
    }
}
