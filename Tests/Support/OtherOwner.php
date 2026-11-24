<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpPgAdmin\Database\PhpPgAdminConnection;

/**
 * A role besides the user of the tests, so the owner entered in a tablespace form differs from the one the form selects
 * by itself. Each thread of the tests has its own.
 */
final readonly class OtherOwner
{
    /**
     * @param PhpPgAdminConnection $administrator The connection that created it, which drops it again.
     */
    private function __construct(private PhpPgAdminConnection $administrator)
    {
    }

    /**
     * Creates it as the user of the tests, who must be logged in to the running server.
     */
    public static function create(): self
    {
        $otherOwner = new self(RunningServer::connection());

        // Removes what an aborted run of this thread left behind.
        $otherOwner->drop();

        // Any role can own a tablespace, also one that cannot log in, like a group role.
        $otherOwner->administrator->exec('CREATE ROLE "' . self::name() . '" NOLOGIN');

        return $otherOwner;
    }

    public function drop(): void
    {
        $this->administrator->exec('DROP ROLE IF EXISTS "' . self::name() . '"');
    }

    public static function name(): string
    {
        return TestEnvironment::perThread('ppa_test_tablespace_owner');
    }
}
