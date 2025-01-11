<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use Locr\Lib\HTTP\StatusCode;
use PhpPgAdmin\{Config, RequestParameter, Website};

class Redirect extends Website
{
    public function __construct()
    {
        parent::__construct();

        $subject = RequestParameter::getString('subject');
        if (is_null($subject)) {
            throw new \InvalidArgumentException(
                'Missing required parameter: subject',
                StatusCode::BadRequest->value
            );
        }

        match ($subject) {
            'server' => $this->redirectToServer(),
            default => throw new \InvalidArgumentException(
                'Invalid or unhandled parameter: subject=' . $subject,
                StatusCode::BadRequest->value
            )
        };
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
                StatusCode::BadRequest->value
            );
        }

        header('Location: ./login.php?server=' . urlencode($server));
        exit;
    }
}
