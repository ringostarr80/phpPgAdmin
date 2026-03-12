<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

use PhpPgAdmin\DDD\ValueObjects\Tablespace\{Comment, Location, Name, Owner};

/**
 * @property-read Comment $Comment
 * @property-read Location $Location
 * @property-read Name $Name
 * @property-read Owner $Owner
 */
final readonly class Tablespace
{
    public const FORM_ID_COMMENT = 'formComment';
    public const FORM_ID_LOCATION = 'formLoc';
    public const FORM_ID_NAME = 'formSpcname';
    public const FORM_ID_OWNER = 'formOwner';

    public function __construct(
        private Name $name,
        private Owner $owner,
        private Location $location,
        private Comment $comment = new Comment(''),
    ) {
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'Comment' => $this->comment,
            'Location' => $this->location,
            'Name' => $this->name,
            'Owner' => $this->owner,
            default => throw new \InvalidArgumentException("Property '$name' does not exist."),
        };
    }
}
