<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\ServerSession;

/**
 * The password is kept as entered: it may be empty (e.g. trust authentication) and surrounding whitespace is part of
 * it. Whether an empty password may log in is decided by ServerSession::loginIsAllowed().
 */
final readonly class Password implements \Stringable
{
    public function __construct(private string $password)
    {
    }

    public function __toString(): string
    {
        return $this->password;
    }
}
