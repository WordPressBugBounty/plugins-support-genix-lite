<?php

/**
 * HTML to Markdown converter.
 *
 * Thin wrapper around league/html-to-markdown that preserves the existing
 * class name and convert() signature used throughout the plugin.
 *
 * Pipelines:
 *   - Chatbot AI context: default settings (tables as markdown, tags preserved)
 *   - Email-to-ticket:    setStripTags(true) + setFlattenTables(true)
 */

defined('ABSPATH') || exit;

class Apbd_Wps_HtmlToMarkdown
{
    private $flattenTables = false;
    private $stripTags = false;

    private static $autoloaded = false;

    public function setFlattenTables($flatten = true)
    {
        $this->flattenTables = (bool) $flatten;
        return $this;
    }

    public function setStripTags($strip = true)
    {
        $this->stripTags = (bool) $strip;
        return $this;
    }

    /**
     * Convert HTML string to Markdown.
     *
     * @param string $html
     * @return string
     */
    public function convert($html)
    {
        if (empty($html)) {
            return '';
        }

        self::loadAutoloader();

        $converter = new \ApbdWps\HTMLToMarkdown\HtmlConverter([
            'header_style'    => 'atx',
            'use_autolinks'   => true,
            'hard_break'      => false,
            'strip_tags'      => $this->stripTags,
            'remove_nodes'    => 'style script head',
        ]);

        // TableConverter is not included by default — register it.
        $converter->getEnvironment()->addConverter(
            new \ApbdWps\HTMLToMarkdown\Converter\TableConverter()
        );

        $markdown = $converter->convert($html);

        if ($this->flattenTables) {
            $markdown = $this->flattenMarkdownTables($markdown);
        }

        // Collapse 3+ newlines to 2.
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

        return trim($markdown);
    }

    /**
     * Load the league/html-to-markdown Composer autoloader once.
     */
    private static function loadAutoloader()
    {
        if (self::$autoloaded) {
            return;
        }

        $autoloader = __DIR__ . '/html-to-markdown/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        self::$autoloaded = true;
    }

    /**
     * Post-process: convert markdown table syntax to plain lines.
     *
     * Replaces pipe-delimited rows with one line per cell, and removes
     * separator rows (| --- | --- |). Used for email signature / layout
     * tables where markdown table syntax is undesirable.
     *
     * @param string $markdown
     * @return string
     */
    private function flattenMarkdownTables($markdown)
    {
        return preg_replace_callback(
            '/(?:^[ \t]*\|.+\|[ \t]*$\n?)+/m',
            function ($match) {
                $lines  = explode("\n", trim($match[0]));
                $output = [];

                foreach ($lines as $line) {
                    $line = trim($line);

                    // Skip separator rows like |---|---| or | --- | --- |
                    if (preg_match('/^\|[\s:|-]+\|$/', $line) && strpos($line, '-') !== false && !preg_match('/[a-zA-Z0-9]/', $line)) {
                        continue;
                    }

                    // Strip leading/trailing pipe, split on inner pipes.
                    $line  = trim($line, '|');
                    $cells = explode('|', $line);

                    foreach ($cells as $cell) {
                        $cell = trim($cell);
                        if ('' !== $cell) {
                            $output[] = $cell;
                        }
                    }
                }

                return implode("\n", $output) . "\n";
            },
            $markdown
        );
    }
}
