<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Location implements \Stringable
{
    private string $location;

    public function __construct(string $location)
    {
        $location = trim($location);

        if (empty($location)) {
            throw new \InvalidArgumentException('Tablespace location cannot be empty!');
        }

        $this->location = $location;
    }

    public function __toString(): string
    {
        return $this->location;
    }
}
