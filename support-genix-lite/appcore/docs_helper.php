<?php

/**
 * Docs helper.
 */

defined('ABSPATH') || exit;

if (!function_exists("ApbdWps_GetSvgIcon")) {
    function ApbdWps_GetSvgIcon($slug = '', $class = '')
    {
        if (empty($slug)) {
            return '';
        }

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $plugin_file = $coreObject->pluginFile;
        $plugin_path = untrailingslashit(plugin_dir_path($plugin_file));

        $svg_path = "{$plugin_path}/assets/icons/{$slug}.svg";

        if (!file_exists($svg_path)) {
            return '';
        }

        $svg_content = file_get_contents($svg_path);

        if ($svg_content === false) {
            return '';
        }

        $svg_content = preg_replace('/<\?xml.*?\?>/', '', $svg_content);
        $svg_content = str_replace('<svg', '<svg class="sgkb-icon sgkb-icon-' . esc_attr($slug) . ' ' . trim(esc_attr($class)) . '"', $svg_content);

        return $svg_content;
    }
}

if (!function_exists("ApbdWps_GetAssetsUrl")) {
    function ApbdWps_GetAssetsUrl($slug = '')
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $plugin_file = $coreObject->pluginFile;
        $plugin_url = untrailingslashit(plugin_dir_url($plugin_file));
        $assets_url = "{$plugin_url}/assets";

        if ($slug) {
            $assets_url = "{$assets_url}/{$slug}";
        }

        return $assets_url;
    }
}

if (!function_exists("ApbdWps_GetBgFromColor")) {
    function ApbdWps_GetBgFromColor($hex_color, $opacity = 0.1)
    {
        $hex_color = ltrim($hex_color, '#');

        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));

        return "rgba($r, $g, $b, $opacity)";
    }
}

if (!function_exists("ApbdWps_GetLightenColor")) {
    function ApbdWps_GetLightenColor($hex_color, $percent = 90)
    {
        $hex_color = ltrim($hex_color, '#');

        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));

        $r = (int)($r + (255 - $r) * $percent / 100);
        $g = (int)($g + (255 - $g) * $percent / 100);
        $b = (int)($b + (255 - $b) * $percent / 100);

        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}

if (!function_exists("ApbdWps_ArrayToLinearGradient")) {
    function ApbdWps_ArrayToLinearGradient($colors, $direction = '135deg')
    {
        if (empty($colors) || !is_array($colors)) {
            return '';
        }

        usort($colors, function ($a, $b) {
            return $a['percent'] - $b['percent'];
        });

        $color_stops = array();

        foreach ($colors as $color_data) {
            $color = $color_data['color'];
            $percent = $color_data['percent'];
            $color_stops[] = "{$color} {$percent}%";
        }

        $gradient = 'linear-gradient(' . $direction . ', ' . implode(', ', $color_stops) . ')';

        return $gradient;
    }
}

/**
 * Get current knowledge base context (Lite version - no Spaces support)
 *
 * Returns information about the current page context including:
 * - type: The page type (category, tag, single, search, main_archive)
 * - space_id: Always null in lite (no Spaces support)
 * - space_slug: Always null in lite (no Spaces support)
 * - category_id: Current category term ID (if applicable)
 * - tag_id: Current tag term ID (if applicable)
 * - multiple_kb_enabled: Always false in lite
 *
 * @return array Context information
 * @since 1.4.34
 */
if (!function_exists("sgkb_get_current_context")) {
    function sgkb_get_current_context()
    {
        $context = array(
            'type' => 'main_archive',
            'space_id' => null,
            'space_slug' => null,
            'category_id' => null,
            'tag_id' => null,
            'multiple_kb_enabled' => false, // Always false in lite
        );

        if (is_tax('sgkb-docs-category')) {
            $term = get_queried_object();
            $context['type'] = 'category';
            $context['category_id'] = $term ? $term->term_id : null;
        } elseif (is_tax('sgkb-docs-tag')) {
            $term = get_queried_object();
            $context['type'] = 'tag';
            $context['tag_id'] = $term ? $term->term_id : null;
        } elseif (is_singular('sgkb-docs')) {
            $context['type'] = 'single';
        } elseif (is_search()) {
            $context['type'] = 'search';
        }

        return $context;
    }
}

