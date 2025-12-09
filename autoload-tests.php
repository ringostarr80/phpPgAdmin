<?php

/**
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \PhpPgAdmin\Example class
 * from /path/to/project/src/Example.php:
 *
 *      new \PhpPgAdmin\Example;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */

declare(strict_types=1);

spl_autoload_register(static function ($class): void {
    // project-specific namespace prefix
    $prefix = '';

    // base directory for the namespace prefix
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});
