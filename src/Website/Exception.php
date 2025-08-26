<?php

declare(strict_types=1);

namespace PhpPgAdmin\Website;

use Locr\Lib\HTTP\StatusCode;
use PhpPgAdmin\Website;

final class Exception extends Website
{
    public function __construct(private \Throwable $exception)
    {
    }

    public static function handle(\Throwable $exception): void
    {
        $exceptionCode = $exception->getCode();
        if (is_int($exceptionCode)) {
            $statusCode = StatusCode::tryFrom($exceptionCode) ?? StatusCode::InternalServerError;
            header($statusCode->buildHeader());
        }
        $website = new self($exception);
        print $website->buildHtmlString();
    }

    protected function buildHtmlBody(\DOMDocument $dom): \DOMElement
    {
        $body = parent::buildHtmlBody($dom);

        $h1 = $dom->createElement(
            'h1',
            htmlentities($this->exception->getCode() . ': ' . $this->exception->getMessage())
        );
        $body->appendChild($h1);

        return $body;
    }
}
