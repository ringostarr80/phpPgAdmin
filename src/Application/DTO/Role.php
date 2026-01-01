<?php

declare(strict_types=1);

namespace PhpPgAdmin\Application\DTO;

use PhpPgAdmin\DDD\ValueObjects\Role as ValueObjectRole;
use PhpPgAdmin\Infrastructure\Http\RequestParameter;

abstract readonly class Role
{
    /**
     * @param array<mixed> $data
     */
    public static function createFromDbArray(array $data): ValueObjectRole
    {
        $requiredFields = [
            'rolname',
            'rolsuper',
            'rolcreatedb',
            'rolcreaterole',
            'rolinherit',
            'rolcanlogin',
            'rolconnlimit',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing field '$field' in data for Role value object.");
            }
        }

        if (!is_string($data['rolname'])) {
            throw new \InvalidArgumentException("Type of field 'rolname' is not string.");
        }

        if (!is_bool($data['rolsuper'])) {
            throw new \InvalidArgumentException("Type of field 'rolsuper' is not bool.");
        }

        if (!is_bool($data['rolcreatedb'])) {
            throw new \InvalidArgumentException("Type of field 'rolcreatedb' is not bool.");
        }

        if (!is_bool($data['rolcreaterole'])) {
            throw new \InvalidArgumentException("Type of field 'rolcreaterole' is not bool.");
        }

        if (!is_bool($data['rolinherit'])) {
            throw new \InvalidArgumentException("Type of field 'rolinherit' is not bool.");
        }

        if (!is_bool($data['rolcanlogin'])) {
            throw new \InvalidArgumentException("Type of field 'rolcanlogin' is not bool.");
        }

        if (!is_int($data['rolconnlimit'])) {
            throw new \InvalidArgumentException("Type of field 'rolconnlimit' is not int.");
        }

        $expires = null;

        if (
            isset($data['rolvaliduntil']) &&
            is_string($data['rolvaliduntil']) &&
            $data['rolvaliduntil'] !== 'infinity'
        ) {
            $expires = new \DateTimeImmutable($data['rolvaliduntil']);
        }

        return new ValueObjectRole(
            name: $data['rolname'],
            isSuperuser: $data['rolsuper'],
            canCreateDb: $data['rolcreatedb'],
            canCreateRole: $data['rolcreaterole'],
            canInheritRights: $data['rolinherit'],
            canLogin: $data['rolcanlogin'],
            connectionLimit: $data['rolconnlimit'],
            expires: $expires,
        );
    }

    public static function createFromFormRequest(): ValueObjectRole
    {
        $rolename = RequestParameter::getString(ValueObjectRole::FORM_ID_NAME) ?? '';
        $super = RequestParameter::getString('formSuper') ?? '';
        $createDb = RequestParameter::getString('formCreateDB') ?? '';
        $createRole = RequestParameter::getString('formCreateRole') ?? '';
        $inherits = RequestParameter::getString('formInherits') ?? '';
        $canLogin = RequestParameter::getString('formCanLogin') ?? '';
        $connLimit = RequestParameter::getString('formConnLimit') ?? '';
        $expires = RequestParameter::getString('formExpires') ?? '';

        $expiry = null;

        if (!empty($expires)) {
            $expiry = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expires) ?: null;
        }

        return new ValueObjectRole(
            name: $rolename,
            isSuperuser: $super === 'on',
            canCreateDb: $createDb === 'on',
            canCreateRole: $createRole === 'on',
            canInheritRights: $inherits === 'on',
            canLogin: $canLogin === 'on',
            connectionLimit: is_numeric($connLimit) ? intval($connLimit) : -1,
            expires: $expiry,
        );
    }
}
