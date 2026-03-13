<?php
/**
 * Simple PSR-4 autoloader for ApbdWps\HTMLToMarkdown.
 *
 * Maps the ApbdWps\HTMLToMarkdown namespace to this directory.
 * Based on league/html-to-markdown (MIT License).
 */

spl_autoload_register(function ($class) {
    $prefix = 'ApbdWps\\HTMLToMarkdown\\';
    $len    = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);
    $file     = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
