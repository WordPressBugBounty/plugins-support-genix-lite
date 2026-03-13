<?php

/**
 * EDD.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_edd extends ApbdWpsModel
{

    public $id;
    public $same_site;
    public $api_endpoint;
    public $api_public_key;
    public $api_token;
    public $details_btn;
    public $admin_url;
    public $int_order;
    public $status;
    // @ Dynamic
    public $site_url;
    public $action;

    /**
     * @property id,same_site,api_endpoint,api_public_key,api_token,details_btn,admin_url,int_order,status
     */
    function __construct()
    {
        parent::__construct();

        $this->SetValidation();
        $this->tableName = 'apbd_wps_edd';
        $this->primaryKey = 'id';
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array('id');
        $this->app_base_name = 'support-genix-lite';
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $id = absint(ApbdWps_GetValue('id'));
        $same_site = sanitize_text_field(ApbdWps_PostValue('same_site', ''));
        $api_endpoint = esc_url_raw(ApbdWps_PostValue('api_endpoint', ''));
        $api_public_key = sanitize_text_field(ApbdWps_PostValue('api_public_key', ''));
        $api_token = sanitize_text_field(ApbdWps_PostValue('api_token', ''));
        $details_btn = sanitize_text_field(ApbdWps_PostValue('details_btn', ''));
        $admin_url = esc_url_raw(ApbdWps_PostValue('admin_url', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $same_site = 'Y' === $same_site ? 'Y' : 'N';
        $details_btn = 'Y' === $details_btn ? 'Y' : 'N';
        $status = 'A' === $status ? 'A' : 'I';

        if ($isNew && str_contains($api_public_key, '*')) {
            $api_public_key = '';
        }

        if ($isNew && str_contains($api_token, '*')) {
            $api_token = '';
        }

        $newData['same_site'] = $same_site;
        $newData['details_btn'] = $details_btn;
        $newData['status'] = $status;

        if ('Y' === $same_site) {
            $exobj = Mapbd_wps_edd::FindBy('same_site', $same_site);
            $ex_id = ((is_object($exobj) && isset($exobj->id)) ? absint($exobj->id) : 0);

            if (! empty($ex_id) && ($ex_id !== $id)) {
                $this->AddError('Multiple same site integration is not allowed.');
                return;
            }
        } else {
            if (
                (1 > strlen($api_endpoint)) ||
                (1 > strlen($api_public_key)) ||
                (1 > strlen($api_token)) ||
                (('Y' === $details_btn) && (1 > strlen($admin_url)))
            ) {
                return;
            }

            $newData['api_endpoint'] = $api_endpoint;

            if (!str_contains($api_public_key, '*')) {
                $newData['api_public_key'] = $api_public_key;
            }

            if (!str_contains($api_token, '*')) {
                $newData['api_token'] = $api_token;
            }

            if ('Y' === $details_btn) {
                $newData['admin_url'] = $admin_url;
            }
        }

        return parent::SetFromPostData($isNew, $newData);
    }

    function SetValidation()
    {
        $this->validations = array(
            'id' => array('Text' => 'Id', 'Rule' => 'max_length[11]|integer'),
            'same_site' => array('Text' => 'Same Site', 'Rule' => 'max_length[1]'),
            'api_public_key' => array('Text' => 'API Public Key', 'Rule' => 'max_length[255]'),
            'api_token' => array('Text' => 'API Token', 'Rule' => 'max_length[255]'),
            'details_btn' => array('Text' => 'Order Details Button', 'Rule' => 'max_length[1]'),
            'int_order' => array('Text' => 'Order', 'Rule' => 'max_length[11]|integer'),
            'status' => array('Text' => 'Status', 'Rule' => 'max_length[1]'),
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();

        switch ($property) {
            case 'same_site':
                $returnObj = array('Y' => 'Yes', 'N' => 'No');
                break;

            case 'status':
                $returnObj = array('A' => 'Active', 'I' => 'Inactive');
                break;

            default:
                break;
        }

        if ($isWithSelect) {
            return array_merge(array('' => 'Select'), $returnObj);
        }

        return $returnObj;
    }

    public function GetPropertyOptionsColor($property, $isWithSelect = false)
    {
        $returnObj = array();

        switch ($property) {
            case 'same_site':
                if ($this->is_edd_active()) {
                    $returnObj = array('Y' => 'primary', 'N' => 'secondary');
                } else {
                    $returnObj = array('Y' => 'danger', 'N' => 'secondary');
                }
                break;

            case 'status':
                $returnObj = array('A' => 'success', 'I' => 'danger');
                break;

            default:
                break;
        }

        return $returnObj;
    }

    function Save()
    {
        $totalFild = $this->GetNewIncId('int_order', 1);

        $this->int_order($totalFild);

        return parent::Save();
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}`(
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `same_site` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `api_endpoint` text NOT NULL DEFAULT '',
                `api_public_key` varchar(255) NOT NULL DEFAULT '',
                `api_token` varchar(255) NOT NULL DEFAULT '',
                `details_btn` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `admin_url` text NOT NULL DEFAULT '',
                `int_order` int(11) unsigned NOT NULL,
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

    public static function changeOrder($id, $type)
    {
        $currentField = Mapbd_wps_edd::FindBy('id', $id);

        if ($currentField) {
            $preOrPost = new self();

            if ('u' === strtolower($type)) {
                // up
                $preOrPost->int_order('<' . $currentField->int_order, true);
                $fields = $preOrPost->SelectAll('', 'int_order', 'DESC', 1);
            } else {
                // down
                $preOrPost->int_order('>' . $currentField->int_order, true);
                $fields = $preOrPost->SelectAll('', 'int_order', 'ASC', 1);
            }

            if (! empty($fields[0])) {
                $preOrPost = $fields[0];

                $nfirst = new self();
                $nfirst->int_order($preOrPost->int_order);
                $nfirst->SetWhereUpdate('id', $currentField->id);

                if ($nfirst->Update()) {
                    $nprevious = new self();
                    $nprevious->int_order($currentField->int_order);
                    $nprevious->SetWhereUpdate('id', $preOrPost->id);
                    return $nprevious->Update();
                }
            }
        }

        return false;
    }

    public static function ResetOrder()
    {
        $flds = Mapbd_wps_edd::FetchAll('', 'id', 'ASC');
        $order = 1;

        foreach ($flds as $fld) {
            $uobj = new self();
            $uobj->int_order($order);
            $uobj->SetWhereUpdate('id', $fld->id);

            if ($uobj->Update(false, false)) {
            }

            $order++;
        }
    }

    static function DeleteById($id)
    {
        return parent::DeleteByKeyValue('id', $id);
    }

    public function is_edd_active()
    {
        return (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') ? true : false);
    }
}
