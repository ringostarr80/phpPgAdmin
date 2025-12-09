<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

final readonly class Filename implements \Stringable
{
    public function __construct(private string $filename)
    {
        if (!file_exists($filename)) {
            error_log("Filename not found: '{$filename}'.");
        }
    }

    public function __toString(): string
    {
        return $this->filename;
    }
}
