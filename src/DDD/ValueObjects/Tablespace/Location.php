<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Location implements \Stringable
{
    private string $location;

    /**
     * An empty location stands for the data directory, where PostgreSQL keeps pg_default and pg_global.
     */
    public function __construct(string $location)
    {
        $this->location = trim($location);
    }

    public function __toString(): string
    {
        return $this->location;
    }
}
