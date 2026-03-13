<?php

/**
 * Docs searches events.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_docs_searches_events extends ApbdWpsModel
{
    public $id;
    public $keyword_id;
    public $founded;
    public $count;
    public $created_at;
    public $created_date;

    /**
     * @property id,keyword_id,founded,count,created_at
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_docs_searches_events";
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
            "keyword_id" => array("Text" => "Keyword ID", "Rule" => "max_length[11]|integer"),
            "founded" => array("Text" => "Founded", "Rule" => "max_length[1]"),
            "count" => array("Text" => "Count", "Rule" => "max_length[11]|integer"),
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

        $keywordsObj = new Mapbd_wps_docs_search_keywords();
        $keywordsTable = $keywordsObj->db->prefix . $keywordsObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `keyword_id` int(11) unsigned NOT NULL,
                `founded` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `count` int(11) NOT NULL DEFAULT 0,
                `created_at` datetime NOT NULL,
                `created_date` date NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `keyword_id_founded_created_date` (`keyword_id`, `founded`, `created_date`),
                KEY `keyword_id` (`keyword_id`),
                KEY `founded` (`founded`),
                KEY `created_at` (`created_at`),
                KEY `created_date` (`created_date`),
                CONSTRAINT `fk_keyword_id` FOREIGN KEY (`keyword_id`) REFERENCES `{$keywordsTable}` (`id`) ON DELETE CASCADE
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

        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0;");
        $wpdb->query($sql);
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1;");
    }

    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            return;
        }

        if (self::IsGeneratedColumn($thisObj->db, $table, 'created_date')) {
            $thisObj->db->query("ALTER TABLE `{$table}` DROP INDEX `keyword_id_founded_created_date`");
            $thisObj->db->query("ALTER TABLE `{$table}` DROP COLUMN `created_date`");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD COLUMN `created_date` date DEFAULT NULL AFTER `created_at`");
            $thisObj->db->query("UPDATE `{$table}` SET `created_date` = DATE(`created_at`)");
            $thisObj->db->query("ALTER TABLE `{$table}` MODIFY COLUMN `created_date` date NOT NULL");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD KEY `created_date` (`created_date`)");
            $thisObj->db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `keyword_id_founded_created_date` (`keyword_id`, `founded`, `created_date`)");
        }
    }

    /* Additional */
}
