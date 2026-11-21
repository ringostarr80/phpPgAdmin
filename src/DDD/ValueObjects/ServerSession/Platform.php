<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\ServerSession;

final readonly class Platform implements \Stringable
{
    private string $name;

    public function __construct(string $name)
    {
        $name = trim($name);

        // Not empty(), which takes "0" for an empty string.
        if ($name === '') {
            throw new \InvalidArgumentException('ServerSession platform cannot be empty!');
        }

        $this->name = $name;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }
}
