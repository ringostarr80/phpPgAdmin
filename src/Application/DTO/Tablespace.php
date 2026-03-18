<?php

declare(strict_types=1);

namespace PhpPgAdmin\Application\DTO;

use PhpPgAdmin\DDD\ValueObjects\Tablespace as ValueObjectTablespace;
use PhpPgAdmin\DDD\ValueObjects\Tablespace\{Comment, Location, Name, Owner};
use PhpPgAdmin\Infrastructure\Http\RequestParameter;

abstract readonly class Tablespace
{
    /**
     * @param array<mixed> $data
     */
    public static function createFromDbArray(array $data): ValueObjectTablespace
    {
        $requiredFields = [
            'spcname',
            'spcowner',
            'spclocation',
            'spccomment',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing field '$field' in data for Tablespace value object.");
            }
        }

        if (!is_string($data['spcname'])) {
            throw new \InvalidArgumentException("Type of field 'spcname' is not string.");
        }

        if (!is_string($data['spcowner'])) {
            throw new \InvalidArgumentException("Type of field 'spcowner' is not string.");
        }

        if (!is_string($data['spclocation'])) {
            throw new \InvalidArgumentException("Type of field 'spclocation' is not string.");
        }

        if (!is_string($data['spccomment'])) {
            throw new \InvalidArgumentException("Type of field 'spccomment' is not string.");
        }

        return new ValueObjectTablespace(
            name: new Name($data['spcname']),
            owner: new Owner($data['spcowner']),
            location: new Location($data['spclocation']),
            comment: new Comment($data['spccomment']),
        );
    }

    public static function createFromFormRequest(): ValueObjectTablespace
    {
        $spacename = RequestParameter::getString(ValueObjectTablespace::FORM_ID_NAME) ?? '';
        $owner = RequestParameter::getString(ValueObjectTablespace::FORM_ID_OWNER) ?? '';
        $location = RequestParameter::getString(ValueObjectTablespace::FORM_ID_LOCATION) ?? '';
        $comment = RequestParameter::getString(ValueObjectTablespace::FORM_ID_COMMENT) ?? '';

        return new ValueObjectTablespace(
            name: new Name($spacename),
            owner: new Owner($owner),
            location: new Location($location),
            comment: new Comment($comment),
        );
    }
}
