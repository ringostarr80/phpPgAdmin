<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpPgAdmin\Website;
use PHPUnit\Framework\Assert;

/**
 * Builds the body of a page and finds the elements in it, for the tests of the pages.
 */
final class PageBody
{
    /**
     * Without the constructor of the page, which starts a PHP session and needs a login.
     *
     * @param class-string<Website> $page
     */
    public static function of(string $page): \DOMElement
    {
        return self::bodyOf((new \ReflectionClass($page))->newInstanceWithoutConstructor());
    }

    /**
     * After the page handled the posted form, as its constructor does for a POST request. A form that the page can
     * carry out changes the database.
     *
     * @param class-string<Website> $page
     */
    public static function afterPost(string $page): \DOMElement
    {
        $website = (new \ReflectionClass($page))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($website, 'handlePostRequest'))->invoke($website);

        return self::bodyOf($website);
    }

    public static function field(\DOMElement $form, string $id): \DOMElement
    {
        $fields = self::query($form, ".//*[@id=\"{$id}\"]");
        Assert::assertCount(1, $fields);

        return $fields[0];
    }

    /**
     * The value of the input field or the text of the textarea with the given id, as the browser shows it.
     */
    public static function fieldValue(\DOMElement $form, string $id): string
    {
        $field = self::field($form, $id);

        return $field->tagName === 'textarea'
            ? self::textareaText($field)
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

    /**
     * The texts of the messages of the page, in their order.
     *
     * @return list<string>
     */
    public static function messages(\DOMElement $body): array
    {
        return array_map(
            static fn (\DOMElement $message): string => $message->textContent,
            self::query($body, './/p[@class="message"]'),
        );
    }

    /**
     * The values of the selected options of the select with the given id, in their order.
     *
     * @return list<string>
     */
    public static function selectedValues(\DOMElement $form, string $id): array
    {
        return array_map(
            static fn (\DOMElement $option): string => $option->getAttribute('value'),
            self::query($form, ".//select[@id=\"{$id}\"]/option[@selected]"),
        );
    }

    /**
     * The text of the textarea as the browser shows it. The HTML parser drops a line break right after the start tag,
     * and takes a carriage return with or without a line feed for one.
     */
    public static function textareaText(\DOMElement $textarea): string
    {
        $text = $textarea->textContent;

        foreach (["\r\n", "\r", "\n"] as $lineBreak) {
            if (str_starts_with($text, $lineBreak)) {
                return substr($text, strlen($lineBreak));
            }
        }

        return $text;
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

    private static function bodyOf(Website $website): \DOMElement
    {
        $buildHtmlBody = new \ReflectionMethod($website, 'buildHtmlBody');
        $body = $buildHtmlBody->invoke($website, new \DOMDocument());
        Assert::assertInstanceOf(\DOMElement::class, $body);

        return $body;
    }
}
