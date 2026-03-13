<?php

/**
 * Analytics Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_analytics_trait
{
    public function initialize__analytics()
    {
        $this->AddAjaxAction("analytics_reactions", [$this, "analytics_reactions"]);
        $this->AddAjaxAction("analytics_top_docs_data", [$this, "analytics_top_docs_data"]);

        add_action('template_redirect', [$this, 'set_analytics_cookies']);
        add_action('wp_head', [$this, 'update_analytics_data']);

        add_action('wp_ajax_sgkb_reaction', [$this, 'sgkb_add_reaction']);
        add_action('wp_ajax_nopriv_sgkb_reaction', [$this, 'sgkb_add_reaction']);
    }

    public function set_analytics_cookies()
    {
        if (!$this->ShouldTrackAnalytics()) {
            return;
        }

        if (!is_singular('sgkb-docs')) {
            return;
        }

        $post_id = get_the_ID();

        if (!isset($_COOKIE["sgkb_docs_visited_{$post_id}"])) {
            setcookie("sgkb_docs_visited_{$post_id}", '1', time() + (86400 * 180), "/");
        }
    }

    public function update_analytics_data()
    {
        $success = false;

        if (!$this->ShouldTrackAnalytics()) {
            return $success;
        }

        global $post_type, $post;

        if (('sgkb-docs' !== $post_type) || !is_singular('sgkb-docs')) {
            return $success;
        }

        if (wp_is_post_revision($post) || is_preview()) {
            return $success;
        }

        $post_id = isset($post->ID) ? absint($post->ID) : 0;

        if (!$post_id) {
            return $success;
        }

        $should_count = $this->should_count_views();

        if (!$should_count) {
            return $success;
        }

        $current_time = current_time('mysql');
        $current_date = date('Y-m-d', strtotime($current_time));

        $is_unique_view = !isset($_COOKIE["sgkb_docs_visited_{$post_id}"]);

        $existsobj = new Mapbd_wps_docs_analytics();
        $existsobj->post_id($post_id);
        $existsobj->created_date($current_date);

        if ($existsobj->Select()) {
            $new_count = absint($existsobj->views) + 1;
            $updateobj = new Mapbd_wps_docs_analytics();
            $updateobj->views($new_count);
            $updateobj->unique_views($is_unique_view ? $existsobj->unique_views + 1 : $existsobj->unique_views);

            $updateobj->SetWhereUpdate('id', $existsobj->id);

            if ($updateobj->Update()) {
                $success = true;
            }
        } else {
            $createobj = new Mapbd_wps_docs_analytics();
            $createobj->post_id($post_id);
            $createobj->views(1);
            $createobj->unique_views($is_unique_view ? 1 : 0);
            $createobj->created_at($current_time);
            $createobj->created_date($current_date);

            if ($createobj->Save()) {
                $success = true;
            }
        }

        return $success;
    }

    public function sgkb_add_reaction()
    {
        $post_id = absint(ApbdWps_PostValue('post_id'));
        $reaction = sanitize_text_field(ApbdWps_PostValue('reaction'));

        $success = $this->store_analytics_reaction($post_id, $reaction);
        $message = $this->__('Thanks for your feedback!');

        if (!$success) {
            $message = $this->__('Something went wrong, please try again.');
        }

        wp_send_json_success($message);
    }

    public function should_count_views()
    {
        $result = true;

        $bot_list = [
            'Alex' => 'ia_archiver',
            'AllTheWeb' => 'fast-webcrawler',
            'Altavista' => 'scooter',
            'Ask Jeeves' => 'jeeves',
            'Baidu' => 'baiduspider',
            'Become.com' => 'become.com',
            'BlogSearch' => 'blogsearch',
            'Bloglines' => 'bloglines',
            'Findexa' => 'findexa',
            'Gais' => 'gaisbo',
            'Gigabot' => 'gigabot',
            'Google Bot' => 'google',
            'Inktomi' => 'slurp@inktomi',
            'Lycos' => 'lycos',
            'MSN' => 'msnbot',
            'NextLinks' => 'findlinks',
            'PubSub' => 'pubsub',
            'RadioUserland' => 'userland',
            'Sogou' => 'spider',
            'Syndic8' => 'syndic8',
            'Technorati' => 'technorati',
            'Turnitin.com' => 'turnitinbot',
            'WhoisSource' => 'surveybot',
            'WiseNut' => 'zyborg',
            'Yahoo' => 'yahoo',
            'Yandex' => 'yandex',
            'so.com' => '360spider',
            'soso.com' => 'sosospider'
        ];

        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        if (!empty($useragent)) {
            foreach ($bot_list as $bot_name => $bot_key) {
                if (false !== stripos($useragent, $bot_key)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    public function store_analytics_reaction($post_id, $reaction)
    {
        $success = false;

        $post_id = absint($post_id);
        $reaction = sanitize_text_field($reaction);

        $post_type = get_post_type($post_id);
        $post_status = get_post_status($post_id);

        if (
            ('sgkb-docs' !== $post_type) ||
            !in_array($post_status, ['publish', 'private', 'inherit']) ||
            !in_array($reaction, ['positive', 'negative', 'neutral'])
        ) {
            return $success;
        }

        $current_time = current_time('mysql');
        $current_date = date('Y-m-d', strtotime($current_time));

        $existsobj = new Mapbd_wps_docs_analytics();
        $existsobj->post_id($post_id);
        $existsobj->created_date($current_date);

        if ($existsobj->Select()) {
            $new_count = absint($existsobj->$reaction) + 1;
            $updateobj = new Mapbd_wps_docs_analytics();
            $updateobj->$reaction($new_count);

            $new_positive = ($reaction === 'positive') ? $new_count : absint($existsobj->positive);
            $new_negative = ($reaction === 'negative') ? $new_count : absint($existsobj->negative);
            $updateobj->score($this->analytics_compute_score($new_positive, $new_negative));

            $updateobj->SetWhereUpdate('id', $existsobj->id);

            if ($updateobj->Update()) {
                $success = true;
            }
        } else {
            $createobj = new Mapbd_wps_docs_analytics();
            $createobj->post_id($post_id);
            $createobj->$reaction(1);
            $createobj->created_at($current_time);
            $createobj->created_date($current_date);
            $createobj->score($this->analytics_compute_score(
                ($reaction === 'positive') ? 1 : 0,
                ($reaction === 'negative') ? 1 : 0
            ));

            if ($createobj->Save()) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Get analytics reactions data for dashboard
     */
    public function analytics_reactions()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $date_start = sanitize_text_field(ApbdWps_GetValue("date_start"));
        $date_ended = sanitize_text_field(ApbdWps_GetValue("date_ended"));
        $category_ids = sanitize_text_field(ApbdWps_GetValue("category"));
        $tag_ids = sanitize_text_field(ApbdWps_GetValue("tag"));
        $author_ids = sanitize_text_field(ApbdWps_GetValue("author"));

        // Get date range data
        $dateRange = $this->prepareDateRangeForAnalytics($date_start, $date_ended);

        // Query current period
        $current = $this->getReactionsForPeriod(
            $dateRange['date_start'],
            $dateRange['date_ended'],
            $category_ids,
            $tag_ids,
            $author_ids
        );

        // Query previous period
        $previous = $this->getReactionsForPeriod(
            $dateRange['prev_date_start'],
            $dateRange['prev_date_ended'],
            $category_ids,
            $tag_ids,
            $author_ids
        );

        $data = [
            'current' => $current,
            'previous' => $previous
        ];

        // $apiResponse->AddData("data", $data);
        // $apiResponse->SetSuccess();
        // $apiResponse->Display();
        die();
    }

    /**
     * Get reactions data for a specific period
     */
    private function getReactionsForPeriod($start_date, $end_date, $category_ids = '', $tag_ids = '', $author_ids = '')
    {
        global $wpdb;

        // Base query
        $query = "
            SELECT
                SUM(da.positive) as positive,
                SUM(da.negative) as negative,
                SUM(da.neutral) as neutral
            FROM {$wpdb->prefix}apbd_wps_docs_analytics da
            INNER JOIN {$wpdb->posts} p ON da.post_id = p.ID
            WHERE p.post_type = 'sgkb-docs'
            AND p.post_status = 'publish'
            AND da.created_date BETWEEN %s AND %s
        ";

        $query_params = [$start_date, $end_date];

        // Add category filter
        if (!empty($category_ids)) {
            $category_ids = array_map('absint', explode(',', $category_ids));
            $category_placeholders = implode(',', array_fill(0, count($category_ids), '%d'));

            $query .= " AND p.ID IN (
                SELECT object_id
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = 'sgkb-docs-category'
                AND tt.term_id IN ({$category_placeholders})
            )";

            $query_params = array_merge($query_params, $category_ids);
        }

        // Add tag filter
        if (!empty($tag_ids)) {
            $tag_ids = array_map('absint', explode(',', $tag_ids));
            $tag_placeholders = implode(',', array_fill(0, count($tag_ids), '%d'));

            $query .= " AND p.ID IN (
                SELECT object_id
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = 'sgkb-docs-tag'
                AND tt.term_id IN ({$tag_placeholders})
            )";

            $query_params = array_merge($query_params, $tag_ids);
        }

        // Add author filter
        if (!empty($author_ids)) {
            $author_ids = array_map('absint', explode(',', $author_ids));
            $author_placeholders = implode(',', array_fill(0, count($author_ids), '%d'));

            $query .= " AND p.post_author IN ({$author_placeholders})";
            $query_params = array_merge($query_params, $author_ids);
        }

        // Use call_user_func_array to pass array values as individual arguments to prepare()
        if (!empty($query_params)) {
            $prepared_query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($query), $query_params));
        } else {
            $prepared_query = $query;
        }
        $result = $wpdb->get_row($prepared_query);

        $positive = absint($result->positive ?? 0);
        $negative = absint($result->negative ?? 0);
        $neutral = absint($result->neutral ?? 0);
        $total = $positive + $negative + $neutral;

        // Calculate score (percentage of positive reactions)
        $score = $total > 0 ? round(($positive / $total) * 100) : 0;

        return [
            'positive' => $positive,
            'negative' => $negative,
            'neutral' => $neutral,
            'total' => $total,
            'score' => $score
        ];
    }

    public function analytics_top_docs_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $category_ids = sanitize_text_field(ApbdWps_GetValue("category"));
        $tag_ids = sanitize_text_field(ApbdWps_GetValue("tag"));
        $author_ids = sanitize_text_field(ApbdWps_GetValue("author"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 5);
        $limitStart = ($limit * ($page - 1));

        $category_ids = array_filter(array_unique(array_map('absint', explode(",", $category_ids))));
        $tag_ids = array_filter(array_unique(array_map('absint', explode(",", $tag_ids))));
        $author_ids = array_filter(array_unique(array_map('absint', explode(",", $author_ids))));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];

        $docs_args = array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $limitStart,
            'orderby' => 'analytics_views',
            'order' => 'DESC',
            'analytics_join' => true,
            'analytics_date_range' => [
                'start' => $date_start,
                'ended' => $date_ended,
            ],
        );

        $taxq_args = [];

        if (!empty($category_ids)) {
            if (in_array(999999, $category_ids, true)) {
                $taxq_args[] = [
                    'relation' => 'OR',
                    [
                        'taxonomy' => 'sgkb-docs-category',
                        'field' => 'term_id',
                        'terms' => $category_ids,
                        'operator' => 'IN',
                    ],
                    [
                        'taxonomy' => 'sgkb-docs-category',
                        'operator' => 'NOT EXISTS',
                    ],
                ];
            } else {
                $taxq_args[] = [
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'term_id',
                    'terms' => $category_ids,
                    'operator' => 'IN',
                ];
            }
        }

        if (!empty($tag_ids)) {
            $taxq_args[] = [
                'taxonomy' => 'sgkb-docs-tag',
                'field' => 'term_id',
                'terms' => $tag_ids,
                'operator' => 'IN',
            ];
        }

        if (!empty($author_ids)) {
            $docs_args['author__in'] = $author_ids;
        }

        if (!empty($taxq_args)) {
            $docs_args['tax_query'] = $taxq_args;
        }

        $docs_args['meta_query'] = array(
            array(
                'key' => 'only_for_chatbot',
                'compare' => 'NOT EXISTS'
            )
        );

        add_filter('posts_join', array($this, 'analytics_join_query'), 10, 2);
        add_filter('posts_fields', array($this, 'analytics_fields_query'), 10, 2);
        add_filter('posts_orderby', array($this, 'analytics_orderby_query'), 10, 2);
        add_filter('posts_groupby', array($this, 'analytics_groupby_query'), 10, 2);

        $count_args = $docs_args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';
        $count_args['analytics_join'] = false;

        unset($count_args['offset']);
        unset($count_args['orderby']);
        unset($count_args['order']);

        $docsQuery = new WP_Query($docs_args);
        $countQuery = new WP_Query($count_args);

        $result = $docsQuery->posts;
        $total = $countQuery->found_posts;

        remove_filter('posts_join', array($this, 'analytics_join_query'), 10);
        remove_filter('posts_fields', array($this, 'analytics_fields_query'), 10);
        remove_filter('posts_orderby', array($this, 'analytics_orderby_query'), 10);
        remove_filter('posts_groupby', array($this, 'analytics_groupby_query'), 10);

        if (!is_wp_error($result) && !empty($result)) {
            foreach ($result as &$post) {
                $this->process_docs_post($post);
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => absint($total),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function analytics_join_query($join, $query)
    {
        global $wpdb;

        if ($query->get('analytics_join')) {
            $date_range = $query->get('analytics_date_range');
            $date_start = $date_range['start'];
            $date_ended = $date_range['ended'];

            $analyticsObj = new Mapbd_wps_docs_analytics();
            $analyticsTable = $analyticsObj->GetTableName();

            $join .= " LEFT JOIN {$analyticsTable} AS analytics ON {$wpdb->posts}.ID = analytics.post_id AND analytics.created_date BETWEEN '{$date_start}' AND '{$date_ended}'";
        }

        return $join;
    }

    public function analytics_fields_query($fields, $query)
    {
        global $wpdb;

        if ($query->get('analytics_join')) {
            $fields .= ", COALESCE(SUM(analytics.views), 0) as analytics_views";
            $fields .= ", COALESCE(SUM(analytics.unique_views), 0) as analytics_unique_views";
            $fields .= ", COALESCE(SUM(analytics.positive), 0) as analytics_positive";
            $fields .= ", COALESCE(SUM(analytics.negative), 0) as analytics_negative";
            $fields .= ", COALESCE(SUM(analytics.neutral), 0) as analytics_neutral";
            $fields .= ", COALESCE(SUM(analytics.score), 0) as analytics_score";
        }

        return $fields;
    }

    public function analytics_orderby_query($orderby, $query)
    {
        if ($query->get('analytics_join') && $query->get('orderby') === 'analytics_views') {
            $orderby = "analytics_views DESC";
        }

        return $orderby;
    }

    public function analytics_groupby_query($groupby, $query)
    {
        global $wpdb;

        if ($query->get('analytics_join')) {
            $groupby = "{$wpdb->posts}.ID";
        }

        return $groupby;
    }

    public function analytics_compute_score($positive, $negative)
    {
        $positive = absint($positive);
        $negative = absint($negative);
        $total = $positive + $negative;

        if ($total > 0) {
            return (int) round((($positive - $negative) / $total) * 100);
        }

        return 0;
    }
}
