<?php

/**
 * Statistics Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_statistics_trait
{
    public function initialize__statistics()
    {
        $this->AddAjaxAction("statistics_overview_data", [$this, "statistics_overview_data"]);
    }

    public function statistics_overview_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        global $wpdb;

        $ids_filter = [];

        $category_ids = sanitize_text_field(ApbdWps_GetValue("category"));
        $tag_ids = sanitize_text_field(ApbdWps_GetValue("tag"));
        $author_ids = sanitize_text_field(ApbdWps_GetValue("author"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $category_ids = array_filter(array_unique(array_map('absint', explode(",", $category_ids))));
        $tag_ids = array_filter(array_unique(array_map('absint', explode(",", $tag_ids))));
        $author_ids = array_filter(array_unique(array_map('absint', explode(",", $author_ids))));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];
        $prev_date_start = $date_range['prev_date_start'];
        $prev_date_ended = $date_range['prev_date_ended'];

        if (!empty($category_ids) || !empty($tag_ids) || !empty($author_ids)) {
            $docs_args = array(
                'post_type' => 'sgkb-docs',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
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

            $docs_query = new WP_Query($docs_args);

            $ids_filter = $docs_query->posts;
            $ids_filter = is_array($ids_filter) && !empty($ids_filter) ? array_map('absint', $ids_filter) : [0];
        }

        $analyticsObj = new Mapbd_wps_docs_analytics();
        $analyticsTable = $analyticsObj->GetTableName();

        $fields = "COALESCE(SUM(views), 0) as views";
        $fields .= ", COALESCE(SUM(unique_views), 0) as unique_views";
        $fields .= ", COALESCE(SUM(positive), 0) as positive";
        $fields .= ", COALESCE(SUM(negative), 0) as negative";
        $fields .= ", COALESCE(SUM(neutral), 0) as neutral";
        $fields .= ", CASE
            WHEN (COALESCE(SUM(positive), 0) + COALESCE(SUM(negative), 0)) > 0
            THEN ROUND(
                (
                    (COALESCE(SUM(positive), 0) - COALESCE(SUM(negative), 0)) /
                    (COALESCE(SUM(positive), 0) + COALESCE(SUM(negative), 0))
                ) * 100, 0
            )
            ELSE 0
        END as score";

        $where = "created_date BETWEEN %s AND %s";

        if (!empty($ids_filter)) {
            $where .= " AND post_id IN (" . implode(',', $ids_filter) . ")";
        }

        $sql = "SELECT $fields FROM $analyticsTable WHERE $where";
        $sql .= " UNION ALL";
        $sql .= " SELECT $fields FROM $analyticsTable WHERE $where";

        $results = $wpdb->get_results($wpdb->prepare($sql, $date_start, $date_ended, $prev_date_start, $prev_date_ended));

        if (is_array($results) && !empty($results)) {
            $current = isset($results[0]) ? $results[0] : $results;
            $previous = isset($results[1]) ? $results[1] : $results;

            $current_views = intval($current->views);
            $current_unique = intval($current->unique_views);
            $current_returning = $current_views - $current_unique;

            $current_positive = intval($current->positive);
            $current_negative = intval($current->negative);
            $current_neutral = intval($current->neutral);
            $current_score = intval($current->score);
            $current_reactions = $current_positive + $current_negative + $current_neutral;

            $previous_views = intval($previous->views);
            $previous_unique = intval($previous->unique_views);
            $previous_returning = $previous_views - $previous_unique;

            $previous_positive = intval($previous->positive);
            $previous_negative = intval($previous->negative);
            $previous_neutral = intval($previous->neutral);
            $previous_score = intval($previous->score);
            $previous_reactions = $previous_positive + $previous_negative + $previous_neutral;

            $data = [
                'views' => [
                    'current' => [
                        'total' => $current_views,
                        'unique' => $current_unique,
                        'returning' => $current_returning,
                    ],
                    'previous' => [
                        'total' => $previous_views,
                        'unique' => $previous_unique,
                        'returning' => $previous_returning,
                    ],
                ],
                'reactions' => [
                    'current' => [
                        'total' => $current_reactions,
                        'positive' => $current_positive,
                        'negative' => $current_negative,
                        'neutral' => $current_neutral,
                        'score' => $current_score,
                    ],
                    'previous' => [
                        'total' => $previous_reactions,
                        'positive' => $previous_positive,
                        'negative' => $previous_negative,
                        'neutral' => $previous_neutral,
                        'score' => $previous_score,
                    ],
                ],
                'searches' => $this->statistics_searches_data($date_range),
            ];

            $apiResponse->SetResponse(true, "", $data);
        }

        echo wp_json_encode($apiResponse);
    }

    public function statistics_searches_data($date_range)
    {
        global $wpdb;

        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];
        $prev_date_start = $date_range['prev_date_start'];
        $prev_date_ended = $date_range['prev_date_ended'];

        $searchesObj = new Mapbd_wps_docs_searches_events();
        $searchesTable = $searchesObj->GetTableName();

        // Current period query.
        $current_sql = "SELECT COALESCE(SUM(count), 0) as count FROM $searchesTable WHERE founded = 'Y' AND created_date BETWEEN %s AND %s";
        $current_sql .= " UNION ALL";
        $current_sql .= " SELECT COALESCE(SUM(count), 0) as count FROM $searchesTable WHERE founded = 'N' AND created_date BETWEEN %s AND %s";

        $current_results = $wpdb->get_results($wpdb->prepare($current_sql, $date_start, $date_ended, $date_start, $date_ended));

        // Previous period query.
        $previous_sql = "SELECT COALESCE(SUM(count), 0) as count FROM $searchesTable WHERE founded = 'Y' AND created_date BETWEEN %s AND %s";
        $previous_sql .= " UNION ALL";
        $previous_sql .= " SELECT COALESCE(SUM(count), 0) as count FROM $searchesTable WHERE founded = 'N' AND created_date BETWEEN %s AND %s";

        $previous_results = $wpdb->get_results($wpdb->prepare($previous_sql, $prev_date_start, $prev_date_ended, $prev_date_start, $prev_date_ended));

        $current = ['total' => 0, 'with_result' => 0, 'no_result' => 0, 'score' => 0];
        $previous = ['total' => 0, 'with_result' => 0, 'no_result' => 0, 'score' => 0];

        if (is_array($current_results) && !empty($current_results)) {
            $current_with_result = intval($current_results[0]->count);
            $current_no_result = intval($current_results[1]->count);
            $current_total = $current_with_result + $current_no_result;
            $current_score = 0;

            if ($current_total > 0) {
                $current_score = round(
                    (
                        ($current_with_result - $current_no_result) /
                        ($current_with_result + $current_no_result)
                    ) * 100,
                    0
                );
            }

            $current['total'] = $current_total;
            $current['with_result'] = $current_with_result;
            $current['no_result'] = $current_no_result;
            $current['score'] = $current_score;
        }

        if (is_array($previous_results) && !empty($previous_results)) {
            $previous_with_result = intval($previous_results[0]->count);
            $previous_no_result = intval($previous_results[1]->count);
            $previous_total = $previous_with_result + $previous_no_result;
            $previous_score = 0;

            if ($previous_total > 0) {
                $previous_score = round(
                    (
                        ($previous_with_result - $previous_no_result) /
                        ($previous_with_result + $previous_no_result)
                    ) * 100,
                    0
                );
            }

            $previous['total'] = $previous_total;
            $previous['with_result'] = $previous_with_result;
            $previous['no_result'] = $previous_no_result;
            $previous['score'] = $previous_score;
        }

        $results = [
            'current' => $current,
            'previous' => $previous,
        ];

        return $results;
    }
}
