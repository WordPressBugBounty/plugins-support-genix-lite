<?php

/**
 * Docs analytics.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_docs_analytics extends ApbdWpsModel
{
    public $id;
    public $post_id;
    public $views;
    public $unique_views;
    public $positive;
    public $negative;
    public $neutral;
    public $score;
    public $created_at;
    public $created_date;

    /**
     * @property id,post_id,views,unique_views,positive,negative,neutral,score,created_at,created_date
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_docs_analytics";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix";
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "post_id" => array("Text" => "Post Id", "Rule" => "max_length[11]|integer"),
            "views" => array("Text" => "Views", "Rule" => "max_length[11]|integer"),
            "unique_views" => array("Text" => "Unique Views", "Rule" => "max_length[11]|integer"),
            "positive" => array("Text" => "Positive", "Rule" => "max_length[11]|integer"),
            "negative" => array("Text" => "Negative", "Rule" => "max_length[11]|integer"),
            "neutral" => array("Text" => "Neutral", "Rule" => "max_length[11]|integer"),
            "created_at" => array("Text" => "Created At", "Rule" => "max_length[20]"),
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

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL DEFAULT 0,
                `views` int(11) NOT NULL DEFAULT 0,
                `unique_views` int(11) NOT NULL DEFAULT 0,
                `positive` int(11) NOT NULL DEFAULT 0,
                `negative` int(11) NOT NULL DEFAULT 0,
                `neutral` int(11) NOT NULL DEFAULT 0,
                `score` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL,
                `created_date` date NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `post_id_created_date` (`post_id`, `created_date`),
                KEY `post_id` (`post_id`),
                KEY `created_at` (`created_at`),
                KEY `created_date` (`created_date`)
            ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }

    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            return;
        }

        // Convert 'score' from generated to regular column
        if (self::IsGeneratedColumn($thisObj->db, $table, 'score')) {
            $thisObj->db->query("ALTER TABLE `{$table}` DROP COLUMN `score`");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD COLUMN `score` int(11) NOT NULL DEFAULT 0 AFTER `neutral`");
            $thisObj->db->query("UPDATE `{$table}` SET `score` = CASE WHEN (COALESCE(`positive`,0)+COALESCE(`negative`,0)) > 0 THEN ROUND(((COALESCE(`positive`,0)-COALESCE(`negative`,0))/(COALESCE(`positive`,0)+COALESCE(`negative`,0)))*100,0) ELSE 0 END");
        }

        // Convert 'created_date' from generated to regular column
        if (self::IsGeneratedColumn($thisObj->db, $table, 'created_date')) {
            $thisObj->db->query("ALTER TABLE `{$table}` DROP INDEX `post_id_created_date`");
            $thisObj->db->query("ALTER TABLE `{$table}` DROP COLUMN `created_date`");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD COLUMN `created_date` date DEFAULT NULL AFTER `created_at`");
            $thisObj->db->query("UPDATE `{$table}` SET `created_date` = DATE(`created_at`)");
            $thisObj->db->query("ALTER TABLE `{$table}` MODIFY COLUMN `created_date` date NOT NULL");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD KEY `created_date` (`created_date`)");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `post_id_created_date` (`post_id`, `created_date`)");
        }
    }

    /* Additional */

    static function getPostTotalViews($post_id = null)
    {
        $views = 0;

        $post_id = absint($post_id);
        $post_type = get_post_type($post_id);

        if ('sgkb-docs' !== $post_type) {
            return $views;
        }

        global $wpdb;

        $thisObj = new static();
        $tableName = $thisObj->db->prefix . $thisObj->tableName;

        $results = $thisObj->SelectQuery($wpdb->prepare("SELECT sum(views) as total_views FROM {$tableName} WHERE post_id = %d", $post_id));

        if (is_array($results) && !empty($results)) {
            $result = isset($results[0]) ? $results[0] : $results;
            $views = isset($result->total_views) ? absint($result->total_views) : 0;
        }

        return $views;
    }
}
