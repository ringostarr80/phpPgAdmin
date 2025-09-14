<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

final class Host implements \Stringable
{
    private string $host = '';

    public function __construct(string $host = '127.0.0.1')
    {
        $host = trim($host);

        if (empty($host)) {
            throw new \InvalidArgumentException('Host cannot be empty!');
        }

        $this->host = $host;
    }

    public function __toString(): string
    {
        return $this->host;
    }
}
