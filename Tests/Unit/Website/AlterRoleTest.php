<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\AlterRole;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Tests\Support\PageBody;

#[CoversMethod(AlterRole::class, 'buildHtmlBody')]
final class AlterRoleTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testThePageHasTheTopBarAndATrailToTheServerAndTheRole(): void
    {
        $body = self::body();

        self::assertCount(1, PageBody::query($body, './/div[@class="topbar"]'));
        self::assertCount(1, PageBody::query($body, './/div[@class="trail"]//a[@title="Server"]'));

        $roleLabels = PageBody::query($body, './/div[@class="trail"]//a[@title="Role"]/span[@class="label"]');
        self::assertCount(1, $roleLabels);
        self::assertSame('auditor', $roleLabels[0]->textContent);
    }

    public function testTheHeadingLinksToTheHelpOnAlteringARole(): void
    {
        $h2 = PageBody::onlyElement(self::body(), 'h2');

        self::assertSame('Alter', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.role.alter&server=127.0.0.1%3A5432%3Aallow',
            PageBody::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPosted(): void
    {
        self::assertSame('post', self::form()->getAttribute('method'));
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID]],
            PageBody::hiddenFields(self::form()),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $_GET = [
            'rolename' => 'auditor',
            'server' => self::SERVER_ID,
        ];
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
        // Without a login, the page reads no role from the server, which these tests do not need.
        return PageBody::of(AlterRole::class);
    }

    private static function form(): \DOMElement
    {
        return PageBody::onlyElement(self::body(), 'form');
    }
}
