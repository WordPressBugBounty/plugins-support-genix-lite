<?php

/**
 * Blocks Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_settings_blocks_trait
{
    public function initialize__blocks()
    {
        add_filter('block_categories_all', [$this, 'add_block_category']);
        add_action('init', [$this, 'add_new_blocks']);
    }

    public function add_block_category($categories)
    {
        if (!sgkb_is_fse_theme()) {
            return $categories;
        }

        return array_merge(
            $categories,
            [
                [
                    'slug' => 'support-genix',
                    'title' => __('Support Genix', 'support-genix'),
                    'icon' => null,
                ],
            ]
        );
    }

    public function add_new_blocks()
    {
        if (!sgkb_is_fse_theme()) {
            return;
        }

        if (!WP_Block_Type_Registry::get_instance()->is_registered('support-genix/archive-docs')) {
            register_block_type(
                'support-genix/archive-docs',
                [
                    'editor_script' => 'support-genix-blocks',
                    'editor_style' => 'support-genix-blocks',
                    'render_callback' => [$this, 'render_archive_docs_block'],
                    'attributes' => [],
                    'category' => 'support-genix',
                ]
            );
        }

        if (!WP_Block_Type_Registry::get_instance()->is_registered('support-genix/single-docs')) {
            register_block_type(
                'support-genix/single-docs',
                [
                    'editor_script' => 'support-genix-blocks',
                    'editor_style' => 'support-genix-blocks',
                    'render_callback' => [$this, 'render_single_docs_block'],
                    'attributes' => [],
                    'category' => 'support-genix',
                ]
            );
        }

        $this->register_block_assets();
    }

    public function register_block_assets()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $plugin_file = $coreObject->pluginFile;
        $plugin_url = untrailingslashit(plugins_url('/', $plugin_file));
        $plugin_path = untrailingslashit(plugin_dir_path($plugin_file));
        $plugin_version = $coreObject->pluginVersion;

        $editor_script_url = $plugin_url . '/blocks/blocks.min.js';
        $editor_script_path = $plugin_path . '/blocks/blocks.min.js';
        $editor_script_version = file_exists($editor_script_path) ?
            filemtime($editor_script_path) . '-' . $plugin_version :
            $plugin_version;

        $editor_style_url = $plugin_url . '/blocks/editor.min.css';
        $editor_style_path = $plugin_path . '/blocks/editor.min.css';
        $editor_style_version = file_exists($editor_style_path) ?
            filemtime($editor_style_path) . '-' . $plugin_version :
            $plugin_version;


        wp_register_script(
            'support-genix-blocks',
            $editor_script_url,
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
            $editor_script_version,
            true
        );

        wp_register_style(
            'support-genix-blocks',
            $editor_style_url,
            ['wp-edit-blocks'],
            $editor_style_version
        );
    }

    public function render_archive_docs_block($attributes, $content)
    {
        $content = '';

        if (
            is_post_type_archive('sgkb-docs') ||
            is_tax(get_object_taxonomies('sgkb-docs')) ||
            (is_search() && 'sgkb-docs' === get_query_var('post_type'))
        ) {
            $className = isset($attributes['className']) ? sanitize_text_field($attributes['className']) : '';

            ob_start();
            echo $className ? '<div class="' . esc_attr($className) . '">' : '';
            sgkb_get_template_part('archive-docs');
            echo $className ? '</div>' : '';
            $content = ob_get_clean();
        }

        return $content;
    }

    public function render_single_docs_block($attributes, $content)
    {
        $content = '';

        if (is_singular('sgkb-docs')) {
            $className = isset($attributes['className']) ? sanitize_text_field($attributes['className']) : '';

            ob_start();
            echo $className ? '<div class="' . esc_attr($className) . '">' : '';
            sgkb_get_template_part('single-docs');
            echo $className ? '</div>' : '';
            $content = ob_get_clean();
        }

        return $content;
    }
}
