<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Builds the body of a page and finds the elements in it, for the tests of the pages.
 */
final class PageBody
{
    /**
     * Without the constructor of the page, which starts a PHP session and needs a login.
     *
     * @param class-string<\PhpPgAdmin\Website> $page
     */
    public static function of(string $page): \DOMElement
    {
        $website = (new \ReflectionClass($page))->newInstanceWithoutConstructor();
        $buildHtmlBody = new \ReflectionMethod($website, 'buildHtmlBody');
        $body = $buildHtmlBody->invoke($website, new \DOMDocument());
        Assert::assertInstanceOf(\DOMElement::class, $body);

        return $body;
    }

    public static function field(\DOMElement $form, string $id): \DOMElement
    {
        $fields = self::query($form, ".//*[@id=\"{$id}\"]");
        Assert::assertCount(1, $fields);

        return $fields[0];
    }

    /**
     * The value of the input field or the text of the textarea with the given id.
     */
    public static function fieldValue(\DOMElement $form, string $id): string
    {
        $field = self::field($form, $id);

        return $field->tagName === 'textarea'
            ? $field->textContent
            : $field->getAttribute('value');
    }

    /**
     * The names and values of the hidden fields of the form, in their order.
     *
     * @return list<array{string, string}>
     */
    public static function hiddenFields(\DOMElement $form): array
    {
        $fields = [];

        foreach (self::query($form, './/input[@type="hidden"]') as $input) {
            $fields[] = [$input->getAttribute('name'), $input->getAttribute('value')];
        }

        return $fields;
    }

    public static function onlyElement(\DOMElement $parent, string $tagName): \DOMElement
    {
        $elements = $parent->getElementsByTagName($tagName);
        Assert::assertCount(1, $elements);
        $element = $elements->item(0);
        Assert::assertInstanceOf(\DOMElement::class, $element);

        return $element;
    }

    /**
     * @return list<\DOMElement>
     */
    public static function query(\DOMElement $context, string $expression): array
    {
        Assert::assertInstanceOf(\DOMDocument::class, $context->ownerDocument);
        $nodes = (new \DOMXPath($context->ownerDocument))->query($expression, $context);
        Assert::assertInstanceOf(\DOMNodeList::class, $nodes);

        $elements = [];

        foreach ($nodes as $node) {
            Assert::assertInstanceOf(\DOMElement::class, $node);
            $elements[] = $node;
        }

        return $elements;
    }
}
