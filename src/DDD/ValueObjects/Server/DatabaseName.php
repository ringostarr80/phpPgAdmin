<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

final class DatabaseName implements \Stringable
{
    private string $name = '';

    public function __construct(string $name)
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Database name cannot be empty!');
        }

        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
