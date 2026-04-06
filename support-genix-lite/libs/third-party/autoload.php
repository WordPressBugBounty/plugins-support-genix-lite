<?php
/**
 * Autoloader for namespace-prefixed third-party packages.
 *
 * Packages are managed via Composer + Mozart.
 * All namespaces are prefixed with ApbdWps\Vendor\ to avoid conflicts.
 *
 * @see composer.json for package list and Mozart configuration.
 */

defined('ABSPATH') || exit;

// Polyfill for PHP < 8.0 (str_contains, str_starts_with, etc.).
require_once __DIR__ . '/packages/Symfony/Polyfill/Php80/bootstrap.php';

spl_autoload_register(function ($class) {
    $prefix = 'ApbdWps\\Vendor\\';
    $len    = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);
    $file     = __DIR__ . '/packages/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});