<?php

/**
 * Support meta.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_support_meta extends ApbdWpsModel
{
    public $id;
    public $item_id;
    public $item_type;
    public $meta_key;
    public $meta_type;
    public $meta_value;


    /**
     *@property id,item_id,item_type,meta_key,meta_type,meta_value
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_support_meta";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "item_id" => array("Text" => "Item Id", "Rule" => "required|max_length[11]|integer"),
            "item_type" => array("Text" => "Item Type", "Rule" => "max_length[1]"),
            "meta_key" => array("Text" => "Meta Key", "Rule" => "max_length[255]"),
            "meta_type" => array("Text" => "Meta Type", "Rule" => "max_length[1]"),
            "meta_value" => array("Text" => "Meta Value", "Rule" => "")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "item_type":
                $returnObj = array("T" => "Ticket", "U" => "User");
                break;
            case "meta_type":
                $returnObj = array("T" => "Ticket", "U" => "User");
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
            case "item_type":
                $returnObj = array("T" => "success", "U" => "success");
                break;
            case "meta_type":
                $returnObj = array("T" => "success", "U" => "success");
                break;
            default:
        }
        return $returnObj;
    }

    public function GetPropertyOptionsIcon($property)
    {
        $returnObj = array();
        switch ($property) {
            case "item_type":
                $returnObj = array("T" => "", "U" => "");
                break;
            case "meta_type":
                $returnObj = array("T" => "", "U" => "");
                break;
            default:
        }
        return $returnObj;
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

    static function TransferDBData()
    {
        $transferred = get_option('apbd_wps_woocommerce_data_transferred', false);

        if (true === rest_sanitize_boolean($transferred)) {
            return;
        }

        $wc_metas = Mapbd_wps_support_meta::FindAllBy('meta_type', 'W');

        if (is_array($wc_metas) && ! empty($wc_metas)) {
            foreach ($wc_metas as $wc_meta) {
                $wc_meta_id = (isset($wc_meta->id) ? absint($wc_meta->id) : 0);
                $wc_meta_value = (isset($wc_meta->meta_value) ? sanitize_text_field($wc_meta->meta_value) : '');

                if (! empty($wc_meta_value) && ! is_serialized($wc_meta_value)) {
                    $wc_meta_order_id = $wc_meta_value;
                    $wc_meta_value = serialize(array('store_id' => '1', 'order_id' => $wc_meta_order_id));

                    $update_obj = new Mapbd_wps_support_meta();
                    $update_obj->SetWhereUpdate('id', $wc_meta_id);
                    $update_obj->meta_value($wc_meta_value);
                    $update_obj->Update();
                }
            }
        }

        update_option('apbd_wps_woocommerce_data_transferred', true);
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `item_id` int(11) unsigned NOT NULL,
                      `item_type` char(1) NOT NULL DEFAULT 'T' COMMENT 'radio(T=Ticket,U=User)',
                      `meta_key` varchar(255) DEFAULT '',
                      `meta_type` char(1) NOT NULL DEFAULT 'T' COMMENT 'radio(D=Default,W=WooCommerce,L=Elite Licenser,E=Envato)',
                      `meta_value` longtext DEFAULT NULL,
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
    static function getTicketMeta($ticket_id)
    {
        $metas = self::FindAllBy("item_id", $ticket_id, ['item_type' => 'T']);
        $response = array();
        foreach ($metas as $meta) {
            $response[$meta->meta_type . $meta->meta_key] = $meta->meta_value;
        }
        return $response;
    }
    static function getUserMeta($ticket_id)
    {
        $metas = self::FindAllBy("item_id", $ticket_id, ['item_type' => 'U']);
        $response = array();
        foreach ($metas as $meta) {
            $response[$meta->meta_type . $meta->meta_key] = $meta->meta_value;
        }
        return $response;
    }
}
