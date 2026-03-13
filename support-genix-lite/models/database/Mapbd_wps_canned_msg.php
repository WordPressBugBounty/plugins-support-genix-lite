<?php

/**
 * Canned message.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_canned_msg extends ApbdWpsModel
{
    public $id;
    public $user_id;
    public $title;
    public $canned_msg;
    public $entry_date;
    public $added_by;
    public $canned_type;
    public $status;
    // @ Dynamic
    public $action;

    public function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_canned_msg";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
        $this->htmlInputField = ['canned_msg'];
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
        $canned_msg = wp_kses_post(ApbdWps_PostValue('canned_msg', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $check__canned_msg = sanitize_text_field($canned_msg);
        $status = 'A' === $status ? 'A' : 'I';

        if (
            (1 > strlen($title)) ||
            (1 > strlen($check__canned_msg))
        ) {
            return;
        }

        $newData['title'] = $title;
        $newData['canned_msg'] = $canned_msg;
        $newData['status'] = $status;

        return parent::SetFromPostData($isNew, $newData);
    }

    public function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "user_id" => array("Text" => "User Id", "Rule" => "max_length[3]"),
            "title" => array("Text" => "Title", "Rule" => "max_length[150]"),
            "entry_date" => array("Text" => "Entry Date", "Rule" => "max_length[20]"),
            "added_by" => array("Text" => "Added By", "Rule" => "max_length[3]"),
            "canned_type" => array("Text" => "Canned Type", "Rule" => "max_length[1]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[255]")
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "canned_type":
                $returnObj = array("T" => "Ticket", "C" => "Chat");
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
            case "canned_type":
                $returnObj = array("T" => "success", "C" => "success");
                break;
            default:
        }
        return $returnObj;
    }

    public function GetPropertyOptionsIcon($property)
    {
        $returnObj = array();
        switch ($property) {
            case "canned_type":
                $returnObj = array("T" => "", "C" => "");
                break;
            default:
        }
        return $returnObj;
    }

    public function cleanCannedMsg()
    {
        if ($this->IsSetPrperty("canned_msg")) {
            $this->canned_msg(ApbdWps_EditorTextFilter($this->canned_msg));
        }
    }

    public function Update($notLimit = false, $isShowMsg = true, $dontProcessIdWhereNotset = true)
    {
        $this->cleanCannedMsg();
        return parent::Update($notLimit, $isShowMsg, $dontProcessIdWhereNotset);
    }

    public function Save()
    {
        if (!$this->IsSetPrperty("added_by")) {
            $user_id = get_current_user_id();
            $this->added_by($user_id);
        }
        $this->cleanCannedMsg();
        return parent::Save();
    }

    public static function GetAllCannedMsg()
    {
        $response_obj = [];
        $cannedMsgList = new Mapbd_wps_canned_msg();
        $cannedMsgList->status('A');
        $msgs = $cannedMsgList->SelectAllGridData();

        if (! empty($msgs)) {
            foreach ($msgs as $msg) {
                $nmsg = new stdClass();
                $nmsg->id = $msg->id;
                $nmsg->title = $msg->title;
                $nmsg->canned_msg = $msg->canned_msg;
                $response_obj[] = $nmsg;
            }
        }

        return $response_obj;
    }

    /**
     * @param Mapbd_wps_ticket $ticket
     */
    public static function GetAllCannedMsgBy($ticket)
    {
        $response_obj = [];
        $cannedMsgList = new Mapbd_wps_canned_msg();
        $cannedMsgList->status('A');
        $msgs = $cannedMsgList->SelectAllGridData();
        $obj = new self();
        if (! empty($msgs)) {
            $params = self::getParamList();
            $params["site_name"] = get_bloginfo('name');
            $params["site_url"] = home_url();
            $params["ticket_user"] = esc_html($obj->__("Ticket User"));
            $params["ticket_user_id"] = esc_html($obj->__("Ticket User ID"));
            $user = get_user_by("ID", $ticket->ticket_user);
            if (! empty($ticket)) {
                if (! empty($user->first_name) || ! empty($user->last_name)) {
                    $params["ticket_user"] = $user->first_name . ' ' . $user->last_name;
                } elseif (! empty($user->display_name)) {
                    $params["ticket_user"] = $user->display_name;
                }
                $params["ticket_user_id"] = absint($user->ID);
            }
            $params["ticket_title"] = esc_html($obj->__($ticket->title));
            $params["reply_user"] = esc_html($obj->__("Agent"));
            $params["reply_user_id"] = esc_html($obj->__("Agent ID"));
            $params["reply_user_grp"] = "";
            $currentUser = wp_get_current_user();
            if ($currentUser instanceof WP_User) {
                if (! empty($currentUser->first_name) || ! empty($currentUser->last_name)) {
                    $params["reply_user"] = $currentUser->first_name . ' ' . $currentUser->last_name;
                } elseif (! empty($currentUser->display_name)) {
                    $params["reply_user"] = $currentUser->display_name;
                }
                $params["reply_user_id"] = absint($currentUser->ID);
                $params["reply_user_grp"] = ApbdWps_GetUserRoleName($currentUser);
            }

            foreach ($msgs as $msg) {
                $nmsg = new stdClass();
                $nmsg->id = $msg->id;
                $nmsg->title = $msg->title;
                $nmsg->canned_msg = self::get_real_msg($params, $msg->canned_msg);
                $response_obj[] = $nmsg;
            }
        }
        return $response_obj;
    }

    public static function getParamList()
    {
        $obj = new self();
        $return_obj = array();
        $return_obj["site_name"] = esc_html($obj->__("Your site name"));
        $return_obj["site_url"] = esc_html($obj->__("Your Site URL"));
        $return_obj["ticket_user"] = esc_html($obj->__("The user who has opened ticket"));
        $return_obj["ticket_title"] = esc_html($obj->__("Ticket title"));
        $return_obj["reply_user"] = esc_html($obj->__("Reply user name"));
        $return_obj["reply_user_grp"] = esc_html($obj->__("Reply user group"));
        return $return_obj;
    }

    public static function getParamListClearData()
    {
        $return_obj = self::getParamList();
        $return_obj = array_map(function ($value) {
            $value = "";
        }, $return_obj);
        $return_obj["site_name"] = wp_title();
        $return_obj["site_url"] = get_site_url();
        return $return_obj;
    }

    public static function get_real_msg($params, $str)
    {
        if (count($params) > 0) {
            $search = array();
            $replace = array();
            foreach ($params as $key => $value) {
                $search[] = "{{" . $key . "}}";
                $replace[] = $value;
            }
            return str_replace($search, $replace, $str);
        }
        return $str;
    }

    public static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
    }

    public static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `user_id` char(3) NOT NULL DEFAULT '',
                      `title` char(150) NOT NULL DEFAULT '',
                      `canned_msg` text DEFAULT NULL COMMENT 'textarea',
                      `entry_date` timestamp NOT NULL DEFAULT current_timestamp(),
                      `added_by` char(3) NOT NULL DEFAULT '',
                      `canned_type` char(1) NOT NULL DEFAULT 'T' COMMENT 'drop(T=Ticket,C=Chat)',
                      `status` char(255) NOT NULL DEFAULT 'A' COMMENT 'bool(A=Active,I=Inactive)',
                      PRIMARY KEY (`id`) USING BTREE
                    ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`");
    }

    /*
     * From version 1.1.2
     */
    public static function UpdateDBTableCharset()
    {
        $thisObj = new static();
        $table_name = $thisObj->db->prefix . $thisObj->tableName;
        $charset = $thisObj->db->charset;
        $collate = $thisObj->db->collate;

        $alter_query = "ALTER TABLE `{$table_name}` CONVERT TO CHARACTER SET {$charset} COLLATE {$collate}";

        $thisObj->db->query($alter_query);
    }
}
