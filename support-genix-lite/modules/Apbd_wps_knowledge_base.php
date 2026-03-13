<?php

/**
 * Knowledge base.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_ai_proxy_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_metabox_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_hierarchy_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_writebot_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_chatbot_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_chatquery_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_chatticket_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_analytics_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_searches_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_statistics_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_duplicator_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_shortcode_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_knowledge_base_migrations_trait.php';

class Apbd_wps_knowledge_base extends ApbdWpsBaseModuleLite
{
    use Apbd_wps_ai_proxy_trait;
    use Apbd_wps_knowledge_base_metabox_trait;
    use Apbd_wps_knowledge_base_hierarchy_trait;
    use Apbd_wps_knowledge_base_writebot_trait;
    use Apbd_wps_knowledge_base_chatbot_trait;
    use Apbd_wps_knowledge_base_chatquery_trait;
    use Apbd_wps_knowledge_base_chatticket_trait;
    use Apbd_wps_knowledge_base_analytics_trait;
    use Apbd_wps_knowledge_base_searches_trait;
    use Apbd_wps_knowledge_base_statistics_trait;
    use Apbd_wps_knowledge_base_duplicator_trait;
    use Apbd_wps_knowledge_base_shortcode_trait;
    use Apbd_wps_knowledge_base_migrations_trait;

    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();

        $this->AddAjaxAction("docs_data", [$this, "docs_data"]);
        $this->AddAjaxAction("docs_group_data", [$this, "docs_group_data"]);
        $this->AddAjaxAction("docs_group_order", [$this, "docs_group_order"]);
        $this->AddAjaxAction("docs_trash_item", [$this, "docs_trash_item"]);
        $this->AddAjaxAction("docs_trash_items", [$this, "docs_trash_items"]);
        $this->AddAjaxAction("docs_restore_item", [$this, "docs_restore_item"]);
        $this->AddAjaxAction("docs_restore_items", [$this, "docs_restore_items"]);
        $this->AddAjaxAction("docs_delete_item", [$this, "docs_delete_item"]);
        $this->AddAjaxAction("docs_delete_items", [$this, "docs_delete_items"]);

        $this->AddAjaxAction("category_add", [$this, "category_add"]);
        $this->AddAjaxAction("category_edit", [$this, "category_edit"]);
        $this->AddAjaxAction("category_data", [$this, "category_data"]);
        $this->AddAjaxAction("category_delete_item", [$this, "category_delete_item"]);
        $this->AddAjaxAction("category_delete_items", [$this, "category_delete_items"]);
        $this->AddAjaxAction("category_data_for_select", [$this, "category_data_for_select"]);
        $this->AddAjaxAction("category_order_change", [$this, "category_order_change"]);
        $this->AddAjaxAction("category_reset_order", [$this, "category_reset_order"]);

        $this->AddAjaxAction("tag_add", [$this, "tag_add"]);
        $this->AddAjaxAction("tag_edit", [$this, "tag_edit"]);
        $this->AddAjaxAction("tag_data", [$this, "tag_data"]);
        $this->AddAjaxAction("tag_delete_item", [$this, "tag_delete_item"]);
        $this->AddAjaxAction("tag_delete_items", [$this, "tag_delete_items"]);
        $this->AddAjaxAction("tag_data_for_select", [$this, "tag_data_for_select"]);

        $this->AddAjaxAction("space_data_for_select", [$this, "space_data_for_select"]);

        $this->AddAjaxAction("author_data_for_select", [$this, "author_data_for_select"]);
        $this->AddAjaxAction("edit_posts_role_for_select", [$this, "edit_posts_role_for_select"]);

        // Add article feedback handler
        add_action('wp_ajax_sgkb_article_feedback', [$this, 'handle_article_feedback']);
        add_action('wp_ajax_nopriv_sgkb_article_feedback', [$this, 'handle_article_feedback']);
        $this->AddAjaxAction("role_for_select", [$this, "role_for_select"]);
        $this->AddAjaxAction("page_for_select", [$this, "page_for_select"]);

        // AJAX Search
        $this->AddAjaxAction("search", [$this, "ajax_search_docs"]);

        $this->AddAjaxAction("config_data", [$this, "config_data"]);
        $this->AddAjaxAction("config_permissions_data", [$this, "config_permissions_data"]);
        $this->AddAjaxAction("config_design_base_data", [$this, "config_design_base_data"]);
        $this->AddAjaxAction("config_design_archive_data", [$this, "config_design_archive_data"]);
        $this->AddAjaxAction("config_design_single_data", [$this, "config_design_single_data"]);
        $this->AddAjaxAction("config_design_style_data", [$this, "config_design_style_data"]);
        $this->AddAjaxAction("config", [$this, "AjaxRequestCallbackConfig"]);
        $this->AddAjaxAction("config_permissions", [$this, "AjaxRequestCallbackConfigPermissions"]);
        $this->AddAjaxAction("config_design_base", [$this, "AjaxRequestCallbackConfigDesignBase"]);
        $this->AddAjaxAction("config_design_archive", [$this, "AjaxRequestCallbackConfigDesignArchive"]);
        $this->AddAjaxAction("config_design_single", [$this, "AjaxRequestCallbackConfigDesignSingle"]);
        $this->AddAjaxAction("config_design_style", [$this, "AjaxRequestCallbackConfigDesignStyle"]);

        $this->AddAjaxAction("docs_suggestions_data", [$this, "docs_suggestions_data"]);
        $this->AddAjaxAction("docs_suggestions", [$this, "AjaxRequestCallbackDocsSuggestions"]);

        add_action('template_redirect', [$this, 'docs_templates'], ~PHP_INT_MAX);
        add_action('template_redirect', [$this, 'docs_single_templates'], ~PHP_INT_MAX);
        add_action('pre_get_posts', [$this, 'docs_pre_get_posts']);

        add_filter('body_class', array($this, 'docs_body_class'));
        add_filter('stackable_frontend_css', array($this, 'fix_stackable_css_type'), 1);

        // Add taxonomy filters to admin post list
        add_action('restrict_manage_posts', [$this, 'add_taxonomy_filters']);

        $this->initialize__metabox();
        $this->initialize__hierarchy();
        $this->initialize__writebot();
        $this->initialize__chatbot();
        $this->initialize__chatquery();
        $this->initialize__chatticket();
        $this->initialize__analytics();
        $this->initialize__searches();
        $this->initialize__statistics();
        $this->initialize__duplicator();
        $this->initialize__shortcode();
        $this->initialize__migrations();
        $this->initialize__conversations();
    }

    /**
     * Initialize chatbot conversation history AJAX actions.
     */
    public function initialize__conversations()
    {
        $this->AddAjaxAction("chatbot_conversations_data", [$this, "chatbot_conversations_data"]);
        $this->AddAjaxAction("chatbot_conversation_detail", [$this, "chatbot_conversation_detail"]);
        $this->AddAjaxAction("chatbot_conversations_stats", [$this, "chatbot_conversations_stats"]);
        $this->AddAjaxAction("chatbot_conversations_delete", [$this, "chatbot_conversations_delete"]);
        $this->AddAjaxAction("chatbot_storage_settings_data", [$this, "chatbot_storage_settings_data"]);
        $this->AddAjaxAction("chatbot_storage_settings_save", [$this, "chatbot_storage_settings_save"]);
        $this->AddAjaxAction("chatbot_embed_sources", [$this, "chatbot_embed_sources"]);
    }

    /**
     * Add taxonomy filter dropdowns to the admin post list for sgkb-docs.
     *
     * @param string $post_type The current post type.
     */
    public function add_taxonomy_filters($post_type)
    {
        if ('sgkb-docs' !== $post_type) {
            return;
        }

        $taxonomies = array(
            'sgkb-docs-category' => __('All Categories', 'support-genix'),
            'sgkb-docs-tag'      => __('All Tags', 'support-genix'),
        );

        // Add space taxonomy if multiple KB is enabled (pro-only feature)
        $multiple_kb = 'N'; // Multiple KB is a pro-only feature
        if ('Y' === $multiple_kb) {
            $taxonomies['sgkb-docs-space'] = __('All Knowledge Bases', 'support-genix');
        }

        foreach ($taxonomies as $taxonomy => $default_label) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $taxonomy_obj = get_taxonomy($taxonomy);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $selected = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';

            wp_dropdown_categories(array(
                'show_option_all' => $default_label,
                'taxonomy'        => $taxonomy,
                'name'            => $taxonomy,
                'orderby'         => 'name',
                'selected'        => $selected,
                'hierarchical'    => $taxonomy_obj->hierarchical,
                'show_count'      => true,
                'hide_empty'      => false,
                'value_field'     => 'slug',
            ));
        }
    }

    /**
     * Get paginated chatbot conversations list.
     */
    public function chatbot_conversations_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $page = absint(ApbdWps_GetValue('page', 1));
        $limit = absint(ApbdWps_GetValue('limit', 20));
        $date_from = sanitize_text_field(ApbdWps_GetValue('date_from', ''));
        $date_to = sanitize_text_field(ApbdWps_GetValue('date_to', ''));
        $user_type = sanitize_text_field(ApbdWps_GetValue('user_type', 'all'));
        $feedback = sanitize_text_field(ApbdWps_GetValue('feedback', 'all'));
        $starred = sanitize_text_field(ApbdWps_GetValue('starred', 'all'));
        $source = sanitize_text_field(ApbdWps_GetValue('source', 'all'));
        $search = sanitize_text_field(ApbdWps_GetValue('search', ''));
        $sort = sanitize_text_field(ApbdWps_GetValue('sort', 'started_at-desc'));

        // Get timezone offset in minutes (positive = ahead of UTC, e.g., UTC+6 = 360)
        $tz_offset = intval(ApbdWps_GetValue('tz_offset', 0));
        $tz_offset_seconds = $tz_offset * 60;

        // Convert local dates to UTC boundaries for filtering
        // Using 'T' and 'Z' suffix to ensure strtotime interprets as UTC
        $date_from_utc = '';
        $date_to_utc = '';
        if (!empty($date_from)) {
            $date_from_utc = gmdate('Y-m-d H:i:s', strtotime($date_from . 'T00:00:00Z') - $tz_offset_seconds);
        }
        if (!empty($date_to)) {
            $date_to_utc = gmdate('Y-m-d H:i:s', strtotime($date_to . 'T23:59:59Z') - $tz_offset_seconds);
        }

        // Parse sort
        $sort_parts = explode('-', $sort);
        $sort_field = isset($sort_parts[0]) ? $sort_parts[0] : 'started_at';
        $sort_dir = isset($sort_parts[1]) && strtoupper($sort_parts[1]) === 'ASC' ? 'ASC' : 'DESC';

        $allowed_sort_fields = array('started_at', 'last_activity_at', 'message_count');
        if (!in_array($sort_field, $allowed_sort_fields)) {
            $sort_field = 'started_at';
        }

        $orderBy = $sort_field . ' ' . $sort_dir;

        $filters = array(
            'date_from_utc' => $date_from_utc,
            'date_to_utc' => $date_to_utc,
            'user_type' => $user_type !== 'all' ? $user_type : '',
            'feedback' => $feedback !== 'all' ? $feedback : '',
            'starred' => $starred !== 'all' ? $starred : '',
            'source' => $source !== 'all' ? $source : '',
            'search' => $search,
        );

        $result = Mapbd_wps_chatbot_session::getSessions($filters, $page, $limit, $orderBy);

        // Format items for frontend
        $items = array();
        foreach ($result['items'] as $item) {
            $items[] = array(
                'session_id' => $item->session_id,
                'first_query' => $item->first_query,
                'message_count' => (int) $item->message_count,
                'user_type' => $item->user_id > 0 ? 'user' : 'guest',
                'user_email' => $item->user_email,
                'user_name' => $item->user_name,
                'guest_identifier' => $item->guest_identifier ? substr($item->guest_identifier, 0, 8) . '...' : null,
                'last_feedback' => $item->last_feedback,
                'helpful_rate' => $item->helpful_rate,
                'session_type' => isset($item->session_type) ? $item->session_type : 'T',
                'duration' => isset($item->duration) ? (int) $item->duration : null,
                'is_starred' => isset($item->is_starred) ? (int) $item->is_starred : 0,
                'source' => isset($item->source) ? $item->source : 'M',
                'embed_token_id' => isset($item->embed_token_id) ? (int) $item->embed_token_id : 0,
                'embed_title' => isset($item->embed_title) ? $item->embed_title : null,
                'page_url' => isset($item->page_url) ? $item->page_url : '',
                'started_at' => $item->started_at,
                'last_activity_at' => $item->last_activity_at,
            );
        }

        $apiResponse->SetResponse(true, '', array(
            'items' => $items,
            'pagination' => array(
                'page' => $result['page'],
                'per_page' => $result['limit'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
            ),
        ));

        echo wp_json_encode($apiResponse);
    }

    /**
     * Get single chatbot conversation detail.
     */
    public function chatbot_conversation_detail()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $session_id = sanitize_text_field(ApbdWps_GetValue('session_id', ''));

        if (empty($session_id)) {
            $apiResponse->SetResponse(false, $this->__('Session ID is required.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get session info
        $session = Mapbd_wps_chatbot_session::FindBy('session_id', $session_id);
        if (!$session) {
            $apiResponse->SetResponse(false, $this->__('Session not found.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get all messages in session
        $messages_raw = Mapbd_wps_chatbot_history::getSessionMessages($session_id);

        $messages = array();
        foreach ($messages_raw as $msg) {
            // Unslash query and content to reverse wp_slash() applied during insert
            $msg->query = wp_unslash($msg->query);
            $msg->content = wp_unslash($msg->content);

            // User message
            $messages[] = array(
                'id' => $msg->id,
                'type' => 'user',
                'content' => $msg->query,
                'timestamp' => $msg->created_at,
            );

            // Bot response (if stored)
            if ((!isset($msg->is_stored_content) || $msg->is_stored_content === 'Y') && !empty($msg->content)) {
                $sources = array();
                if (!empty($msg->docs_ids)) {
                    $doc_ids = array_filter(array_map('absint', explode(',', $msg->docs_ids)));
                    foreach ($doc_ids as $doc_id) {
                        $post = get_post($doc_id);
                        if ($post) {
                            $sources[] = array(
                                'id' => $doc_id,
                                'title' => $post->post_title,
                                'url' => get_permalink($doc_id),
                            );
                        }
                    }
                }

                $messages[] = array(
                    'id' => $msg->id . '_bot',
                    'type' => 'bot',
                    'content' => $msg->content,
                    'sources' => $sources,
                    'feedback' => $msg->feedback,
                    'timestamp' => $msg->created_at,
                );
            } elseif (isset($msg->is_stored_content) && $msg->is_stored_content === 'N') {
                // Content not stored, show placeholder
                $messages[] = array(
                    'id' => $msg->id . '_bot',
                    'type' => 'bot',
                    'content' => '<em>[AI response not stored - storage optimization enabled]</em>',
                    'sources' => array(),
                    'feedback' => $msg->feedback,
                    'timestamp' => $msg->created_at,
                );
            }
        }

        // Get user info
        $user_info = array();
        if ($session->user_id > 0) {
            $user = get_user_by('ID', $session->user_id);
            if ($user) {
                $user_info = array(
                    'type' => 'user',
                    'email' => $user->user_email,
                    'name' => $user->display_name,
                );
            }
        } else {
            $user_info = array(
                'type' => 'guest',
                'identifier' => $session->guest_identifier ? substr($session->guest_identifier, 0, 8) . '...' : 'Unknown',
            );
        }

        // Get embed title if source is Embed
        $embed_title = null;
        if (isset($session->source) && $session->source === 'E' && !empty($session->embed_token_id)) {
            $embed_token = Mapbd_wps_chatbot_embed_token::FindBy('id', $session->embed_token_id);
            if ($embed_token) {
                $embed_title = $embed_token->title;
            }
        }

        $apiResponse->SetResponse(true, '', array(
            'session_id' => $session_id,
            'user_info' => $user_info,
            'started_at' => $session->started_at,
            'last_activity_at' => $session->last_activity_at,
            'message_count' => (int) $session->message_count,
            'session_type' => isset($session->session_type) ? $session->session_type : 'T',
            'duration' => isset($session->duration) ? (int) $session->duration : null,
            'source' => isset($session->source) ? $session->source : 'M',
            'embed_token_id' => isset($session->embed_token_id) ? (int) $session->embed_token_id : 0,
            'embed_title' => $embed_title,
            'page_url' => isset($session->page_url) ? $session->page_url : '',
            'messages' => $messages,
        ));

        echo wp_json_encode($apiResponse);
    }

    /**
     * Get chatbot conversation statistics.
     * Note: Charts and helpful rate are Pro features. Lite returns static demo data for these.
     */
    public function chatbot_conversations_stats()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $date_from = sanitize_text_field(ApbdWps_GetValue('date_from', gmdate('Y-m-d', strtotime('-30 days'))));
        $date_to = sanitize_text_field(ApbdWps_GetValue('date_to', gmdate('Y-m-d')));

        // Extract user_type, feedback, and source filters
        $user_type = sanitize_text_field(ApbdWps_GetValue('user_type', 'all'));
        $feedback = sanitize_text_field(ApbdWps_GetValue('feedback', 'all'));
        $source = sanitize_text_field(ApbdWps_GetValue('source', 'all'));

        // Build filters array
        $filters = array(
            'user_type' => $user_type !== 'all' ? $user_type : '',
            'feedback' => $feedback !== 'all' ? $feedback : '',
            'source' => $source !== 'all' ? $source : '',
        );

        // Get timezone offset in minutes (positive = ahead of UTC, e.g., UTC+6 = 360)
        $tz_offset = intval(ApbdWps_GetValue('tz_offset', 0));
        $tz_offset_seconds = $tz_offset * 60;

        // Calculate UTC boundaries for the selected date range in user's local timezone
        // User's local midnight = UTC time - offset
        // Using 'T' and 'Z' suffix to ensure strtotime interprets as UTC
        $date_from_utc = gmdate('Y-m-d H:i:s', strtotime($date_from . 'T00:00:00Z') - $tz_offset_seconds);
        $date_to_utc = gmdate('Y-m-d H:i:s', strtotime($date_to . 'T23:59:59Z') - $tz_offset_seconds);

        // Get summary stats with filters (real data for Total Sessions, Today, This Week)
        $stats = Mapbd_wps_chatbot_history::getConversationStatsUTC($date_from_utc, $date_to_utc, $filters);

        // Calculate "Today" in user's local timezone
        $local_today = gmdate('Y-m-d', time() + $tz_offset_seconds);
        $today_start_utc = gmdate('Y-m-d H:i:s', strtotime($local_today . 'T00:00:00Z') - $tz_offset_seconds);
        $today_end_utc = gmdate('Y-m-d H:i:s', strtotime($local_today . 'T23:59:59Z') - $tz_offset_seconds);
        $today_stats = Mapbd_wps_chatbot_history::getConversationStatsUTC($today_start_utc, $today_end_utc, $filters);

        // Calculate "This Week" in user's local timezone (last 7 days including today)
        $local_week_start = gmdate('Y-m-d', time() + $tz_offset_seconds - (6 * 86400));
        $week_start_utc = gmdate('Y-m-d H:i:s', strtotime($local_week_start . 'T00:00:00Z') - $tz_offset_seconds);
        $week_end_utc = $today_end_utc;
        $week_stats = Mapbd_wps_chatbot_history::getConversationStatsUTC($week_start_utc, $week_end_utc, $filters);

        // Lite Edition: Return static demo data for charts (Pro feature)
        // Generate static daily trend for last 14 days with appealing growth pattern
        $daily_trend = array();
        $demo_counts = array(12, 15, 18, 14, 22, 25, 20, 28, 32, 30, 35, 38, 42, 45);
        for ($i = 13; $i >= 0; $i--) {
            $daily_trend[] = array(
                'date' => gmdate('Y-m-d', strtotime("-{$i} days")),
                'count' => $demo_counts[13 - $i],
            );
        }

        // Static hourly distribution with realistic business hours peak
        $hourly_distribution = array(
            array('hour' => 0, 'count' => 2),
            array('hour' => 1, 'count' => 1),
            array('hour' => 2, 'count' => 1),
            array('hour' => 3, 'count' => 0),
            array('hour' => 4, 'count' => 1),
            array('hour' => 5, 'count' => 2),
            array('hour' => 6, 'count' => 5),
            array('hour' => 7, 'count' => 8),
            array('hour' => 8, 'count' => 15),
            array('hour' => 9, 'count' => 25),
            array('hour' => 10, 'count' => 32),
            array('hour' => 11, 'count' => 28),
            array('hour' => 12, 'count' => 20),
            array('hour' => 13, 'count' => 24),
            array('hour' => 14, 'count' => 30),
            array('hour' => 15, 'count' => 35),
            array('hour' => 16, 'count' => 28),
            array('hour' => 17, 'count' => 22),
            array('hour' => 18, 'count' => 15),
            array('hour' => 19, 'count' => 12),
            array('hour' => 20, 'count' => 8),
            array('hour' => 21, 'count' => 6),
            array('hour' => 22, 'count' => 4),
            array('hour' => 23, 'count' => 3),
        );

        $apiResponse->SetResponse(true, '', array(
            'summary' => array(
                'total_sessions' => (int) ($stats->total_sessions ?? 0),
                'total_messages' => (int) ($stats->total_messages ?? 0),
                'today_sessions' => (int) ($today_stats->total_sessions ?? 0),
                'week_sessions' => (int) ($week_stats->total_sessions ?? 0),
                'helpful_rate' => 0, // Pro feature - always 0 in Lite
                'unhelpful_count' => 0, // Pro feature - always 0 in Lite
            ),
            'daily_trend' => $daily_trend,
            'hourly_distribution' => $hourly_distribution,
        ));

        echo wp_json_encode($apiResponse);
    }

    /**
     * Delete chatbot conversations (admin manual delete).
     * Allows deletion of any conversation including starred ones.
     */
    public function chatbot_conversations_delete()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $session_ids = ApbdWps_PostValue('session_ids', array());

        if (empty($session_ids) || !is_array($session_ids)) {
            $apiResponse->SetResponse(false, $this->__('No sessions selected.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        global $wpdb;
        $history_table = $wpdb->prefix . 'apbd_wps_chatbot_history';
        $session_table = $wpdb->prefix . 'apbd_wps_chatbot_session';

        $placeholders = implode(',', array_fill(0, count($session_ids), '%s'));
        $sanitized_ids = array_map('sanitize_text_field', $session_ids);

        // Delete history records for all selected sessions
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$history_table} WHERE session_id IN ({$placeholders})",
            $sanitized_ids
        ));

        // Delete session records for all selected sessions
        $deleted_count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$session_table} WHERE session_id IN ({$placeholders})",
            $sanitized_ids
        ));

        $apiResponse->SetResponse(true, $this->__('Conversations deleted successfully.'), array(
            'deleted_count' => $deleted_count,
        ));
        echo wp_json_encode($apiResponse);
    }

    /**
     * Get chatbot storage settings.
     */
    public function chatbot_storage_settings_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $apiResponse->SetResponse(true, '', array(
            'chatbot_retention_period' => $this->GetModuleOption('chatbot_retention_period', '30days'),
            'chatbot_auto_cleanup' => $this->GetModuleOption('chatbot_auto_cleanup', 'N'),
            'chatbot_cleanup_days' => (int) $this->GetModuleOption('chatbot_cleanup_days', 30),
            'chatbot_max_messages' => (int) $this->GetModuleOption('chatbot_max_messages', 100),
        ));

        echo wp_json_encode($apiResponse);
    }

    /**
     * Save chatbot storage settings.
     */
    public function chatbot_storage_settings_save()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $retention_period = sanitize_text_field(ApbdWps_PostValue('chatbot_retention_period', 'forever'));
        $auto_cleanup = sanitize_text_field(ApbdWps_PostValue('chatbot_auto_cleanup', 'N'));
        $auto_cleanup = $auto_cleanup === 'Y' ? 'Y' : 'N';
        $cleanup_days = absint(ApbdWps_PostValue('chatbot_cleanup_days', 30));
        $max_messages = absint(ApbdWps_PostValue('chatbot_max_messages', 100));

        // Validate retention period
        $allowed_periods = array('7days', '30days', '90days', 'forever');
        if (!in_array($retention_period, $allowed_periods)) {
            $retention_period = 'forever';
        }

        // Validate cleanup days
        if ($cleanup_days < 1) {
            $cleanup_days = 30;
        }

        $this->AddIntoOption('chatbot_retention_period', $retention_period);
        $this->AddIntoOption('chatbot_auto_cleanup', $auto_cleanup);
        $this->AddIntoOption('chatbot_cleanup_days', $cleanup_days);
        $this->AddIntoOption('chatbot_max_messages', $max_messages);
        $this->UpdateOption();

        $apiResponse->SetResponse(true, $this->__('Settings saved successfully.'));
        echo wp_json_encode($apiResponse);
    }

    /**
     * Get list of embed tokens for source filter dropdown.
     */
    public function chatbot_embed_sources()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!self::UserCanAccessAnalytics()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_embed_token';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            $apiResponse->SetResponse(true, '', array('sources' => array()));
            echo wp_json_encode($apiResponse);
            return;
        }

        $results = $wpdb->get_results("SELECT id, title FROM {$table} WHERE status = 'A' ORDER BY title ASC");

        $sources = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $sources[] = array(
                    'id' => (int) $row->id,
                    'title' => $row->title,
                );
            }
        }

        $apiResponse->SetResponse(true, '', array('sources' => $sources));
        echo wp_json_encode($apiResponse);
    }

    public function OnInit()
    {
        parent::OnInit();

        $this->post_type_docs();
        $this->taxonomy_docs_category();
        $this->taxonomy_docs_tag();

        add_filter('posts_join', array($this, 'custom_join_query'), 10, 2);
        add_filter('posts_fields', array($this, 'custom_fields_query'), 10, 2);
        add_filter('posts_groupby', array($this, 'custom_groupby_query'), 10, 2);

        add_filter('template_include', array($this, 'custom_docs_template'));
        add_filter('post_type_link', [$this, 'custom_docs_permalink'], 10, 3);

        add_action('transition_post_status', [$this, 'clearObjectCache'], 10, 3);
        add_action('new_to_auto-draft', [$this, 'setDocsCategory'], 10, 1);

        // add_action('save_post_sgkb-docs', [$this, 'saveDocsHook'], 10, 1);
        add_action('wp_after_insert_post', [$this, 'saveDocsHook'], 10, 1);
        add_action('sgkb_after_insert_post', [$this, 'saveDocsHook'], 10, 1);
        add_action('rest_after_insert_sgkb-docs', [$this, 'saveDocsHook'], 10, 1);
        add_action('before_delete_post', [$this, 'deleteDocsHook'], 10, 1);

        add_action('created_sgkb-docs-category', [$this, 'saveCategoryHook'], 10, 1);
        add_action('pre_delete_term', [$this, 'deleteCategoryHook'], 10, 2);

        $this->OnInit__chatbot();
    }

    function GetMultiLangFields()
    {
        return [
            'hero_title' => '',
            'hero_subtitle' => '',
            'chatbot_text_title' => '',
            'chatbot_text_welcome_message' => '',
            'chatbot_text_feedback_message' => '',
            'chatbot_text_helpful_response_message' => '',
            'chatbot_text_not_helpful_response_message' => '',
            'chatbot_text_related_docs_title' => '',
            'chatbot_text_create_ticket_link_text' => '',
            'chatbot_text_input_placeholder' => '',
            'chatbot_text_nothing_found_message' => '',
            'chatbot_text_error_message' => '',
            'chatbot_text_docs_title' => '',
        ];
    }

    function SetOption()
    {
        $optionName = $this->getModuleOptionName();
        $this->SetMultiLangOption($optionName);
    }

    function UpdateOption()
    {
        $optionName = $this->getModuleOptionName();
        return $this->UpdateMultiLangOption($optionName);
    }

    public function ClientStyle()
    {
        parent::ClientStyle();

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $assetsSlug = $coreObject->support_genix_assets_slug;

        $style_primary_brand_color = $this->GetOption('style_primary_brand_color', '');
        $style_hero_bg_color = $this->GetOption('style_hero_bg_color', '');
        $style_main_container_width = $this->GetOption('style_main_container_width', 1140);
        $style_custom_css = $this->GetOption('style_custom_css', '');

        $style_primary_brand_color = strtolower(trim($style_primary_brand_color));
        $style_hero_bg_color = strtolower(trim($style_hero_bg_color));
        $style_main_container_width = absint($style_main_container_width);
        $style_custom_css = trim($style_custom_css);

        if (empty($style_primary_brand_color)) {
            $style_primary_brand_color = '#3b82f6';
        }

        if (empty($style_hero_bg_color)) {
            $style_hero_bg_color = [['color' => '#3b82f6', 'percent' => 0], ['color' => '#1e40af', 'percent' => 100]];
        } else {
            $style_hero_bg_color = stripslashes($style_hero_bg_color);
            $style_hero_bg_color = json_decode($style_hero_bg_color, true);

            if ((JSON_ERROR_NONE !== json_last_error())) {
                $style_hero_bg_color = [['color' => '#3b82f6', 'percent' => 0], ['color' => '#1e40af', 'percent' => 100]];
            }
        }

        $style_hero_bg_color = ApbdWps_ArrayToLinearGradient($style_hero_bg_color);
        $style_custom_css = ApbdWps_KsesCss($style_custom_css);

        // Modern layout only - removed old layout support
        // Add modern grid and component styles
        $this->AddClientStyle($assetsSlug . "-docs-modern-grid", "docs-modern-grid.css");
        $this->AddClientStyle($assetsSlug . "-docs-modern-components", "docs-modern-components.css");
        $this->AddClientStyle($assetsSlug . "-docs-modern-category", "docs-modern-category.css");
        $this->AddClientStyle($assetsSlug . "-docs-modern-search", "docs-modern-search.css");

        // Add modern article styles for single pages
        if (is_singular('sgkb-docs')) {
            $this->AddClientStyle($assetsSlug . "-docs-modern-article", "docs-modern-article.css");

            // Add GLightbox for image lightbox feature
            $show_lightbox = $this->GetOption('single_doc_image_lightbox', 'Y');
            if ($show_lightbox === 'Y') {
                wp_enqueue_style(
                    'glightbox',
                    'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css',
                    array(),
                    '3.2.0'
                );
                wp_enqueue_script(
                    'glightbox',
                    'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js',
                    array(),
                    '3.2.0',
                    true
                );
            }
        }

        // Add modern JavaScript
        $this->AddClientScript($assetsSlug . "-docs-modern", "docs-modern.js", false, ['jquery']);

        // Localize script for modern JS
        wp_localize_script($assetsSlug . "-docs-modern", "sgkb_docs_config", [
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('ajax-nonce'),
        ]);

        // Build inline styles for modern layout
        $inline_style = '';

        // Set CSS custom property for primary brand color
        if ($style_primary_brand_color) {
            $inline_style .= '
                :root {
                    --sgkb-primary: ' . $style_primary_brand_color . ';
                    --sgkb-primary-rgb: ' . $this->hex2rgb($style_primary_brand_color) . ';
                    --sgkb-primary-dark: ' . $this->darken_color($style_primary_brand_color, 10) . ';
                    --sgkb-primary-light: ' . $this->lighten_color($style_primary_brand_color, 10) . ';
                }
            ';
        }

        // Apply main container width to content areas (not hero sections)
        if ($style_main_container_width) {
            $inline_style .= '
                .sgkb-container {
                    max-width: ' . $style_main_container_width . 'px;
                    margin: 0 auto;
                }
                .sgkb-article-body-modern .sgkb-container {
                    max-width: ' . $style_main_container_width . 'px;
                }
            ';
        }

        // Apply hero background gradient to all hero sections
        if ($style_hero_bg_color) {
            $inline_style .= '
                .sgkb-hero-modern,
                .sgkb-category-header-modern,
                .sgkb-article-header-modern {
                    background: ' . $style_hero_bg_color . ' !important;
                }
            ';
        }

        // Apply brand color using CSS variable (keeping specific overrides for compatibility)
        if ($style_primary_brand_color) {
            $inline_style .= '
                /* Category Icon Background */
                .sgkb-category-icon {
                    background: ' . $style_primary_brand_color . ' !important;
                }

                /* Navigation Indicator */
                .sgkb-nav-indicator {
                    background: ' . $style_primary_brand_color . ' !important;
                }

                /* Table of Contents Links */
                .sgkb-toc-link {
                    color: #6b7280;
                    transition: all 0.2s ease;
                }

                .sgkb-toc-link:hover {
                    color: ' . $style_primary_brand_color . ' !important;
                    border-left-color: ' . $style_primary_brand_color . ' !important;
                }

                .sgkb-toc-link.active {
                    color: ' . $style_primary_brand_color . ' !important;
                    border-left-color: ' . $style_primary_brand_color . ' !important;
                }

                /* Feedback Buttons */
                .sgkb-feedback-btn {
                    transition: all 0.2s ease;
                }

                .sgkb-feedback-btn:hover {
                    border-color: ' . $style_primary_brand_color . ' !important;
                    color: ' . $style_primary_brand_color . ' !important;
                }

                .sgkb-feedback-btn.active {
                    background: ' . $style_primary_brand_color . ' !important;
                    border-color: ' . $style_primary_brand_color . ' !important;
                    color: #ffffff !important;
                }

                /* Links and interactive elements */
                .sgkb-category-card-modern:hover {
                    border-color: ' . $style_primary_brand_color . ';
                    box-shadow: 0 10px 30px rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.15);
                }

                .sgkb-category-view-all {
                    color: ' . $style_primary_brand_color . ';
                }

                .sgkb-category-view-all:hover {
                    background: ' . $style_primary_brand_color . ';
                    color: #ffffff;
                }

                /* Search elements */
                .sgkb-hero-search-modern:focus-within {
                    border-color: ' . $style_primary_brand_color . ';
                    box-shadow: 0 0 0 3px rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.1);
                }

                .sgkb-search-result-item:hover {
                    background: rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.05);
                }

                /* Buttons and CTAs */
                .sgkb-hero-cta {
                    background: ' . $style_primary_brand_color . ';
                }

                .sgkb-hero-cta:hover {
                    background: ' . $this->darken_color($style_primary_brand_color, 10) . ';
                }

                /* Category navigation active indicator */
                .sgkb-category-nav-item.active::before {
                    background: ' . $style_primary_brand_color . ';
                }

                /* Stats cards */
                .sgkb-stat-card-modern svg {
                    color: ' . $style_primary_brand_color . ';
                }

                /* Additional brand color applications */
                .sgkb-category-card-modern .sgkb-category-icon {
                    background: ' . $style_primary_brand_color . ';
                }

                .sgkb-article-tag {
                    background: rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.1);
                    color: ' . $style_primary_brand_color . ';
                }

                .sgkb-article-tag:hover {
                    background: rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.2);
                }

                /* Progress bars and indicators */
                .sgkb-reading-progress {
                    background: ' . $style_primary_brand_color . ';
                }

                /* Category badges */
                .sgkb-category-badge {
                    background: ' . $style_primary_brand_color . ';
                    color: #ffffff;
                }

                /* Active states */
                .sgkb-nav-item.active {
                    color: ' . $style_primary_brand_color . ';
                }

                /* Focus states for accessibility */
                button:focus-visible,
                a:focus-visible {
                    outline-color: ' . $style_primary_brand_color . ';
                }

                /* Related Articles Cards */
                .sgkb-related-card:hover {
                    border-color: ' . $style_primary_brand_color . ' !important;
                    box-shadow: 0 4px 12px rgba(' . $this->hex2rgb($style_primary_brand_color) . ', 0.15) !important;
                }

                /* Related Articles Read More Link */
                .sgkb-related-readmore {
                    color: ' . $style_primary_brand_color . ' !important;
                    transition: all 0.2s ease;
                }

                .sgkb-related-readmore:hover {
                    color: ' . $this->darken_color($style_primary_brand_color, 15) . ' !important;
                }

                .sgkb-related-readmore svg {
                    color: ' . $style_primary_brand_color . ' !important;
                    transition: transform 0.2s ease;
                }

                .sgkb-related-card:hover .sgkb-related-readmore svg {
                    transform: translateX(4px);
                }
            ';
        }

        // Add custom CSS
        if ($style_custom_css) {
            $inline_style .= "\n/* Custom CSS */\n" . $style_custom_css;
        }

        // Add inline styles to modern CSS
        if (!empty($inline_style)) {
            wp_add_inline_style($assetsSlug . "-docs-modern-components", $inline_style);
        }
    }

    /**
     * Convert hex color to RGB
     */
    private function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "$r, $g, $b";
    }

    /**
     * Darken a hex color by percentage
     */
    private function darken_color($hex, $percent)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        // Darken
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));

        return '#' . sprintf("%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Lighten a hex color by percentage
     */
    private function lighten_color($hex, $percent)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        // Lighten
        $r = max(0, min(255, $r + ((255 - $r) * $percent / 100)));
        $g = max(0, min(255, $g + ((255 - $g) * $percent / 100)));
        $b = max(0, min(255, $b + ((255 - $b) * $percent / 100)));

        return '#' . sprintf("%02x%02x%02x", $r, $g, $b);
    }

    public function OnAdminGlobalStyles()
    {
        parent::OnAdminGlobalStyles();

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $assetsSlug = $coreObject->support_genix_assets_slug;

        $this->OnAdminGlobalStyles__writebot($assetsSlug);
    }

    public function docs_templates()
    {
        $shouldFlush = get_option('sg_flush_rewrite_rules', false);

        if ($shouldFlush) {
            flush_rewrite_rules();
            update_option('sg_flush_rewrite_rules', false);
            wp_redirect(add_query_arg(array(), $_SERVER['REQUEST_URI']), 301);
            exit;
        }
    }

    public function docs_single_templates()
    {
        $disable_ofcb_single = $this->GetOption('disable_ofcb_single', 'N');

        if (
            ('Y' !== $disable_ofcb_single) ||
            !is_single() ||
            ('sgkb-docs' !== get_post_type())
        ) {
            return;
        }

        global $post;

        $only_for_chatbot = get_post_meta($post->ID, 'only_for_chatbot', true);

        if ('1' === $only_for_chatbot) {
            $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();
            if (!$canWriteDocs) {
                $archive_link = get_post_type_archive_link('sgkb-docs');
                $redirect_link = $archive_link ? $archive_link : home_url();
                wp_redirect($redirect_link);
                exit;
            }
        }
    }

    public function docs_body_class($classes)
    {
        if (
            is_post_type_archive('sgkb-docs') ||
            is_tax(get_object_taxonomies('sgkb-docs')) ||
            (is_search() && 'sgkb-docs' === get_query_var('post_type')) ||
            is_singular('sgkb-docs')
        ) {
            $theme = sanitize_key(get_template());

            if (! empty($theme)) {
                $classes[] = 'sgkb-' . $theme;
            }

            $classes[] = 'sgkb-template';
        }

        return $classes;
    }

    public function fix_stackable_css_type($css)
    {
        if (!is_string($css)) {
            return '';
        }

        return $css;
    }

    public function docs_pre_get_posts($query)
    {
        if (
            !is_admin() &&
            $query->is_main_query() &&
            (
                is_post_type_archive('sgkb-docs') ||
                is_tax(get_object_taxonomies('sgkb-docs')) ||
                (is_search() && ('sgkb-docs' === $query->get('post_type')))
            )
        ) {
            $archive_docs_per_page = $this->GetOption('archive_docs_per_page', 10);
            $archive_docs_orderby = $this->GetOption('archive_docs_orderby', 'default');
            $archive_docs_order = $this->GetOption('archive_docs_order', 'DESC');

            $query->set('posts_per_page', $archive_docs_per_page);

            if ('default' !== $archive_docs_orderby) {
                $query->set('orderby', $archive_docs_orderby);
                $query->set('order', $archive_docs_order);
            }

            // Always filter out posts that are only for chatbot from archive pages
            $meta_query = $query->get('meta_query') ?: array();
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => 'only_for_chatbot',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'only_for_chatbot',
                    'value' => '1',
                    'compare' => '!='
                )
            );
            $query->set('meta_query', $meta_query);

            if (is_tax('sgkb-docs-category')) {
                $tax_query = isset($query->tax_query) ? $query->tax_query : null;
                $tax_query = isset($tax_query->queries) ? $tax_query->queries : [];

                if (is_array($tax_query) && !empty($tax_query)) {
                    $tax_query = array_map(function ($single) {
                        if (
                            isset($single['taxonomy']) &&
                            'sgkb-docs-category' === $single['taxonomy']
                        ) {
                            $single['include_children'] = false;
                        }

                        return $single;
                    }, $tax_query);

                    $query->set('tax_query', $tax_query);
                }
            }

            $meta_query = $query->get('meta_query') ?: [];
            $meta_query[] = [
                'key' => 'only_for_chatbot',
                'compare' => 'NOT EXISTS'
            ];

            $query->set('meta_query', $meta_query);
        }

        return $query;
    }

    public function post_type_docs()
    {
        $docs_archive = $this->GetOption('docs_archive', 'N');
        $docs_base_slug = $this->GetOption('docs_base_slug', 'sgkb-docs');
        $docs_single_slug = $this->GetOption('docs_single_slug', 'sgkb-docs');

        $has_archive = ('Y' === $docs_archive) ? (!empty($docs_base_slug) ? untrailingslashit($docs_base_slug) : 'sgkb-docs') : false;
        $rewrite_slug = (!empty($docs_single_slug) ? untrailingslashit($docs_single_slug) : 'sgkb-docs');

        $labels = array(
            'name' => _x('Docs', 'Post Type General Name', 'support-genix'),
            'singular_name' => _x('Docs', 'Post Type Singular Name', 'support-genix'),
            'menu_name' => _x('Docs', 'Admin Menu text', 'support-genix'),
            'name_admin_bar' => _x('Docs', 'Add New on Toolbar', 'support-genix'),
            'archives' => __('Docs Archives', 'support-genix'),
            'attributes' => __('Docs Attributes', 'support-genix'),
            'parent_item_colon' => __('Parent Docs:', 'support-genix'),
            'all_items' => __('All Docs', 'support-genix'),
            'add_new_item' => __('Add New Docs', 'support-genix'),
            'add_new' => __('Add New', 'support-genix'),
            'new_item' => __('New Docs', 'support-genix'),
            'edit_item' => __('Edit Docs', 'support-genix'),
            'update_item' => __('Update Docs', 'support-genix'),
            'view_item' => __('View Docs', 'support-genix'),
            'view_items' => __('View Docs', 'support-genix'),
            'search_items' => __('Search Docs', 'support-genix'),
            'not_found' => __('Not found', 'support-genix'),
            'not_found_in_trash' => __('Not found in Trash', 'support-genix'),
            'featured_image' => __('Featured Image', 'support-genix'),
            'set_featured_image' => __('Set featured image', 'support-genix'),
            'remove_featured_image' => __('Remove featured image', 'support-genix'),
            'use_featured_image' => __('Use as featured image', 'support-genix'),
            'insert_into_item' => __('Insert into Docs', 'support-genix'),
            'uploaded_to_this_item' => __('Uploaded to this Docs', 'support-genix'),
            'items_list' => __('Docs list', 'support-genix'),
            'items_list_navigation' => __('Docs list navigation', 'support-genix'),
            'filter_items_list' => __('Filter Docs list', 'support-genix'),
        );

        $rewrite = array(
            'slug' => $rewrite_slug,
            'with_front' => false,
            'pages' => true,
            'feeds' => true,
        );

        $args = array(
            'label' => __('Docs', 'support-genix'),
            'description' => '',
            'labels' => $labels,
            'menu_icon' => '',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'comments', 'custom-fields'),
            'taxonomies' => array('sgkb-docs-category', 'sgkb-docs-tag'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Hidden in dashboard menu.
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'hierarchical' => false,
            'exclude_from_search' => false,
            'show_in_rest' => true,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'has_archive' => $has_archive,
            'rewrite' => $rewrite,
        );

        register_post_type('sgkb-docs', $args);
    }

    public function taxonomy_docs_category()
    {
        $category_base = $this->GetOption('category_base', 'sgkb-docs-category');
        $rewrite_slug = (!empty($category_base) ? untrailingslashit($category_base) : 'sgkb-docs-category');

        $labels = array(
            'name' => _x('Categories', 'taxonomy general name', 'support-genix'),
            'singular_name' => _x('Category', 'taxonomy singular name', 'support-genix'),
            'search_items' => __('Search Categories', 'support-genix'),
            'all_items' => __('All Categories', 'support-genix'),
            'parent_item' => __('Parent Category', 'support-genix'),
            'parent_item_colon' => __('Parent Category:', 'support-genix'),
            'edit_item' => __('Edit Category', 'support-genix'),
            'update_item' => __('Update Category', 'support-genix'),
            'add_new_item' => __('Add New Category', 'support-genix'),
            'new_item_name' => __('New Category Name', 'support-genix'),
            'menu_name' => __('Categories', 'support-genix'),
        );

        $rewrite = array(
            'hierarchical' => true,
            'slug' => $rewrite_slug,
            'with_front' => false,
            'ep_mask' => EP_CATEGORIES,
        );

        $args = array(
            'labels' => $labels,
            'description' => '',
            'hierarchical' => true,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => $rewrite,
        );

        register_taxonomy('sgkb-docs-category', array('sgkb-docs'), $args);
    }

    public function taxonomy_docs_tag()
    {
        $tag_base = $this->GetOption('tag_base', 'sgkb-docs-tag');
        $rewrite_slug = (!empty($tag_base) ? untrailingslashit($tag_base) : 'sgkb-docs-tag');

        $labels = array(
            'name' => _x('Tags', 'taxonomy general name', 'support-genix'),
            'singular_name' => _x('Tag', 'taxonomy singular name', 'support-genix'),
            'search_items' => __('Search Tags', 'support-genix'),
            'all_items' => __('All Tags', 'support-genix'),
            'parent_item' => __('Parent Tag', 'support-genix'),
            'parent_item_colon' => __('Parent Tag:', 'support-genix'),
            'edit_item' => __('Edit Tag', 'support-genix'),
            'update_item' => __('Update Tag', 'support-genix'),
            'add_new_item' => __('Add New Tag', 'support-genix'),
            'new_item_name' => __('New Tag Name', 'support-genix'),
            'menu_name' => __('Tags', 'support-genix'),
        );

        $rewrite = array(
            'hierarchical' => false,
            'slug' => $rewrite_slug,
            'with_front' => false,
            'ep_mask' => EP_TAGS,
        );

        $args = array(
            'labels' => $labels,
            'description' => '',
            'hierarchical' => false,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => $rewrite,
        );

        register_taxonomy('sgkb-docs-tag', array('sgkb-docs'), $args);
    }

    public function custom_join_query($join, $query)
    {
        global $wpdb;

        if (
            (
                is_post_type_archive('sgkb-docs') ||
                is_tax(get_object_taxonomies('sgkb-docs')) ||
                ('sgkb-docs' === $query->get('post_type'))
            ) &&
            !$query->get('analytics_join') &&
            !$query->get('sgkb_search')
        ) {
            $analyticsObj = new Mapbd_wps_docs_analytics();
            $analyticsTable = $analyticsObj->GetTableName();

            $join .= " LEFT JOIN {$analyticsTable} AS analytics ON {$wpdb->posts}.ID = analytics.post_id";
        }

        return $join;
    }

    public function custom_fields_query($fields, $query)
    {
        if (
            (
                is_post_type_archive('sgkb-docs') ||
                is_tax(get_object_taxonomies('sgkb-docs')) ||
                ('sgkb-docs' === $query->get('post_type'))
            ) &&
            !$query->get('analytics_join') &&
            !$query->get('sgkb_search')
        ) {
            $fields .= ", COALESCE(SUM(analytics.views), 0) as analytics_views";
            $fields .= ", COALESCE(SUM(analytics.positive), 0) as analytics_positive";
            $fields .= ", COALESCE(SUM(analytics.negative), 0) as analytics_negative";
            $fields .= ", COALESCE(SUM(analytics.neutral), 0) as analytics_neutral";
        }

        return $fields;
    }

    public function custom_groupby_query($groupby, $query)
    {
        global $wpdb;

        if (
            (
                is_post_type_archive('sgkb-docs') ||
                is_tax(get_object_taxonomies('sgkb-docs')) ||
                ('sgkb-docs' === $query->get('post_type'))
            ) &&
            !$query->get('analytics_join') &&
            !$query->get('sgkb_search')
        ) {
            $groupby = "{$wpdb->posts}.ID";
        }

        return $groupby;
    }

    public function custom_docs_template($template)
    {
        if (sgkb_is_fse_theme()) {
            return $template;
        }

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($coreObject->pluginFile));

        $template_name = $this->custom_template_name();

        if (! empty($template_name)) {
            if ($theme_template_file = locate_template(array('support-genix/docs/' . $template_name))) {
                $template_file = realpath($theme_template_file);
            } else {
                $template_file = realpath($pluginPath . '/templates/docs/' . $template_name);
            }

            if (is_file($template_file) && file_exists($template_file)) {
                $template = $template_file;
            }
        }

        return $template;
    }

    public function custom_template_name()
    {
        if (
            is_post_type_archive('sgkb-docs') ||
            is_tax(get_object_taxonomies('sgkb-docs')) ||
            (is_search() && 'sgkb-docs' === get_query_var('post_type'))
        ) {
            return 'archive-docs.php';
        }

        if (is_singular('sgkb-docs')) {
            return 'single-docs.php';
        }

        return '';
    }

    public function custom_docs_permalink($permalink, $post, $leavename)
    {
        if (
            ('sgkb-docs' !== $post->post_type) ||
            empty($permalink)
        ) {
            return $permalink;
        }

        // Initialize default values
        $space_slug = 'nonspace';
        $category_slug = 'uncategorized';
        $author_slug = get_the_author_meta('user_nicename', $post->post_author);

        // Get category slug (always, regardless of Multiple KB setting)
        $categories = wp_get_object_terms($post->ID, 'sgkb-docs-category');

        if (!is_wp_error($categories) && is_array($categories) && !empty($categories)) {
            $category_item = isset($categories[0]) ? $categories[0] : null;

            if (is_object($category_item) && isset($category_item->slug)) {
                $category_slug = $category_item->slug;
            }
        }

        // Replace permalink placeholders
        $permalink = str_replace('%knowledge_base%', $space_slug, $permalink);
        $permalink = str_replace('%space%', $space_slug, $permalink);
        $permalink = str_replace('%category%', $category_slug, $permalink);
        $permalink = str_replace('%author%', $author_slug, $permalink);

        return $permalink;
    }

    public function clearObjectCache($new_status, $old_status, $post)
    {
        if (
            ('sgkb-docs' === $post->post_type) &&
            ($new_status !== $old_status)
        ) {
            wp_cache_flush();
        }
    }

    public function saveDocsHook($post_id)
    {
        if (
            ('sgkb-docs' !== get_post_type($post_id)) ||
            wp_is_post_autosave($post_id) ||
            wp_is_post_revision($post_id)
        ) {
            return;
        }

        if ($post_id instanceof \WP_Post) {
            $post_id = $post_id->ID;
        }

        $term_ids = wp_get_post_terms($post_id, 'sgkb-docs-category', array(
            'fields' => 'ids'
        ));

        if (!empty($term_ids)) {
            foreach ($term_ids as $term_id) {
                $term_order_str = get_term_meta($term_id, '_sg_docs_order', true);
                $term_order_arr = $term_order_str ? array_filter(array_unique(array_map('absint', explode(',', $term_order_str)))) : [];

                if (!in_array($post_id, $term_order_arr)) {
                    array_unshift($term_order_arr, $post_id);
                }

                update_term_meta($term_id, '_sg_docs_order', implode(',', $term_order_arr));
            }

            $docs_order_str = get_option('_sg_docs_order', '');
            $docs_order_arr = $docs_order_str ? array_filter(array_unique(array_map('absint', explode(',', $docs_order_str)))) : [];
            $docs_order_arr = array_filter($docs_order_arr, function ($item) use ($post_id) {
                return absint($item) !== absint($post_id);
            });

            update_option('_sg_docs_order', implode(',', $docs_order_arr));
        } else {
            $docs_order_str = get_option('_sg_docs_order', '');
            $docs_order_arr = $docs_order_str ? array_filter(array_unique(array_map('absint', explode(',', $docs_order_str)))) : [];

            if (!in_array($post_id, $docs_order_arr)) {
                array_unshift($docs_order_arr, $post_id);
            }

            update_option('_sg_docs_order', implode(',', $docs_order_arr));
        }

        $all_terms = get_terms([
            'taxonomy' => 'sgkb-docs-category',
            'hide_empty' => false,
            'hierarchical' => false,
            'fields' => 'ids',
        ]);

        foreach ($all_terms as $term_id) {
            if (in_array($term_id, $term_ids)) {
                continue;
            }

            $term_order_str = get_term_meta($term_id, '_sg_docs_order', true);
            $term_order_arr = $term_order_str ? array_filter(array_unique(array_map('absint', explode(',', $term_order_str)))) : [];
            $term_order_arr = array_filter($term_order_arr, function ($item) use ($post_id) {
                return absint($item) !== absint($post_id);
            });

            update_term_meta($term_id, '_sg_docs_order', implode(',', $term_order_arr));
        }
    }

    public function deleteDocsHook($post_id)
    {
        $categories = get_the_terms($post_id, 'sgkb-docs-category');

        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $term_order_str = get_term_meta($category->term_id, '_sg_docs_order', true);
                $term_order_arr = $term_order_str ? array_filter(array_unique(array_map('absint', explode(',', $term_order_str)))) : [];
                $term_order_arr = array_filter($term_order_arr, function ($item) use ($post_id) {
                    return absint($item) !== absint($post_id);
                });

                update_term_meta($category->term_id, '_sg_docs_order', implode(',', $term_order_arr));
            }
        }

        $docs_order_str = get_option('_sg_docs_order', '');
        $docs_order_arr = $docs_order_str ? array_filter(array_unique(array_map('absint', explode(',', $docs_order_str)))) : [];
        $docs_order_arr = array_filter($docs_order_arr, function ($item) use ($post_id) {
            return absint($item) !== absint($post_id);
        });

        update_option('_sg_docs_order', implode(',', $docs_order_arr));
    }

    public function setDocsCategory($post)
    {
        $category_id = isset($_GET['post_cat']) ? absint($_GET['post_cat']) : 0;
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '';

        if (
            empty($category_id) ||
            empty($request_uri) ||
            (false === strpos($request_uri, 'wp-admin/post-new.php'))
        ) {
            return;
        }

        $term_exists = get_term_by('term_id', $category_id, 'sgkb-docs-category');

        if (false === $term_exists) {
            return;
        }

        wp_set_post_terms($post->ID, [$category_id], 'sgkb-docs-category', false);
    }

    public function saveCategoryHook($term_id)
    {
        $this->SetTaxonomyTermOrder('sgkb-docs-category', $term_id);
    }

    public function deleteCategoryHook($term_id, $taxonomy)
    {
        if ('sgkb-docs-category' !== $taxonomy) {
            return;
        }

        $term_order_str = get_term_meta($term_id, '_sg_docs_order', true);
        $term_order_arr = $term_order_str ? array_filter(array_unique(array_map('absint', explode(',', $term_order_str)))) : [];

        $docs_order_str = get_option('_sg_docs_order', '');
        $docs_order_arr = $docs_order_str ? array_filter(array_unique(array_map('absint', explode(',', $docs_order_str)))) : [];
        $docs_order_arr = array_merge($docs_order_arr, $term_order_arr);

        update_option('_sg_docs_order', implode(',', $docs_order_arr));
    }

    /* Docs */

    public function docs_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $status = sanitize_text_field(ApbdWps_GetValue("status"));
        $category_ids = sanitize_text_field(ApbdWps_GetValue("category"));
        $tag_ids = sanitize_text_field(ApbdWps_GetValue("tag"));
        $author_ids = sanitize_text_field(ApbdWps_GetValue("author"));
        $search = sanitize_text_field(ApbdWps_GetValue("search"));

        $sort = sanitize_text_field(ApbdWps_GetValue("sort"));
        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));

        $orderBy = 'id';
        $order = 'ASC';

        if ($sort) {
            $sort = explode('-', $sort);

            if (isset($sort[0]) && !empty($sort[0])) {
                $orderBy = sanitize_key($sort[0]);
            }

            if (isset($sort[1]) && !empty($sort[1])) {
                $order = 'asc' === sanitize_key($sort[1]) ? 'ASC' : 'DESC';
            }
        }

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 10);
        $limitStart = ($limit * ($page - 1));

        $category_ids = array_filter(array_unique(array_map('absint', explode(",", $category_ids))));
        $tag_ids = array_filter(array_unique(array_map('absint', explode(",", $tag_ids))));
        $author_ids = array_filter(array_unique(array_map('absint', explode(",", $author_ids))));

        $docs_args = array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'any',
            'orderby' => $orderBy,
            'order' => $order,
            'posts_per_page' => $limit,
            'offset' => $limitStart,
        );

        $taxq_args = [];

        if (!empty($status)) {
            $docs_args['post_status'] = $status;
        }

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

        if (0 < strlen($search)) {
            $docs_args['s'] = $search;
        }

        if (!empty($taxq_args)) {
            $docs_args['tax_query'] = $taxq_args;
        }

        add_filter('posts_join', array($this, 'custom_join_query'), 10, 2);
        add_filter('posts_fields', array($this, 'custom_fields_query'), 10, 2);
        add_filter('posts_groupby', array($this, 'custom_groupby_query'), 10, 2);

        $count_args = $docs_args;
        $count_args['posts_per_page'] = 1;
        $count_args['fields'] = 'ids';
        unset($count_args['offset']);

        $docsQuery = new WP_Query($docs_args);
        $countQuery = new WP_Query($count_args);

        $result = $docsQuery->posts;
        $total = $countQuery->found_posts;

        remove_filter('posts_join', array($this, 'custom_join_query'), 10);
        remove_filter('posts_fields', array($this, 'custom_fields_query'), 10);
        remove_filter('posts_groupby', array($this, 'custom_groupby_query'), 10);

        if (!is_wp_error($result) && !empty($result)) {
            foreach ($result as &$post) {
                $this->process_docs_post($post);
            }

            $apiResponse->SetResponse(true, "", [
                'result' => $result,
                'total' => $total,
            ]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_group_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $category_ids = sanitize_text_field(ApbdWps_GetValue("category"));
        $status = sanitize_text_field(ApbdWps_GetValue("status"));
        $tag_ids = sanitize_text_field(ApbdWps_GetValue("tag"));
        $author_ids = sanitize_text_field(ApbdWps_GetValue("author"));
        $search = sanitize_text_field(ApbdWps_GetValue("search"));
        $hide_empty = ApbdWps_GetValue("hide_empty");

        $category_ids = array_filter(array_unique(array_map('absint', explode(",", $category_ids))));
        $tag_ids = array_filter(array_unique(array_map('absint', explode(",", $tag_ids))));
        $author_ids = array_filter(array_unique(array_map('absint', explode(",", $author_ids))));
        $hide_empty = 'Y' === $hide_empty ? true : false;

        $groups = [];
        $term_ids = [];

        $term_args = [
            'taxonomy' => 'sgkb-docs-category',
            'hide_empty' => false,
            'hierarchical' => false,
            'meta_key' => '_sg_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
        ];

        if (!empty($category_ids)) {
            $term_args['include'] = $category_ids;
        }

        $terms = get_terms($term_args);

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $category) {
                $term_id = $category->term_id;

                $docs_order = get_term_meta($term_id, '_sg_docs_order', true);
                $docs_order = $docs_order ? array_filter(array_unique(array_map('absint', explode(',', $docs_order)))) : [];

                $docs_args = [
                    'post_type' => 'sgkb-docs',
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                ];

                $taxq_args = [];

                if (!empty($status)) {
                    $docs_args['post_status'] = $status;
                }

                if (!empty($tag_ids)) {
                    $taxq_args[] = [
                        'taxonomy' => 'sgkb-docs-tag',
                        'field' => 'term_id',
                        'terms' => $tag_ids,
                        'operator' => 'IN',
                    ];
                }

                if (!empty($docs_order)) {
                    $docs_args['post__in'] = $docs_order;
                    $docs_args['orderby'] = 'post__in';
                } else {
                    $taxq_args[] = [
                        'taxonomy' => 'sgkb-docs-category',
                        'field' => 'term_id',
                        'terms' => $term_id,
                        'operator' => 'IN',
                        'include_children' => false,
                    ];
                }

                if (!empty($author_ids)) {
                    $docs_args['author__in'] = $author_ids;
                }

                if (0 < strlen($search)) {
                    $docs_args['s'] = $search;
                }

                if (!empty($taxq_args)) {
                    $docs_args['tax_query'] = $taxq_args;
                }

                add_filter('posts_join', array($this, 'custom_join_query'), 10, 2);
                add_filter('posts_fields', array($this, 'custom_fields_query'), 10, 2);
                add_filter('posts_groupby', array($this, 'custom_groupby_query'), 10, 2);

                $docsQuery = new WP_Query($docs_args);
                $term_docs = $docsQuery->posts;

                remove_filter('posts_join', array($this, 'custom_join_query'), 10);
                remove_filter('posts_fields', array($this, 'custom_fields_query'), 10);
                remove_filter('posts_groupby', array($this, 'custom_groupby_query'), 10);

                if (!is_wp_error($term_docs) && (!$hide_empty || !empty($term_docs))) {
                    if (!empty($term_docs)) {
                        foreach ($term_docs as &$post) {
                            $this->process_docs_post($post, false);
                        }

                        $category->docs = $term_docs;
                    } else {
                        $category->docs = [];
                    }

                    $category->color = get_term_meta($term_id, '_sg_color', true);
                    $category->icon_image = get_term_meta($term_id, '_sg_icon_image', true);

                    $groups[] = $category;
                }

                $term_ids[] = $term_id;
            }
        }

        if (empty($category_ids) || in_array(999999, $category_ids, true)) {
            $docs_order = get_option('_sg_docs_order', '');
            $docs_order = $docs_order ? array_filter(array_unique(array_map('absint', explode(',', $docs_order)))) : [];

            $docs_args = [
                'post_type' => 'sgkb-docs',
                'post_status' => 'any',
                'posts_per_page' => -1,
            ];

            $taxq_args = [];

            if (!empty($status)) {
                $docs_args['post_status'] = $status;
            }

            if (!empty($tag_ids)) {
                $taxq_args[] = [
                    'taxonomy' => 'sgkb-docs-tag',
                    'field' => 'term_id',
                    'terms' => $tag_ids,
                    'operator' => 'IN',
                ];
            }

            if (!empty($docs_order)) {
                $docs_args['post__in'] = $docs_order;
                $docs_args['orderby'] = 'post__in';
            } else {
                $taxq_args[] = [
                    [
                        'taxonomy' => 'sgkb-docs-category',
                        'field' => 'term_id',
                        'terms' => $term_ids,
                        'operator' => 'NOT IN',
                    ]
                ];
            }

            if (!empty($author_ids)) {
                $docs_args['author__in'] = $author_ids;
            }

            if (0 < strlen($search)) {
                $docs_args['s'] = $search;
            }

            if (!empty($taxq_args)) {
                $docs_args['tax_query'] = $taxq_args;
            }

            add_filter('posts_join', array($this, 'custom_join_query'), 10, 2);
            add_filter('posts_fields', array($this, 'custom_fields_query'), 10, 2);
            add_filter('posts_groupby', array($this, 'custom_groupby_query'), 10, 2);

            $docsQuery = new WP_Query($docs_args);
            $term_docs = $docsQuery->posts;

            remove_filter('posts_join', array($this, 'custom_join_query'), 10);
            remove_filter('posts_fields', array($this, 'custom_fields_query'), 10);
            remove_filter('posts_groupby', array($this, 'custom_groupby_query'), 10);

            if (!is_wp_error($term_docs) && (!$hide_empty || !empty($term_docs))) {
                if (!empty($term_docs)) {
                    foreach ($term_docs as &$post) {
                        $this->process_docs_post($post, false);
                    }
                } else {
                    $term_docs = [];
                }

                $groups[] = [
                    'term_id' => 0,
                    'name' => $this->__('Uncategorized'),
                    'slug' => 'uncategorized',
                    'term_group' => 0,
                    'term_taxonomy_id' => 0,
                    'taxonomy' => 'sgkb-docs-category',
                    'description' => '',
                    'parent' => 0,
                    'count' => count($term_docs),
                    'filter' => 'raw',
                    'parent_category_name' => '',
                    'docs' => $term_docs,
                    'color' => '',
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $groups,
            'total' => count($groups),
        ]);

        echo wp_json_encode($apiResponse);
    }

    private function process_docs_post(&$post, $cats = true)
    {
        $post_id = absint($post->ID);
        $edit_link = get_edit_post_link($post_id);
        $categories = $cats ? get_the_terms($post_id, 'sgkb-docs-category') : [];
        $tags = get_the_terms($post_id, 'sgkb-docs-tag');
        $author = get_user($post->post_author);
        $only_for_chatbot = get_post_meta($post->ID, 'only_for_chatbot', true);

        $post->key = $post_id;
        $post->guid = is_string($post->guid) ? htmlspecialchars_decode($post->guid) : '#';
        $post->edit_guid = is_string($edit_link) ? htmlspecialchars_decode($edit_link) : '#';
        $post->permalink = get_the_permalink($post);

        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as &$category) {
                $category->color = get_term_meta($category->term_id, '_sg_color', true);
                $category->icon_image = get_term_meta($category->term_id, '_sg_icon_image', true);
            }

            $post->categories = $categories;
        } else {
            $post->categories = [];
        }

        if (!is_wp_error($tags) && !empty($tags)) {
            foreach ($tags as &$tag) {
                $tag->color = get_term_meta($tag->term_id, '_sg_color', true);
            }

            $post->tags = $tags;
        } else {
            $post->tags = [];
        }

        if ($author) {
            $post->post_author_name = $author->display_name;
        }

        $post->only_for_chatbot = $only_for_chatbot;
    }

    public function docs_group_order($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $order = sanitize_text_field(ApbdWps_PostValue('order', ''));
            $order = $order ? array_filter(array_unique(array_map('absint', explode(',', $order)))) : [];

            if (empty($order)) {
                $hasError = true;
            }

            if (!$hasError) {
                wp_cache_flush();

                $ex_order_str = !empty($param_id) ? get_term_meta($param_id, '_sg_docs_order', true) : get_option('_sg_docs_order', '');
                $ex_order_arr = $ex_order_str ? array_filter(array_unique(array_map('absint', explode(',', $ex_order_str)))) : [];

                if (!empty($ex_order_arr)) {
                    $ms_order = [];

                    foreach ($ex_order_arr as $post_id) {
                        if (!in_array($post_id, $order)) {
                            $ms_order[] = $post_id;
                        }
                    }

                    if (!empty($ms_order)) {
                        $order = array_merge($ms_order, $order);
                    }
                }

                if (!empty($param_id)) {
                    update_term_meta($param_id, '_sg_docs_order', implode(',', $order));
                } else {
                    update_option('_sg_docs_order', implode(',', $order));
                }

                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_trash_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $trash_post = wp_trash_post($param_id);

            if (!is_wp_error($trash_post) && $trash_post) {
                $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_trash_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $param_id = absint($param_id);

                    if (!empty($param_id)) {
                        wp_trash_post($param_id, true);
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_restore_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $restore_post = wp_untrash_post($param_id);

            if (!is_wp_error($restore_post) && $restore_post) {
                $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_restore_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $param_id = absint($param_id);

                    if (!empty($param_id)) {
                        wp_untrash_post($param_id, true);
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_delete_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $post_status = get_post_status($param_id);

            if ('trash' === $post_status) {
                $delete_post = wp_delete_post($param_id, true);

                if (!is_wp_error($delete_post) && $delete_post) {
                    $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function docs_delete_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $param_id = absint($param_id);

                    if (!empty($param_id)) {
                        $post_status = get_post_status($param_id);

                        if ('trash' === $post_status) {
                            wp_delete_post($param_id, true);
                        }
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    /* Categories */

    public function category_add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $slug = sanitize_text_field(ApbdWps_PostValue('slug', ''));
            $parent = absint(ApbdWps_PostValue('parent', ''));
            $description = sanitize_text_field(ApbdWps_PostValue('description', ''));
            $color = sanitize_text_field(ApbdWps_PostValue('color', ''));
            $icon_image = esc_url_raw(ApbdWps_PostValue('icon_image', ''));

            if (1 > strlen($title)) {
                $hasError = true;
            }

            if (!$hasError) {
                $insert_term = wp_insert_term($title, 'sgkb-docs-category', array(
                    'slug' => $slug,
                    'parent' => $parent,
                    'description' => $description,
                ));

                if (!is_wp_error($insert_term) && $insert_term) {
                    $term_id = isset($insert_term['term_id']) ? absint($insert_term['term_id']) : 0;

                    update_term_meta($term_id, '_sg_color', $color);
                    update_term_meta($term_id, '_sg_icon_image', $icon_image);

                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_edit($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        $hasError = false;

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $slug = sanitize_text_field(ApbdWps_PostValue('slug', ''));
            $parent = absint(ApbdWps_PostValue('parent', ''));
            $description = sanitize_text_field(ApbdWps_PostValue('description', ''));
            $color = sanitize_text_field(ApbdWps_PostValue('color', ''));
            $icon_image = esc_url_raw(ApbdWps_PostValue('icon_image', ''));

            if (1 > strlen($title)) {
                $hasError = true;
            }

            if (!$hasError) {
                $update_term = wp_update_term($param_id, 'sgkb-docs-category', array(
                    'name' => $title,
                    'slug' => $slug,
                    'parent' => $parent,
                    'description' => $description,
                ));

                if (!is_wp_error($update_term) && $update_term) {
                    update_term_meta($param_id, '_sg_color', $color);
                    update_term_meta($param_id, '_sg_icon_image', $icon_image);

                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $total = wp_count_terms('sgkb-docs-category');
        $total = !is_wp_error($total) ? absint($total) : 0;

        if (0 < $total) {
            $sort = ApbdWps_GetValue("sort");
            $page = ApbdWps_GetValue("page");
            $limit = ApbdWps_GetValue("limit");

            $orderBy = 'fld_order';
            $order = 'ASC';

            if ($sort) {
                $sort = explode('-', $sort);

                if (isset($sort[0]) && !empty($sort[0])) {
                    $orderBy = sanitize_key($sort[0]);
                }

                if (isset($sort[1]) && !empty($sort[1])) {
                    $order = 'asc' === sanitize_key($sort[1]) ? 'ASC' : 'DESC';
                }
            }

            $page = max(absint($page), 1);
            $limit = max(absint($limit), 10);
            $limitStart = ($limit * ($page - 1));

            $args = array(
                'taxonomy' => 'sgkb-docs-category',
                'hide_empty' => false,
                'hierarchical' => true,
                'number' => $limit,
                'offset' => $limitStart,
            );

            if (!$orderBy) {
                $args['number'] = 0;
                $args['offset'] = 0;
            } elseif ('fld_order' === $orderBy) {
                $args['meta_key'] = '_sg_order';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = $order;
            } else {
                $args['orderby'] = $orderBy;
                $args['order'] = $order;
            }

            if ('fld_order' === $orderBy) {
                $args['meta_key'] = '_sg_order';
                $args['orderby'] = 'meta_value_num';
            }

            $items = get_terms($args);

            if (!$orderBy) {
                $count = 0;
                $result = [];
                $children = $this->get_term_hierarchy('sgkb-docs-category');

                $this->get_term_lebel('sgkb-docs-category', $result, $items, $children, $limitStart, $limit, $count);
            } else {
                $result = $items;
            }

            if (!is_wp_error($result) && !empty($result)) {
                $parents = array_reduce($result, function ($current, $item) {
                    $id = absint($item->term_id);
                    $name = sanitize_text_field($item->name);

                    if ($id) {
                        $current[$id] = $name;
                    }

                    return $current;
                }, []);

                foreach ($result as &$data) {
                    $data->parent_category_name = isset($parents[$data->parent]) ? $parents[$data->parent] : '';
                    $data->color = get_term_meta($data->term_id, '_sg_color', true);
                    $data->icon_image = get_term_meta($data->term_id, '_sg_icon_image', true);
                    $data->fld_order = get_term_meta($data->term_id, '_sg_order', true);
                }

                $apiResponse->SetResponse(true, "", [
                    'result' => $result,
                    'total' => $total,
                ]);
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_delete_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $delete_term = wp_delete_term($param_id, 'sgkb-docs-category');

            if (!is_wp_error($delete_term) && $delete_term) {
                $fill_order = $this->FillTaxonomyTermOrder('sgkb-docs-category');

                if ($fill_order) {
                    $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_delete_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $param_id = absint($param_id);

                    if (!empty($param_id)) {
                        wp_delete_term($param_id, 'sgkb-docs-category');
                    }
                }

                $fill_order = $this->FillTaxonomyTermOrder('sgkb-docs-category');

                if ($fill_order) {
                    $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $total = wp_count_terms('sgkb-docs-category');
        $total = !is_wp_error($total) ? absint($total) : 0;

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Category') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Categories'),
            ];
        }

        if (0 < $total) {
            $records = get_terms(array(
                'taxonomy' => 'sgkb-docs-category',
                'hide_empty' => false,
                'hierarchical' => false,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
            ));

            if (!is_wp_error($records) && !empty($records)) {
                $parents = [];

                foreach ($records as $record) {
                    $id = absint($record->term_id);
                    $parent = absint($record->parent);

                    if ($id) {
                        $parents[$id] = strval($parent);
                    }
                }

                if ($records) {
                    foreach ($records as $record) {
                        $id = absint($record->term_id);
                        $title = $record->name;

                        if ($id !== $except_id) {
                            $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';
                            $child = $this->FilterChildList($parents, $id);

                            $result[] = [
                                $valkey => strval($id),
                                'label' => $title,
                                'child' => $child,
                            ];
                        }
                    }
                }
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => $total,
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function category_order_change($param_id = 0, $param_type = '')
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");
        $param_type = ApbdWps_GetValue('typ');

        if (!empty($param_id) && !empty($param_type) && in_array($param_type, ['u', 'd'], true)) {
            $change_order = $this->ChangeTaxonomyTermOrder('sgkb-docs-category', $param_id, $param_type);

            if ($change_order) {
                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function category_reset_order()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $reset_order = $this->ResetTaxonomyTermOrder('sgkb-docs-category');

        if ($reset_order) {
            $apiResponse->SetResponse(true, $this->__('Successfully reset.'));
        } else {
            $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
        }

        echo wp_json_encode($apiResponse);
    }

    /* Tags */

    public function tag_add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $slug = sanitize_text_field(ApbdWps_PostValue('slug', ''));
            $description = sanitize_text_field(ApbdWps_PostValue('description', ''));
            $color = sanitize_text_field(ApbdWps_PostValue('color', ''));

            if (1 > strlen($title)) {
                $hasError = true;
            }

            if (!$hasError) {
                $insert_term = wp_insert_term($title, 'sgkb-docs-tag', array(
                    'slug' => $slug,
                    'description' => $description,
                ));

                if (!is_wp_error($insert_term) && $insert_term) {
                    update_term_meta($insert_term['term_id'], '_sg_color', $color);
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function tag_edit($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        $hasError = false;

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $slug = sanitize_text_field(ApbdWps_PostValue('slug', ''));
            $description = sanitize_text_field(ApbdWps_PostValue('description', ''));
            $color = sanitize_text_field(ApbdWps_PostValue('color', ''));

            if (1 > strlen($title)) {
                $hasError = true;
            }

            if (!$hasError) {
                $update_term = wp_update_term($param_id, 'sgkb-docs-tag', array(
                    'name' => $title,
                    'slug' => $slug,
                    'description' => $description,
                ));

                if (!is_wp_error($update_term) && $update_term) {
                    update_term_meta($param_id, '_sg_color', $color);
                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function tag_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $total = wp_count_terms('sgkb-docs-tag');
        $total = !is_wp_error($total) ? absint($total) : 0;

        if (0 < $total) {
            $sort = ApbdWps_GetValue("sort");
            $page = ApbdWps_GetValue("page");
            $limit = ApbdWps_GetValue("limit");

            $orderBy = 'id';
            $order = 'ASC';

            if ($sort) {
                $sort = explode('-', $sort);

                if (isset($sort[0]) && !empty($sort[0])) {
                    $orderBy = sanitize_key($sort[0]);
                }

                if (isset($sort[1]) && !empty($sort[1])) {
                    $order = 'asc' === sanitize_key($sort[1]) ? 'ASC' : 'DESC';
                }
            }

            $page = max(absint($page), 1);
            $limit = max(absint($limit), 10);
            $limitStart = ($limit * ($page - 1));

            $result = get_terms(array(
                'taxonomy' => 'sgkb-docs-tag',
                'hide_empty' => false,
                'hierarchical' => false,
                'orderby' => $orderBy,
                'order' => $order,
                'number' => $limit,
                'offset' => $limitStart,
            ));

            if (!is_wp_error($result) && !empty($result)) {
                foreach ($result as &$data) {
                    $data->color = get_term_meta($data->term_id, '_sg_color', true);
                }

                $apiResponse->SetResponse(true, "", [
                    'result' => $result,
                    'total' => $total,
                ]);
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function tag_delete_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = absint(ApbdWps_GetValue("id"));

        if (!empty($param_id)) {
            $delete_term = wp_delete_term($param_id, 'sgkb-docs-tag');

            if (!is_wp_error($delete_term) && $delete_term) {
                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function tag_delete_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $param_id = absint($param_id);

                    if (!empty($param_id)) {
                        wp_delete_term($param_id, 'sgkb-docs-tag');
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function tag_data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $total = wp_count_terms('sgkb-docs-tag');
        $total = !is_wp_error($total) ? absint($total) : 0;

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Tag') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Tags'),
            ];
        }

        if (0 < $total) {
            $records = get_terms(array(
                'taxonomy' => 'sgkb-docs-tag',
                'hide_empty' => false,
                'hierarchical' => false,
            ));

            if (!is_wp_error($records) && !empty($records)) {
                if ($records) {
                    foreach ($records as $record) {
                        $id = absint($record->term_id);
                        $title = $record->name;

                        if ($id !== $except_id) {
                            $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                            $result[] = [
                                $valkey => strval($id),
                                'label' => $title,
                            ];
                        }
                    }
                }
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => $total,
        ]);

        echo wp_json_encode($apiResponse);
    }

    /**
     * AJAX Search for documentation
     */
    public function ajax_search_docs()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $query = sanitize_text_field(ApbdWps_GetValue("query", ""));

        if (empty($query) || strlen($query) < 2) {
            $apiResponse->status = false;
            $apiResponse->msg = __("Please enter at least 2 characters", 'support-genix');
            echo wp_json_encode($apiResponse);
            return;
        }

        // Search in docs
        $args = array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 10,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );

        // Check if searching in specific category
        $category_slug = sanitize_text_field(ApbdWps_GetValue("category", ""));
        if (!empty($category_slug)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'slug',
                    'terms' => $category_slug
                )
            );
        }

        $docs_query = new WP_Query($args);

        ob_start();
?>
        <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); text-align: left;">
            <?php
            if ($docs_query->have_posts()) {
            ?>
                <div class="sgkb-search-results-dropdown" style="display: flex; flex-direction: column; background: #ffffff; color: #111827; text-align: left;">
                    <div class="sgkb-search-results-header" style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                        <span class="sgkb-search-results-count" style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">
                            <?php echo sprintf(__('Found %d results', 'support-genix'), $docs_query->found_posts); ?>
                        </span>
                    </div>
                    <div class="sgkb-search-results-list" style="background: #ffffff; padding: 8px; margin: 0; overflow: hidden auto;">
                        <?php while ($docs_query->have_posts()) : $docs_query->the_post(); ?>
                            <?php
                            $categories = get_the_terms(get_the_ID(), 'sgkb-docs-category');
                            $category_name = '';
                            if ($categories && !is_wp_error($categories)) {
                                $category = reset($categories);
                                $category_name = $category->name;
                            }
                            ?>
                            <a href="<?php the_permalink(); ?>" class="sgkb-search-result-item" style="display: block; padding: 12px 16px; text-decoration: none; color: #111827; border-bottom: 1px solid #f3f4f6; transition: all 0.2s ease; background: transparent;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                <div class="sgkb-search-result-content" style="display: flex; flex-direction: column; gap: 4px;">
                                    <div class="sgkb-search-result-title" style="font-size: 14px; font-weight: 600; color: #111827; line-height: 1.4; margin: 0; padding: 0;"><?php the_title(); ?></div>
                                    <?php
                                    // Try to get manual excerpt first
                                    $excerpt = get_the_excerpt();

                                    // If no manual excerpt, generate from content
                                    if (empty($excerpt) || $excerpt == get_the_title()) {
                                        $content = get_post_field('post_content', get_the_ID());
                                        // Remove shortcodes
                                        $content = strip_shortcodes($content);
                                        // Remove HTML tags
                                        $content = wp_strip_all_tags($content);
                                        // Remove extra whitespace
                                        $content = preg_replace('/\s+/', ' ', $content);
                                        // Trim to word count
                                        $excerpt = wp_trim_words($content, 20, '...');
                                    }

                                    // Ensure excerpt is not empty
                                    if (empty($excerpt)) {
                                        $excerpt = __('No description available for this article.', 'support-genix');
                                    }
                                    ?>
                                    <div class="sgkb-search-result-excerpt" style="font-size: 12px; color: #6b7280; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; margin: 2px 0 0 0; max-height: 2.8em;"><?php echo esc_html($excerpt); ?></div>
                                    <?php if ($category_name) : ?>
                                        <div class="sgkb-search-result-meta" style="display: flex; align-items: center; gap: 8px; font-size: 12px; margin-top: 4px;">
                                            <span class="sgkb-search-result-category" style="color: #6b7280; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px; display: inline-block;"><?php echo esc_html($category_name); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                    <?php if ($docs_query->found_posts > 10) : ?>
                        <div class="sgkb-search-results-footer" style="padding: 12px 16px; border-top: 1px solid #e5e7eb; background: #f9fafb;">
                            <a href="<?php echo esc_url(home_url('/?s=' . urlencode($query) . '&post_type=sgkb-docs')); ?>" class="sgkb-search-view-all" style="display: inline-flex; align-items: center; gap: 8px; color: #7229dd; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease;" onmouseover="this.style.gap='12px'; this.style.color='#5521a8'" onmouseout="this.style.gap='8px'; this.style.color='#7229dd'">
                                <?php echo sprintf(__('View all %d results', 'support-genix'), $docs_query->found_posts); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            } else {
            ?>
                <div class="sgkb-search-no-results" style="padding: 48px 32px; text-align: center; background: #ffffff;">
                    <svg class="sgkb-search-no-results-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" style="color: #9ca3af; margin: 0 auto 16px auto; display: block;">
                        <path d="M11 6C13.7614 6 16 8.23858 16 11M16.6588 16.6549L21 21M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p class="sgkb-search-no-results-text" style="font-size: 1rem; color: #1f2937; margin: 0 0 8px 0;">No results found for "<strong style="color: #7229dd;"><?php echo esc_html($query); ?></strong>"</p>
                    <p class="sgkb-search-no-results-hint" style="font-size: 0.875rem; color: #6b7280; margin: 0;"><?php _e('Try searching with different keywords', 'support-genix'); ?></p>
                </div>
            <?php
            }
            ?>
        </div>
<?php

        wp_reset_postdata();

        $html = ob_get_clean();

        $apiResponse->status = true;
        $apiResponse->data = $html;
        echo wp_json_encode($apiResponse);
    }

    public function space_data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $apiResponse->SetResponse(true, "", [
            'result' => [],
            'total' => 0,
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function author_data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Author') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Authors'),
            ];
        }

        $records = get_users(array(
            'capability' => array('edit_posts'),
        ));

        if (!is_wp_error($records) && !empty($records)) {
            if ($records) {
                foreach ($records as $record) {
                    $id = absint($record->ID);
                    $title = $record->display_name;

                    if ($id !== $except_id) {
                        $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                        $result[] = [
                            $valkey => strval($id),
                            'label' => $title,
                        ];
                    }
                }
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function edit_posts_role_for_select($except_key = '', $select = false, $select_all = false, $with_key = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_key = ApbdWps_GetValue("except_key", "");
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_key = ApbdWps_GetValue("with_key", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_key = sanitize_text_field($except_key);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_key = rest_sanitize_boolean($with_key);
        $no_value = rest_sanitize_boolean($no_value);

        $roles = wp_roles()->roles;

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Role') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Roles'),
            ];
        }

        foreach ($roles as $key => $role) {
            $key = strval($key);
            $title = $role['name'];
            $capabilities = $role['capabilities'];

            if (!isset($capabilities['edit_posts']) || !$capabilities['edit_posts']) {
                continue;
            }

            if ($key !== $except_key) {
                $title .= $with_key ? ' ' . $this->___('(Key: %d)', $key) : '';

                $result[] = [
                    $valkey => $key,
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function role_for_select($except_key = '', $select = false, $select_all = false, $with_key = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_key = ApbdWps_GetValue("except_key", "");
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_key = ApbdWps_GetValue("with_key", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_key = sanitize_text_field($except_key);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_key = rest_sanitize_boolean($with_key);
        $no_value = rest_sanitize_boolean($no_value);

        $roles = wp_roles()->roles;

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Role') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Roles'),
            ];
        }

        foreach ($roles as $key => $role) {
            $key = strval($key);
            $title = $role['name'];

            if ($key !== $except_key) {
                $title .= $with_key ? ' ' . $this->___('(Key: %d)', $key) : '';

                $result[] = [
                    $valkey => $key,
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function page_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $pages = get_pages();

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Page') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Pages'),
            ];
        }

        foreach ($pages as $page) {
            $id = $page->ID;
            $title = $page->post_title;

            $id = absint($id);

            if ($id !== $except_id) {
                $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                $result[] = [
                    $valkey => strval($id),
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    /* Config */

    public function config_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $docs_archive = $this->GetOption('docs_archive', 'N');
        $docs_base_slug = $this->GetOption('docs_base_slug', 'sgkb-docs');
        $docs_single_slug = $this->GetOption('docs_single_slug', 'sgkb-docs');
        $category_base = $this->GetOption('category_base', 'sgkb-docs-category');
        $tag_base = $this->GetOption('tag_base', 'sgkb-docs-tag');
        $disable_ofcb_single = $this->GetOption('disable_ofcb_single', 'N');

        $docs_archive = ('Y' === $docs_archive) ? true : false;
        $disable_ofcb_single = ('Y' === $disable_ofcb_single) ? true : false;

        $data = [
            'docs_archive' => $docs_archive,
            'docs_base_slug' => $docs_base_slug,
            'docs_single_slug' => $docs_single_slug,
            'category_base' => $category_base,
            'tag_base' => $tag_base,
            'disable_ofcb_single' => $disable_ofcb_single,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function config_permissions_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $write_docs_roles = $this->GetOption('write_docs_roles', 'administrator');
        $access_analytics_roles = $this->GetOption('access_analytics_roles', 'administrator');
        $access_config_roles = $this->GetOption('access_config_roles', 'administrator');
        $track_analytics_for = $this->GetOption('track_analytics_for', 'everyone');
        $track_analytics_roles = $this->GetOption('track_analytics_roles', '0');

        $data = [
            'write_docs_roles' => $write_docs_roles,
            'access_analytics_roles' => $access_analytics_roles,
            'access_config_roles' => $access_config_roles,
            'track_analytics_for' => $track_analytics_for,
            'track_analytics_roles' => $track_analytics_roles,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function config_design_base_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $base_type = $this->GetOption('base_type', 'archive');
        $term_layout = $this->GetOption('term_layout', 'masonry');
        $term_number_of_columns = $this->GetOption('term_number_of_columns', 3);
        $term_orderby = $this->GetOption('term_orderby', 'default');
        $term_order = $this->GetOption('term_order', 'DESC');
        $term_number_of_docs = $this->GetOption('term_number_of_docs', 5);
        $term_docs_orderby = $this->GetOption('term_docs_orderby', 'default');
        $term_docs_order = $this->GetOption('term_docs_order', 'DESC');
        $term_title_link = $this->GetOption('term_title_link', 'N');
        $term_docs_count = $this->GetOption('term_docs_count', 'Y');
        $term_docs_count_text = $this->GetOption('term_docs_count_text', $this->__('Docs'));
        $term_docs_count_text_singular = $this->GetOption('term_docs_count_text_singular', $this->__('Doc'));
        $term_view_all_docs_btn = $this->GetOption('term_view_all_docs_btn', 'Y');
        $term_view_all_docs_btn_text = $this->GetOption('term_view_all_docs_btn_text', $this->__('View All'));
        $header_title = $this->GetOption('header_title', $this->__('Knowledge Base'));
        $header_subtitle = $this->GetOption('header_subtitle', $this->__('Search our knowledge base or discover helpful articles and resources'));

        // Modern Layout Settings
        $use_modern_layout = $this->GetOption('use_modern_layout', 'Y');
        $modern_grid_columns = $this->GetOption('modern_grid_columns', '3');
        $modern_show_hero = $this->GetOption('modern_show_hero', 'Y');
        $modern_show_stats = $this->GetOption('modern_show_stats', 'Y');
        $modern_show_featured = $this->GetOption('modern_show_featured', 'Y');
        $modern_show_icons = $this->GetOption('modern_show_icons', 'Y');
        $modern_show_description = $this->GetOption('modern_show_description', 'Y');
        $modern_docs_per_category = $this->GetOption('modern_docs_per_category', 5);
        $show_recent_docs = $this->GetOption('show_recent_docs', 'Y');
        $hero_title = $this->GetOption('hero_title', $this->__('How can we help?'));
        $hero_subtitle = $this->GetOption('hero_subtitle', $this->__('Search our knowledge base or browse categories below'));

        $term_title_link = ('Y' === $term_title_link) ? true : false;
        $term_docs_count = ('Y' === $term_docs_count) ? true : false;
        $term_view_all_docs_btn = ('Y' === $term_view_all_docs_btn) ? true : false;
        $use_modern_layout = ('Y' === $use_modern_layout) ? true : false;
        $modern_show_hero = ('Y' === $modern_show_hero) ? true : false;
        $modern_show_stats = ('Y' === $modern_show_stats) ? true : false;
        $modern_show_featured = ('Y' === $modern_show_featured) ? true : false;
        $modern_show_icons = ('Y' === $modern_show_icons) ? true : false;
        $modern_show_description = ('Y' === $modern_show_description) ? true : false;
        $show_recent_docs = ('Y' === $show_recent_docs) ? true : false;

        $data = [
            'base_type' => $base_type,
            'term_layout' => $term_layout,
            'term_number_of_columns' => $term_number_of_columns,
            'term_orderby' => $term_orderby,
            'term_order' => $term_order,
            'term_number_of_docs' => $term_number_of_docs,
            'term_docs_orderby' => $term_docs_orderby,
            'term_docs_order' => $term_docs_order,
            'term_title_link' => $term_title_link,
            'term_docs_count' => $term_docs_count,
            'term_docs_count_text' => $term_docs_count_text,
            'term_docs_count_text_singular' => $term_docs_count_text_singular,
            'term_view_all_docs_btn' => $term_view_all_docs_btn,
            'term_view_all_docs_btn_text' => $term_view_all_docs_btn_text,
            'header_title' => $header_title,
            'header_subtitle' => $header_subtitle,
            // Modern layout settings
            'use_modern_layout' => $use_modern_layout,
            'modern_grid_columns' => $modern_grid_columns,
            'modern_show_hero' => $modern_show_hero,
            'modern_show_stats' => $modern_show_stats,
            'modern_show_featured' => $modern_show_featured,
            'modern_show_icons' => $modern_show_icons,
            'modern_show_description' => $modern_show_description,
            'modern_docs_per_category' => $modern_docs_per_category,
            'show_recent_docs' => $show_recent_docs,
            'hero_title' => $hero_title,
            'hero_subtitle' => $hero_subtitle,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function config_design_archive_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $archive_docs_layout = $this->GetOption('archive_docs_layout', 'masonry');
        $archive_number_of_columns = $this->GetOption('archive_number_of_columns', 3);
        $archive_docs_per_page = $this->GetOption('archive_docs_per_page', 10);
        $archive_docs_orderby = $this->GetOption('archive_docs_orderby', 'default');
        $archive_docs_order = $this->GetOption('archive_docs_order', 'DESC');

        $data = [
            'archive_docs_layout' => $archive_docs_layout,
            'archive_number_of_columns' => $archive_number_of_columns,
            'archive_docs_per_page' => $archive_docs_per_page,
            'archive_docs_orderby' => $archive_docs_orderby,
            'archive_docs_order' => $archive_docs_order,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function config_design_single_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $single_doc_title = $this->GetOption('single_doc_title', 'Y');
        $single_doc_thumbnail = $this->GetOption('single_doc_thumbnail', 'Y');
        $single_doc_tags = $this->GetOption('single_doc_tags', 'Y');
        $single_doc_modified_date = $this->GetOption('single_doc_modified_date', 'Y');
        $single_doc_breadcrumb = $this->GetOption('single_doc_breadcrumb', 'Y');
        $single_doc_comment = $this->GetOption('single_doc_comment', 'Y');
        $single_doc_reaction = $this->GetOption('single_doc_reaction', 'Y');

        $single_doc_title = ('Y' === $single_doc_title) ? true : false;
        $single_doc_thumbnail = ('Y' === $single_doc_thumbnail) ? true : false;
        $single_doc_tags = ('Y' === $single_doc_tags) ? true : false;
        $single_doc_modified_date = ('Y' === $single_doc_modified_date) ? true : false;
        $single_doc_breadcrumb = ('Y' === $single_doc_breadcrumb) ? true : false;
        $single_doc_comment = ('Y' === $single_doc_comment) ? true : false;
        $single_doc_reaction = ('Y' === $single_doc_reaction) ? true : false;

        $data = [
            'single_doc_title' => $single_doc_title,
            'single_doc_thumbnail' => $single_doc_thumbnail,
            'single_doc_tags' => $single_doc_tags,
            'single_doc_modified_date' => $single_doc_modified_date,
            'single_doc_breadcrumb' => $single_doc_breadcrumb,
            'single_doc_comment' => $single_doc_comment,
            'single_doc_reaction' => $single_doc_reaction,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function config_design_style_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $style_primary_brand_color = $this->GetOption('style_primary_brand_color', '');
        $style_hero_bg_color = $this->GetOption('style_hero_bg_color', '');
        $style_main_container_width = $this->GetOption('style_main_container_width', 1140);
        $style_custom_css = $this->GetOption('style_custom_css', '');

        $style_primary_brand_color = strtolower(trim($style_primary_brand_color));
        $style_hero_bg_color = strtolower(trim($style_hero_bg_color));
        $style_main_container_width = absint($style_main_container_width);
        $style_custom_css = trim($style_custom_css);

        if (empty($style_primary_brand_color)) {
            $style_primary_brand_color = '#3b82f6';
        }

        if (empty($style_hero_bg_color)) {
            $style_hero_bg_color = [['color' => '#3b82f6', 'percent' => 0], ['color' => '#1e40af', 'percent' => 100]];
        } else {
            $style_hero_bg_color = stripslashes($style_hero_bg_color);
            $style_hero_bg_color = json_decode($style_hero_bg_color, true);

            if ((JSON_ERROR_NONE !== json_last_error())) {
                $style_hero_bg_color = [['color' => '#3b82f6', 'percent' => 0], ['color' => '#1e40af', 'percent' => 100]];
            }
        }

        if (empty($style_custom_css)) {
            $style_custom_css = '';
        }

        $data = [
            'style_primary_brand_color' => $style_primary_brand_color,
            'style_hero_bg_color' => $style_hero_bg_color,
            'style_main_container_width' => $style_main_container_width,
            'style_custom_css' => $style_custom_css,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfig()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $docs_archive = sanitize_text_field(ApbdWps_PostValue('docs_archive', ''));
            $docs_base_slug = sanitize_text_field(ApbdWps_PostValue('docs_base_slug', ''));
            $docs_single_slug = ApbdWps_KsesHtml(ApbdWps_PostValue('docs_single_slug', ''));
            $category_base = sanitize_text_field(ApbdWps_PostValue('category_base', ''));
            $tag_base = sanitize_text_field(ApbdWps_PostValue('tag_base', ''));
            $disable_ofcb_single = sanitize_text_field(ApbdWps_PostValue('disable_ofcb_single', ''));

            $docs_archive = 'Y' === $docs_archive ? 'Y' : 'N';
            $disable_ofcb_single = 'Y' === $disable_ofcb_single ? 'Y' : 'N';

            // Docs archive slug.
            $docs_base_slug = preg_replace('#^/?index\.php#', '', $docs_base_slug);
            $docs_base_slug = (0 < strlen($docs_base_slug)) ? $docs_base_slug : 'sgkb-docs';

            // Docs single slug.
            $docs_single_slug = preg_replace('#^/?index\.php#', '', $docs_single_slug);
            $docs_single_slug = (0 < strlen($docs_single_slug)) ? $docs_single_slug : 'sgkb-docs';

            $this->AddIntoOption('docs_archive', $docs_archive);
            $this->AddIntoOption('docs_base_slug', $docs_base_slug);
            $this->AddIntoOption('docs_single_slug', $docs_single_slug);
            $this->AddIntoOption('category_base', $category_base);
            $this->AddIntoOption('tag_base', $tag_base);
            $this->AddIntoOption('disable_ofcb_single', $disable_ofcb_single);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        update_option('sg_flush_rewrite_rules', true);
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfigPermissions()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $write_docs_roles_arr = sanitize_text_field(ApbdWps_PostValue('write_docs_roles_arr', ''));
            $access_analytics_roles_arr = sanitize_text_field(ApbdWps_PostValue('access_analytics_roles_arr', ''));
            $access_config_roles_arr = sanitize_text_field(ApbdWps_PostValue('access_config_roles_arr', ''));
            $track_analytics_for = sanitize_text_field(ApbdWps_PostValue('track_analytics_for', 'everyone'));
            $track_analytics_roles_arr = sanitize_text_field(ApbdWps_PostValue('track_analytics_roles_arr', ''));

            $write_docs_roles = array_unique(array_map('sanitize_text_field', explode(',', $write_docs_roles_arr)));
            $write_docs_roles = in_array('administrator', $write_docs_roles, true) ? $write_docs_roles : array_merge(['administrator'], $write_docs_roles);
            $write_docs_roles = implode(',', $write_docs_roles);

            $access_analytics_roles = array_unique(array_map('sanitize_text_field', explode(',', $access_analytics_roles_arr)));
            $access_analytics_roles = in_array('administrator', $access_analytics_roles, true) ? $access_analytics_roles : array_merge(['administrator'], $access_analytics_roles);
            $access_analytics_roles = implode(',', $access_analytics_roles);

            $access_config_roles = array_unique(array_map('sanitize_text_field', explode(',', $access_config_roles_arr)));
            $access_config_roles = in_array('administrator', $access_config_roles, true) ? $access_config_roles : array_merge(['administrator'], $access_config_roles);
            $access_config_roles = implode(',', $access_config_roles);

            $track_analytics_roles = array_unique(array_map('sanitize_text_field', explode(',', $track_analytics_roles_arr)));
            $track_analytics_roles = in_array('0', $track_analytics_roles, true) ? ['0'] : $track_analytics_roles;
            $track_analytics_roles = implode(',', $track_analytics_roles);

            $this->AddIntoOption('write_docs_roles', $write_docs_roles);
            $this->AddIntoOption('access_analytics_roles', $access_analytics_roles);
            $this->AddIntoOption('access_config_roles', $access_config_roles);
            $this->AddIntoOption('track_analytics_for', $track_analytics_for);
            $this->AddIntoOption('track_analytics_roles', $track_analytics_roles);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfigDesignBase()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $base_type = sanitize_text_field(ApbdWps_PostValue('base_type', ''));
            $term_layout = sanitize_text_field(ApbdWps_PostValue('term_layout', ''));
            $term_number_of_columns = sanitize_text_field(ApbdWps_PostValue('term_number_of_columns', ''));
            $term_orderby = sanitize_text_field(ApbdWps_PostValue('term_orderby', ''));
            $term_order = sanitize_text_field(ApbdWps_PostValue('term_order', ''));
            $term_number_of_docs = sanitize_text_field(ApbdWps_PostValue('term_number_of_docs', ''));
            $term_docs_orderby = sanitize_text_field(ApbdWps_PostValue('term_docs_orderby', ''));
            $term_docs_order = sanitize_text_field(ApbdWps_PostValue('term_docs_order', ''));
            $term_title_link = sanitize_text_field(ApbdWps_PostValue('term_title_link', ''));
            $term_docs_count = sanitize_text_field(ApbdWps_PostValue('term_docs_count', ''));
            $term_docs_count_text = sanitize_text_field(ApbdWps_PostValue('term_docs_count_text', ''));
            $term_docs_count_text_singular = sanitize_text_field(ApbdWps_PostValue('term_docs_count_text_singular', ''));
            $term_view_all_docs_btn = sanitize_text_field(ApbdWps_PostValue('term_view_all_docs_btn', ''));
            $term_view_all_docs_btn_text = sanitize_text_field(ApbdWps_PostValue('term_view_all_docs_btn_text', ''));
            $header_title = sanitize_text_field(ApbdWps_PostValue('header_title', ''));
            $header_subtitle = sanitize_text_field(ApbdWps_PostValue('header_subtitle', ''));

            // Modern Layout Settings
            $use_modern_layout = sanitize_text_field(ApbdWps_PostValue('use_modern_layout', ''));
            $modern_grid_columns = sanitize_text_field(ApbdWps_PostValue('modern_grid_columns', ''));
            $modern_show_hero = sanitize_text_field(ApbdWps_PostValue('modern_show_hero', ''));
            $modern_show_stats = sanitize_text_field(ApbdWps_PostValue('modern_show_stats', ''));
            $modern_show_featured = sanitize_text_field(ApbdWps_PostValue('modern_show_featured', ''));
            $modern_show_icons = sanitize_text_field(ApbdWps_PostValue('modern_show_icons', ''));
            $modern_show_description = sanitize_text_field(ApbdWps_PostValue('modern_show_description', ''));
            $modern_docs_per_category = sanitize_text_field(ApbdWps_PostValue('modern_docs_per_category', ''));
            $show_recent_docs = sanitize_text_field(ApbdWps_PostValue('show_recent_docs', ''));
            $hero_title = sanitize_text_field(ApbdWps_PostValue('hero_title', ''));
            $hero_subtitle = sanitize_text_field(ApbdWps_PostValue('hero_subtitle', ''));

            $term_number_of_columns = max(2, min(intval($term_number_of_columns), 4));
            $term_number_of_docs = max(1, min(intval($term_number_of_docs), 20));
            $modern_docs_per_category = max(1, min(intval($modern_docs_per_category), 20));

            $term_title_link = 'Y' === $term_title_link ? 'Y' : 'N';
            $term_docs_count = 'Y' === $term_docs_count ? 'Y' : 'N';
            $term_view_all_docs_btn = 'Y' === $term_view_all_docs_btn ? 'Y' : 'N';
            $use_modern_layout = 'Y' === $use_modern_layout ? 'Y' : 'N';
            $modern_show_hero = 'Y' === $modern_show_hero ? 'Y' : 'N';
            $modern_show_stats = 'Y' === $modern_show_stats ? 'Y' : 'N';
            $modern_show_featured = 'Y' === $modern_show_featured ? 'Y' : 'N';
            $modern_show_icons = 'Y' === $modern_show_icons ? 'Y' : 'N';
            $modern_show_description = 'Y' === $modern_show_description ? 'Y' : 'N';
            $show_recent_docs = 'Y' === $show_recent_docs ? 'Y' : 'N';

            $this->AddIntoOption('base_type', $base_type);
            $this->AddIntoOption('term_layout', $term_layout);
            $this->AddIntoOption('term_number_of_columns', $term_number_of_columns);
            $this->AddIntoOption('term_orderby', $term_orderby);
            $this->AddIntoOption('term_order', $term_order);
            $this->AddIntoOption('term_number_of_docs', $term_number_of_docs);
            $this->AddIntoOption('term_docs_orderby', $term_docs_orderby);
            $this->AddIntoOption('term_docs_order', $term_docs_order);
            $this->AddIntoOption('term_title_link', $term_title_link);
            $this->AddIntoOption('term_docs_count', $term_docs_count);
            $this->AddIntoOption('term_docs_count_text', $term_docs_count_text);
            $this->AddIntoOption('term_docs_count_text_singular', $term_docs_count_text_singular);
            $this->AddIntoOption('term_view_all_docs_btn', $term_view_all_docs_btn);
            $this->AddIntoOption('term_view_all_docs_btn_text', $term_view_all_docs_btn_text);
            $this->AddIntoOption('header_title', $header_title);
            $this->AddIntoOption('header_subtitle', $header_subtitle);

            // Save Modern Layout Settings
            $this->AddIntoOption('use_modern_layout', $use_modern_layout);
            $this->AddIntoOption('modern_grid_columns', $modern_grid_columns);
            $this->AddIntoOption('modern_show_hero', $modern_show_hero);
            $this->AddIntoOption('modern_show_stats', $modern_show_stats);
            $this->AddIntoOption('modern_show_featured', $modern_show_featured);
            $this->AddIntoOption('modern_show_icons', $modern_show_icons);
            $this->AddIntoOption('modern_show_description', $modern_show_description);
            $this->AddIntoOption('modern_docs_per_category', $modern_docs_per_category);
            $this->AddIntoOption('show_recent_docs', $show_recent_docs);
            $this->AddIntoOption('hero_title', $hero_title);
            $this->AddIntoOption('hero_subtitle', $hero_subtitle);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfigDesignArchive()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $archive_docs_layout = sanitize_text_field(ApbdWps_PostValue('archive_docs_layout', ''));
            $archive_number_of_columns = sanitize_text_field(ApbdWps_PostValue('archive_number_of_columns', ''));
            $archive_docs_per_page = sanitize_text_field(ApbdWps_PostValue('archive_docs_per_page', ''));
            $archive_docs_orderby = sanitize_text_field(ApbdWps_PostValue('archive_docs_orderby', ''));
            $archive_docs_order = sanitize_text_field(ApbdWps_PostValue('archive_docs_order', ''));

            $archive_number_of_columns = max(2, min(intval($archive_number_of_columns), 4));
            $archive_docs_per_page = max(1, min(intval($archive_docs_per_page), 20));

            $this->AddIntoOption('archive_docs_layout', $archive_docs_layout);
            $this->AddIntoOption('archive_number_of_columns', $archive_number_of_columns);
            $this->AddIntoOption('archive_docs_per_page', $archive_docs_per_page);
            $this->AddIntoOption('archive_docs_orderby', $archive_docs_orderby);
            $this->AddIntoOption('archive_docs_order', $archive_docs_order);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfigDesignSingle()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $single_doc_title = sanitize_text_field(ApbdWps_PostValue('single_doc_title', ''));
            $single_doc_thumbnail = sanitize_text_field(ApbdWps_PostValue('single_doc_thumbnail', ''));
            $single_doc_tags = sanitize_text_field(ApbdWps_PostValue('single_doc_tags', ''));
            $single_doc_modified_date = sanitize_text_field(ApbdWps_PostValue('single_doc_modified_date', ''));
            $single_doc_breadcrumb = sanitize_text_field(ApbdWps_PostValue('single_doc_breadcrumb', ''));
            $single_doc_comment = sanitize_text_field(ApbdWps_PostValue('single_doc_comment', ''));
            $single_doc_reaction = sanitize_text_field(ApbdWps_PostValue('single_doc_reaction', ''));

            $single_doc_title = 'Y' === $single_doc_title ? 'Y' : 'N';
            $single_doc_thumbnail = 'Y' === $single_doc_thumbnail ? 'Y' : 'N';
            $single_doc_tags = 'Y' === $single_doc_tags ? 'Y' : 'N';
            $single_doc_modified_date = 'Y' === $single_doc_modified_date ? 'Y' : 'N';
            $single_doc_breadcrumb = 'Y' === $single_doc_breadcrumb ? 'Y' : 'N';
            $single_doc_comment = 'Y' === $single_doc_comment ? 'Y' : 'N';
            $single_doc_reaction = 'Y' === $single_doc_reaction ? 'Y' : 'N';

            $this->AddIntoOption('single_doc_title', $single_doc_title);
            $this->AddIntoOption('single_doc_thumbnail', $single_doc_thumbnail);
            $this->AddIntoOption('single_doc_tags', $single_doc_tags);
            $this->AddIntoOption('single_doc_modified_date', $single_doc_modified_date);
            $this->AddIntoOption('single_doc_breadcrumb', $single_doc_breadcrumb);
            $this->AddIntoOption('single_doc_comment', $single_doc_comment);
            $this->AddIntoOption('single_doc_reaction', $single_doc_reaction);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackConfigDesignStyle()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $style_primary_brand_color = sanitize_text_field(ApbdWps_PostValue('style_primary_brand_color', ''));
            $style_hero_bg_color = sanitize_text_field(ApbdWps_PostValue('style_hero_bg_color', ''));
            $style_main_container_width = sanitize_text_field(ApbdWps_PostValue('style_main_container_width', ''));
            $style_custom_css = ApbdWps_KsesCss(ApbdWps_PostValue('style_custom_css', ''));

            $style_primary_brand_color = strtolower(trim($style_primary_brand_color));
            $style_hero_bg_color = strtolower(trim($style_hero_bg_color));
            $style_main_container_width = absint($style_main_container_width);
            $style_custom_css = trim($style_custom_css);

            if (empty($style_primary_brand_color)) {
                $style_primary_brand_color = '#3b82f6';
            }

            if (empty($style_hero_bg_color)) {
                $style_hero_bg_color = '[{"color":"#3b82f6","percent":0},{"color":"#1e40af","percent":100}]';
            } else {
                $style_hero_bg_color = stripslashes($style_hero_bg_color);
                $style_hero_bg_color = json_decode($style_hero_bg_color, true);

                if ((JSON_ERROR_NONE !== json_last_error())) {
                    $style_hero_bg_color = '[{"color":"#3b82f6","percent":0},{"color":"#1e40af","percent":100}]';
                }

                if (is_array($style_hero_bg_color)) {
                    $style_hero_bg_color = wp_json_encode($style_hero_bg_color);
                }
            }

            if (empty($style_custom_css)) {
                $style_custom_css = '';
            }

            if (1 > strlen($style_primary_brand_color)) {
                $hasError = true;
            }

            $this->AddIntoOption('style_primary_brand_color', $style_primary_brand_color);
            $this->AddIntoOption('style_hero_bg_color', $style_hero_bg_color);
            $this->AddIntoOption('style_main_container_width', $style_main_container_width);
            $this->AddIntoOption('style_custom_css', $style_custom_css);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    /* Docs suggestions */

    public function docs_suggestions_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $docs_suggestions_status = $this->GetOption('docs_suggestions_status', 'I');
        $docs_suggestions_heading = $this->GetOption('docs_suggestions_heading', $this->__('Knowledge Base:'));
        $docs_suggestions_limit = $this->GetOption('docs_suggestions_limit', 5);

        $docs_suggestions_status = ('A' === $docs_suggestions_status) ? true : false;

        $data = [
            'docs_suggestions_status' => $docs_suggestions_status,
            'docs_suggestions_heading' => $docs_suggestions_heading,
            'docs_suggestions_limit' => $docs_suggestions_limit,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackDocsSuggestions()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $docs_suggestions_status = sanitize_text_field(ApbdWps_PostValue('docs_suggestions_status', ''));

            if ('A' === $docs_suggestions_status) {
                $docs_suggestions_heading = sanitize_text_field(ApbdWps_PostValue('docs_suggestions_heading', ''));
                $docs_suggestions_limit = sanitize_text_field(ApbdWps_PostValue('docs_suggestions_limit', ''));

                // Limit.
                $docs_suggestions_limit = max(1, min(intval($docs_suggestions_limit), 20));

                if (
                    (1 > strlen($docs_suggestions_heading))
                ) {
                    $hasError = true;
                }

                $this->AddIntoOption('docs_suggestions_status', 'A');
                $this->AddIntoOption('docs_suggestions_heading', $docs_suggestions_heading);
                $this->AddIntoOption('docs_suggestions_limit', $docs_suggestions_limit);
            } else {
                $this->AddIntoOption('docs_suggestions_status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    /* Permissions */

    public static function UserCanWriteDocs()
    {
        $is_user_logged_in = is_user_logged_in();

        if (!$is_user_logged_in || !current_user_can('edit_posts')) {
            return false;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;
        $user_id = $user->ID;

        if (is_super_admin($user_id) || in_array('administrator', $user_roles)) {
            return true;
        }

        $write_docs_roles = Apbd_wps_knowledge_base::GetModuleOption('write_docs_roles', 'administrator');
        $write_docs_roles = array_unique(array_map('sanitize_text_field', explode(',', $write_docs_roles)));

        if (is_array($user_roles) && !empty($user_roles)) {
            foreach ($user_roles as $user_role) {
                if (in_array($user_role, $write_docs_roles, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function UserCanAccessAnalytics()
    {
        $is_user_logged_in = is_user_logged_in();

        if (!$is_user_logged_in || !current_user_can('edit_posts')) {
            return false;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;
        $user_id = $user->ID;

        if (is_super_admin($user_id) || in_array('administrator', $user_roles)) {
            return true;
        }

        $access_analytics_roles = Apbd_wps_knowledge_base::GetModuleOption('access_analytics_roles', 'administrator');
        $access_analytics_roles = array_unique(array_map('sanitize_text_field', explode(',', $access_analytics_roles)));

        if (is_array($user_roles) && !empty($user_roles)) {
            foreach ($user_roles as $user_role) {
                if (in_array($user_role, $access_analytics_roles, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function UserCanAccessConfig()
    {
        $is_user_logged_in = is_user_logged_in();

        if (!$is_user_logged_in || !current_user_can('edit_posts')) {
            return false;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;
        $user_id = $user->ID;

        if (is_super_admin($user_id) || in_array('administrator', $user_roles)) {
            return true;
        }

        $access_config_roles = Apbd_wps_knowledge_base::GetModuleOption('access_config_roles', 'administrator');
        $access_config_roles = array_unique(array_map('sanitize_text_field', explode(',', $access_config_roles)));

        if (is_array($user_roles) && !empty($user_roles)) {
            foreach ($user_roles as $user_role) {
                if (in_array($user_role, $access_config_roles, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function ShouldTrackAnalytics()
    {
        $is_user_logged_in = is_user_logged_in();

        $track_analytics_for = Apbd_wps_knowledge_base::GetModuleOption('track_analytics_for', 'everyone');
        $track_analytics_roles = Apbd_wps_knowledge_base::GetModuleOption('track_analytics_roles', '0');

        $track_analytics_roles = array_unique(array_map('sanitize_text_field', explode(',', $track_analytics_roles)));
        $track_analytics_roles = in_array('0', $track_analytics_roles, true) ? ['0'] : $track_analytics_roles;

        if ('guest' === $track_analytics_for) {
            return !$is_user_logged_in;
        }

        if (('everyone' === $track_analytics_for) && !$is_user_logged_in) {
            return true;
        }

        if (!$is_user_logged_in) {
            return false;
        }

        if (in_array('0', $track_analytics_roles, true)) {
            return true;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;

        if (is_array($user_roles) && !empty($user_roles)) {
            foreach ($user_roles as $user_role) {
                if (in_array($user_role, $track_analytics_roles, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /* Extra */

    public function FilterChildList($parents, $id)
    {
        $child = [];

        foreach ($parents as $child_id => $parent) {
            if ($parent === strval($id)) {
                $child[] = $child_id;
                $child = array_merge($child, $this->FilterChildList($parents, $child_id));
            }
        }

        return $child;
    }

    public function SetTaxonomyTermOrder($taxonomy, $term_id)
    {
        $order = 1;
        $term_id = absint($term_id);

        $ex_term_ids = get_terms(array(
            'taxonomy' => $taxonomy,
            'meta_key' => '_sg_order',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'number' => 1,
            'hide_empty' => false,
            'hierarchical' => false,
            'fields' => 'ids',
        ));

        if (!is_wp_error($ex_term_ids) && !empty($ex_term_ids)) {
            $max_term_id = isset($ex_term_ids[0]) ? absint($ex_term_ids[0]) : 0;

            if ($max_term_id) {
                $max_taxo_order = get_term_meta($max_term_id, '_sg_order', true);

                if ($max_taxo_order) {
                    $order = absint($max_taxo_order) + 1;
                }
            }
        }

        update_term_meta($term_id, '_sg_order', $order);

        return true;
    }

    public function ChangeTaxonomyTermOrder($taxonomy, $term_id, $order_type)
    {
        $ex_order = get_term_meta($term_id, '_sg_order', true);

        if (('u' == $order_type) && (1 < $ex_order)) {
            $ex_term_ids = get_terms(array(
                'taxonomy' => $taxonomy,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'number' => 1,
                'hide_empty' => false,
                'hierarchical' => false,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_sg_order',
                        'value' => $ex_order,
                        'compare' => '<',
                        'type' => 'NUMERIC'
                    )
                )
            ));

            if (!is_wp_error($ex_term_ids) && !empty($ex_term_ids)) {
                $max_term_id = isset($ex_term_ids[0]) ? absint($ex_term_ids[0]) : 0;

                if ($max_term_id) {
                    $max_taxo_order = get_term_meta($max_term_id, '_sg_order', true);

                    if ($max_taxo_order) {
                        update_term_meta($max_term_id, '_sg_order', absint($ex_order));
                        update_term_meta($term_id, '_sg_order', absint($max_taxo_order));
                    }
                }
            }
        } elseif ('d' == $order_type) {
            $ex_term_ids = get_terms(array(
                'taxonomy' => $taxonomy,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
                'number' => 1,
                'hide_empty' => false,
                'hierarchical' => false,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_sg_order',
                        'value' => $ex_order,
                        'compare' => '>',
                        'type' => 'NUMERIC'
                    )
                )
            ));

            if (!is_wp_error($ex_term_ids) && !empty($ex_term_ids)) {
                $min_term_id = isset($ex_term_ids[0]) ? absint($ex_term_ids[0]) : 0;

                if ($min_term_id) {
                    $min_taxo_order = get_term_meta($min_term_id, '_sg_order', true);

                    if ($min_taxo_order) {
                        update_term_meta($min_term_id, '_sg_order', absint($ex_order));
                        update_term_meta($term_id, '_sg_order', absint($min_taxo_order));
                    }
                }
            }
        }

        return true;
    }

    public function ResetTaxonomyTermOrder($taxonomy)
    {
        $term_ids = get_terms(array(
            'taxonomy' => $taxonomy,
            'meta_key' => '_sg_order',
            'orderby' => 'id',
            'order' => 'ASC',
            'hide_empty' => false,
            'hierarchical' => false,
            'fields' => 'ids',
        ));

        if (!is_wp_error($term_ids) && !empty($term_ids)) {
            $order = 1;

            foreach ($term_ids as $term_id) {
                update_term_meta($term_id, '_sg_order', $order);
                $order++;
            }
        }

        return true;
    }

    public function FillTaxonomyTermOrder($taxonomy)
    {
        $term_ids = get_terms(array(
            'taxonomy' => $taxonomy,
            'meta_key' => '_sg_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'hide_empty' => false,
            'hierarchical' => false,
            'fields' => 'ids',
        ));

        if (!is_wp_error($term_ids) && !empty($term_ids)) {
            $order = 1;

            foreach ($term_ids as $term_id) {
                update_term_meta($term_id, '_sg_order', $order);
                $order++;
            }
        }

        return true;
    }

    public function PrepareAnalyticsDateRange($date_range_str = '')
    {
        $date_range = explode('-to-', $date_range_str);
        $date_range = is_array($date_range) ? $date_range : [];

        $date_start = (isset($date_range[0]) ? sanitize_text_field($date_range[0]) : '');
        $date_ended = (isset($date_range[1]) ? sanitize_text_field($date_range[1]) : '');

        if (!$date_start || !$date_ended) {
            $current_date = current_time('Y-m-d');
            $date_start = date_format(date_create($current_date)->sub(new DateInterval('P1M'))->add(new DateInterval('P1D')), 'Y-m-d');
            $date_ended = $current_date;
        }

        $date_start_obj = new DateTime($date_start);
        $date_ended_obj = new DateTime($date_ended);
        $date_reange_diff = $date_start_obj->diff($date_ended_obj);

        $prev_date_ended_obj = clone $date_start_obj;
        $prev_date_ended_obj->sub(new DateInterval('P1D'));

        $prev_date_start_obj = clone $prev_date_ended_obj;
        $prev_date_start_obj->sub($date_reange_diff);

        $prev_date_start = $prev_date_start_obj->format('Y-m-d');
        $prev_date_ended = $prev_date_ended_obj->format('Y-m-d');

        return [
            'date_start' => $date_start,
            'date_ended' => $date_ended,
            'prev_date_start' => $prev_date_start,
            'prev_date_ended' => $prev_date_ended,
        ];
    }

    /**
     * Handle article feedback AJAX request
     */
    public function handle_article_feedback()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
            wp_die(__('Security check failed', 'support-genix'));
        }

        $article_id = absint($_POST['article_id']);
        $feedback_type = sanitize_text_field($_POST['feedback_type']);

        // Validate inputs
        if (!$article_id || !in_array($feedback_type, ['helpful', 'not-helpful'])) {
            wp_send_json_error(__('Invalid feedback data', 'support-genix'));
        }

        // Check if post exists and is a docs post
        $post = get_post($article_id);
        if (!$post || $post->post_type !== 'sgkb-docs') {
            wp_send_json_error(__('Article not found', 'support-genix'));
        }

        // Store feedback in post meta
        $feedback_key = '_sgkb_feedback_' . $feedback_type;
        $current_count = absint(get_post_meta($article_id, $feedback_key, true));
        update_post_meta($article_id, $feedback_key, $current_count + 1);

        // Store total feedback count
        $total_feedback = absint(get_post_meta($article_id, '_sgkb_feedback_total', true));
        update_post_meta($article_id, '_sgkb_feedback_total', $total_feedback + 1);

        // Convert feedback to analytics format and store in analytics table
        $reaction = ($feedback_type === 'helpful') ? 'positive' : 'negative';
        $this->store_analytics_reaction($article_id, $reaction);

        // Optional: Store user feedback to prevent duplicate submissions
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $user_feedback_key = '_sgkb_feedback_' . md5($user_ip . $article_id);
        update_post_meta($article_id, $user_feedback_key, $feedback_type);

        wp_send_json_success([
            'message' => __('Thank you for your feedback!', 'support-genix'),
            'feedback_type' => $feedback_type
        ]);
    }

    /**
     * Get popular search keywords for display
     */
    public function get_popular_searches($limit = 5)
    {
        global $wpdb;

        // Get date range for last 30 days
        $date_ended = current_time('Y-m-d');
        $date_start = date('Y-m-d', strtotime('-30 days'));

        // Get table names
        $keywordsobj = new Mapbd_wps_docs_search_keywords();
        $keywords_table = $keywordsobj->GetTableName();

        $eventsobj = new Mapbd_wps_docs_searches_events();
        $events_table = $eventsobj->GetTableName();

        // Query to get top searched keywords that had results
        $result = $wpdb->get_results($wpdb->prepare("
            SELECT k.keyword, SUM(e.count) as total_count
            FROM {$keywords_table} k
            JOIN {$events_table} e ON k.id = e.keyword_id
            WHERE e.founded = 'Y' AND e.created_date BETWEEN %s AND %s
            GROUP BY k.id, k.keyword
            ORDER BY total_count DESC, k.id DESC
            LIMIT %d
        ", $date_start, $date_ended, $limit));

        // If no searches found, return empty array
        if (empty($result)) {
            return array();
        }

        return $result;
    }

    /**
     * From version 1.8.26
     */
    static function TransferSettings()
    {
        $instance = Apbd_wps_knowledge_base::GetModuleInstance();

        $term_number_of_columns = $instance->GetOption('term_number_of_columns', 3);
        $term_number_of_docs = $instance->GetOption('term_number_of_docs', 5);
        $header_title = $instance->GetOption('header_title', $instance->__('Knowledge Base'));
        $header_subtitle = $instance->GetOption('header_subtitle', $instance->__('Search our knowledge base or discover helpful articles and resources'));
        $chatbot_create_ticket_link = $instance->GetOption('chatbot_create_ticket_link', 'Y');

        $chatbot_create_ticket_link = 'Y' === $chatbot_create_ticket_link ? 'Y' : 'N';

        $instance->AddIntoOption('modern_grid_columns', $term_number_of_columns);
        $instance->AddIntoOption('modern_docs_per_category', $term_number_of_docs);
        $instance->AddIntoOption('hero_title', $header_title);
        $instance->AddIntoOption('hero_subtitle', $header_subtitle);
        $instance->AddIntoOption('chatbot_enable_create_ticket', $chatbot_create_ticket_link);

        $instance->UpdateOption();
    }

    /**
     * Build language filtering SQL fragments for WPML/Polylang.
     *
     * @param string $lang_code Optional language code. If empty, detects the current language.
     * @return array ['join_sql' => string, 'where_sql' => string, 'params' => array]
     */
    protected static function buildLanguageFilterSQL($lang_code = '')
    {
        global $wpdb;

        $result = ['join_sql' => '', 'where_sql' => '', 'params' => []];

        if (empty($lang_code)) {
            if (class_exists('SitePress')) {
                $lang_code = apply_filters('wpml_current_language', null);
            } elseif (function_exists('pll_current_language')) {
                $lang_code = pll_current_language();
            }
        }

        if (empty($lang_code)) {
            return $result;
        }

        // WPML language filtering
        if (class_exists('SitePress')) {
            $icl_table = $wpdb->prefix . 'icl_translations';
            $result['join_sql'] = " INNER JOIN {$icl_table} wpml_t ON p.ID = wpml_t.element_id AND wpml_t.element_type = 'post_sgkb-docs'";
            $result['where_sql'] = " AND wpml_t.language_code = %s";
            $result['params'][] = $lang_code;
        }
        // Polylang language filtering
        elseif (function_exists('pll_current_language')) {
            $lang_term = get_term_by('slug', $lang_code, 'language');
            if ($lang_term && !is_wp_error($lang_term)) {
                $result['join_sql'] = " INNER JOIN {$wpdb->term_relationships} lang_tr ON p.ID = lang_tr.object_id";
                $result['join_sql'] .= " INNER JOIN {$wpdb->term_taxonomy} lang_tt ON lang_tr.term_taxonomy_id = lang_tt.term_taxonomy_id";
                $result['where_sql'] = " AND lang_tt.taxonomy = 'language' AND lang_tt.term_id = %d";
                $result['params'][] = $lang_term->term_id;
            }
        }

        return $result;
    }
}
