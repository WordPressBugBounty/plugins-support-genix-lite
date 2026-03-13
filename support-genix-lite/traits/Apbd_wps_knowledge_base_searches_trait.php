<?php

/**
 * Searches Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_searches_trait
{
    public function initialize__searches()
    {
        $this->AddAjaxAction("searches_event", [$this, "searches_event"]);
        $this->AddAjaxAction("searches_top_keywords_data", [$this, "searches_top_keywords_data"]);
        $this->AddAjaxAction("searches_no_result_keywords_data", [$this, "searches_no_result_keywords_data"]);

        add_action('pre_get_posts', [$this, 'searches_pre_get_posts']);
        add_action('sgkb_docs_update_searches_data', [$this, 'update_searches_data'], 10, 2);

        add_action('wp_ajax_sgkb_search', [$this, 'sgkb_search_data']);
        add_action('wp_ajax_nopriv_sgkb_search', [$this, 'sgkb_search_data']);

        // Add handler for tracking popular search clicks
        add_action('wp_ajax_sgkb_track_popular_search', [$this, 'track_popular_search']);
        add_action('wp_ajax_nopriv_sgkb_track_popular_search', [$this, 'track_popular_search']);

        // Add handler for getting popular searches for dropdown
        add_action('wp_ajax_sgkb_get_popular_searches', [$this, 'ajax_get_popular_searches']);
        add_action('wp_ajax_nopriv_sgkb_get_popular_searches', [$this, 'ajax_get_popular_searches']);
    }

    public function searches_pre_get_posts($query)
    {
        if (
            is_admin() ||
            !$query->is_main_query() ||
            !$query->is_search() ||
            !('sgkb-docs' === $query->get('post_type'))
        ) {
            return;
        }

        $keyword = $query->get('s');

        if (0 < strlen($keyword)) {
            $count_query = clone $query;
            $count_query->set('fields', 'ids');
            $count_query->set('posts_per_page', 1);
            $count_query->get_posts();

            $found_count = $count_query->found_posts;
            $found_count = absint($found_count);

            do_action('sgkb_docs_update_searches_data', $keyword, $found_count);
        }

        return $query;
    }

    public function update_searches_data($keyword, $found_count)
    {
        if (!$this->ShouldTrackAnalytics()) {
            return;
        }

        $keyword = sanitize_text_field($keyword);
        $keyword = strtolower($keyword);
        $founded = $found_count ? 'Y' : 'N';

        $current_time = current_time('mysql');
        $current_date = date('Y-m-d', strtotime($current_time));

        $keyword_id = 0;

        $keywordobj = new Mapbd_wps_docs_search_keywords();
        $keywordobj->keyword($keyword);

        if ($keywordobj->Select()) {
            $keyword_id = $keywordobj->id;
        } else {
            $keywordobj = new Mapbd_wps_docs_search_keywords();
            $keywordobj->keyword($keyword);
            $keywordobj->created_at($current_time);

            if ($keywordobj->Save()) {
                $keyword_id = $keywordobj->id;
            }
        }

        if (empty($keyword_id)) {
            return;
        }

        $existsobj = new Mapbd_wps_docs_searches_events();
        $existsobj->keyword_id($keyword_id);
        $existsobj->founded($founded);
        $existsobj->created_date($current_date);

        if ($existsobj->Select()) {
            $updateobj = new Mapbd_wps_docs_searches_events();
            $updateobj->count($existsobj->count + 1);

            $updateobj->SetWhereUpdate('id', $existsobj->id);
            $updateobj->Update();
        } else {
            $createobj = new Mapbd_wps_docs_searches_events();
            $createobj->keyword_id($keyword_id);
            $createobj->founded($founded);
            $createobj->count(1);
            $createobj->created_at($current_time);
            $createobj->created_date($current_date);

            $createobj->Save();
        }
    }

    public function searches_event($keyword)
    {
        $count_args = [
            'post_type' => 'sgkb-docs',
            'posts_per_page' => -1,
            'fields' => 'ids',
            's' => $keyword,
        ];

        $count_query = new WP_Query($count_args);
        $count_query->get_posts();

        $found_count = $count_query->found_posts;
        $found_count = absint($found_count);

        $this->update_searches_data($keyword, $found_count);
    }

    public function searches_top_keywords_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 5);
        $limitStart = ($limit * ($page - 1));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];

        $keywordsobj = new Mapbd_wps_docs_search_keywords();
        $keywords_table = $keywordsobj->GetTableName();

        $eventosbj = new Mapbd_wps_docs_searches_events();
        $events_table = $eventosbj->GetTableName();

        global $wpdb;

        $sql = "
            SELECT k.id, k.keyword, SUM(e.count) as count
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'Y' AND e.created_date BETWEEN %s AND %s
            GROUP BY k.id, k.keyword
            ORDER BY count DESC, k.id DESC
            LIMIT %d, %d
        ";
        $result = $wpdb->get_results($wpdb->prepare($sql, $date_start, $date_ended, $limitStart, $limit));

        $sql_total = "
            SELECT COUNT(DISTINCT k.id) as total
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'Y' AND e.created_date BETWEEN %s AND %s
        ";
        $total = $wpdb->get_var($wpdb->prepare($sql_total, $date_start, $date_ended));

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => absint($total),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function searches_no_result_keywords_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 5);
        $limitStart = ($limit * ($page - 1));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];

        $keywordsobj = new Mapbd_wps_docs_search_keywords();
        $keywords_table = $keywordsobj->GetTableName();

        $eventosbj = new Mapbd_wps_docs_searches_events();
        $events_table = $eventosbj->GetTableName();

        global $wpdb;

        $sql = "
            SELECT k.id, k.keyword, SUM(e.count) as count
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'N' AND e.created_date BETWEEN %s AND %s
            GROUP BY k.id, k.keyword
            ORDER BY count DESC, k.id DESC
            LIMIT %d, %d
        ";
        $result = $wpdb->get_results($wpdb->prepare($sql, $date_start, $date_ended, $limitStart, $limit));

        $sql_total = "
            SELECT COUNT(DISTINCT k.id) as total
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'N' AND e.created_date BETWEEN %s AND %s
        ";
        $total = $wpdb->get_var($wpdb->prepare($sql_total, $date_start, $date_ended));

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => absint($total),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function sgkb_search_data()
    {
        $result = '';

        $query = sanitize_text_field(ApbdWps_PostValue("query"));

        if (0 < strlen($query)) {
            $docs_args = array(
                'post_type' => 'sgkb-docs',
                'post_status' => 'publish',
                'orderby' => 'relevance',
                'posts_per_page' => 10,
                's' => $query,
                'sgkb_search' => true,
            );

            $docs_args['meta_query'] = array(
                array(
                    'key' => 'only_for_chatbot',
                    'compare' => 'NOT EXISTS'
                )
            );

            $docsQuery = new WP_Query($docs_args);
            $posts = $docsQuery->posts;
            $found_count = $docsQuery->found_posts;

            if (is_array($posts) && !empty($posts)) {
                // Add search header with count
                $post_count = count($posts);
                $result .= '<div class="sgkb-hero-search-header">';
                $result .= '<span class="sgkb-hero-search-count">';
                $result .= sprintf(
                    esc_html(_n('Found %d result', 'Found %d results', $post_count, 'support-genix')),
                    $post_count
                );
                $result .= '</span>';
                $result .= '</div>';

                $result .= '<div class="sgkb-hero-search-articles">';

                foreach ($posts as $post) {
                    $post_id = absint($post->ID);

                    $categories = get_the_terms($post->ID, 'sgkb-docs-category');
                    $category = ($categories && !is_wp_error($categories) && !empty($categories)) ? $categories[0] : null;

                    $result .= '<article class="sgkb-hero-search-article">';
                    $result .= '<a class="sgkb-hero-search-article-link" href="' . esc_url(get_the_permalink($post_id)) . '">';
                    $result .= '<div class="sgkb-hero-search-article-inner">';
                    $result .= '<div class="sgkb-hero-search-article-body">';

                    if ($category) {
                        $category_name = $category->name;

                        $result .= '<div class="sgkb-hero-search-article-category">';
                        $result .= '<span class="sgkb-hero-search-article-category-title">' . esc_html($category_name) . '</span>';
                        $result .= '</div>';
                    }

                    $result .= '<h3 class="sgkb-hero-search-article-title">' . esc_html($post->post_title) . '</h3>';

                    // Get excerpt with smart generation
                    $excerpt = get_the_excerpt($post_id);
                    if (empty($excerpt) || $excerpt == $post->post_title) {
                        $content = get_post_field('post_content', $post_id);
                        $content = strip_shortcodes($content);
                        $content = wp_strip_all_tags($content);
                        $content = preg_replace('/\s+/', ' ', $content);
                        $excerpt = wp_trim_words($content, 20, '...');
                    }
                    if (empty($excerpt)) {
                        $excerpt = __('No description available for this article.', 'support-genix');
                    }

                    $result .= '<div class="sgkb-hero-search-article-excerpt">' . esc_html($excerpt) . '</div>';

                    $result .= '</div>';

                    $result .= '</div>';
                    $result .= '</a>';
                    $result .= '</article>';
                }

                $result .= '</div>';
            } else {
                $result .= '<div class="sgkb-hero-search-empty">' . __('No results found!', 'support-genix') . '</div>';
            }

            do_action('sgkb_docs_update_searches_data', $query, $found_count);
        }

        wp_send_json_success($result);
    }

    /**
     * Track clicks on popular search tags
     */
    public function track_popular_search()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';

        if (empty($keyword)) {
            wp_send_json_error('No keyword provided');
            return;
        }

        // Check if we should track analytics
        if (!$this->ShouldTrackAnalytics()) {
            wp_send_json_success('Analytics tracking disabled');
            return;
        }

        // Trigger a search event to increment the count
        // We'll consider popular search clicks as successful searches
        $this->update_searches_data($keyword, 1);

        wp_send_json_success('Popular search tracked');
    }

    /**
     * AJAX handler to get popular searches for dropdown
     */
    public function ajax_get_popular_searches()
    {
        // Get popular searches using the module method
        $kb_module = Apbd_wps_knowledge_base::GetModuleInstance();
        $popular_searches = $kb_module->get_popular_searches(8);

        // Format the response
        $searches = array();
        if (!empty($popular_searches)) {
            foreach ($popular_searches as $search) {
                $searches[] = $search->keyword;
            }
        }

        // If no searches found, return some defaults
        if (empty($searches)) {
            $searches = array(
                __('Getting started', 'support-genix'),
                __('Installation', 'support-genix'),
                __('Account setup', 'support-genix'),
                __('Troubleshooting', 'support-genix')
            );
        }

        wp_send_json_success($searches);
    }
}
