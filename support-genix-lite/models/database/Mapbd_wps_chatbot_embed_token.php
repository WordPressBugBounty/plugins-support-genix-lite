<?php

/**
 * Chatbot embed token model.
 *
 * Stores embed tokens for external chatbot embedding with domain whitelisting
 * and feature configuration.
 *
 * @since 1.4.37
 */

defined('ABSPATH') || exit;

class Mapbd_wps_chatbot_embed_token extends ApbdWpsModel
{
    public $id;
    public $title;
    public $token;
    public $allowed_domains;
    public $feature_opts;
    public $allowed_spaces;
    public $allowed_categories;
    public $primary_color;
    public $restrict_docs_browsing;
    public $status;
    public $created_at;
    public $updated_at;

    // Computed/derived properties for API responses (PHP 8.2+ compatibility)
    public $token_masked;
    public $allowed_domains_list;
    public $feature_opts_parsed;
    public $allowed_spaces_parsed;
    public $allowed_categories_parsed;
    public $effective_primary_color;
    public $created_at_formatted;

    /**
     * @property id,title,token,allowed_domains,feature_opts,allowed_spaces,allowed_categories,primary_color,status,created_at,updated_at
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_chatbot_embed_token";
        $this->primaryKey = "id";
        $this->uniqueKey = array(array("token"));
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "title" => array("Text" => "Title", "Rule" => "required|max_length[100]"),
            "token" => array("Text" => "Token", "Rule" => "required|max_length[64]"),
            "allowed_domains" => array("Text" => "Allowed Domains", "Rule" => ""),
            "feature_opts" => array("Text" => "Feature Options", "Rule" => ""),
            "allowed_spaces" => array("Text" => "Allowed Spaces", "Rule" => ""),
            "allowed_categories" => array("Text" => "Allowed Categories", "Rule" => ""),
            "primary_color" => array("Text" => "Primary Color", "Rule" => "max_length[7]"),
            "restrict_docs_browsing" => array("Text" => "Restrict Docs Browsing", "Rule" => "max_length[1]"),
            "status" => array("Text" => "Status", "Rule" => "required|max_length[1]"),
            "created_at" => array("Text" => "Created At", "Rule" => "max_length[20]"),
            "updated_at" => array("Text" => "Updated At", "Rule" => "max_length[20]"),
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "status":
                $returnObj = array("A" => "Active", "I" => "Inactive");
                break;
            default:
        }
        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }
        return $returnObj;
    }

    /**
     * Create the database table.
     */
    public static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(100) NOT NULL DEFAULT '' COMMENT 'Display name for admin',
                `token` varchar(64) NOT NULL COMMENT '64-char secure token',
                `allowed_domains` text DEFAULT NULL COMMENT 'Comma-separated list of allowed domains',
                `feature_opts` text DEFAULT NULL COMMENT 'Comma-separated feature flags',
                `allowed_spaces` text DEFAULT NULL COMMENT 'Comma-separated allowed KB space IDs',
                `allowed_categories` text DEFAULT NULL COMMENT 'Comma-separated allowed KB category IDs',
                `primary_color` varchar(7) DEFAULT NULL COMMENT 'Hex color code e.g. #0bbc5c',
                `restrict_docs_browsing` char(1) NOT NULL DEFAULT 'N' COMMENT 'Y=Restrict N=No restriction',
                `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, I=Inactive',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_token` (`token`),
                KEY `idx_status` (`status`)
            ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Drop the database table.
     */
    public function DropDBTable()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tableName;
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }

    static function DeleteById($id)
    {
        return parent::DeleteByKeyValue("id", $id);
    }
}
