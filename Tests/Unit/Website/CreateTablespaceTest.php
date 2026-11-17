<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\DDD\ValueObjects\Tablespace;
use PhpPgAdmin\Website\CreateTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTablespace::class, 'buildHtmlBody')]
final class CreateTablespaceTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    /**
     * @var array<mixed>
     */
    private array $server = [];

    public function testThePageHasTheTopBarAndATrailToTheServer(): void
    {
        $body = self::body();

        self::assertCount(1, self::query($body, './/div[@class="topbar"]'));
        self::assertCount(
            1,
            self::query($body, './/div[@class="trail"]//a[@href="all_db.php?server=127.0.0.1%3A5432%3Aallow"]'),
        );
    }

    public function testTheHeadingLinksToTheHelpOnCreatingATablespace(): void
    {
        $h2 = self::onlyElement(self::body(), 'h2');

        self::assertSame('Create tablespace', $h2->firstChild?->textContent);
        self::assertSame(
            'help.php?help=pg.tablespace.create&server=127.0.0.1%3A5432%3Aallow',
            self::onlyElement($h2, 'a')->getAttribute('href'),
        );
    }

    public function testTheFormIsPostedAndStartsEmpty(): void
    {
        $form = self::form();

        self::assertSame('post', $form->getAttribute('method'));
        self::assertSame('', self::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame('', self::fieldValue($form, Tablespace::FORM_ID_LOCATION));
        self::assertSame('', self::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testAfterAFailedCreateTheFormShowsTheEnteredValues(): void
    {
        // The constructor redirects to the tablespaces after a successful create, so the page only shows a posted form
        // when the create failed.
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            Tablespace::FORM_ID_COMMENT => 'Old data',
            Tablespace::FORM_ID_LOCATION => '/srv/archive',
            Tablespace::FORM_ID_NAME => 'archive',
        ];

        $form = self::form();

        self::assertSame('archive', self::fieldValue($form, Tablespace::FORM_ID_NAME));
        self::assertSame('/srv/archive', self::fieldValue($form, Tablespace::FORM_ID_LOCATION));
        self::assertSame('Old data', self::fieldValue($form, Tablespace::FORM_ID_COMMENT));
    }

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        $fields = [];

        foreach (self::query(self::form(), './/input[@type="hidden"]') as $input) {
            $fields[] = [$input->getAttribute('name'), $input->getAttribute('value')];
        }

        self::assertSame([['action', 'save_create'], ['server', self::SERVER_ID]], $fields);
    }

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_GET = ['server' => self::SERVER_ID];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    private static function body(): \DOMElement
    {
        // Without its constructor, which starts a PHP session and needs a login. Without a login, the form has no
        // owners to choose from, which these tests do not check.
        $page = (new \ReflectionClass(CreateTablespace::class))->newInstanceWithoutConstructor();
        $buildHtmlBody = new \ReflectionMethod($page, 'buildHtmlBody');
        $body = $buildHtmlBody->invoke($page, new \DOMDocument());
        self::assertInstanceOf(\DOMElement::class, $body);

        return $body;
    }

    /**
     * The value of the input field or the text of the textarea with the given id.
     */
    private static function fieldValue(\DOMElement $form, string $id): string
    {
        $fields = self::query($form, ".//*[@id=\"{$id}\"]");
        self::assertCount(1, $fields);

        return $fields[0]->tagName === 'textarea'
            ? $fields[0]->textContent
            : $fields[0]->getAttribute('value');
    }

    private static function form(): \DOMElement
    {
        return self::onlyElement(self::body(), 'form');
    }

    private static function onlyElement(\DOMElement $parent, string $tagName): \DOMElement
    {
        $elements = $parent->getElementsByTagName($tagName);
        self::assertCount(1, $elements);
        $element = $elements->item(0);
        self::assertInstanceOf(\DOMElement::class, $element);

        return $element;
    }

    /**
     * @return list<\DOMElement>
     */
    private static function query(\DOMElement $context, string $expression): array
    {
        self::assertInstanceOf(\DOMDocument::class, $context->ownerDocument);
        $nodes = (new \DOMXPath($context->ownerDocument))->query($expression, $context);
        self::assertInstanceOf(\DOMNodeList::class, $nodes);

        $elements = [];

        foreach ($nodes as $node) {
            self::assertInstanceOf(\DOMElement::class, $node);
            $elements[] = $node;
        }

        return $elements;
    }
}
