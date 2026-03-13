<?php

/**
 * Template functions.
 */

defined('ABSPATH') || exit;

if (!function_exists('sgkb_is_fse_theme')) {
    function sgkb_is_fse_theme()
    {
        if (function_exists('wp_is_block_theme')) {
            return wp_is_block_theme();
        }

        $theme_dir = trailingslashit(get_template_directory());

        $required_templates = array(
            'templates/index.html',
            'block-templates/index.html',
        );

        foreach ($required_templates as $template) {
            if (file_exists($theme_dir . $template)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('sgkb_get_template_part')) {
    function sgkb_get_template_part($slug, $name = '')
    {
        $template = '';

        // Load from theme.
        if ($name) {
            $template = locate_template(array(
                "support-genix/docs/{$slug}-{$name}.php",
                "support-genix/docs/{$slug}.php"
            ));
        } else {
            $template = locate_template(array(
                "support-genix/docs/{$slug}.php"
            ));
        }

        // Load from plugin.
        if (!$template) {
            $coreObject = ApbdWps_SupportLite::GetInstance();
            $plugin_path = untrailingslashit(plugin_dir_path($coreObject->pluginFile));
            $locate_base = "{$plugin_path}/templates/docs";

            if ($name) {
                if (file_exists("{$locate_base}/{$slug}-{$name}.php")) {
                    $template = "{$locate_base}/{$slug}-{$name}.php";
                } else if (file_exists("{$locate_base}/{$slug}.php")) {
                    $template = "{$locate_base}/{$slug}.php";
                }
            } else {
                if (file_exists("{$locate_base}/{$slug}.php")) {
                    $template = "{$locate_base}/{$slug}.php";
                }
            }
        }

        $template = apply_filters('sgkb_get_template_part', $template, $slug, $name);

        if ($template) {
            load_template($template, false);
        }
    }
}
