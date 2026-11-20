<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpPgAdmin\Database\PhpPgAdminConnection;

/**
 * The objects named "0", which PHP's empty() takes for an empty string, although PostgreSQL takes it as a name. The
 * name cannot have a suffix per thread like the other names of the tests, so the threads of Infection take turns with
 * it.
 */
final class NameZero
{
    public const NAME = '0';

    /**
     * The key of the advisory lock that holds the turn, which no other lock of the tests uses.
     */
    private const LOCK_KEY = "hashtext('ppa_test_name_zero')";

    /**
     * @var list<string> The kinds of the objects that it created, which release() drops again.
     */
    private array $createdKinds = [];

    /**
     * @param PhpPgAdminConnection $connection The connection that holds the turn of this thread.
     */
    private function __construct(private readonly PhpPgAdminConnection $connection)
    {
    }

    /**
     * Waits for the turn of this thread, as the user of the tests, who must be logged in to the running server.
     */
    public static function take(): self
    {
        $nameZero = new self(RunningServer::connection());
        $nameZero->connection->exec('SELECT pg_advisory_lock(' . self::LOCK_KEY . ')');

        return $nameZero;
    }

    public function createDatabase(): void
    {
        // template1 cannot be copied while another test is connected to it, template0 takes no connections.
        $this->create('DATABASE', 'TEMPLATE template0');
    }

    /**
     * Drops the objects that it created and ends the turn of this thread.
     */
    public function release(): void
    {
        foreach ($this->createdKinds as $kind) {
            $this->connection->exec("DROP {$kind} IF EXISTS \"" . self::NAME . '"');
        }

        $this->connection->exec('SELECT pg_advisory_unlock(' . self::LOCK_KEY . ')');
    }

    /**
     * Unlike the other objects of the tests, an object named "0" that exists already is not dropped first, as it may
     * not be one of the tests. The create fails then.
     */
    private function create(string $kind, string $options = ''): void
    {
        $this->connection->exec("CREATE {$kind} \"" . self::NAME . "\" {$options}");
        $this->createdKinds[] = $kind;
    }
}
