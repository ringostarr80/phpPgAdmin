<?php

declare(strict_types=1);

namespace PhpPgAdmin\DDD\ValueObjects;

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
final readonly class Role
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
