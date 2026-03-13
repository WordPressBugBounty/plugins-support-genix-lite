<?php

/**
 * Ticket tag.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_ticket_tag extends ApbdWpsModel
{
    public $id;
    public $title;
    public $show_on;
    public $status;
    // @ Dynamic
    public $action;

    public function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_ticket_tag";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix";
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));
        $show_on = '';

        $status = 'A' === $status ? 'A' : 'I';

        if (1 > strlen($title)) {
            return;
        }

        $newData['title'] = $title;
        $newData['status'] = $status;
        $newData['show_on'] = $show_on;

        return parent::SetFromPostData($isNew, $newData);
    }

    public function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "title" => array("Text" => "Title", "Rule" => "max_length[150]"),
            "show_on" => array("Text" => "Show On", "Rule" => "max_length[1]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]")
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "show_on":
                $returnObj = array("B" => "Both", "K" => "Only Knowledge", "T" => "Only on Ticket");
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
            case "show_on":
                $returnObj = array("B" => "success", "K" => "success", "T" => "success");
                break;
            case "status":
                $returnObj = array("A" => "success", "I" => "danger");
                break;
            default:
        }
        return $returnObj;
    }

    public function GetPropertyOptionsIcon($property)
    {
        $returnObj = array();
        switch ($property) {
            case "show_on":
                $returnObj = array("B" => "", "K" => "", "T" => "");
                break;
            default:
        }
        return $returnObj;
    }

    public static function getAllTags()
    {
        $mainObj = new Mapbd_wps_ticket_tag();
        $mainObj->status('A');
        $tags = $mainObj->SelectAllGridData();
        $tags = apply_filters('apbd-wps/filter/tag', $tags);
        return $tags;
    }

    public static function getAllTagsWithParents($tag_id, $counter = 1)
    {
        $returnTags = [];
        if ($tag_id != 0) {
            $ctg = Mapbd_wps_ticket_tag::FindBy("id", $tag_id);
            if (! empty($ctg)) {
                $returnTags[] = $tag_id;
            }
        }

        return $returnTags;
    }

    public static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `title` char(150) NOT NULL DEFAULT '',
                      `show_on` char(1) NOT NULL DEFAULT 'B' COMMENT 'radio(B=Both,K=Only Knowledge,T=Only on Ticket)',
                      `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'bool(A=Active,I=Inactive)',
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

    public static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
    }
}
