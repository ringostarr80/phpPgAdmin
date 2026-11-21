<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Website\CreateRole;
use PHPUnit\Framework\Attributes\{CoversMethod, TestWith};
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(CreateRole::class, 'buildHtmlBody')]
#[CoversMethod(CreateRole::class, 'buildCreateOrEditRoleTable')]
final class CreateRoleTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(
            1,
            PageBody::query($body, './/div[@class="trail"]//a[@href="all_db.php?server=127.0.0.1%3A5432%3Aallow"]'),
        );
    }

    public function testTheHeadingLinksToTheHelpOnCreatingARole(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Create role', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.role.create&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndStartsWithoutANameAndWithoutAttributes(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_NAME));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD_CONFIRMATION));
        self::assertSame([], self::checkedCheckboxes($form));
    }

    public function testAfterAFailedCreateTheFormKeepsTheEnteredNameAndPasswords(): void
    {
        self::postTheForm();

        $form = self::form();

        self::assertSame('auditor', PageBody::fieldValue($form, Role::FORM_ID_NAME));
        self::assertSame('Entered password', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD));
        self::assertSame('Entered confirmation', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD_CONFIRMATION));
    }

    #[TestWith(['formSuper'])]
    #[TestWith(['formCreateDB'])]
    #[TestWith(['formCreateRole'])]
    #[TestWith(['formInherits'])]
    #[TestWith(['formCanLogin'])]
    public function testAfterAFailedCreateTheFormChecksOnlyTheCheckedAttribute(string $checkbox): void
    {
        // The browser posts a checked checkbox as "on" and leaves out an unchecked one.
        self::postTheForm([$checkbox => 'on']);

        self::assertSame([$checkbox], self::checkedCheckboxes(self::form()));
    }

    public function testAfterAFailedCreateTheFormKeepsTheEnteredConnectionLimitAndExpiry(): void
    {
        self::postTheForm(['formConnLimit' => '7', 'formExpires' => '2031-02-03T04:05']);

        $form = self::form();

        self::assertSame('7', PageBody::fieldValue($form, 'formConnLimit'));
        self::assertSame('2031-02-03T04:05', PageBody::fieldValue($form, 'formExpires'));
    }

    public function testAfterACreateWithoutANameThePageAsksForOne(): void
    {
        self::postTheForm([Role::FORM_ID_NAME => '']);

        self::assertSame(
            ['You must give a name for the role.'],
            PageBody::messages(PageBody::afterPost(CreateRole::class)),
        );
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    protected function setUp(): void
    {
        $_GET = ['server' => self::SERVER_ID];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        // Without a login, the form has no roles to choose for the memberships, which these tests do not check.
        return PageBody::of(CreateRole::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
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

    /**
     * The request after a failed create: the constructor redirects to the roles after a successful one, so the page
     * only shows a posted form when the create failed. The passwords do not match, which fails a create.
     *
     * @param array<string, string> $fields The posted fields besides the name and the passwords.
     */
    private static function postTheForm(array $fields = []): void
    {
        $_POST = [
            Role::FORM_ID_NAME => 'auditor',
            Role::FORM_ID_PASSWORD => 'Entered password',
            Role::FORM_ID_PASSWORD_CONFIRMATION => 'Entered confirmation',
            ...$fields,
        ];
    }
}
