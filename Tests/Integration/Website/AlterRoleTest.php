<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Website\{AlterRole, CreateRole};
use PHPUnit\Framework\Attributes\{CoversMethod, TestWith};
use PHPUnit\Framework\TestCase;
use Tests\Support\{NameZero, PageBody, RunningServer, TestEnvironment};

#[CoversMethod(CreateRole::class, 'buildCreateOrEditRoleTable')]
final class AlterRoleTest extends TestCase
{
    private const ALTERED_ROLE = 'ppa_test_altered_role';
    private const GROUP_ROLE = 'ppa_test_group_role';
    private const MEMBER_ROLE = 'ppa_test_member_role';
    private const ADMIN_MEMBER_ROLE = 'ppa_test_admin_member_role';
    private const MISSING_ROLE = 'ppa_test_missing_role';

    /**
     * The attributes of a role that the form shows as checkboxes.
     */
    private const ATTRIBUTES = ['SUPERUSER', 'CREATEDB', 'CREATEROLE', 'INHERIT', 'LOGIN'];

    private ?NameZero $nameZero = null;

    public function testTheFormShowsTheNameOfTheRoleWithoutLettingItBeChanged(): void
    {
        $role = self::createRole(self::ALTERED_ROLE);

        $name = PageBody::field(self::form(), Role::FORM_ID_NAME);

        self::assertSame($role, $name->getAttribute('value'));
        self::assertTrue($name->hasAttribute('readonly'));
        // PostgreSQL keeps no more than 63 bytes of a name.
        self::assertSame('63', $name->getAttribute('maxlength'));
    }

    public function testTheFormShowsTheNameZero(): void
    {
        // PHP's empty() takes "0" for an empty string, PostgreSQL takes it as a name.
        $this->nameZero = NameZero::take();
        $this->nameZero->createRole();
        $_GET['rolename'] = NameZero::NAME;

        self::assertSame(NameZero::NAME, PageBody::fieldValue(self::form(), Role::FORM_ID_NAME));
    }

    #[TestWith(['SUPERUSER', 'formSuper'])]
    #[TestWith(['CREATEDB', 'formCreateDB'])]
    #[TestWith(['CREATEROLE', 'formCreateRole'])]
    #[TestWith(['INHERIT', 'formInherits'])]
    #[TestWith(['LOGIN', 'formCanLogin'])]
    public function testTheFormChecksOnlyTheAttributeThatTheRoleHas(string $attribute, string $checkbox): void
    {
        $options = array_map(
            static fn (string $other): string => $other === $attribute ? $other : "NO{$other}",
            self::ATTRIBUTES,
        );
        self::createRole(self::ALTERED_ROLE, implode(' ', $options));

        self::assertSame([$checkbox], self::checkedCheckboxes(self::form()));
    }

    public function testTheFormShowsTheConnectionLimitAndTheExpiryOfTheRole(): void
    {
        // PostgreSQL gives the time back in the time zone of the session, in which it takes it.
        self::createRole(self::ALTERED_ROLE, "CONNECTION LIMIT 7 VALID UNTIL '2031-02-03 04:05'");

        $form = self::form();

        self::assertSame('7', PageBody::fieldValue($form, 'formConnLimit'));
        self::assertSame('2031-02-03T04:05', PageBody::fieldValue($form, 'formExpires'));
    }

    public function testTheFormSelectsTheMembershipsOfTheRole(): void
    {
        $role = self::createRole(self::ALTERED_ROLE);
        $group = self::createRole(self::GROUP_ROLE);
        $member = self::createRole(self::MEMBER_ROLE);
        $adminMember = self::createRole(self::ADMIN_MEMBER_ROLE);
        $connection = RunningServer::connection();
        $connection->exec("GRANT \"{$group}\" TO \"{$role}\"");
        $connection->exec("GRANT \"{$role}\" TO \"{$member}\"");
        $connection->exec("GRANT \"{$role}\" TO \"{$adminMember}\" WITH ADMIN OPTION");

        $form = self::form();

        self::assertSame([$group], PageBody::selectedValues($form, 'memberof[]'));
        self::assertSame([$member], PageBody::selectedValues($form, 'members[]'));
        self::assertSame([$adminMember], PageBody::selectedValues($form, 'adminmembers[]'));
    }

    public function testForARoleThatDoesNotExistTheFormShowsNoAttributesAndNoMemberships(): void
    {
        $_GET['rolename'] = self::MISSING_ROLE;

        $form = self::form();

        self::assertSame([], self::checkedCheckboxes($form));
        self::assertSame('', PageBody::fieldValue($form, 'formConnLimit'));
        self::assertSame('', PageBody::fieldValue($form, 'formExpires'));

        foreach (['memberof[]', 'members[]', 'adminmembers[]'] as $memberships) {
            self::assertSame([], PageBody::selectedValues($form, $memberships));
        }
    }

    public function testAfterAFailedAlterTheFormKeepsTheEnteredPasswords(): void
    {
        $role = self::createRole(self::ALTERED_ROLE);
        // The alter fails, as the password does not match its confirmation.
        $_POST = [
            Role::FORM_ID_NAME => $role,
            Role::FORM_ID_PASSWORD => 'Entered password',
            Role::FORM_ID_PASSWORD_CONFIRMATION => 'Entered confirmation',
        ];

        $form = self::form();

        self::assertSame('Entered password', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD));
        self::assertSame('Entered confirmation', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD_CONFIRMATION));
    }

    protected function setUp(): void
    {
        $_GET = [
            'rolename' => TestEnvironment::perThread(self::ALTERED_ROLE),
            'server' => RunningServer::id(),
        ];
        $_POST = [];
        RunningServer::logIn();
    }

    protected function tearDown(): void
    {
        // Dropping a role revokes its memberships as well.
        foreach ([self::ALTERED_ROLE, self::GROUP_ROLE, self::MEMBER_ROLE, self::ADMIN_MEMBER_ROLE] as $role) {
            RunningServer::connection()->exec('DROP ROLE IF EXISTS "' . TestEnvironment::perThread($role) . '"');
        }

        $this->nameZero?->release();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * @return string The name of the role in this thread.
     */
    private static function createRole(string $role, string $options = ''): string
    {
        $connection = RunningServer::connection();
        $role = TestEnvironment::perThread($role);

        // Removes what an aborted run of this thread left behind.
        $connection->exec("DROP ROLE IF EXISTS \"{$role}\"");
        $connection->exec("CREATE ROLE \"{$role}\" {$options}");

        return $role;
    }

    /**
     * PageBody::of() leaves out the constructor, so a posted form does not alter the role.
     */
    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(PageBody::of(AlterRole::class), 'form');
    }

    /**
     * The ids of the checked checkboxes of the form, in their order.
     *
     * @return list<string>
     */
    private static function checkedCheckboxes(\DOMElement $form): array
    {
        return array_map(
            static fn (\DOMElement $checkbox): string => $checkbox->getAttribute('id'),
            PageBody::query($form, './/input[@type="checkbox"][@checked]'),
        );
    }
}
