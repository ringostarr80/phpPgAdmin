<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Comment implements \Stringable
{
    /**
     * Not trimmed: PostgreSQL stores a comment as it is given, so the alter form would otherwise change a comment that
     * was not edited.
     */
    public function __construct(private string $comment)
    {
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
