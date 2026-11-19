<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Owner implements \Stringable
{
    /**
     * Not trimmed: PostgreSQL keeps the blanks around a quoted role name, so they are part of it.
     */
    public function __construct(private string $owner)
    {
    }

    public function __toString(): string
    {
        return $this->owner;
    }
}
