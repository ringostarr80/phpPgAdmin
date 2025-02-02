<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

class Filename implements \Stringable
{
    public function __construct(private string $filename)
    {
        if (!is_file($filename)) {
            throw new \InvalidArgumentException('Filename not found: "' . $filename . '".');
        }
    }

    public function __toString(): string
    {
        return $this->filename;
    }
}
