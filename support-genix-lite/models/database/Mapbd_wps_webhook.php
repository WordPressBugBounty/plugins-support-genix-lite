<?php

/**
 * Webhook.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_webhook extends ApbdWpsModel
{
    public $id;
    public $title;
    public $url;
    public $on_tckt_create;
    public $on_client_create;
    public $on_tckt_replied;
    public $on_tckt_closed;
    public $status;
    // @ Dynamic
    public $event_opts;
    public $action;


    /**
     *@property id,title,url,on_tckt_create,on_client_create,on_tckt_replied,on_tckt_closed,status
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_webhook";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
        $url = esc_url_raw(ApbdWps_PostValue('url', ''));
        $event_opts = sanitize_text_field(ApbdWps_PostValue('event_opts', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $status = 'A' === $status ? 'A' : 'I';

        if (
            (1 > strlen($title)) ||
            (1 > strlen($url))
        ) {
            return;
        }

        $newData['title'] = $title;
        $newData['url'] = $url;
        $newData['status'] = $status;

        // Event options.
        $event_opts = explode(',', $event_opts);
        $all__event_opts = ['on_tckt_create', 'on_tckt_replied', 'on_tckt_closed', 'on_client_create'];

        foreach ($all__event_opts as $opt) {
            if (in_array($opt, $event_opts)) {
                $newData[$opt] = 'Y';
            } else {
                $newData[$opt] = 'N';
            }
        }

        return parent::SetFromPostData($isNew, $newData);
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "title" => array("Text" => "Title", "Rule" => "max_length[100]"),
            "on_tckt_create" => array("Text" => "On Tckt Create", "Rule" => "required|max_length[1]"),
            "on_client_create" => array("Text" => "On Client Create", "Rule" => "required|max_length[1]"),
            "on_tckt_replied" => array("Text" => "On Tckt Replied", "Rule" => "required|max_length[1]"),
            "on_tckt_closed" => array("Text" => "On Tckt Replied", "Rule" => "required|max_length[1]"),
            "status" => array("Text" => "Status", "Rule" => "required|max_length[1]")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "on_tckt_create":
            case "on_client_create":
            case "on_tckt_replied":
            case "on_tckt_closed":
                $returnObj = array("Y" => "Yes", "N" => "No");
                break;
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


    public function GetPropertyOptionsColor($property)
    {
        $returnObj = array();
        switch ($property) {
            case "on_tckt_create":
            case "on_client_create":
            case "on_tckt_replied":
            case "on_tckt_closed":
                $returnObj = array("Y" => "success", "N" => "danger");
                break;
            case "status":
                $returnObj = array("A" => "success", "I" => "danger");
                break;
            default:
        }
        return $returnObj;
    }


    static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
    }

    /**
     * From version 1.4.11
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") == $table) {
            $sql = "ALTER TABLE `{$table}` ADD `on_tckt_closed` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)'";
            $update = $thisObj->db->query($sql);
        }
    }

    /**
     * From version 1.4.4
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
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `title` char(100) NOT NULL DEFAULT '',
                      `url` char(255) NOT NULL DEFAULT '' COMMENT 'textarea',
                      `on_tckt_create` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      `on_client_create` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      `on_tckt_replied` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      `on_tckt_closed` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      `status` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      PRIMARY KEY (`id`)
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
