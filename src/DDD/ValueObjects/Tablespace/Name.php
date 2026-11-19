<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Name implements \Stringable
{
    public function __construct(private string $name)
    {
        // Only checked, not trimmed: PostgreSQL keeps the blanks around a quoted name, so they are part of it. Not
        // empty(), which takes "0" for an empty string, although PostgreSQL takes it as a name.
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Tablespace name cannot be empty!');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
