<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PhpPgAdmin\Database\ScramSha256;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The expected secrets come from libpq's PQencryptPasswordConn(), with the salt that libpq chose for them.
 */
#[CoversClass(ScramSha256::class)]
final class ScramSha256Test extends TestCase
{
    public function testTheSecretIsTheOneLibpqBuildsWithTheSameSalt(): void
    {
        self::assertSame(
            'SCRAM-SHA-256$4096:YRvNpM8YGeV67NX6UgylkQ=='
            . '$u588g/fz3YHHREPu3sAA2PHu9kzYcB5Ssn+UDjWvb20=:UNrZ/a5qe2e5CyQEXA+HO+cnxkKDG/6BS7TRwW9VrEI=',
            ScramSha256::secret('secret', 4_096, self::salt('YRvNpM8YGeV67NX6UgylkQ==')),
        );
    }

    public function testTheSecretIsTheOneLibpqBuildsWithTheIterationsThatTheServerSets(): void
    {
        // Since PostgreSQL 16, the server sets them in scram_iterations.
        self::assertSame(
            'SCRAM-SHA-256$5000:eNwf/D2NTSl6lrLRrQ/MIw=='
            . '$gGkYVxcynnaEvp2hqMcqOPIonXIoiXMZYFV+p19GinM=:cY/FbJNCyG9W0uUYIeciIckZQERte7KCgKpHVEaTAlI=',
            ScramSha256::secret('secret', 5_000, self::salt('eNwf/D2NTSl6lrLRrQ/MIw==')),
        );
    }

    public function testTheSecretIsTheOneLibpqBuildsWithASingleIteration(): void
    {
        // The fewest iterations that the server takes in scram_iterations.
        self::assertSame(
            'SCRAM-SHA-256$1:r7B9n7kZ18J9C5j6E3oLdw=='
            . '$Yz9vrVHRj11Hz5qpfp9fsGIUZhQOUcuW8CjiCFH14PU=:9wid+kLz+J/L8JOiF9YkyJxhQqyfiPY0CMO4XD/9C4c=',
            ScramSha256::secret('secret', 1, self::salt('r7B9n7kZ18J9C5j6E3oLdw==')),
        );
    }

    public function testASecretNeedsAtLeastOneIteration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScramSha256::secret('secret', 0);
    }

    public function testEachSecretGetsItsOwnSaltOf16BytesLikeInLibpq(): void
    {
        $salts = array_map(
            static fn (string $secret): string => self::salt(explode('$', explode(':', $secret)[1])[0]),
            [ScramSha256::secret('secret', 4_096), ScramSha256::secret('secret', 4_096)],
        );

        self::assertSame([16, 16], array_map(strlen(...), $salts));
        self::assertNotSame($salts[0], $salts[1]);
    }

    private static function salt(string $base64): string
    {
        $salt = base64_decode($base64, true);
        self::assertIsString($salt);

        return $salt;
    }
}
