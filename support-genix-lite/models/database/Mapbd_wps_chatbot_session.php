<?php

/**
 * Chatbot session - Denormalized table for fast conversation list queries.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_chatbot_session extends ApbdWpsModel
{
    public $id;
    public $session_id;
    public $user_id;
    public $guest_identifier;
    public $first_query;
    public $message_count;
    public $last_feedback;
    public $session_type;
    public $duration;
    public $is_starred;
    public $source;
    public $embed_token_id;
    public $page_url;
    public $started_at;
    public $last_activity_at;

    /**
     * @property id,session_id,user_id,guest_identifier,first_query,message_count,last_feedback,session_type,duration,is_starred,source,embed_token_id,page_url,started_at,last_activity_at
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_chatbot_session";
        $this->primaryKey = "id";
        $this->uniqueKey = array(array("session_id"));
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix";
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "session_id" => array("Text" => "Session ID", "Rule" => "required|max_length[64]"),
            "user_id" => array("Text" => "User Id", "Rule" => "max_length[11]|integer"),
            "guest_identifier" => array("Text" => "Guest Identifier", "Rule" => "max_length[64]"),
            "first_query" => array("Text" => "First Query", "Rule" => "max_length[255]"),
            "message_count" => array("Text" => "Message Count", "Rule" => "max_length[11]|integer"),
            "last_feedback" => array("Text" => "Last Feedback", "Rule" => "max_length[1]"),
            "session_type" => array("Text" => "Session Type", "Rule" => "max_length[1]"),
            "duration" => array("Text" => "Duration", "Rule" => "max_length[11]|integer"),
            "is_starred" => array("Text" => "Is Starred", "Rule" => "max_length[1]|integer"),
            "source" => array("Text" => "Source", "Rule" => "max_length[1]"),
            "embed_token_id" => array("Text" => "Embed Token Id", "Rule" => "max_length[11]|integer"),
            "page_url" => array("Text" => "Page URL", "Rule" => "max_length[500]"),
            "started_at" => array("Text" => "Started At", "Rule" => "max_length[20]"),
            "last_activity_at" => array("Text" => "Last Activity At", "Rule" => "max_length[20]"),
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();

        if ($property === "last_feedback") {
            $returnObj = array("H" => "Helpful", "N" => "Neutral", "U" => "Unhelpful");
        } elseif ($property === "session_type") {
            $returnObj = array("T" => "Text", "V" => "Voice");
        } elseif ($property === "source") {
            $returnObj = array("M" => "Main Site", "E" => "Embed");
        }

        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }

        return $returnObj;
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `session_id` varchar(64) NOT NULL,
                `user_id` int(11) NOT NULL DEFAULT 0,
                `guest_identifier` varchar(64) DEFAULT NULL,
                `first_query` varchar(255) NOT NULL,
                `message_count` int(11) NOT NULL DEFAULT 1,
                `last_feedback` char(1) NOT NULL DEFAULT 'N' COMMENT 'drop(H=Helpful,N=Neutral,U=Unhelpful)',
                `session_type` char(1) NOT NULL DEFAULT 'T' COMMENT 'drop(T=Text,V=Voice)',
                `duration` int(11) DEFAULT NULL,
                `is_starred` tinyint(1) NOT NULL DEFAULT 0,
                `source` char(1) NOT NULL DEFAULT '' COMMENT 'drop(M=Main Site,E=Embed)',
                `embed_token_id` int(11) NOT NULL DEFAULT 0,
                `page_url` varchar(500) NOT NULL DEFAULT '',
                `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_activity_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_session_id` (`session_id`),
                KEY `idx_started_at` (`started_at`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_is_starred` (`is_starred`),
                KEY `idx_source` (`source`)
            ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    static function UpdateDBTable()
    {
        $thisObj = new static();
        // Add session_type column: T = Text, V = Voice
        $thisObj->DBColumnAddOrModify('session_type', 'char', 1, "'T'", 'NOT NULL', 'last_feedback', 'drop(T=Text,V=Voice)');
        // Add duration column for voice chat duration in seconds
        $thisObj->DBColumnAddOrModify('duration', 'int', 11, 'NULL', '', 'session_type', '');
    }

    static function UpdateDBTable2()
    {
        $thisObj = new static();
        // Add is_starred column for marking important conversations (prevents auto-delete)
        $thisObj->DBColumnAddOrModify('is_starred', 'tinyint', 1, '0', 'NOT NULL', 'duration', '');

        // Add index for is_starred column for efficient filtering
        global $wpdb;
        $table = $wpdb->prefix . $thisObj->tableName;
        $index_name = 'idx_is_starred';
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = %s AND table_name = %s AND index_name = %s",
            DB_NAME,
            $table,
            $index_name
        ));
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` (`is_starred`)");
        }
    }

    static function UpdateDBTable3()
    {
        $thisObj = new static();
        // Add source column: M = Main Site, E = Embed
        $thisObj->DBColumnAddOrModify('source', 'char', 1, "''", 'NOT NULL', 'is_starred', 'drop(M=Main Site,E=Embed)');
        // Add embed_token_id column for tracking which embed token was used
        $thisObj->DBColumnAddOrModify('embed_token_id', 'int', 11, '0', 'NOT NULL', 'source', '');
        // Add page_url column for tracking the browser URL where the chat session started
        $thisObj->DBColumnAddOrModify('page_url', 'varchar', 500, "''", 'NOT NULL', 'embed_token_id', '');

        // Add index for source column for efficient filtering
        global $wpdb;
        $table = $wpdb->prefix . $thisObj->tableName;
        $index_name = 'idx_source';
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = %s AND table_name = %s AND index_name = %s",
            DB_NAME,
            $table,
            $index_name
        ));
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` (`source`)");
        }
    }

    function DropDBTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tableName;
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }

    /**
     * Normalize page URL: trailing slash for pages, no trailing slash for home.
     *
     * @param string $url
     * @return string
     */
    private static function normalizePageUrl($url)
    {
        $url = sanitize_url(strtok($url, '?'));
        if (empty($url)) {
            return '';
        }
        $path = wp_parse_url($url, PHP_URL_PATH);
        if (empty($path) || $path === '/') {
            return rtrim($url, '/');
        }
        return trailingslashit($url);
    }

    /**
     * Find or create session record.
     *
     * @param string $session_id
     * @param array $data
     * @return Mapbd_wps_chatbot_session
     */
    public static function findOrCreate($session_id, $data = array())
    {
        $session = self::FindBy('session_id', $session_id);

        if ($session) {
            // Update existing session - use setter methods to mark properties for update
            $session->message_count((int) $session->message_count + 1);
            $session->last_activity_at(gmdate('Y-m-d H:i:s'));
            if (!empty($data['feedback']) && $data['feedback'] !== 'N') {
                $session->last_feedback($data['feedback']);
            }
            $session->Update();
            return $session;
        }

        // Create new session - use setter methods to mark properties for save
        $session = new self();
        $session->session_id($session_id);
        $session->user_id(isset($data['user_id']) ? absint($data['user_id']) : 0);
        $session->guest_identifier(isset($data['guest_identifier']) ? $data['guest_identifier'] : null);
        $session->first_query(isset($data['first_query']) ? substr(sanitize_text_field($data['first_query']), 0, 255) : '');
        $session->message_count(1);
        $session->last_feedback(isset($data['feedback']) ? $data['feedback'] : 'N');
        $session->session_type(isset($data['session_type']) ? $data['session_type'] : 'T');
        $session->duration(isset($data['duration']) ? absint($data['duration']) : null);
        $session->source(isset($data['source']) ? sanitize_text_field($data['source']) : 'M');
        $session->embed_token_id(isset($data['embed_token_id']) ? absint($data['embed_token_id']) : 0);
        $session->page_url(isset($data['page_url']) ? self::normalizePageUrl($data['page_url']) : '');
        $session->started_at(gmdate('Y-m-d H:i:s'));
        $session->last_activity_at(gmdate('Y-m-d H:i:s'));
        $session->Save();

        return $session;
    }

    /**
     * Get paginated sessions for list view.
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @param string $orderBy
     * @return array
     */
    public static function getSessions($filters = array(), $page = 1, $limit = 20, $orderBy = 'started_at DESC')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_session';
        $users_table = $wpdb->prefix . 'users';

        $where = array("1=1");
        $params = array();

        // Date filter (using UTC datetime boundaries)
        if (!empty($filters['date_from_utc'])) {
            $where[] = "s.started_at >= %s";
            $params[] = $filters['date_from_utc'];
        }
        if (!empty($filters['date_to_utc'])) {
            $where[] = "s.started_at <= %s";
            $params[] = $filters['date_to_utc'];
        }

        // User type filter
        if (!empty($filters['user_type'])) {
            if ($filters['user_type'] === 'guest') {
                $where[] = "s.user_id = 0";
            } elseif ($filters['user_type'] === 'user') {
                $where[] = "s.user_id > 0";
            }
        }

        // Feedback filter - check history table for actual message feedback
        $history_table_filter = $wpdb->prefix . 'apbd_wps_chatbot_history';
        if (!empty($filters['feedback'])) {
            if ($filters['feedback'] === 'helpful') {
                // Sessions with at least one helpful feedback
                $where[] = "EXISTS (SELECT 1 FROM {$history_table_filter} hf WHERE hf.session_id = s.session_id AND hf.feedback = 'H')";
            } elseif ($filters['feedback'] === 'unhelpful') {
                // Sessions with at least one unhelpful feedback
                $where[] = "EXISTS (SELECT 1 FROM {$history_table_filter} hf WHERE hf.session_id = s.session_id AND hf.feedback = 'U')";
            } elseif ($filters['feedback'] === 'none') {
                // Sessions with no helpful or unhelpful feedback (only 'N' or NULL)
                $where[] = "NOT EXISTS (SELECT 1 FROM {$history_table_filter} hf WHERE hf.session_id = s.session_id AND hf.feedback IN ('H', 'U'))";
            }
        }

        // Source filter
        if (!empty($filters['source'])) {
            if ($filters['source'] === 'main') {
                $where[] = "s.source = 'M'";
            } elseif (strpos($filters['source'], 'embed_') === 0) {
                $embed_id = absint(str_replace('embed_', '', $filters['source']));
                if ($embed_id > 0) {
                    $where[] = "s.source = 'E'";
                    $where[] = "s.embed_token_id = %d";
                    $params[] = $embed_id;
                }
            }
        }

        // Starred filter
        if (!empty($filters['starred'])) {
            if ($filters['starred'] === 'starred') {
                $where[] = "s.is_starred = 1";
            } elseif ($filters['starred'] === 'unstarred') {
                $where[] = "s.is_starred = 0";
            }
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "s.first_query LIKE %s";
            $params[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $limit;

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$table} s WHERE {$where_sql}";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = (int) $wpdb->get_var($count_sql);

        // Get paginated results with user info
        $history_table = $wpdb->prefix . 'apbd_wps_chatbot_history';
        $embed_token_table = $wpdb->prefix . 'apbd_wps_chatbot_embed_token';

        $sql = "
            SELECT
                s.*,
                u.user_email,
                u.display_name as user_name,
                et.title as embed_title,
                (SELECT COUNT(*) FROM {$history_table} h WHERE h.session_id = s.session_id) as actual_message_count,
                (SELECT SUM(CASE WHEN h.feedback = 'H' THEN 1 ELSE 0 END) FROM {$history_table} h WHERE h.session_id = s.session_id) as helpful_count,
                (SELECT SUM(CASE WHEN h.feedback = 'U' THEN 1 ELSE 0 END) FROM {$history_table} h WHERE h.session_id = s.session_id) as unhelpful_count
            FROM {$table} s
            LEFT JOIN {$users_table} u ON s.user_id = u.ID
            LEFT JOIN {$embed_token_table} et ON s.embed_token_id = et.id
            WHERE {$where_sql}
            ORDER BY {$orderBy}
            LIMIT %d OFFSET %d
        ";

        $params[] = $limit;
        $params[] = $offset;

        $sql = $wpdb->prepare($sql, $params);
        $items = $wpdb->get_results($sql);

        // Process items - use actual count, calculate helpful rate, and fetch first_query fallback
        foreach ($items as &$item) {
            // Use actual message count from history table (more accurate)
            if (isset($item->actual_message_count)) {
                $item->message_count = (int) $item->actual_message_count;
                unset($item->actual_message_count);
            }

            // Calculate helpful rate per conversation (only from session_id matched records)
            $helpful = (int) ($item->helpful_count ?? 0);
            $unhelpful = (int) ($item->unhelpful_count ?? 0);
            $total_feedback = $helpful + $unhelpful;

            if ($total_feedback > 0) {
                $item->helpful_rate = round(($helpful / $total_feedback) * 100);
            } else {
                $item->helpful_rate = null; // No feedback given
            }

            unset($item->helpful_count, $item->unhelpful_count);

            // Fallback: fetch first_query from history if session's first_query is empty
            if (empty($item->first_query)) {
                // Try matching by session_id first
                if (!empty($item->session_id)) {
                    $fallback = $wpdb->get_var($wpdb->prepare(
                        "SELECT query FROM {$history_table} WHERE session_id = %s ORDER BY created_at ASC LIMIT 1",
                        $item->session_id
                    ));
                    if ($fallback) {
                        $item->first_query = $fallback;
                    }
                }

                // If still empty, try matching by approximate time (for legacy/mismatched data)
                // Use 12-hour window to account for timezone differences between tables
                if (empty($item->first_query) && !empty($item->started_at)) {
                    $fallback = $wpdb->get_var($wpdb->prepare(
                        "SELECT query FROM {$history_table}
                         WHERE created_at BETWEEN DATE_SUB(%s, INTERVAL 12 HOUR) AND DATE_ADD(%s, INTERVAL 12 HOUR)
                         ORDER BY created_at ASC LIMIT 1",
                        $item->started_at,
                        $item->started_at
                    ));
                    if ($fallback) {
                        $item->first_query = $fallback;
                    }
                }
            }
        }

        return array(
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        );
    }

    /**
     * Delete sessions older than specified days.
     *
     * @param int $days
     * @return int|false Number of rows deleted or false on error
     */
    public static function deleteOlderThan($days)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'apbd_wps_chatbot_session';

        $sql = $wpdb->prepare(
            "DELETE FROM {$table} WHERE started_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            absint($days)
        );

        return $wpdb->query($sql);
    }

    /**
     * Delete by ID.
     *
     * @param int $id
     * @return bool
     */
    static function DeleteById($id)
    {
        return parent::DeleteByKeyValue("id", $id);
    }

    /**
     * Delete by session ID.
     *
     * @param string $session_id
     * @return bool
     */
    static function DeleteBySessionId($session_id)
    {
        return parent::DeleteByKeyValue("session_id", $session_id);
    }

    /**
     * Update feedback for a session.
     *
     * @param string $session_id
     * @param string $feedback H/U/N
     * @return bool
     */
    public static function updateFeedback($session_id, $feedback)
    {
        $session = self::FindBy('session_id', $session_id);
        if (!$session) {
            return false;
        }

        $session->last_feedback($feedback);
        $session->last_activity_at(gmdate('Y-m-d H:i:s'));
        return $session->Update();
    }
}
