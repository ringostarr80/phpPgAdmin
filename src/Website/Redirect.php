<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use Locr\Lib\HTTP\StatusCode;
use PhpPgAdmin\{Config, Website};
use PhpPgAdmin\Infrastructure\Http\RequestParameter;
use PhpPgAdmin\Infrastructure\Session\{LastTabs, Logins};

final class Redirect extends Website
{
    public function tryRedirect(): void
    {
        $subject = RequestParameter::getString('subject');

        if (is_null($subject)) {
            throw new \InvalidArgumentException('Missing required parameter: subject', StatusCode::BadRequest->value);
        }

        match ($subject) {
            'root' => $this->redirectToRoot(),
            'server' => $this->redirectToServer(),
            default => trigger_error(
                'Redirecting subject ("' . $subject . '") not found the new way. Continue the old way!',
                E_USER_DEPRECATED,
            )
        };
    }

    /**
     * The tab of the root that was used last, or else its first one, like the legacy Misc::getLastTabURL().
     */
    public static function buildRootUrl(): string
    {
        return match (LastTabs::of('root')) {
            'servers' => './servers.php',
            default => './intro.php',
        };
    }

    /**
     * The tab of a server that was used last, or else its first one, like the legacy Misc::getLastTabURL(). The
     * servers share it.
     */
    public static function buildServerUrl(string $serverId): string
    {
        $page = match (LastTabs::of('server')) {
            'export' => 'all_db_export.php',
            'roles' => 'roles.php',
            'tablespaces' => 'tablespaces.php',
            default => 'all_db.php',
        };

        return "./{$page}?" . http_build_query(['server' => $serverId]);
    }

    /**
     * Leads to the login form itself while one is not logged in to the server (see redirectToServer()).
     */
    #[\Override]
    public static function requiresServerLogin(): bool
    {
        return false;
    }

    private function redirectToRoot(): void
    {
        header('Location: ' . self::buildRootUrl());
        exit;
    }

    private function redirectToServer(): void
    {
        $server = RequestParameter::getString('server');

        if (is_null($server)) {
            throw new \InvalidArgumentException('Missing required parameter: server', StatusCode::BadRequest->value);
        }

        if (!Config::serverExists($server)) {
            throw new \InvalidArgumentException(
                _('Attempt to connect with invalid server parameter, possibly someone is trying to hack your system.'),
                StatusCode::BadRequest->value,
            );
        }

        $locationUrl = Logins::isLoggedIn($server)
            ? self::buildServerUrl($server)
            : Login::buildUrl($server);

        header('Location: ' . $locationUrl);
        exit;
    }
}
