<?php

/**
 * Debug log.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_debug_log extends ApbdWpsModel
{
    public $id;
    public $entry_type;
    public $log_type;
    public $title;
    public $log_data;
    public $status;
    public $entry_time;
    // @ Dynamic
    public $action;

    const LogType_GENERAL = "GEN";
    const LogType_EMAIL = "EML";
    const LogType_IMAP = "IMP";
    const LogType_OTHER = "OTH";
    const EntryType_ERROR = "E";
    const EntryType_SUCCESS = "S";
    const Status_SUCCESS = "S";
    const Status_FAILED = "F";

    /**
     * @property id,entry_type,log_type,title,log_data,status,entry_time
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_debug_log";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array(array("entry_type"));
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
        $this->htmlInputField = ['log_data'];
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "entry_type" => array("Text" => "Entry Type", "Rule" => "max_length[1]"),
            "log_type" => array("Text" => "Log Type", "Rule" => "max_length[4]"),
            "title" => array("Text" => "Title", "Rule" => "required|max_length[255]"),
            "log_data" => array("Text" => "Log Data", "Rule" => "required"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]"),
            "entry_time" => array("Text" => "Entry Time", "Rule" => "max_length[20]")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "entry_type":
                $returnObj = array("E" => "Error", "S" => "Success");
                break;
            case "log_type":
                $returnObj = array("GEN" => "General", "EML" => "Email", "OTH" => "Others");
                break;
            case "status":
                $returnObj = array("F" => "Failed", "S" => "Success");
                break;
            default:
        }
        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }
        return $returnObj;
    }

    public function GetPropertyOptionsColor($property)
    {
        $returnObj = array();
        switch ($property) {
            case "entry_type":
                $returnObj = array("E" => "danger", "S" => "success");
                break;
            case "log_type":
                $returnObj = array("GEN" => "success", "EML" => "success", "OTH" => "success");
                break;
            case "status":
                $returnObj = array("F" => "danger", "S" => "success");
                break;
            default:
        }
        return $returnObj;
    }

    public function GetPropertyOptionsIcon($property)
    {
        $returnObj = array();
        switch ($property) {
            case "entry_type":
                $returnObj = array("E" => "", "S" => "fa fa-check-circle-o");
                break;
            case "log_type":
                $returnObj = array("GEN" => "", "EML" => "", "OTH" => "");
                break;
            case "status":
                $returnObj = array("F" => "fa fa-times-circle-o", "S" => "fa fa-check-circle-o");
                break;
            default:
        }
        return $returnObj;
    }
    static function getLogObject($title, $log_data = '', $log_type = 'GEN', $entry_type = 'E', $status = 'F')
    {
        if (!is_string($log_data)) {
            $log_data = json_encode($log_data);
        }
        $nobj = new self();
        $nobj->title($title);
        $nobj->log_data($log_data);
        $nobj->entry_type($entry_type);
        $nobj->log_type($log_type);
        $nobj->status($status);
        $nobj->entry_time(gmdate("Y-m-d H:i:s"));

        return $nobj;
    }

    static function AddGeneralLog($title, $log_data = '')
    {
        $nobj = self::getLogObject($title, $log_data, self::LogType_GENERAL, self::EntryType_ERROR, self::Status_FAILED);
        return $nobj->Save();
    }

    static function AddEmailLog($title, $log_data = '')
    {
        $nobj = self::getLogObject($title, $log_data, self::LogType_EMAIL, self::EntryType_ERROR, self::Status_FAILED);
        return $nobj->Save();
    }

    static function AddImapLog($title, $log_data = '')
    {
        $nobj = self::getLogObject($title, $log_data, self::LogType_IMAP, self::EntryType_ERROR, self::Status_FAILED);
        return $nobj->Save();
    }

    static function ClearAll()
    {
        global $wpdb;
        $thisobj = new self();
        $table_name = $wpdb->prefix . $thisobj->tableName;
        $wpdb->query("DELETE FROM `" . esc_sql($table_name) . "`");
    }

    /**
     * From version 1.1.2
     */
    static function UpdateDBTableCharset()
    {
        $thisObj = new static();
        $table_name = $thisObj->db->prefix . $thisObj->tableName;
        $charset = $thisObj->db->charset;
        $collate = $thisObj->db->collate;

        $alter_query = "ALTER TABLE `{$table_name}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collate}";

        $thisObj->db->query($alter_query);
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `entry_type` char(1) NOT NULL DEFAULT 'S' COMMENT 'radio(E=Error,S=Success)',
                      `log_type` char(4) NOT NULL DEFAULT 'GEN' COMMENT 'drop(GEN=General,EML=Email,OTH=Others)',
                      `title` char(255) NOT NULL,
                      `log_data` text NOT NULL,
                      `status` char(1) NOT NULL DEFAULT 'S' COMMENT 'drop(F=Failed,S=Success)',
                      `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`) USING BTREE,
                      KEY `entry_type` (`entry_type`) USING BTREE
                    ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`");
    }
}
