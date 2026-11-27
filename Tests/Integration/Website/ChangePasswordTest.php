<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Config;
use PhpPgAdmin\Database\ScramSha256;
use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Infrastructure\Session\Logins;
use PhpPgAdmin\Website\ChangePassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, PageRedirect, RunningServer, TestEnvironment};

/**
 * Each test logs in as a role of its own, as a change would change the password of the user of the tests.
 */
#[CoversClass(ChangePassword::class)]
final class ChangePasswordTest extends TestCase
{
    private const ROLE = 'ppa_test_password_change';

    private const OLD_PASSWORD = 'ppa_test_old_password';

    private const NEW_PASSWORD = 'ppa_test_new_password';

    public function testAChangeGivesTheRoleOfTheLoginTheNewPassword(): void
    {
        // Without the superuser attribute, which only a superuser may set or keep.
        self::logInAsTheRole();
        self::postTheNewPassword();

        PageRedirect::afterPost(ChangePassword::class);

        $secret = self::secretOfTheRole();
        self::assertSame(self::scramSecretOfTheNewPassword($secret), $secret);
    }

    public function testAChangeLeadsToTheAccountWithAMessage(): void
    {
        self::logInAsTheRole();
        self::postTheNewPassword();

        self::assertSame(
            ['account.php', ['message' => 'Password changed.', 'server' => RunningServer::id()]],
            PageRedirect::afterPost(ChangePassword::class),
        );
    }

    public function testAfterAChangeTheSessionKeepsTheNewPassword(): void
    {
        self::logInAsTheRole();
        self::postTheNewPassword();

        PageRedirect::afterPost(ChangePassword::class);

        self::assertSame(self::NEW_PASSWORD, self::passwordOfTheSession());
    }

    public function testAFailedChangeShowsTheFormAgainWithTheErrorOfPostgreSql(): void
    {
        // PostgreSQL alters no role in a read-only transaction.
        self::logInAsTheRole(readOnly: true);
        self::postTheNewPassword();

        $body = PageBody::afterPost(ChangePassword::class);

        self::assertSame(['Failed to change password.'], PageBody::messages($body));
        $errors = PageBody::query($body, './/pre[@class="error"]');
        self::assertCount(1, $errors);
        // The error names the statement in every language.
        self::assertStringContainsString('ALTER ROLE', $errors[0]->textContent);
        self::assertCount(1, PageBody::query($body, './/form'));
    }

    public function testAfterAFailedChangeTheSessionKeepsTheOldPassword(): void
    {
        self::logInAsTheRole(readOnly: true);
        self::postTheNewPassword();

        self::assertNull(PageRedirect::afterPost(ChangePassword::class));
        self::assertSame(self::OLD_PASSWORD, self::passwordOfTheSession());
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        // The test logged in as the role.
        RunningServer::logIn();
        RunningServer::connection()->exec('DROP ROLE IF EXISTS "' . self::role() . '"');

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function role(): string
    {
        return TestEnvironment::perThread(self::ROLE);
    }

    /**
     * Creates the role with the old password and logs in as it.
     */
    private static function logInAsTheRole(bool $readOnly = false): void
    {
        $connection = RunningServer::connection();
        $role = self::role();

        // Removes what an aborted run of this thread left behind.
        $connection->exec("DROP ROLE IF EXISTS \"{$role}\"");
        $connection->exec("CREATE ROLE \"{$role}\" LOGIN PASSWORD '" . self::OLD_PASSWORD . "'");

        if ($readOnly) {
            $connection->exec("ALTER ROLE \"{$role}\" SET default_transaction_read_only = on");
        }

        RunningServer::logInAs($role, self::OLD_PASSWORD);
    }

    private static function postTheNewPassword(): void
    {
        $_POST = [
            'ok' => 'OK',
            'server' => RunningServer::id(),
            Role::FORM_ID_PASSWORD => self::NEW_PASSWORD,
            Role::FORM_ID_PASSWORD_CONFIRMATION => self::NEW_PASSWORD,
        ];
    }

    private static function passwordOfTheSession(): string
    {
        return (string)Logins::serverSession(RunningServer::id(), Config::getServers())?->Password;
    }

    /**
     * Read as the user of the tests, as only a superuser may read pg_authid.
     */
    private static function secretOfTheRole(): string
    {
        $session = $_SESSION;
        RunningServer::logIn();
        $connection = RunningServer::connection();
        $_SESSION = $session;

        $statement = $connection->query(
            'SELECT rolpassword FROM pg_catalog.pg_authid WHERE rolname = ' . $connection->quote(self::role()),
        );
        self::assertNotFalse($statement);
        $secret = $statement->fetchColumn();
        self::assertIsString($secret);

        return $secret;
    }

    /**
     * The SCRAM-SHA-256 secret that NEW_PASSWORD gives with the salt and the iterations of the stored secret, as each
     * secret gets a random salt.
     */
    private static function scramSecretOfTheNewPassword(string $storedSecret): string
    {
        if (preg_match('/^SCRAM-SHA-256\$(\d+):([^$]+)\$/', $storedSecret, $matches) !== 1) {
            self::fail("The role has no SCRAM-SHA-256 secret, but {$storedSecret}.");
        }

        return ScramSha256::secret(self::NEW_PASSWORD, (int)$matches[1], (string)base64_decode($matches[2], true));
    }
}
