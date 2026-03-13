<?php

/**
 * Incoming webhook.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_incoming_webhook extends ApbdWpsModel
{
    public $id;
    public $title;
    public $hash;
    public $status;
    // @ Dynamic
    public $endpoint;
    public $action;


    /**
     *@property id,title,hash,url,status
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_incoming_webhook";
        $this->primaryKey = "id";
        $this->uniqueKey = array(array("hash"));
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $status = 'A' === $status ? 'A' : 'I';

        if ((1 > strlen($title))) {
            return;
        }

        $newData['title'] = $title;
        $newData['status'] = $status;

        return parent::SetFromPostData($isNew, $newData);
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "title" => array("Text" => "Title", "Rule" => "max_length[100]"),
            "hash" => array("Text" => "Hash", "Rule" => "xss_clean|max_length[40]"),
            "status" => array("Text" => "Status", "Rule" => "required|max_length[1]")

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


    public function GetPropertyOptionsColor($property)
    {
        $returnObj = array();
        switch ($property) {
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
                      `hash` char(40) NOT NULL DEFAULT '',
                      `status` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      PRIMARY KEY (`id`)
                    ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    function generate_hash()
    {
        $liccode = md5(wp_rand(10, 99) . wp_rand(10, 99) . time() . wp_rand(10, 99));
        $finallicense = substr($liccode, 0, 8) . "-" . substr($liccode, 8, 8) . "-" . substr($liccode, 16, 8) . "-" . substr($liccode, 24, 8);
        $this->hash($finallicense);
    }

    function GetWebhookUrl()
    {
        $hash = $this->hash;
        $home_url = get_home_url();
        $webhook_base = trailingslashit((false !== strpos($home_url, '?')) ? substr($home_url, 0, (strpos($home_url, '?'))) : $home_url);
        return (! empty($hash) ? add_query_arg(array('sgwebhook' => 1, 'hash' => $hash), $webhook_base) : '');
    }

    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`");
    }

    function Save()
    {
        if (empty($this->hash)) {
            $this->generate_hash();
        }
        return parent::Save();
    }
}
