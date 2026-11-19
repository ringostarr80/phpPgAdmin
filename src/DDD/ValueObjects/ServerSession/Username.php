<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\ServerSession;

final readonly class Username implements \Stringable
{
    /**
     * Not trimmed: PostgreSQL keeps the blanks around a quoted role name, so they are part of it.
     */
    public function __construct(private string $name)
    {
        // Not empty(), which takes "0" for an empty string, although PostgreSQL takes it as a role name.
        if ($name === '') {
            throw new \InvalidArgumentException('ServerSession username cannot be empty!');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
