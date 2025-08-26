<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects\Server;

/**
 * @property-read int $Value
 */
final class Port
{
    public function __construct(private int $port = 5432)
    {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Port must be between 1 and 65535');
        }
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'Value' => $this->port,
            default => throw new \InvalidArgumentException('Invalid property: ' . $name),
        };
    }
}
