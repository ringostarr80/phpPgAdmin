<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

/**
 * @property-read int $Value
 */
final class DbSize implements \Stringable
{
    public function __construct(private readonly int $size)
    {
        if ($size < 0) {
            throw new \InvalidArgumentException('Database size cannot be negative.');
        }
    }

    public function prettyFormat(): string
    {
        $multiplier = 1;
        $limit = 10 * 1_024;

        $unitStrings = [
            _('bytes'),
            _('kB'),
            _('MB'),
            _('GB'),
            _('TB'),
        ];

        foreach ($unitStrings as $unitString) {
            if ($this->size < $limit * $multiplier) {
                return sprintf('%d %s', floor(($this->size + $multiplier / 2) / $multiplier), $unitString);
            }

            $multiplier *= 1_024;
        }

        $multiplier /= 1_024;

        return sprintf('%d %s', floor(($this->size + $multiplier / 2) / $multiplier), $unitString);
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'Value' => $this->size,
            default => throw new \InvalidArgumentException("Property '$name' does not exist."),
        };
    }

    public function __toString(): string
    {
        return (string)$this->size;
    }
}
