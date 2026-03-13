<?php

/**
 * Ticket category.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_ticket_category extends ApbdWpsModel
{
    public $id;
    public $title;
    public $parent_category;
    public $parent_category_path;
    public $show_on;
    public $fld_order;
    public $status;
    // @ Dynamic
    public $action;
    // @ Additional
    public $parent_category_title;

    public function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_ticket_category";
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
        $parent_category = absint(ApbdWps_PostValue('parent_category', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));
        $parent_category_path = '';
        $show_on = '';

        $parent_category = strval($parent_category);
        $status = 'A' === $status ? 'A' : 'I';

        if (1 > strlen($title)) {
            return;
        }

        $newData['title'] = $title;
        $newData['parent_category'] = $parent_category;
        $newData['status'] = $status;
        $newData['parent_category_path'] = $parent_category_path;
        $newData['show_on'] = $show_on;

        return parent::SetFromPostData($isNew, $newData);
    }

    public function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "title" => array("Text" => "Title", "Rule" => "max_length[150]"),
            "parent_category" => array("Text" => "Parent Category", "Rule" => "max_length[10]|integer"),
            "parent_category_path" => array("Text" => "Parent Category Path", "Rule" => "max_length[50]"),
            "show_on" => array("Text" => "Show On", "Rule" => "max_length[1]"),
            "fld_order" => array("Text" => "Order", "Rule" => "max_length[3]|integer"),
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

    public static function getAllCategories()
    {
        $mainObj = new Mapbd_wps_ticket_category();
        $mainObj->status('A');
        $categories = $mainObj->SelectAllGridData();
        $categories = apply_filters('apbd-wps/filter/category', $categories);
        return $categories;
    }

    public static function getAllCategoriesWithParents($cat_id, $counter = 1)
    {
        $returnCats = [];
        if ($cat_id != 0) {
            $ctg = Mapbd_wps_ticket_category::FindBy("id", $cat_id);
            if (! empty($ctg)) {
                $returnCats[] = $cat_id;
                if (! empty($ctg->parent_category)) {
                    if (empty($counter <= 15)) {
                        $returnCats = array_merge(
                            $returnCats,
                            self::getAllCategoriesWithParents($ctg->parent_category, $counter + 1)
                        );
                    }
                }
            }
        }

        return $returnCats;
    }

    function Save()
    {
        $totalFild = $this->GetNewIncId("fld_order", 1);
        $this->fld_order($totalFild);
        return parent::Save();
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
                      `parent_category` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'FK({$table},id,title)',
                      `parent_category_path` char(50) NOT NULL,
                      `show_on` char(1) NOT NULL DEFAULT 'B' COMMENT 'radio(B=Both,K=Only Knowledge,T=Only on Ticket)',
                      `fld_order` int(3) unsigned NOT NULL,
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

    public static function changeOrder($id, $type)
    {
        $currentField = Mapbd_wps_ticket_category::FindBy("id", $id);
        if ($currentField) {
            $preOrPost = new self();
            if (strtolower($type) == "u") {
                //up
                $preOrPost->fld_order("<" . $currentField->fld_order, true);
                $fields = $preOrPost->SelectAll('', 'fld_order', 'DESC', 1);
            } else {
                //down
                $preOrPost->fld_order(">" . $currentField->fld_order, true);
                $fields = $preOrPost->SelectAll('', 'fld_order', 'ASC', 1);
            }


            if (! empty($fields[0])) {
                $preOrPost = $fields[0];
                $nfirst = new self();
                $nfirst->fld_order($preOrPost->fld_order);
                $nfirst->SetWhereUpdate("id", $currentField->id);
                if ($nfirst->Update()) {
                    $nprevious = new self();
                    $nprevious->fld_order($currentField->fld_order);
                    $nprevious->SetWhereUpdate("id", $preOrPost->id);
                    return $nprevious->Update();
                }
            }
        }
        return false;
    }

    public static function ResetOrder()
    {
        $flds = Mapbd_wps_ticket_category::FetchAll('', 'id', 'ASC');
        $order = 1;
        foreach ($flds as $fld) {
            $uobj = new self();
            $uobj->fld_order($order);
            $uobj->SetWhereUpdate("id", $fld->id);
            if ($uobj->Update(false, false)) {
            }
            $order++;
        }
    }

    public static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
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

    /**
     * From version 1.4.20
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") == $table) {
            $sql = "ALTER TABLE `{$table}` ADD `fld_order` int(3) unsigned NOT NULL AFTER `show_on`";
            $thisObj->db->query($sql);
        }
    }
}
