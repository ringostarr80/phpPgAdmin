<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\DDD\ValueObjects\Role;
use PhpPgAdmin\Website\ChangePassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\{PageBody, PageRedirect};

#[CoversClass(ChangePassword::class)]
final class ChangePasswordTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    /**
     * As long as the minimum length of the test configuration.
     */
    private const PASSWORD = 'Entered1';

    public function testThePageHasTheTopBarAndATrailToTheServerWithoutTheRole(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));
        self::assertSame([], PageBody::query($body, './/div[@class="trail"]//a[@title="Role"]'));
    }

    public function testTheHeadingLinksToTheHelpOnAlteringARole(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Change password', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.role.alter&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndStartsWithoutPasswords(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD_CONFIRMATION));
    }

    public function testThePasswordAndItsConfirmationAreRequiredAndHidden(): void
    {
        $form = self::form();

        self::assertSame(
            [Role::FORM_ID_PASSWORD, Role::FORM_ID_PASSWORD_CONFIRMATION],
            array_map(
                static fn (\DOMElement $label): string => $label->getAttribute('for'),
                PageBody::query($form, './/th[contains(@class, "required")]/label'),
            ),
        );
        self::assertSame('password', PageBody::field($form, Role::FORM_ID_PASSWORD)->getAttribute('type'));
        self::assertSame(
            'password',
            PageBody::field($form, Role::FORM_ID_PASSWORD_CONFIRMATION)->getAttribute('type'),
        );
    }

    public function testTheHiddenFieldOfTheFormIsTheServer(): void
    {
        self::assertSame([['server', self::SERVER_ID]], PageBody::hiddenFields(self::form()));
    }

    public function testTheFormHasAnOkAndACancelButton(): void
    {
        $form = self::form();
        $buttons = PageBody::query($form, './/input[@type="submit"]');

        self::assertSame(
            [['ok', 'OK'], ['cancel', 'Cancel']],
            array_map(
                static fn (\DOMElement $button): array => [
                    $button->getAttribute('name'),
                    $button->getAttribute('value'),
                ],
                $buttons,
            ),
        );
        // A non-breaking space keeps the buttons apart.
        self::assertSame('nbsp', $buttons[0]->nextSibling?->nodeName);
        self::assertSame($buttons[1], $buttons[0]->nextSibling->nextSibling);
    }

    public function testTheFieldsOfTheFormTakeTheWholeWidthOfTheirColumn(): void
    {
        // The default theme stretches the fields of a form table.
        self::assertSame('form-table', PageBody::onlyElement(self::form(), 'table')->getAttribute('class'));
    }

    public function testCancelLeadsBackToTheAccountWithoutAMessage(): void
    {
        $_POST = [
            'cancel' => 'Cancel',
            'server' => self::SERVER_ID,
            Role::FORM_ID_PASSWORD => self::PASSWORD,
            Role::FORM_ID_PASSWORD_CONFIRMATION => self::PASSWORD,
        ];

        self::assertSame(
            ['account.php', ['server' => self::SERVER_ID]],
            PageRedirect::afterPost(ChangePassword::class),
        );
    }

    public function testAPasswordShorterThanTheMinimumLengthIsTooShort(): void
    {
        self::postTheForm('Entered', 'Entered');

        self::assertSame(['Password is too short.'], self::messagesAfterThePost());
    }

    public function testTheMinimumLengthCountsCharactersRatherThanBytes(): void
    {
        // Seven characters in fourteen bytes.
        self::postTheForm('äöüÄÖÜß', 'äöüÄÖÜß');

        self::assertSame(['Password is too short.'], self::messagesAfterThePost());
    }

    public function testAPasswordOfTheMinimumLengthMustMatchItsConfirmation(): void
    {
        self::postTheForm(self::PASSWORD, 'Entered2');

        self::assertSame(['Password does not match confirmation.'], self::messagesAfterThePost());
    }

    public function testAPasswordWithoutAConfirmationDoesNotMatchIt(): void
    {
        $_POST = [
            'ok' => 'OK',
            'server' => self::SERVER_ID,
            Role::FORM_ID_PASSWORD => self::PASSWORD,
        ];

        self::assertSame(['Password does not match confirmation.'], self::messagesAfterThePost());
    }

    public function testAfterARefusedChangeTheFormKeepsOnlyThePassword(): void
    {
        self::postTheForm(self::PASSWORD, 'Entered2');

        $form = PageBody::onlyElement(PageBody::afterPost(ChangePassword::class), 'form');

        self::assertSame(self::PASSWORD, PageBody::fieldValue($form, Role::FORM_ID_PASSWORD));
        self::assertSame('', PageBody::fieldValue($form, Role::FORM_ID_PASSWORD_CONFIRMATION));
    }

    public function testWithoutALoginTheChangeFailsAndShowsTheFormAgain(): void
    {
        self::postTheForm(self::PASSWORD, self::PASSWORD);

        self::assertNull(PageRedirect::afterPost(ChangePassword::class));
        self::assertSame(['Failed to change password.'], self::messagesAfterThePost());
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = ['server' => self::SERVER_ID];
        $_POST = [];
        $_SESSION = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        return PageBody::of(ChangePassword::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }

    /**
     * @return list<string>
     */
    private static function messagesAfterThePost(): array
    {
        return PageBody::messages(PageBody::afterPost(ChangePassword::class));
    }

    /**
     * Like the browser for the OK button. Without a login, a change that passes the checks fails.
     */
    private static function postTheForm(string $password, string $confirmation): void
    {
        $_POST = [
            'ok' => 'OK',
            'server' => self::SERVER_ID,
            Role::FORM_ID_PASSWORD => $password,
            Role::FORM_ID_PASSWORD_CONFIRMATION => $confirmation,
        ];
    }
}
