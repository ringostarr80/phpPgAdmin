<?php

declare(strict_types=1);

namespace PhpPgAdmin\Database;

/**
 * The secret that PostgreSQL stores for a password with SCRAM-SHA-256, built on the client like libpq's
 * PQencryptPasswordConn() does, so the password does not reach the server in plain text.
 */
final class ScramSha256
{
    /**
     * The length of the salt that libpq gives a secret.
     */
    private const SALT_LENGTH = 16;

    /**
     * @param string $salt Only for a secret that is known beforehand, otherwise each one gets a random salt.
     */
    public static function secret(string $password, int $iterations, ?string $salt = null): string
    {
        if ($iterations < 1) {
            throw new \InvalidArgumentException('A SCRAM secret needs at least one iteration.');
        }

        $salt ??= random_bytes(self::SALT_LENGTH);
        // Like libpq, takes the password as it is when SASLprep refuses it.
        $preparedPassword = SaslPrep::prepare($password) ?? $password;

        // RFC 5802, section 3.
        $saltedPassword = hash_pbkdf2('sha256', $preparedPassword, $salt, $iterations, 0, true);
        $storedKey = hash('sha256', hash_hmac('sha256', 'Client Key', $saltedPassword, true), true);
        $serverKey = hash_hmac('sha256', 'Server Key', $saltedPassword, true);

        return 'SCRAM-SHA-256$' . $iterations . ':' . base64_encode($salt)
            . '$' . base64_encode($storedKey) . ':' . base64_encode($serverKey);
    }
}