if (!function_exists("sgkb_render_breadcrumbs")) {
    function sgkb_render_breadcrumbs($args = array())
    {
        global $post;

        $defaults = array(
            'show_home' => true,
            'home_text' => __('Home', 'support-genix'),
            'docs_text' => __('Documentation', 'support-genix'),
            'separator' => ' / ',
            'show_current' => true,
        );

        $args = wp_parse_args($args, $defaults);
        $context = sgkb_get_current_context();

        echo '<nav class="sgkb-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'support-genix') . '">';
        echo '<ol class="sgkb-breadcrumb-list">';

        // Home link
        if ($args['show_home']) {
            echo '<li class="sgkb-breadcrumb-item">';
            echo '<a href="' . esc_url(home_url('/')) . '" class="sgkb-breadcrumb-link">' . esc_html($args['home_text']) . '</a>';
            echo '</li>';
        }

        // Documentation link
        $docs_base_url = get_post_type_archive_link('sgkb-docs');
        if ($docs_base_url) {
            echo '<li class="sgkb-breadcrumb-item">';
            echo '<span class="sgkb-breadcrumb-separator">' . esc_html($args['separator']) . '</span>';

            // If we're on the main docs archive, don't link it
            if ($context['type'] === 'main_archive') {
                echo '<span class="sgkb-breadcrumb-current">' . esc_html($args['docs_text']) . '</span>';
            } else {
                echo '<a href="' . esc_url($docs_base_url) . '" class="sgkb-breadcrumb-link">' . esc_html($args['docs_text']) . '</a>';
            }
            echo '</li>';
        }

        // Category link (for archives or single posts)
        $category = null;

        // For single posts, get category directly from the post
        if ($context['type'] === 'single') {
            $post_id = $post ? $post->ID : get_the_ID();
            if ($post_id) {
                $categories = wp_get_object_terms($post_id, 'sgkb-docs-category');
                if ($categories && !is_wp_error($categories) && !empty($categories)) {
                    $category = $categories[0];
                }
            }
        }
        // For archives, use category from context
        elseif ($context['category_id']) {
            $category = get_term($context['category_id'], 'sgkb-docs-category');
        }

        if ($category && !is_wp_error($category)) {
            echo '<li class="sgkb-breadcrumb-item">';
            echo '<span class="sgkb-breadcrumb-separator">' . esc_html($args['separator']) . '</span>';

            // Build category URL (regular category link in lite - no space context)
            $category_url = get_term_link($category);

            // If we're on the category archive, don't link it
            if ($context['type'] === 'category') {
                echo '<span class="sgkb-breadcrumb-current">' . esc_html($category->name) . '</span>';
            } else {
                echo '<a href="' . esc_url($category_url) . '" class="sgkb-breadcrumb-link">' . esc_html($category->name) . '</a>';
            }
            echo '</li>';
        }

        // Current post (if on single page)
        if ($context['type'] === 'single' && is_singular('sgkb-docs') && $args['show_current']) {
            echo '<li class="sgkb-breadcrumb-item">';
            echo '<span class="sgkb-breadcrumb-separator">' . esc_html($args['separator']) . '</span>';
            echo '<span class="sgkb-breadcrumb-current">' . esc_html(get_the_title()) . '</span>';
            echo '</li>';
        }

        // Tag (if in tag context)
        if ($context['type'] === 'tag' && $context['tag_id']) {
            $tag = get_term($context['tag_id'], 'sgkb-docs-tag');
            if ($tag && !is_wp_error($tag)) {
                echo '<li class="sgkb-breadcrumb-item">';
                echo '<span class="sgkb-breadcrumb-separator">' . esc_html($args['separator']) . '</span>';
                echo '<span class="sgkb-breadcrumb-current">' . esc_html($tag->name) . '</span>';
                echo '</li>';
            }
        }

        echo '</ol>';
        echo '</nav>';
    }
}
