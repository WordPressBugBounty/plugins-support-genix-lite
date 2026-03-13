<?php

/**
 * Shortcode Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_shortcode_trait
{
    public function initialize__shortcode()
    {
        add_shortcode('sgkb_archive_docs', [$this, 'sgkb_archive_docs']);
        add_shortcode('sgkb_single_docs', [$this, 'sgkb_single_docs']);
    }

    public function sgkb_archive_docs()
    {
        $content = '';

        if (
            is_post_type_archive('sgkb-docs') ||
            is_tax(get_object_taxonomies('sgkb-docs')) ||
            (is_search() && 'sgkb-docs' === get_query_var('post_type'))
        ) {
            ob_start();
            sgkb_get_template_part('archive-docs');
            $content = ob_get_clean();
        }

        return $content;
    }

    public function sgkb_single_docs()
    {
        $content = '';

        if (is_singular('sgkb-docs')) {
            ob_start();
            sgkb_get_template_part('single-docs');
            $content = ob_get_clean();
        }

        return $content;
    }
}
