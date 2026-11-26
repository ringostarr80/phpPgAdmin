<?php

declare(strict_types=1);

namespace PhpPgAdmin\Infrastructure\Http;

/**
 * The file uploads that PHP accepts, by its settings file_uploads and upload_max_filesize.
 */
final class FileUpload
{
    /**
     * The largest file in bytes that PHP accepts, as the form field MAX_FILE_SIZE takes it: 0 for any size. Null if
     * PHP accepts no file uploads at all.
     */
    public static function maxSize(): ?int
    {
        return self::maxSizeBy(ini_get('file_uploads'), ini_get('upload_max_filesize'));
    }

    /**
     * Like maxSize(), with the settings as ini_get() returns them: false for a setting that PHP does not know. PHP
     * takes them only before the request starts, so the tests pass them in.
     */
    public static function maxSizeBy(string|false $fileUploads, string|false $uploadMaxFilesize): ?int
    {
        if (!filter_var($fileUploads, FILTER_VALIDATE_BOOL) || $uploadMaxFilesize === false) {
            return null;
        }

        // PHP reads upload_max_filesize with this parser and takes 0 and less for any size. MAX_FILE_SIZE takes only
        // 0 for that: with a negative one, PHP rejects every file.
        return max(0, ini_parse_quantity($uploadMaxFilesize));
    }
}
