<?php
/**
 * Loco Translate Custom Extractor for Support Genix Lite
 *
 * Adds support for custom translation methods that Loco Translate
 * doesn't recognize by default:
 * - ...->__(), ...->___(), ...->_e(), ...->_ee()
 * - ...->SetTitle(), ...->SetSubtitle()
 * - ...->AddInfo(), ...->AddWarning(), ...->AddError()
 * Matches any ->method() call regardless of what precedes it:
 * $variable->__(), self::method()->__(), (new Class)->__(), etc.
 * Note: AddModel and AddModelNonSearchable were legacy POT keywords — they don't exist in the codebase.
 *
 * @package Support Genix Lite
 */

defined('ABSPATH') || exit;

add_action('loco_extracted_template', 'apbd_wps_lite_loco_extract_custom_strings', 10, 2);

/**
 * Hook into Loco Translate extraction to add strings from custom methods.
 *
 * @param Loco_gettext_Extraction $extraction The extraction object
 * @param string $domain The text domain being extracted
 */
function apbd_wps_lite_loco_extract_custom_strings($extraction, $domain)
{
    if ($domain !== 'support-genix-lite') {
        return;
    }

    $plugin_dir = dirname(__DIR__) . '/';
    $php_files = apbd_wps_lite_loco_get_php_files($plugin_dir);

    // All custom methods called as ...->method('string')
    // First argument is the translatable string
    $custom_methods = [
        '__', '___', '_e', '_ee',
        'SetTitle', 'SetSubtitle',
        'AddInfo', 'AddWarning', 'AddError',
    ];

    // Collect all strings with all their file references
    $string_refs = [];

    foreach ($php_files as $file) {
        $relative_path = str_replace($plugin_dir, '', $file);

        // Skip vendor/node_modules/build directories
        if (preg_match('#(vendor|node_modules|dashboard/dist|portal/dist|chatbot/dist)/#', $relative_path)) {
            continue;
        }

        $content = file_get_contents($file);
        if (empty($content)) {
            continue;
        }

        $strings = apbd_wps_lite_loco_extract_from_tokens($content, $custom_methods);

        foreach ($strings as $string_data) {
            $key = $string_data['string'];
            if ($key === '') {
                continue;
            }
            $string_refs[$key][] = $relative_path . ':' . $string_data['line'];
        }
    }

    // Add all unique strings with all their file references
    foreach ($string_refs as $key => $refs) {
        try {
            $loco_string = new Loco_gettext_String($key);
            $loco_string->addFileReferences($refs);
            $extraction->addString($loco_string, $domain);
        } catch (Exception $e) {
            // Silently skip if string can't be added
        }
    }
}

/**
 * Extract translatable strings from PHP tokens by scanning ->method() calls.
 *
 * @param string $source PHP source code
 * @param array $custom_methods Array of method names to scan
 * @return array Array of ['string' => ..., 'line' => ...]
 */
function apbd_wps_lite_loco_extract_from_tokens($source, $custom_methods)
{
    $results = [];
    $tokens = token_get_all($source);
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        // Match ->method('string') regardless of what precedes ->
        // Catches: $var->__(), self::method()->__(), (new Class)->__(), etc.
        if (!is_array($token) || $token[0] !== T_OBJECT_OPERATOR) {
            continue;
        }

        // Get method name
        $methodIdx = apbd_wps_lite_loco_skip_whitespace($tokens, $i + 1, $count);
        if ($methodIdx === false) {
            continue;
        }

        $methodToken = $tokens[$methodIdx];
        if (!is_array($methodToken) || $methodToken[0] !== T_STRING) {
            continue;
        }

        if (!in_array($methodToken[1], $custom_methods, true)) {
            continue;
        }

        $string = apbd_wps_lite_loco_get_first_string_arg($tokens, $methodIdx + 1, $count);
        if ($string !== null) {
            $results[] = [
                'string' => $string,
                'line' => $methodToken[2],
            ];
        }
    }

    return $results;
}

/**
 * Skip whitespace tokens and return index of next non-whitespace token.
 *
 * @param array $tokens PHP tokens array
 * @param int $start Starting index
 * @param int $count Total token count
 * @return int|false Index of next non-whitespace token, or false
 */
function apbd_wps_lite_loco_skip_whitespace($tokens, $start, $count)
{
    for ($i = $start; $i < $count; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
            continue;
        }
        return $i;
    }
    return false;
}

/**
 * Get the first string argument from a function call.
 * Expects tokens starting from just after the method name.
 *
 * @param array $tokens PHP tokens array
 * @param int $start Starting index (should be at or before '(')
 * @param int $count Total token count
 * @return string|null The extracted string, or null if not found
 */
function apbd_wps_lite_loco_get_first_string_arg($tokens, $start, $count)
{
    // Find opening parenthesis
    $i = $start;
    while ($i < $count) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
            $i++;
            continue;
        }
        if ($tokens[$i] === '(') {
            $i++;
            break;
        }
        return null;
    }

    // Skip whitespace after '('
    while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
        $i++;
    }

    if ($i >= $count) {
        return null;
    }

    // Check for string token (single or double quoted)
    $token = $tokens[$i];
    if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
        $raw = $token[1];
        // Remove quotes and unescape
        $quote = $raw[0];
        $str = substr($raw, 1, -1);
        if ($quote === '"') {
            $str = stripcslashes($str);
        } else {
            $str = str_replace(["\\\\", "\\'"], ["\\", "'"], $str);
        }
        return $str;
    }

    return null;
}

/**
 * Recursively get all PHP files in a directory.
 *
 * @param string $dir Directory path
 * @return array Array of file paths
 */
function apbd_wps_lite_loco_get_php_files($dir)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}
