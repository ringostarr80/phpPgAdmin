<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

enum SslMode: string
{
    case DISABLED = 'disable';
    case ALLOW = 'allow';
    case PREFER = 'prefer';
    case REQUIRE = 'require';
    case UNSPECIFIED = 'unspecified';
}
