<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Tablespace;

final readonly class Comment implements \Stringable
{
    private string $comment;

    public function __construct(string $comment)
    {
        $this->comment = trim($comment);
    }

    public function __toString(): string
    {
        return $this->comment;
    }
}
