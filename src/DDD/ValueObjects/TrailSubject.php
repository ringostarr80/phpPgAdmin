<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

enum TrailSubject: string
{
    case Role = 'role';
    case Server = 'server';
}
