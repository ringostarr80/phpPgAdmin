<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

final readonly class DatabaseName implements \Stringable
{
    private string $name;

    public function __construct(string $name)
    {
        $name = trim($name);

        // Not empty(), which takes "0" for an empty string, although PostgreSQL takes it as a database name.
        if ($name === '') {
            throw new \InvalidArgumentException('Database name cannot be empty!');
        }

        $this->name = $name;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }
}
