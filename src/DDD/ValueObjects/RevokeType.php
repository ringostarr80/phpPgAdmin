<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

enum RevokeType: string
{
    case Cascade = 'CASCADE';
    case Restrict = 'RESTRICT';
}
