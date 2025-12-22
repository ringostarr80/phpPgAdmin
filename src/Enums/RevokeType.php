<?php

declare(strict_types=1);

namespace PhpPgAdmin\Enums;

enum RevokeType: string
{
    case Cascade = 'CASCADE';
    case Restrict = 'RESTRICT';
}
