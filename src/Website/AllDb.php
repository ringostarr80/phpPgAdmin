<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use PhpPgAdmin\{Config, TrailSubject, Website, WebsiteComponents};
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\Logins;

final class AllDb extends Website
{
    public function __construct()
    {
        $this->scripts['multiactionform'] = ['src' => 'multiactionform.js'];

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
        $body->appendChild(WebsiteComponents::buildServerDatabasesTabs($dom, $serverId, 'databases'));

        $message = RequestParameter::getString('message') ?? '';

        if (!empty($message)) {
            $body->appendChild(WebsiteComponents::buildMessage($dom, $message));
        }

        $serverSession = Logins::serverSession($serverId, Config::getServers());
        $form = $dom->createElement('form');
        $form->setAttribute('id', 'multi_form');
        $form->setAttribute('action', 'all_db.php');
        $form->setAttribute('method', 'post');
        $form->setAttribute('enctype', 'multipart/form-data');
        $form->appendChild(WebsiteComponents::buildDatabasesTable($dom, $serverSession));
        $form->appendChild($dom->createElement('br'));
        $form->appendChild(WebsiteComponents::buildMultipleActionsTableForDatabases($dom, $serverSession));
        $body->appendChild($form);

        $navLinks = [
            [
                'label' => _('Create database'),
                'url' => 'create_db.php',
                'url-params' => [
                    'server' => RequestParameter::getString('server') ?? '',
                ],
            ],
        ];
        $body->appendChild(WebsiteComponents::buildNavLinks($dom, $navLinks));

        return $body;
    }

    #[\Override]
    protected function title(): string
    {
        return _('Databases');
    }

    /**
     * Carries out the actions on multiple lines. Leaves leading to the next page to the constructor, whose die would
     * end the tests.
     *
     * @return ?string The page to lead to, or null to show this page again.
     */
    private function handlePostRequest(): ?string
    {
        // Delete is the only action, "--" is none.
        if (RequestParameter::getString('action') !== 'confirm_drop') {
            return null;
        }

        $serverId = RequestParameter::getString('server') ?? '';
        // The checked databases.
        $databases = array_values(
            array_filter(
                RequestParameter::getArray('ma') ?? [],
                static fn (mixed $database): bool => is_string($database) && $database !== '',
            ),
        );

        if ($databases === []) {
            return 'all_db.php?' . http_build_query([
                'message' => _('You must specify at least one database to drop.'),
                'server' => $serverId,
                'subject' => 'server',
            ]);
        }

        // drop_db.php asks whether to drop them.
        return 'drop_db.php?' . http_build_query(['database' => $databases, 'server' => $serverId]);
    }
}
