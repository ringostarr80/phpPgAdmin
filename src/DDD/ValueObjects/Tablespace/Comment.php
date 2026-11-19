<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Comment implements \Stringable
{
    public function __construct(private string $comment)
    {
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
