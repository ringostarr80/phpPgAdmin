<?php

declare(strict_types=1);

namespace Tests\Integration\Website;

use PhpPgAdmin\Database\PhpPgAdminConnection;
use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Website\{AlterTablespace, CreateTablespace};
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\{OtherOwner, PageBody, RunningServer, TestEnvironment};

#[CoversMethod(AlterTablespace::class, 'buildHtmlBody')]
#[CoversMethod(AlterTablespace::class, 'withEnteredValues')]
#[CoversMethod(CreateTablespace::class, 'buildCreateOrEditTablespaceTable')]
final class AlterTablespaceTest extends TestCase
{
    /**
     * Every database cluster has it, so most tests do not need to create a tablespace, which needs an empty directory
     * on the database server.
     */
    private const TABLESPACE = 'pg_default';

    private ?OtherOwner $otherOwner = null;

    public function testTheFormShowsTheNameAndTheOwnerOfTheTablespace(): void
    {
        $form = self::form();

        self::assertSame(self::TABLESPACE, PageBody::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame([self::bootstrapSuperuser()], PageBody::selectedValues($form, Tablespace::FORM_ID_OWNER));
    }

    public function testTheFormShowsAnOwnerThatCannotLogIn(): void
    {
        $connection = RunningServer::connection();
        // Removes what an aborted run of this thread left behind.
        self::dropTheTablespaceOfAGroup($connection);
        // Like a group role, which cannot log in and still can own a tablespace.
        $connection->exec('CREATE ROLE "' . self::group() . '" NOLOGIN');
        // PostgreSQL puts an in-place tablespace into its data directory, so it needs no empty directory.
        $connection->exec('SET allow_in_place_tablespaces = true');
        $connection->exec(
            'CREATE TABLESPACE "' . self::tablespaceOfAGroup() . '" OWNER "' . self::group() . '" LOCATION \'\'',
        );
        $_GET['tablespace'] = self::tablespaceOfAGroup();

        try {
            self::assertSame([self::group()], PageBody::selectedValues(self::form(), Tablespace::FORM_ID_OWNER));
        } finally {
            self::dropTheTablespaceOfAGroup($connection);
        }
    }

    public function testTheFormAltersTheTablespaceInsteadOfCreatingOne(): void
    {
        $buttons = array_map(
            static fn (\DOMElement $input): array => [$input->getAttribute('name'), $input->getAttribute('value')],
            PageBody::query(self::form(), './/input[@type="submit"]'),
        );

        self::assertSame([['alter', 'Alter'], ['cancel', 'Cancel']], $buttons);
    }

    public function testAfterAFailedAlterTheFormKeepsTheEnteredNameOwnerAndComment(): void
    {
        $this->otherOwner = OtherOwner::create();
        self::postTheForm(name: 'ppa_test_renamed', owner: OtherOwner::name(), comment: 'Entered comment');

        $form = self::form();

        self::assertSame('ppa_test_renamed', PageBody::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame([OtherOwner::name()], PageBody::selectedValues($form, Tablespace::FORM_ID_OWNER));
        self::assertSame('Entered comment', PageBody::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testAfterAFailedAlterTheFormKeepsTheLoadedLocation(): void
    {
        // PostgreSQL cannot move a tablespace. pg_default lies in the data directory, which has an empty location.
        self::postTheForm(location: '/srv/elsewhere');

        self::assertSame('', PageBody::fieldValue(self::form(), Tablespace::FORM_ID_LOCATION));
    }

    public function testAfterAFailedAlterWithoutANameTheFormShowsTheLoadedName(): void
    {
        // A tablespace needs a name, and blanks are none.
        self::postTheForm(name: ' ');

        self::assertSame(self::TABLESPACE, PageBody::fieldValue(self::form(), Tablespace::FORM_ID_NAME));
    }

    public function testAfterAFailedAlterTheFormKeepsTheNameZero(): void
    {
        // PHP's empty() takes "0" for an empty string, PostgreSQL takes it as a name.
        self::postTheForm(name: '0');

        self::assertSame('0', PageBody::fieldValue(self::form(), Tablespace::FORM_ID_NAME));
    }

    public function testAfterAnAlterWithoutANameThePageSaysItFailed(): void
    {
        // The alter fails before it reaches PostgreSQL, so pg_default stays as it is and no SQL error is shown.
        self::postTheForm(name: ' ');

        self::assertSame(
            ['Tablespace alteration failed.'],
            PageBody::messages(PageBody::afterPost(AlterTablespace::class)),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [
            'server' => RunningServer::id(),
            'tablespace' => self::TABLESPACE,
        ];
        $_POST = [];
        RunningServer::logIn();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->otherOwner?->drop();

        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    /**
     * The form as it comes back after a failed alter. PageBody::of() leaves out the constructor, so pg_default is not
     * altered. A value that is not given is the one the form showed.
     */
    private static function postTheForm(
        string $name = self::TABLESPACE,
        ?string $owner = null,
        string $location = '',
        string $comment = '',
    ): void {
        $_POST = [
            Tablespace::FORM_ID_COMMENT => $comment,
            Tablespace::FORM_ID_LOCATION => $location,
            Tablespace::FORM_ID_NAME => $name,
            Tablespace::FORM_ID_OWNER => $owner ?? self::bootstrapSuperuser(),
        ];
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(PageBody::of(AlterTablespace::class), 'form');
    }

    /**
     * The superuser that created the database cluster, who owns pg_default. Its name differs between installations.
     */
    private static function bootstrapSuperuser(): string
    {
        // PostgreSQL gives the bootstrap superuser the fixed oid 10.
        $statement = RunningServer::connection()->query('SELECT rolname FROM pg_catalog.pg_roles WHERE oid = 10');
        self::assertNotFalse($statement);
        $rolname = $statement->fetchColumn();
        self::assertIsString($rolname);

        return $rolname;
    }

    private static function group(): string
    {
        return TestEnvironment::perThread('ppa_test_tablespace_group');
    }

    private static function tablespaceOfAGroup(): string
    {
        return TestEnvironment::perThread('ppa_test_tablespace_of_a_group');
    }

    private static function dropTheTablespaceOfAGroup(PhpPgAdminConnection $connection): void
    {
        // The group cannot be dropped while it owns the tablespace.
        $connection->exec('DROP TABLESPACE IF EXISTS "' . self::tablespaceOfAGroup() . '"');
        $connection->exec('DROP ROLE IF EXISTS "' . self::group() . '"');
    }
}
