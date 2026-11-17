<?php

declare(strict_types=1);

namespace Tests\Unit\Website;

use PhpPgAdmin\Website\CreateTablespace;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversMethod(CreateTablespace::class, 'buildHtmlBody')]
final class CreateTablespaceTest extends TestCase
{
    private const SERVER_ID = '127.0.0.1:5432:allow';

    public function testTheHiddenFieldsOfTheFormAreTheActionAndTheServer(): void
    {
        $_GET['server'] = self::SERVER_ID;

        self::assertSame(
            [['action', 'save_create'], ['server', self::SERVER_ID]],
            self::hiddenFieldsOfTheForm(),
        );
    }

    protected function setUp(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    /**
     * The name and value of each hidden field, in the order of the form.
     *
     * @return list<array{string, string}>
     */
    private static function hiddenFieldsOfTheForm(): array
    {
        // Without its constructor, which starts a PHP session and needs a login. Without a login, the form has no
        // owners to choose from, which this test does not check.
        $page = (new \ReflectionClass(CreateTablespace::class))->newInstanceWithoutConstructor();
        $buildHtmlBody = new \ReflectionMethod($page, 'buildHtmlBody');
        $body = $buildHtmlBody->invoke($page, new \DOMDocument());
        self::assertInstanceOf(\DOMElement::class, $body);

        $forms = $body->getElementsByTagName('form');
        self::assertCount(1, $forms);
        $form = $forms->item(0);
        self::assertInstanceOf(\DOMElement::class, $form);

        $fields = [];

        foreach ($form->getElementsByTagName('input') as $input) {
            if ($input->getAttribute('type') === 'hidden') {
                $fields[] = [$input->getAttribute('name'), $input->getAttribute('value')];
            }
        }

        return $fields;
    }
}
