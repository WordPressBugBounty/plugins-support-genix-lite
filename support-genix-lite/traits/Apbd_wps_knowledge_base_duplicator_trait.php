<?php

/**
 * Duplicator Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_duplicator_trait
{
    public function initialize__duplicator()
    {
        $this->AddAjaxAction("docs_duplicate_item", [$this, "docs_duplicate_item"]);
    }

    public function docs_duplicate_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $duplicate_post = $this->duplicate_post($param_id);

            if (!is_wp_error($duplicate_post) && $duplicate_post) {
                $apiResponse->SetResponse(true, $this->__('Successfully duplicated.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function duplicate_post($original_id)
    {
        global $wpdb;

        $appended = esc_html__('Copy', 'post-duplicator');
        $timestamp = current_time('timestamp', 0);
        $timestamp_gmt = current_time('timestamp', 1);

        // Duplicate post.
        $duplicate = get_post($original_id, 'ARRAY_A');

        // Post title.
        $post_title = sanitize_text_field($duplicate['post_title']);
        $post_title = "{$post_title} {$appended}";

        // Post name.
        $post_name = sanitize_title($duplicate['post_name']);
        $post_name = "{$post_name}-copy";

        // Modify post elements.
        $duplicate['post_title'] = $post_title;
        $duplicate['post_name'] = $post_name;
        $duplicate['post_status'] = 'draft';
        $duplicate['post_author'] = get_current_user_id();
        $duplicate['post_date'] = date('Y-m-d H:i:s', $timestamp);
        $duplicate['post_date_gmt'] = date('Y-m-d H:i:s', $timestamp_gmt);
        $duplicate['post_modified'] = date('Y-m-d H:i:s', $timestamp);
        $duplicate['post_modified_gmt'] = date('Y-m-d H:i:s', $timestamp_gmt);

        // Remove post elements.
        unset($duplicate['ID']);
        unset($duplicate['guid']);
        unset($duplicate['comment_count']);

        // Modified allowed html.
        add_filter('wp_kses_allowed_html', [$this, 'additional_kses'], 10, 2);
        $duplicate['post_content'] = wp_slash(wp_kses_post($duplicate['post_content']));
        remove_filter('wp_kses_allowed_html', [$this, 'additional_kses'], 10, 2);

        // Insert the post.
        $duplicate_id = wp_insert_post($duplicate);

        // Duplicate taxonomies.
        $taxonomies = get_object_taxonomies($duplicate['post_type']);
        $disabled_taxonomies = ['post_translations'];
        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $disabled_taxonomies)) {
                continue;
            }

            $terms = wp_get_post_terms($original_id, $taxonomy, array(
                'fields' => 'names'
            ));

            wp_set_object_terms($duplicate_id, $terms, $taxonomy);
        }

        // Duplicate custom fields.
        $custom_fields = get_post_custom($original_id);
        foreach ($custom_fields as $key => $value) {
            if (is_array($value) && count($value) > 0) {
                foreach ($value as $i => $v) {
                    if (! apply_filters("mtphr_post_duplicator_meta_{$key}_enabled", true)) {
                        continue;
                    }

                    $meta_value = apply_filters("mtphr_post_duplicator_meta_value", $v, $key, $duplicate_id, $duplicate['post_type']);

                    $data = array(
                        'post_id' => intval($duplicate_id),
                        'meta_key' => sanitize_text_field($key),
                        'meta_value' => $meta_value,
                    );

                    $formats = array(
                        '%d',
                        '%s',
                        '%s',
                    );

                    $wpdb->insert($wpdb->prefix . 'postmeta', $data, $formats);
                }
            }
        }

        // Hook to execute after insert post.
        do_action('sgkb_after_insert_post', $duplicate_id);

        return $duplicate_id;
    }

    public function  additional_kses($allowed_tags)
    {
        $allowed_tags['center'] = array(
            'id' => true,
            'class' => true,
            'style' => true,
            'align' => true,
        );

        return $allowed_tags;
    }
}
