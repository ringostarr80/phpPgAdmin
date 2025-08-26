<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\ServerSession;

final class Password implements \Stringable
{
    private string $password = '';

    public function __construct(string $password)
    {
        $password = trim($password);
        if (empty($password)) {
            throw new \InvalidArgumentException('ServerSession password cannot be empty!');
        }

        $this->password = $password;
    }

    public function __toString(): string
    {
        return $this->password;
    }
}
