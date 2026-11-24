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

    public function testTheSecretIsTheOneLibpqBuildsForAPasswordThatSaslPrepNormalizes(): void
    {
        // A u with a combining diaeresis, which SASLprep composes to an ü.
        self::assertSame(
            'SCRAM-SHA-256$4096:lCTIgZl23MXntLd8gr6bLQ=='
            . '$eoKoPbgnjDhMuwWfTBEK+LzZvMstFI8dZvpUE8ivMq8=:KPKNmcY9k64vyATOC1zQOvS6xzHn5UPdVb212GM6EeA=',
            ScramSha256::secret("gru\u{308}n", 4_096, self::salt('lCTIgZl23MXntLd8gr6bLQ==')),
        );
    }

    public function testTheSecretIsTheOneLibpqBuildsForAPasswordThatSaslPrepRefuses(): void
    {
        // SASLprep prohibits the private use character, so libpq takes the password as it is, with its ligature.
        self::assertSame(
            'SCRAM-SHA-256$4096:ZkviUMk1ISAVksmi/N5cCQ=='
            . '$KxTMMXuUqAUjl4jtUFqSWBAcBvdu7RCM6Zjj1iT238o=:D992cDTaUuKm2tj7Taz0kaJRD1QPQ5EKrLqqUsRK5LM=',
            ScramSha256::secret("\u{E000}\u{FB01}", 4_096, self::salt('ZkviUMk1ISAVksmi/N5cCQ==')),
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
