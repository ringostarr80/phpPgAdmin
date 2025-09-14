<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use Locr\Lib\HTTP\StatusCode;
use PhpPgAdmin\{Config, RequestParameter, Website};
use PhpPgAdmin\DDD\Entities\ServerSession;

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

    private function redirectToRoot(): void
    {
        $locationUrl = './intro.php';
        header('Location: ' . $locationUrl);
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

        $locationUrl = './login.php';
        $locationUrlParams = [
            'server' => $server,
            'subject' => 'server',
        ];

        if (ServerSession::isLoggedIn($server)) {
            $locationUrl = './all_db.php';
            unset($locationUrlParams['subject']);
        }

        $locationUrl .= '?' . http_build_query($locationUrlParams);

        header('Location: ' . $locationUrl);
        exit;
    }
}
