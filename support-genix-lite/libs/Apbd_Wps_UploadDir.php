<?php

/**
 * Upload directory bootstrap.
 *
 * Hardening files are templates in views/upload-dir/, not PHP strings (malware-scanner false positives).
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_UploadDir")) {
    class Apbd_Wps_UploadDir
    {
        protected static function TemplateDir()
        {
            return dirname(__DIR__) . '/views/upload-dir/';
        }

        /**
         * @return string '' when the template is unreadable.
         */
        public static function HtaccessContent()
        {
            static $content = null;

            if (null !== $content) {
                return $content;
            }

            $content = '';
            $file = self::TemplateDir() . 'access-rules.php';

            // Not require_once: a repeat call would return true, not the rules.
            if (is_readable($file)) {
                $rules = require $file;
                $content = is_string($rules) ? $rules : '';
            }

            return $content;
        }

        /**
         * Ensure the directory, .htaccess and index.php exist.
         *
         * @param string $basePath
         * @param bool   $forceHtaccess Rewrite .htaccess even when unchanged.
         */
        public static function Ensure($basePath, $forceHtaccess = false)
        {
            $basePath = untrailingslashit($basePath);

            if (!is_dir($basePath)) {
                wp_mkdir_p($basePath);
            }

            $desired = self::HtaccessContent();
            if ('' !== $desired) {
                $htaccessFile = $basePath . '/.htaccess';
                $current      = file_exists($htaccessFile) ? @file_get_contents($htaccessFile) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged -- Compare-before-write on a file we own.
                if ($forceHtaccess || $current !== $desired) {
                    ApbdWps_FilePutContents($htaccessFile, $desired);
                }
            }

            $indexFile = $basePath . '/index.php';
            if (!file_exists($indexFile)) {
                $stub = @file_get_contents(self::TemplateDir() . 'index.php'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged -- Local plugin template.
                if (is_string($stub) && '' !== $stub) {
                    ApbdWps_FilePutContents($indexFile, $stub);
                }
            }
        }
    }
}
