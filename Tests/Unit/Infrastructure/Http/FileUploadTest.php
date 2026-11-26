<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Http;

use PhpPgAdmin\Infrastructure\Http\FileUpload;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;

#[CoversClass(FileUpload::class)]
final class FileUploadTest extends TestCase
{
    #[DataProvider('uploadMaxFilesizeProvider')]
    public function testMaxSizeIsUploadMaxFilesizeInBytes(string $uploadMaxFilesize, int $bytes): void
    {
        self::assertSame($bytes, FileUpload::maxSizeBy('1', $uploadMaxFilesize));
    }

    #[DataProvider('unlimitedUploadMaxFilesizeProvider')]
    public function testUploadMaxFilesizeOfZeroOrLessAllowsAnySize(string $uploadMaxFilesize): void
    {
        self::assertSame(0, FileUpload::maxSizeBy('1', $uploadMaxFilesize));
    }

    #[DataProvider('fileUploadsOffProvider')]
    public function testWithoutFileUploadsThereIsNoMaxSize(string|false $fileUploads): void
    {
        self::assertNull(FileUpload::maxSizeBy($fileUploads, '2M'));
    }

    public function testWithoutUploadMaxFilesizeThereIsNoMaxSize(): void
    {
        self::assertNull(FileUpload::maxSizeBy('1', false));
    }

    public function testMaxSizeFollowsTheSettingsOfPhp(): void
    {
        self::assertSame(
            FileUpload::maxSizeBy(ini_get('file_uploads'), ini_get('upload_max_filesize')),
            FileUpload::maxSize(),
        );
    }

    /**
     * @return array<string, array{string|false}>
     */
    public static function fileUploadsOffProvider(): array
    {
        return [
            // ini_get() returns '' for Off in php.ini.
            'empty' => [''],
            'unknown setting' => [false],
            'zero' => ['0'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unlimitedUploadMaxFilesizeProvider(): array
    {
        return [
            'negative' => ['-1'],
            'zero' => ['0'],
        ];
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function uploadMaxFilesizeProvider(): array
    {
        return [
            'bytes' => ['100', 100],
            // The legacy Misc::inisizeToBytes() knew no G, so the legacy SQL window offered no upload then.
            'gigabytes' => ['1G', 1_073_741_824],
            'kilobytes' => ['512K', 524_288],
            'megabytes' => ['2M', 2_097_152],
        ];
    }
}
