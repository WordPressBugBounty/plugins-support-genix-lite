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
            'home_text' => __('Home', 'support-genix-lite'),
            'docs_text' => __('Documentation', 'support-genix-lite'),
            'separator' => ' / ',
            'show_current' => true,
        );

        $args = wp_parse_args($args, $defaults);
        $context = sgkb_get_current_context();

        echo '<nav class="sgkb-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'support-genix-lite') . '">';
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

if (!function_exists('sgkb_get_chatbot_only_ids')) {
    function sgkb_get_chatbot_only_ids()
    {
        static $ids = null;

        if (null !== $ids) {
            return $ids;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single indexed lookup, cached in a static for the request.
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                'only_for_chatbot',
                '1'
            )
        );

        $ids = array_values(array_unique(array_map('absint', (array) $rows)));

        return $ids;
    }
}

if (!function_exists('sgkb_exclude_chatbot_only')) {
    function sgkb_exclude_chatbot_only($args = array())
    {
        $ids = sgkb_get_chatbot_only_ids();

        if (empty($ids)) {
            return $args;
        }

        if (!empty($args['post__in'])) {
            $remaining = array_values(array_diff(array_map('absint', (array) $args['post__in']), $ids));
            $args['post__in'] = !empty($remaining) ? $remaining : array(0);

            return $args;
        }

        $exclude = isset($args['post__not_in']) && is_array($args['post__not_in'])
            ? array_merge($args['post__not_in'], $ids)
            : $ids;

        $args['post__not_in'] = array_values(array_unique(array_map('absint', $exclude)));

        return $args;
    }
}

if (!function_exists('sgkb_get_docs_grouped_by_term')) {
    function sgkb_get_docs_grouped_by_term($term_ids, $taxonomy, $per_term = 0, $args = array(), $include_children = false, $exclude_mode = 'ids')
    {
        $term_ids = array_values(array_unique(array_filter(array_map('absint', (array) $term_ids))));

        $buckets = array();
        foreach ($term_ids as $term_id) {
            $buckets[$term_id] = array();
        }

        if (empty($term_ids)) {
            return $buckets;
        }

        $defaults = array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'update_post_term_cache' => true,
            'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bucketing helper, single taxonomy clause.
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_ids,
                    'include_children' => (bool) $include_children,
                ),
            ),
        );

        $extra_tax = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : array();
        unset($args['tax_query']);

        $query_args = array_merge($defaults, $args);

        if (!empty($extra_tax)) {
            $tax_query = $query_args['tax_query'];

            foreach ($extra_tax as $key => $clause) {
                if ('relation' === $key || !is_array($clause)) {
                    continue;
                }

                $tax_query[] = $clause;
            }

            $tax_query['relation'] = 'AND';
            $query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- bucketing helper.
        }

        if ('not_exists' === $exclude_mode) {
            $meta_query = isset($query_args['meta_query']) && is_array($query_args['meta_query']) ? $query_args['meta_query'] : array();
            $meta_query[] = array(
                'key' => 'only_for_chatbot',
                'compare' => 'NOT EXISTS',
            );
            $query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- single keyed clause, no OR group.
        } else {
            $query_args = sgkb_exclude_chatbot_only($query_args);
        }

        $query = new WP_Query($query_args);

        if (empty($query->posts)) {
            return $buckets;
        }

        // `fields => ids` yields plain IDs; anything else yields WP_Post objects.
        $is_id_list = isset($query_args['fields']) && 'ids' === $query_args['fields'];
        $post_ids = $is_id_list ? array_map('absint', $query->posts) : wp_list_pluck($query->posts, 'ID');

        $by_post = array();

        if ($is_id_list) {
            // No post objects, so nothing primed the object term cache.
            $relations = wp_get_object_terms($post_ids, $taxonomy, array('fields' => 'all_with_object_id'));

            if (is_wp_error($relations)) {
                return $buckets;
            }

            foreach ($relations as $relation) {
                $by_post[(int) $relation->object_id][] = (int) $relation->term_id;
            }
        } else {
            // WP_Query already primed the object term cache in one query; read it.
            foreach ($post_ids as $post_id) {
                $terms = get_the_terms($post_id, $taxonomy);

                if (is_wp_error($terms) || empty($terms)) {
                    continue;
                }

                $by_post[$post_id] = wp_list_pluck($terms, 'term_id');
            }
        }

        $descendants = array();
        if ($include_children) {
            foreach ($term_ids as $term_id) {
                $children = get_term_children($term_id, $taxonomy);
                $descendants[$term_id] = is_wp_error($children) ? array() : array_map('absint', $children);
            }
        }

        foreach ($query->posts as $post) {
            $post_id = $is_id_list ? (int) $post : (int) $post->ID;

            if (empty($by_post[$post_id])) {
                continue;
            }

            $post_terms = $by_post[$post_id];

            foreach ($term_ids as $term_id) {
                $matches = in_array($term_id, $post_terms, true);

                if (!$matches && !empty($descendants[$term_id])) {
                    $matches = (bool) array_intersect($descendants[$term_id], $post_terms);
                }

                if (!$matches) {
                    continue;
                }

                if ($per_term > 0 && count($buckets[$term_id]) >= $per_term) {
                    continue;
                }

                $buckets[$term_id][] = $post;
            }
        }

        return $buckets;
    }
}

if (!function_exists('sgkb_get_term_doc_counts')) {
    function sgkb_get_term_doc_counts($term_ids, $taxonomy, $include_children = false, $exclude_mode = 'ids', $args = array())
    {
        $args = is_array($args) ? $args : array();
        $args['fields'] = 'ids';

        $buckets = sgkb_get_docs_grouped_by_term($term_ids, $taxonomy, 0, $args, $include_children, $exclude_mode);

        $counts = array();
        foreach ($buckets as $term_id => $posts) {
            $counts[$term_id] = count($posts);
        }

        return $counts;
    }
}
