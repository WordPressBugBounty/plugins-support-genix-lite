<?php

/**
 * Chatbot history.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_chatbot_history extends ApbdWpsModel
{
    public $id;
    public $user_id;
    public $session_id;
    public $guest_identifier;
    public $query;
    public $content;
    public $feedback;
    public $docs_ids;
    public $is_stored_content;
    public $conv_hash;
    public $created_at;
    public $updated_at;
    // @ Dynamic
    public $docs_list;

    /**
     * @property id,user_id,session_id,guest_identifier,query,content,feedback,docs_ids,is_stored_content,conv_hash,updated_at,created_at
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_chatbot_history";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->htmlInputField = ['query', 'content'];
        $this->app_base_name = "support-genix-lite";
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "user_id" => array("Text" => "User Id", "Rule" => "max_length[11]|integer"),
            "session_id" => array("Text" => "Session ID", "Rule" => "max_length[64]"),
            "guest_identifier" => array("Text" => "Guest Identifier", "Rule" => "max_length[64]"),
            "feedback" => array("Text" => "Feedback", "Rule" => "max_length[1]"),
            "docs_ids" => array("Text" => "Doc IDs", "Rule" => "max_length[255]"),
            "is_stored_content" => array("Text" => "Is Stored Content", "Rule" => "max_length[1]"),
            "conv_hash" => array("Text" => "Hash", "Rule" => "xss_clean|max_length[40]"),
            "created_at" => array("Text" => "Created At", "Rule" => "max_length[20]"),
            "updated_at" => array("Text" => "Updated At", "Rule" => "max_length[20]"),
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();

        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }

        return $returnObj;
    }

    public function GetPropertyOptionsColor($property)
    {
        return array();
    }

    public function GetPropertyOptionsIcon($property)
    {
        return array();
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL DEFAULT 0,
                `session_id` varchar(64) NULL DEFAULT NULL COMMENT 'Session ID for grouping conversations',
                `guest_identifier` varchar(64) NULL DEFAULT NULL COMMENT 'Hashed guest identifier',
                `query` text NOT NULL COMMENT 'textarea',
                `content` longtext NOT NULL COMMENT 'textarea',
                `docs_ids` varchar(255) NOT NULL DEFAULT '',
                `is_stored_content` char(1) NOT NULL DEFAULT 'Y' COMMENT 'Whether AI response was stored',
                `feedback` char(1) NOT NULL DEFAULT 'N' COMMENT 'drop(H=Happy,N=Neutral,U=Unhappy)',
                `conv_hash` char(40) NOT NULL DEFAULT '',
                `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`id`),
                KEY `idx_session_id` (`session_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_guest_identifier` (`guest_identifier`),
                KEY `idx_created_at` (`created_at`)
            ) $charsetCollate;";
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        }
    }

    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $sql = "DROP TABLE IF EXISTS $table_name;";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $wpdb->query($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /* Additional */

    static function create_user_history($query, $content, $docs_ids)
    {
        $logged_in = is_user_logged_in();
        $user_id = get_current_user_id();

        if (!$logged_in || !$user_id) {
            return null;
        }

        $docs_ids = (is_array($docs_ids) ? implode(',', $docs_ids) : (is_string($docs_ids) ? $docs_ids : ''));
        $conv_hash = md5(uniqid(wp_rand(), true));
        $current_time = gmdate("Y-m-d H:i:s");

        $history = new Mapbd_wps_chatbot_history();
        $history->user_id($user_id);
        $history->query($query);
        $history->content($content);
        $history->docs_ids($docs_ids);
        $history->feedback('N');
        $history->conv_hash($conv_hash);
        $history->created_at($current_time);
        $history->updated_at($current_time);

        if ($history->save()) {
            global $wpdb;
            $table_name = $history->getTableName();

            // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            $current_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
                $user_id
            ));
            // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

            if (20 < $current_count) {
                // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table_name}
                    WHERE user_id = %d
                    AND id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM {$table_name}
                            WHERE user_id = %d
                            ORDER BY id DESC
                            LIMIT 20
                        ) AS keep_items
                    )",
                    $user_id,
                    $user_id
                ));
                // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB
            }

            unset($history->id);
            unset($history->user_id);
            unset($history->docs_ids);
            unset($history->created_at);
            unset($history->updated_at);
            unset($history->settedPropertyforLog);

            return $history;
        }

        return null;
    }

    static function create_guest_history($query, $content, $docs_ids)
    {
        $docs_ids = (is_array($docs_ids) ? implode(',', $docs_ids) : (is_string($docs_ids) ? $docs_ids : ''));
        $conv_hash = md5(uniqid(wp_rand(), true));

        $history = new Mapbd_wps_chatbot_history();
        $history->query = $query;
        $history->content = $content;
        $history->feedback = 'N';
        $history->conv_hash = $conv_hash;

        unset($history->id);
        unset($history->user_id);
        unset($history->docs_ids);
        unset($history->created_at);
        unset($history->updated_at);
        unset($history->settedPropertyforLog);

        return $history;
    }

    static function create_error_history($query, $content, $docs_ids)
    {
        $docs_ids = (is_array($docs_ids) ? implode(',', $docs_ids) : (is_string($docs_ids) ? $docs_ids : ''));
        $conv_hash = md5(uniqid(wp_rand(), true));

        $history = new Mapbd_wps_chatbot_history();
        $history->query = $query;
        $history->content = $content;
        $history->conv_hash = $conv_hash;

        unset($history->id);
        unset($history->user_id);
        unset($history->docs_ids);
        unset($history->feedback);
        unset($history->created_at);
        unset($history->updated_at);
        unset($history->settedPropertyforLog);

        return $history;
    }

    /**
     * Get all messages in a session.
     *
     * @param string $session_id
     * @return array
     */
    public static function getSessionMessages($session_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_results($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get conversation statistics for analytics.
     *
     * @param string $date_from
     * @param string $date_to
     * @return object
     */
    public static function getConversationStats($date_from, $date_to)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                COUNT(DISTINCT session_id) as total_sessions,
                COUNT(*) as total_messages,
                SUM(CASE WHEN feedback = 'H' THEN 1 ELSE 0 END) as helpful_count,
                SUM(CASE WHEN feedback = 'U' THEN 1 ELSE 0 END) as unhelpful_count,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_messages
            FROM {$table}
            WHERE session_id IS NOT NULL
            AND created_at BETWEEN %s AND %s
        ", $date_from . ' 00:00:00', $date_to . ' 23:59:59');
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_row($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get hourly distribution for peak hours chart.
     *
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public static function getHourlyDistribution($date_from, $date_to)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                HOUR(created_at) as hour,
                COUNT(DISTINCT session_id) as count
            FROM {$table}
            WHERE session_id IS NOT NULL
            AND created_at BETWEEN %s AND %s
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ", $date_from . ' 00:00:00', $date_to . ' 23:59:59');
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_results($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get daily trend for line chart.
     *
     * @param string $date_from
     * @param string $date_to
     * @return array
     */
    public static function getDailyTrend($date_from, $date_to)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                DATE(created_at) as date,
                COUNT(DISTINCT session_id) as count
            FROM {$table}
            WHERE session_id IS NOT NULL
            AND created_at BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $date_from . ' 00:00:00', $date_to . ' 23:59:59');
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_results($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get conversation stats with full UTC datetime range.
     *
     * @param string $date_from_utc Full UTC datetime (Y-m-d H:i:s)
     * @param string $date_to_utc Full UTC datetime (Y-m-d H:i:s)
     * @param array $filters Optional filters (user_type, feedback)
     * @return object
     */
    public static function getConversationStatsUTC($date_from_utc, $date_to_utc, $filters = array())
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';
        $session_table = $wpdb->prefix . 'apbd_wps_chatbot_session';

        $where = "h.session_id IS NOT NULL AND h.created_at BETWEEN %s AND %s";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $params = array($date_from_utc, $date_to_utc);
        $join = '';

        // User type filter
        if (!empty($filters['user_type'])) {
            if ($filters['user_type'] === 'guest') {
                $where .= " AND h.user_id = 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['user_type'] === 'user') {
                $where .= " AND h.user_id > 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Feedback filter - filter to sessions that have matching feedback
        if (!empty($filters['feedback'])) {
            if ($filters['feedback'] === 'helpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'H')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'unhelpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'U')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'none') {
                $where .= " AND h.session_id NOT IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback IN ('H', 'U'))";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Source filter
        if (!empty($filters['source'])) {
            $join = " INNER JOIN {$session_table} cs ON h.session_id = cs.session_id";
            if ($filters['source'] === 'main') {
                $where .= " AND cs.source = 'M'";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif (strpos($filters['source'], 'embed_') === 0) {
                $embed_id = absint(str_replace('embed_', '', $filters['source']));
                if ($embed_id > 0) {
                    $where .= " AND cs.source = 'E' AND cs.embed_token_id = %d";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
                    $params[] = $embed_id;
                }
            }
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                COUNT(DISTINCT h.session_id) as total_sessions,
                COUNT(*) as total_messages,
                SUM(CASE WHEN h.feedback = 'H' THEN 1 ELSE 0 END) as helpful_count,
                SUM(CASE WHEN h.feedback = 'U' THEN 1 ELSE 0 END) as unhelpful_count
            FROM {$table} h
            {$join}
            WHERE {$where}
        ", $params);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_row($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get daily trend with timezone offset for proper local date grouping.
     *
     * @param string $date_from_utc Full UTC datetime (Y-m-d H:i:s)
     * @param string $date_to_utc Full UTC datetime (Y-m-d H:i:s)
     * @param int $tz_offset Timezone offset in minutes (positive = ahead of UTC)
     * @param array $filters Optional filters (user_type, feedback)
     * @return array
     */
    public static function getDailyTrendWithTimezone($date_from_utc, $date_to_utc, $tz_offset, $filters = array())
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';
        $session_table = $wpdb->prefix . 'apbd_wps_chatbot_session';

        $where = "h.session_id IS NOT NULL AND h.created_at BETWEEN %s AND %s";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $params = array($tz_offset, $date_from_utc, $date_to_utc);
        $join = '';

        // User type filter
        if (!empty($filters['user_type'])) {
            if ($filters['user_type'] === 'guest') {
                $where .= " AND h.user_id = 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['user_type'] === 'user') {
                $where .= " AND h.user_id > 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Feedback filter
        if (!empty($filters['feedback'])) {
            if ($filters['feedback'] === 'helpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'H')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'unhelpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'U')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'none') {
                $where .= " AND h.session_id NOT IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback IN ('H', 'U'))";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Source filter
        if (!empty($filters['source'])) {
            $join = " INNER JOIN {$session_table} cs ON h.session_id = cs.session_id";
            if ($filters['source'] === 'main') {
                $where .= " AND cs.source = 'M'";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif (strpos($filters['source'], 'embed_') === 0) {
                $embed_id = absint(str_replace('embed_', '', $filters['source']));
                if ($embed_id > 0) {
                    $where .= " AND cs.source = 'E' AND cs.embed_token_id = %d";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
                    $params[] = $embed_id;
                }
            }
        }

        // Add tz_offset again for GROUP BY clause
        $params[] = $tz_offset;

        // Convert UTC to local time using DATE_ADD with offset in minutes
        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                DATE(DATE_ADD(h.created_at, INTERVAL %d MINUTE)) as date,
                COUNT(DISTINCT h.session_id) as count
            FROM {$table} h
            {$join}
            WHERE {$where}
            GROUP BY DATE(DATE_ADD(h.created_at, INTERVAL %d MINUTE))
            ORDER BY date ASC
        ", $params);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_results($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Get hourly distribution with timezone offset for proper local hour grouping.
     *
     * @param string $date_from_utc Full UTC datetime (Y-m-d H:i:s)
     * @param string $date_to_utc Full UTC datetime (Y-m-d H:i:s)
     * @param int $tz_offset Timezone offset in minutes (positive = ahead of UTC)
     * @param array $filters Optional filters (user_type, feedback)
     * @return array
     */
    public static function getHourlyDistributionWithTimezone($date_from_utc, $date_to_utc, $tz_offset, $filters = array())
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';
        $session_table = $wpdb->prefix . 'apbd_wps_chatbot_session';

        $where = "h.session_id IS NOT NULL AND h.created_at BETWEEN %s AND %s";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $params = array($tz_offset, $date_from_utc, $date_to_utc);
        $join = '';

        // User type filter
        if (!empty($filters['user_type'])) {
            if ($filters['user_type'] === 'guest') {
                $where .= " AND h.user_id = 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['user_type'] === 'user') {
                $where .= " AND h.user_id > 0";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Feedback filter
        if (!empty($filters['feedback'])) {
            if ($filters['feedback'] === 'helpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'H')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'unhelpful') {
                $where .= " AND h.session_id IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback = 'U')";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif ($filters['feedback'] === 'none') {
                $where .= " AND h.session_id NOT IN (SELECT DISTINCT session_id FROM {$table} WHERE feedback IN ('H', 'U'))";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            }
        }

        // Source filter
        if (!empty($filters['source'])) {
            $join = " INNER JOIN {$session_table} cs ON h.session_id = cs.session_id";
            if ($filters['source'] === 'main') {
                $where .= " AND cs.source = 'M'";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
            } elseif (strpos($filters['source'], 'embed_') === 0) {
                $embed_id = absint(str_replace('embed_', '', $filters['source']));
                if ($embed_id > 0) {
                    $where .= " AND cs.source = 'E' AND cs.embed_token_id = %d";  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
                    $params[] = $embed_id;
                }
            }
        }

        // Add tz_offset again for GROUP BY clause
        $params[] = $tz_offset;

        // Convert UTC to local time using DATE_ADD with offset in minutes
        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare("
            SELECT
                HOUR(DATE_ADD(h.created_at, INTERVAL %d MINUTE)) as hour,
                COUNT(DISTINCT h.session_id) as count
            FROM {$table} h
            {$join}
            WHERE {$where}
            GROUP BY HOUR(DATE_ADD(h.created_at, INTERVAL %d MINUTE))
            ORDER BY hour ASC
        ", $params);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->get_results($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Delete records older than specified days.
     *
     * @param int $days
     * @return int|false Number of rows deleted or false on error
     */
    public static function deleteOlderThan($days)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_history';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $sql = $wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            absint($days)
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB

        return $wpdb->query($sql);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
    }

    /**
     * Update table structure for new fields.
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();

        // Add session_id column
        $thisObj->DBColumnAddOrModify(
            'session_id',
            'varchar',
            64,
            'NULL',
            'NULL',
            'user_id',
            'Session ID for grouping conversations'
        );

        // Add guest_identifier column
        $thisObj->DBColumnAddOrModify(
            'guest_identifier',
            'varchar',
            64,
            'NULL',
            'NULL',
            'session_id',
            'Hashed guest identifier'
        );

        // Add is_stored_content column
        $thisObj->DBColumnAddOrModify(
            'is_stored_content',
            'char',
            1,
            "'Y'",
            'NOT NULL',
            'docs_ids',
            'Whether AI response was stored'
        );

        // Add indexes
        global $wpdb;
        $table = $wpdb->prefix . $thisObj->tableName;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = %s AND table_name = %s AND index_name = %s",
            DB_NAME,
            $table,
            'idx_session_id'
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_session_id (session_id)");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = %s AND table_name = %s AND index_name = %s",
            DB_NAME,
            $table,
            'idx_created_at'
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_created_at (created_at)");  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, PluginCheck.Security.DirectDB -- Custom plugin table; direct query intentional, identifiers are internal $wpdb->prefix names, values prepared/sanitized.
        }
    }
}
