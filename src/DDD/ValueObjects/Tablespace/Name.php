<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Name implements \Stringable
{
    private string $name;

    public function __construct(string $name)
    {
        $name = trim($name);

        // Not empty(), which takes "0" for an empty string, although PostgreSQL takes it as a name.
        if ($name === '') {
            throw new \InvalidArgumentException('Tablespace name cannot be empty!');
        }

        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
