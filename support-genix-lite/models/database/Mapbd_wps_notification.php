<?php

/**
 * Notification.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_notification extends ApbdWpsModel
{
    public $id;
    public $user_id;
    public $title;
    public $msg;
    public $entry_type;
    public $entry_link;
    public $n_counter;
    public $is_popup_link;
    public $view_time;
    public $entry_time;
    public $item_type;
    public $extra_param;
    public $msg_param;
    public $status;
    // @ Dynamic
    public $action;


    /**
     *@property id,user_id,title,msg,entry_type,entry_link,n_counter,is_popup_link,view_time,entry_time,item_type,extra_param,msg_param,status
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_notification";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array(array("user_id"), array("user_id", "item_type"));
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "user_id" => array("Text" => "User Id", "Rule" => "required|max_length[10]"),
            "title" => array("Text" => "Title", "Rule" => "required|max_length[100]"),
            "msg" => array("Text" => "Msg", "Rule" => "max_length[255]"),
            "entry_type" => array("Text" => "Entry Type", "Rule" => "max_length[1]"),
            "entry_link" => array("Text" => "Entry Link", "Rule" => "max_length[150]"),
            "n_counter" => array("Text" => "N Counter", "Rule" => "max_length[2]|numeric"),
            "is_popup_link" => array("Text" => "Is Popup Link", "Rule" => "max_length[1]"),
            "view_time" => array("Text" => "View Time", "Rule" => "max_length[20]"),
            "entry_time" => array("Text" => "Entry Time", "Rule" => "max_length[20]"),
            "item_type" => array("Text" => "Item Type", "Rule" => "max_length[2]"),
            "extra_param" => array("Text" => "Extra Param", "Rule" => "max_length[255]"),
            "msg_param" => array("Text" => "Msg Param", "Rule" => "max_length[255]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "entry_type":
                $returnObj = array("N" => "Notification", "M" => "message");
                break;
            case "is_popup_link":
                $returnObj = array("Y" => "Yes", "N" => "No");
                break;
            case "status":
                $returnObj = array("A" => "Active", "V" => "Viewed", "D" => "Deleted");
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
                $returnObj = array("N" => "success", "M" => "success");
                break;
            case "status":
                $returnObj = array("A" => "success", "V" => "success", "D" => "danger");
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
                $returnObj = array("N" => "", "M" => "");
                break;
            case "status":
                $returnObj = array("A" => "fa fa-check-circle-o", "V" => "", "D" => "fa fa-times-circle-o");
                break;
            default:
        }
        return $returnObj;
    }
    static function SetSeenNotification($ticket_id, $user_id)
    {
        $obj = new self();
        $obj->status('V');
        $obj->SetWhereUpdate("user_id", $user_id);
        $obj->SetWhereUpdate("item_type", 'T');
        $obj->SetWhereUpdate("extra_param", $ticket_id);
        return $obj->Update(true);
    }
    static function getUnseenNotification($user_id)
    {
        $mainobj = new Mapbd_wps_notification();
        $mainobj->user_id($user_id);
        $mainobj->status('A');
        $responseData = new Apbd_Wps_APIDataResponse();
        $responseData->page = 1;
        $responseData->limit = 100;
        $responseData->total = (int) $mainobj->CountALL();
        $responseData->pagetotal = ceil($responseData->total / $responseData->limit);
        $responseData->rowdata = $mainobj->SelectAllGridData('', 'entry_time', 'desc', 100, 0);
        foreach ($responseData->rowdata as &$data) {
            $msg_body = ((is_string($data->msg) && ! empty($data->msg)) ? $data->msg : '');
            $msg_body = str_replace('%s', '%{user_name}', $msg_body);

            $data->msg_body = $msg_body;
            $data->user_name = ((is_string($data->msg_param) && ! empty($data->msg_param)) ? $data->msg_param : '');
            $data->msg_param = array_merge([$data->msg], explode('|', $data->msg_param));
            $data->entry_time = ApbdWps_GetWPDateTimeWithFormat($data->entry_time, true);
            $data->msg = call_user_func_array([Apbd_wps_settings::GetModuleInstance(), '__'], $data->msg_param);
            unset($data->msg_param);
        }
        $responseData = apply_filters('apbd-wps/filter/before-unseen-notification', $responseData);
        return $responseData;
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
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` char(10) NOT NULL,
                      `title` char(100) NOT NULL,
                      `msg` char(255) NOT NULL DEFAULT '',
                      `entry_type` char(1) NOT NULL DEFAULT 'N' COMMENT 'radio(N=Notification,M=message)',
                      `entry_link` char(150) NOT NULL DEFAULT '',
                      `n_counter` decimal(2,0) unsigned NOT NULL DEFAULT 1,
                      `is_popup_link` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(Y=Yes,N=No)',
                      `view_time` timestamp NULL DEFAULT NULL,
                      `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      `item_type` char(2) NOT NULL DEFAULT '',
                      `extra_param` char(255) NOT NULL DEFAULT '',
                      `msg_param` char(255) NOT NULL DEFAULT '',
                      `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'radio(A=Active,V=Viewed,D=Deleted)',
                      PRIMARY KEY (`id`) USING BTREE,
                      KEY `user_type` (`user_id`) USING BTREE,
                      KEY `user_id_item` (`user_id`,`item_type`) USING BTREE
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

    static function AddNotification($user_id, $title, $msg, $msg_param, $link, $is_popup = false, $itemType = '', $status = 'A', $extraParam = '')
    {
        $obj = new self();
        $obj->user_id($user_id);
        $obj->title($title);
        $obj->entry_type("N");
        $obj->msg($msg);
        $obj->entry_link($link);
        $obj->is_popup_link($is_popup ? "Y" : "N");
        $obj->entry_time(gmdate('Y-m-d H:i:s'));
        $obj->item_type($itemType);
        $obj->extra_param($extraParam);
        $obj->msg_param($msg_param);
        $obj->status($status);
        return  $obj->Save();
    }
}
