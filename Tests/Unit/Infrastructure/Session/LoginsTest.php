<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Session;

use PhpPgAdmin\DDD\Entities\Server;
use PhpPgAdmin\DDD\ValueObjects\Server\{Host, Name};
use PhpPgAdmin\Infrastructure\Session\Logins;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Logins::class)]
final class LoginsTest extends TestCase
{
    private Server $server;

    public function testServerSessionAcceptsAnEmptyPassword(): void
    {
        $this->logIn('alice', '');

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame('alice', (string)$serverSession->Username);
        self::assertSame('', (string)$serverSession->Password);
    }

    public function testServerSessionKeepsTheUsernameAsEntered(): void
    {
        // The login checks the name with its blanks, so the pages connect with it.
        $this->logIn(' alice ', 'secret');

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame(' alice ', (string)$serverSession->Username);
    }

    public function testServerSessionKeepsThePasswordAsEntered(): void
    {
        $this->logIn('alice', ' secret ');

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame(' secret ', (string)$serverSession->Password);
    }

    public function testServerSessionTakesThePlatformOfTheLogin(): void
    {
        $this->logIn('alice', 'secret', 'PostgreSQL 18');

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame('PostgreSQL 18', (string)$serverSession->Platform);
    }

    public function testServerSessionWithoutPlatformHasAnUnknownPlatform(): void
    {
        $this->logIn('alice', 'secret');

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame('Unknown', (string)$serverSession->Platform);
    }

    /**
     * Only the login of the server counts, like in isLoggedIn(). The legacy pages kept credentials for all servers in
     * sharedUsername and sharedPassword, which no login sets any more.
     */
    public function testServerSessionIgnoresCredentialsForAllServers(): void
    {
        $this->logIn('alice', 'secret');
        $_SESSION['sharedUsername'] = 'bob';
        $_SESSION['sharedPassword'] = '';

        $serverSession = Logins::serverSession($this->server->id(), [$this->server]);

        self::assertNotNull($serverSession);
        self::assertSame('alice', (string)$serverSession->Username);
        self::assertSame('secret', (string)$serverSession->Password);
    }

    public function testServerSessionWithOnlyCredentialsForAllServersReturnsNull(): void
    {
        $_SESSION['sharedUsername'] = 'bob';
        $_SESSION['sharedPassword'] = 'secret';

        self::assertFalse(Logins::isLoggedIn($this->server->id()));
        self::assertNull(Logins::serverSession($this->server->id(), [$this->server]));
    }

    public function testServerSessionWithoutUsernameReturnsNull(): void
    {
        $this->logIn('', 'secret');

        self::assertNull(Logins::serverSession($this->server->id(), [$this->server]));
    }

    public function testServerSessionWithoutLoginReturnsNull(): void
    {
        self::assertNull(Logins::serverSession($this->server->id(), [$this->server]));
    }

    public function testServerSessionOfAnUnknownServerReturnsNull(): void
    {
        $this->logIn('alice', 'secret');

        self::assertNull(Logins::serverSession('db.example.org:5432:allow', [$this->server]));
    }

    public function testChangePasswordKeepsTheNewPasswordInTheLoginToTheServer(): void
    {
        $otherServer = new Server(name: new Name('Other Server'), host: new Host('db.example.org'));
        $_SESSION['webdbLogin'] = [
            $otherServer->id() => ['password' => 'secret', 'username' => 'bob'],
            $this->server->id() => ['password' => 'secret', 'platform' => 'PostgreSQL 18', 'username' => 'alice'],
        ];

        Logins::changePassword($this->server->id(), 'changed');

        self::assertSame(
            [
                $otherServer->id() => ['password' => 'secret', 'username' => 'bob'],
                $this->server->id() => ['password' => 'changed', 'platform' => 'PostgreSQL 18', 'username' => 'alice'],
            ],
            $_SESSION['webdbLogin'],
        );
    }

    public function testChangePasswordWithoutALoginToTheServerLogsInToNone(): void
    {
        $otherServer = new Server(name: new Name('Other Server'), host: new Host('db.example.org'));
        $logins = [$otherServer->id() => ['password' => 'secret', 'username' => 'bob']];
        $_SESSION['webdbLogin'] = $logins;

        Logins::changePassword($this->server->id(), 'changed');

        self::assertSame($logins, $_SESSION['webdbLogin']);
    }

    public function testChangePasswordWithoutAnyLoginLeavesTheSessionAlone(): void
    {
        Logins::changePassword($this->server->id(), 'changed');

        self::assertSame([], $_SESSION);
    }

    public function testIsLoggedInWithAnEmptyPassword(): void
    {
        $this->logIn('alice', '');

        self::assertTrue(Logins::isLoggedIn($this->server->id()));
    }

    public function testIsLoggedInWithoutUsername(): void
    {
        $this->logIn('', 'secret');

        self::assertFalse(Logins::isLoggedIn($this->server->id()));
    }

    public function testIsLoggedInWithoutLogin(): void
    {
        self::assertFalse(Logins::isLoggedIn($this->server->id()));
    }

    public function testLoggedInServersSkipTheServersWithoutLogin(): void
    {
        $otherServer = new Server(name: new Name('Other Server'), host: new Host('db.example.org'));
        $this->logIn('alice', 'secret');

        self::assertSame([$this->server], Logins::loggedInServers([$otherServer, $this->server]));
    }

    public function testLoggedInServersKeepTheOrderOfTheServers(): void
    {
        $otherServer = new Server(name: new Name('Other Server'), host: new Host('db.example.org'));
        $_SESSION['webdbLogin'] = [
            $otherServer->id() => ['password' => 'secret', 'username' => 'bob'],
            $this->server->id() => ['password' => 'secret', 'username' => 'alice'],
        ];

        self::assertSame([$this->server, $otherServer], Logins::loggedInServers([$this->server, $otherServer]));
    }

    public function testWithoutLoginThereAreNoLoggedInServers(): void
    {
        self::assertSame([], Logins::loggedInServers([$this->server]));
    }

    #[\Override]
    protected function setUp(): void
    {
        $_SESSION = [];

        $this->server = new Server(name: new Name('Test Server'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function logIn(string $username, string $password, ?string $platform = null): void
    {
        $login = [
            'password' => $password,
            'username' => $username,
        ];

        if ($platform !== null) {
            $login['platform'] = $platform;
        }

        $_SESSION['webdbLogin'] = [$this->server->id() => $login];
    }
}
