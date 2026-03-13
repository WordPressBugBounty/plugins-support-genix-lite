<?php

/**
 * Single Article Page - Modern Layout Only
 */

defined('ABSPATH') || exit;

if (!sgkb_is_fse_theme()) {
    get_header();
}

// Include the modern article template
include plugin_dir_path(__FILE__) . 'single/modern-article.php';

if (!sgkb_is_fse_theme()) {
    get_footer();
}
