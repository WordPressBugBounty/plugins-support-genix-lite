<?php
// phpcs:ignoreFile -- Bundled third-party library (Composer + Mozart); not subject to plugin coding standards.

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('ABSPATH') || exit;

if (\PHP_VERSION_ID < 80000) {
    class ValueError extends Error
    {
    }
}
