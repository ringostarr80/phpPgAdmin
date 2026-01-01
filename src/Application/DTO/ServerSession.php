<?php

declare(strict_types=1);

namespace PhpPgAdmin\Application\DTO;

use PhpPgAdmin\Config;
use PhpPgAdmin\DDD\Entities\ServerSession as EntityServerSession;

abstract readonly class ServerSession
{
    public static function createFromRequestParameter(): ?EntityServerSession
    {
        if (!isset($_REQUEST['server']) || !is_string($_REQUEST['server'])) {
            return null;
        }

        return EntityServerSession::fromServerId($_REQUEST['server'], Config::getServers());
    }
}
