<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Owner implements \Stringable
{
    private string $owner;

    public function __construct(string $owner)
    {
        $this->owner = trim($owner);
    }

    public function __toString(): string
    {
        return $this->owner;
    }
}
