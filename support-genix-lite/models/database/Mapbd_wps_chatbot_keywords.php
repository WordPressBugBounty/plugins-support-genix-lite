<?php

/**
 * Chatbot keywords.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_chatbot_keywords extends ApbdWpsModel
{
    public $id;
    public $keyword;
    public $created_at;

    /**
     * @property id,keyword,created_at
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_chatbot_keywords";
        $this->primaryKey = "id";
        $this->uniqueKey = array("keyword");
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix";
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "keyword" => array("Text" => "Keyword", "Rule" => "max_length[255]"),
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
                `keyword` varchar(255) NOT NULL DEFAULT '',
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `keyword` (`keyword`(191))
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

    /* Additional */
}
