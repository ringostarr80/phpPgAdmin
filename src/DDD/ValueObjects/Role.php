<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

use PhpPgAdmin\RequestParameter;

/**
 * @property-read bool $CanCreateDb
 * @property-read bool $CanCreateRole
 * @property-read bool $CanInheritRights
 * @property-read bool $CanLogin
 * @property-read int $ConnectionLimit
 * @property-read ?\DateTimeInterface $Expires
 * @property-read bool $IsSuperuser
 * @property-read string $Name
 */
final class Role
{
    public const FORM_ID_NAME = 'name';
    public const FORM_ID_PASSWORD = 'password';
    public const FORM_ID_PASSWORD_CONFIRMATION = 'password_confirmation';

    public function __construct(
        private string $name,
        private bool $isSuperuser = false,
        private bool $canCreateDb = false,
        private bool $canCreateRole = false,
        private bool $canInheritRights = true,
        private bool $canLogin = false,
        private int $connectionLimit = -1,
        private ?\DateTimeInterface $expires = null,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromDbArray(array $data): self
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

        return new self(
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

    public static function fromForm(): self
    {
        $rolename = RequestParameter::getString(self::FORM_ID_NAME) ?? '';
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

        return new self(
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

    public function __get(string $name): mixed
    {
        return match ($name) {
            'CanCreateDb' => $this->canCreateDb,
            'CanCreateRole' => $this->canCreateRole,
            'CanInheritRights' => $this->canInheritRights,
            'CanLogin' => $this->canLogin,
            'ConnectionLimit' => $this->connectionLimit,
            'Expires' => $this->expires,
            'IsSuperuser' => $this->isSuperuser,
            'Name' => $this->name,
            default => throw new \InvalidArgumentException("Property '$name' does not exist."),
        };
    }
}
