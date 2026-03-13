<?php

/**
 * Custom field.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_custom_field extends ApbdWpsModel
{
    public $id;
    public $field_label;
    public $field_slug;
    public $help_text;
    public $choose_category;
    public $fld_option;
    public $fld_order;
    public $where_to_create;
    public $create_for;
    public $field_type;
    public $is_required;
    public $has_condition;
    public $conditions;
    public $condition_rel;
    public $status;
    public $is_half_field;
    // @ Dynamic
    public $form_opts;
    public $categories;
    public $input_name;
    public $v_rules;
    public $action;


    /**
     *@property id,field_label,field_slug,help_text,choose_category,where_to_create,field_type,is_required,has_condition,conditions,condition_rel,status,table_datalist
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_custom_field";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $id = absint(ApbdWps_GetValue('id'));
        $field_type = sanitize_text_field(ApbdWps_PostValue('field_type', ''));
        $fld_option = sanitize_text_field(ApbdWps_PostValue('fld_option', ''));
        $field_label = sanitize_text_field(ApbdWps_PostValue('field_label', ''));
        $field_slug = sanitize_text_field(ApbdWps_PostValue('field_slug', ''));
        $help_text = sanitize_text_field(ApbdWps_PostValue('help_text', ''));
        $form_opts = sanitize_text_field(ApbdWps_PostValue('form_opts', ''));
        $where_to_create = sanitize_text_field(ApbdWps_PostValue('where_to_create', ''));
        $create_for = sanitize_text_field(ApbdWps_PostValue('create_for', ''));
        $category_arr = sanitize_text_field(ApbdWps_PostValue('category_arr', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $where_to_create = 'T' === $where_to_create ? 'T' : 'I';
        $create_for = 'A' === $create_for ? 'A' : 'B';
        $status = 'A' === $status ? 'A' : 'I';

        // Choose category.
        $choose_category = array_unique(array_map('absint', explode(',', $category_arr)));
        $choose_category = in_array(0, $choose_category, true) ? [0] : $choose_category;
        $choose_category = implode(',', $choose_category);

        // Field slug.
        $field_slug = (1 > $field_slug ? $field_label : $field_slug);
        $field_slug = strtolower($field_slug);
        $field_slug = str_replace(array(' ', '-'), array('_', '_'), $field_slug);
        $field_slug = preg_replace('/[^\w-]+$/', '', $field_slug);
        $field_slug = $this->GenerateUniqueFieldSlug($field_slug, $id);

        if (
            (1 > strlen($field_label)) ||
            (1 > strlen($field_slug)) ||
            !in_array($field_type, ['T', 'N', 'D', 'S', 'R', 'W', 'E', 'U'], true)
        ) {
            return;
        }

        $newData['field_type'] = $field_type;
        $newData['field_label'] = $field_label;
        $newData['field_slug'] = $field_slug;
        $newData['help_text'] = $help_text;
        $newData['where_to_create'] = $where_to_create;
        $newData['create_for'] = $create_for;
        $newData['choose_category'] = $choose_category;
        $newData['status'] = $status;

        // Form options.
        $form_opts = explode(',', $form_opts);
        $all__form_opts = ['is_required', 'is_half_field'];

        foreach ($all__form_opts as $opt) {
            if (in_array($opt, $form_opts)) {
                $newData[$opt] = 'Y';
            } else {
                $newData[$opt] = 'N';
            }
        }

        if (in_array($field_type, ['R', 'W', 'E'], true)) {
            if (1 > strlen($fld_option)) {
                return;
            }

            // Field option.
            $fld_option = array_unique(array_map('sanitize_text_field', explode(',', $fld_option)));
            $fld_option = implode(',', $fld_option);

            $newData['fld_option'] = $fld_option;
        }

        return parent::SetFromPostData($isNew, $newData);
    }

    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "field_label" => array("Text" => "Field Label", "Rule" => "max_length[255]"),
            "field_slug" => array("Text" => "Field Slug", "Rule" => "max_length[255]"),
            "help_text" => array("Text" => "Help Text", "Rule" => "max_length[255]"),
            "choose_category" => array("Text" => "Choose Category", "Rule" => "max_length[225]"),
            "fld_order" => array("Text" => "Order", "Rule" => "max_length[3]|integer"),
            "where_to_create" => array("Text" => "Where To Create", "Rule" => "max_length[1]"),
            "create_for" => array("Text" => "Create for", "Rule" => "max_length[1]"),
            "field_type" => array("Text" => "Field Type", "Rule" => "max_length[1]"),
            "is_required" => array("Text" => "Is Required", "Rule" => "max_length[1]"),
            "has_condition" => array("Text" => "Has Condition", "Rule" => "max_length[1]"),
            "condition_rel" => array("Text" => "Condition Relation", "Rule" => "max_length[1]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]"),
            "is_half_field" => array("Text" => "Is Half Field", "Rule" => "max_length[1]")
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "where_to_create":
                $returnObj = array("I" => "In Registration Form", "T" => "Ticket Open Form Category");
                break;
            case "create_for":
                $returnObj = array("A" => "Admin Only", "B" => "Both(Clients & Admin)");
                break;
            case "field_type":
                $returnObj = array("T" => "Textbox", "N" => "Numeric", "D" => "Date", "S" => "Switch", "R" => "Radio", "W" => "Dropdown", "E" => "Text/Instruction", "U" => "URL Input");
                break;
            case "is_required":
                $returnObj = array("Y" => "Yes", "N" => "No");
                break;
            case "has_condition":
                $returnObj = array("Y" => "Yes", "N" => "No");
                break;
            case "condition_rel":
                $returnObj = array("A" => "And", "O" => "Or");
                break;
            case "status":
                $returnObj = array("A" => "Active", "I" => "Inactive");
                break;
            case "is_half_field":
                $returnObj = array("Y" => "Yes", "N" => "No");
                break;
            default:
        }
        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }
        return $returnObj;
    }
    public function GetPropertyOptionsColor($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "is_required":
                $returnObj = array("Y" => "success", "N" => "danger");
                break;
            case "status":
                $returnObj = array("A" => "success", "I" => "danger");
                break;
            case "is_half_field":
                $returnObj = array("Y" => "success", "N" => "danger");
                break;
            default:
        }
        return $returnObj;
    }

    public function GenerateUniqueFieldSlug($field_slug, $current_field_id)
    {
        if (! empty($field_slug)) {
            $existing_field = Mapbd_wps_custom_field::FindBy('field_slug', $field_slug);
            $existing_field_id = ((is_object($existing_field) && isset($existing_field->id)) ? absint($existing_field->id) : 0);

            if (! empty($existing_field_id) && ($existing_field_id !== $current_field_id)) {
                $field_slug = $field_slug . '_2';
                $field_slug = $this->GenerateUniqueFieldSlug($field_slug, $current_field_id);
            }
        }

        return $field_slug;
    }


    //auto generated

    /**
     * @param $datalist
     * @param self $fld
     * @param array $currentArray
     * @return array|mixed
     */
    static function getParentArray(&$datalist, $choose_category, $label = 1)
    {
        if ($label >= 10) {
            return [];
        }
        $currentArray = explode(",", $choose_category);
        foreach ($currentArray as $key => $ctg_id) {
            $currentArray[$key] = $ctg_id;
            if (! empty($ctg_id) && $ctg_id != 0 && ! empty($datalist[$ctg_id])) {
                $itemArray = self::getParentArray($datalist, $datalist[$ctg_id]->parent_category, $label + 1);
                $currentArray = array_merge($currentArray, $itemArray);
            } elseif (empty($ctg_id) || $ctg_id == 0) {
                unset($currentArray[$key]);
            }
        }
        return $currentArray;
    }
    static function getCustomFieldForAPI()
    {
        $mainobj = new self();
        $mainobj->status('A');
        $custom_fields = new stdClass();
        $custom_fields->reg_form = [];
        $custom_fields->ticket_form = [];
        $isClient = Apbd_wps_settings::isClientLoggedIn();
        if ($isClient) {
            $mainobj->create_for('B');
        }
        $data = $mainobj->SelectAllGridData("id,field_label,field_slug,help_text,choose_category,where_to_create,field_type,fld_option,fld_order,is_required,has_condition,conditions,condition_rel,status,is_half_field", "fld_order", 'asc');

        $ctgs = Mapbd_wps_ticket_category::FindAllByIdentiry("status", "A", "id");
        foreach ($data as &$fld) {
            $fld->input_name = "D" . $fld->id;
            $fld->v_rules = ($fld->is_required == "Y" ? 'required' : '');
            if ($fld->where_to_create == "I") {
                $custom_fields->reg_form[] = $fld;
            } else {
                $fld->categories = self::getParentArray($ctgs, $fld->choose_category);
                $fld->categories = array_values(array_unique($fld->categories));
                $custom_fields->ticket_form[] = $fld;
            }
            $fld->choose_category = null !== $fld->choose_category ? explode(',', $fld->choose_category) : array();

            // Conditions.
            $fld->has_condition = 'N';
            $fld->conditions = [];
            $fld->condition_rel = 'A';
        }
        return $custom_fields;
    }
    static function getCustomFieldForTicketDetailsAPI($ticket_id)
    {
        $custom_fields = new stdClass();
        $custom_fields->reg_form = [];
        $custom_fields->ticket_form = [];
        $ticket = Mapbd_wps_ticket::FindBy("id", $ticket_id);
        $isClient = Apbd_wps_settings::isClientLoggedIn();
        if (! empty($ticket)) {
            $categories = Mapbd_wps_ticket_category::getAllCategoriesWithParents($ticket->cat_id);
            $mainobj = new self();
            $mainobj->status('A');
            if ($isClient) {
                $mainobj->create_for('B');
            }
            $data = $mainobj->SelectAllGridData("id,field_label,field_slug,help_text,choose_category,where_to_create,field_type,fld_option,fld_order,is_required,has_condition,conditions,condition_rel,status,is_half_field", "fld_order", 'asc');
            $ctgs = Mapbd_wps_ticket_category::FindAllByIdentiry("status", "A", "id");
            foreach ($data as &$fld) {
                $fldsCtgs = null !== $fld->choose_category ? explode(",", $fld->choose_category) : array();
                $isFound = in_array('0', $fldsCtgs);
                if (!$isFound) {
                    foreach ($fldsCtgs as $fldsCtg) {
                        if (in_array($fldsCtg, $categories)) {
                            $isFound = true;
                            break;
                        }
                    }
                }
                if (!$isFound) {
                    continue;
                }
                $fld->input_name = "D" . $fld->id;
                $fld->v_rules = ($fld->is_required == "Y" ? 'required' : '');
                if ($fld->where_to_create == "I") {
                    $custom_fields->reg_form[] = $fld;
                } else {
                    $fld->categories = self::getParentArray($ctgs, $fld->choose_category);
                    $fld->categories = array_values(array_unique($fld->categories));
                    $custom_fields->ticket_form[] = $fld;
                }
                $fld->choose_category = null !== $fld->choose_category ? explode(',', $fld->choose_category) : array();

                // Conditions.
                $fld->has_condition = 'N';
                $fld->conditions = [];
                $fld->condition_rel = 'A';
            }
        }
        return $custom_fields;
    }
    function Save()
    {
        $totalFild = $this->GetNewIncId("fld_order", 1);
        $this->fld_order($totalFild);
        return parent::Save();
    }

    /**
     * From version 1.0.9
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") == $table) {
            $sql = "ALTER TABLE `{$table}` ADD `field_slug` char(255) NOT NULL DEFAULT ''";
            $update = $thisObj->db->query($sql);
        }
    }

    /**
     * From version 1.4.24
     */
    static function UpdateDBTable2()
    {
        $thisObj = new static();
        $tableName = $thisObj->db->prefix . $thisObj->tableName;

        $thisObj->DBColumnAddOrModify('has_condition', 'char', 1, "'N'", 'NOT NULL', 'is_required', 'bool(Y=Yes,N=No)');
        $thisObj->DBColumnAddOrModify('conditions', 'longtext', 0, '', 'NOT NULL', 'has_condition', 'textarea');
        $thisObj->DBColumnAddOrModify('condition_rel', 'char', 1, "'N'", 'NOT NULL', 'conditions', 'bool(A=And,O=Or)');
        $thisObj->db->query("UPDATE `{$tableName}` SET `has_condition` = 'N'");
        $thisObj->db->query("UPDATE `{$tableName}` SET `condition_rel` = 'A'");
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
            $sql = "CREATE TABLE `{$table}`(
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `field_label` char(255) NOT NULL DEFAULT '',
                      `field_slug` char(255) NOT NULL DEFAULT '',
                      `help_text` char(255) NOT NULL DEFAULT '',
                      `choose_category` char(225) NOT NULL DEFAULT '' COMMENT 'FK(wp_apbd_wps_ticket_category,id,title)',
                      `fld_option` text NOT NULL DEFAULT '',
                      `fld_order` int(3) unsigned NOT NULL,
                      `where_to_create` char(1) NOT NULL DEFAULT 'I' COMMENT 'radio(I=In Registartion Form,T=Ticket Open Form Category)',
                      `create_for` char(1) NOT NULL DEFAULT 'B' COMMENT 'radio(A=Admin Only,B=Both)',
                      `field_type` char(1) NOT NULL DEFAULT 'T' COMMENT 'radio(T=Textbox,N=Numeric,D=Date,S=Switch,R=Radio,W=Dropdown,E=Text/Instruction,U=URL Input)',
                      `is_required` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                      `has_condition` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(Y=Yes,N=No)',
                      `conditions` longtext NOT NULL COMMENT 'textarea',
                      `condition_rel` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(A=And,O=Or)',
                      `status` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                      `is_half_field` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(Y=Yes,N=No)',
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
    public static function changeOrder($id, $type)
    {
        $currentField = Mapbd_wps_custom_field::FindBy("id", $id);
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
        $flds = Mapbd_wps_custom_field::FetchAll('', 'id', 'ASC');
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

    static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
    }
}
