<?php

/**
 * WooCommerce.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_woocommerce extends ApbdWpsModel
{

    public $id;
    public $same_site;
    public $store_title;
    public $store_url;
    public $api_consumer_key;
    public $api_consumer_secret;
    public $verify_email;
    public $verify_ssl;
    public $prevent_for_c;
    public $prevent_for_r;
    public $wh_ticket;
    public $wh_ticket_cat;
    public $wh_ticket_title;
    public $wh_ticket_desc;
    public $int_order;
    public $status;
    // @ Dynamic
    public $disallow_opts;
    public $verify_opts;
    public $action;

    /**
     * @property id,same_site,store_title,store_url,api_consumer_key,api_consumer_secret,verify_email,verify_ssl,prevent_for_c,prevent_for_r,wh_ticket,wh_ticket_cat,wh_ticket_title,wh_ticket_desc,int_order,status
     */
    function __construct()
    {
        parent::__construct();

        $this->SetValidation();
        $this->tableName = 'apbd_wps_woocommerce';
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
        $store_title = sanitize_text_field(ApbdWps_PostValue('store_title', ''));
        $disallow_opts = sanitize_text_field(ApbdWps_PostValue('disallow_opts', ''));
        $verify_opts = sanitize_text_field(ApbdWps_PostValue('verify_opts', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        // Lite: Force same-site only, ignore POST value.
        $same_site = 'Y';

        // Lite: Force auto-ticket off, ignore POST value.
        $wh_ticket = 'N';

        $status = 'A' === $status ? 'A' : 'I';

        if (1 > strlen($store_title)) {
            return;
        }

        $newData['same_site'] = $same_site;
        $newData['store_title'] = $store_title;
        $newData['wh_ticket'] = $wh_ticket;
        $newData['status'] = $status;

        if ($isNew) {
            $newData['wh_ticket_title'] = $this->__('Order #{{order_id}} has been placed by {{user_full_name}} at {{store_title}}');
            $newData['wh_ticket_desc'] = $this->__('A new Order #{{order_id}} has been placed by {{user_full_name}} in your store {{store_title}}.');
        }

        $disallow_opts = explode(',', $disallow_opts);
        $all__disallow_opts = ['prevent_for_c', 'prevent_for_r'];

        foreach ($all__disallow_opts as $opt) {
            if (in_array($opt, $disallow_opts)) {
                $newData[$opt] = 'Y';
            } else {
                $newData[$opt] = 'N';
            }
        }

        $verify_opts = explode(',', $verify_opts);
        $all__verify_opts = ['verify_email', 'verify_ssl'];

        foreach ($all__verify_opts as $opt) {
            if (in_array($opt, $verify_opts)) {
                $newData[$opt] = 'Y';
            } else {
                $newData[$opt] = 'N';
            }
        }

        // Lite: Only same-site allowed, check for duplicate.
        $exobj = Mapbd_wps_woocommerce::FindBy('same_site', 'Y');
        $ex_id = ((is_object($exobj) && isset($exobj->id)) ? absint($exobj->id) : 0);

        if (! empty($ex_id) && ($ex_id !== $id)) {
            $this->AddError('Multiple same site integration is not allowed.');
            return;
        }

        return parent::SetFromPostData($isNew, $newData);
    }

    function SetValidation()
    {
        $this->validations = array(
            'id'                  => array('Text' => 'Id', 'Rule' => 'max_length[11]|integer'),
            'same_site'           => array('Text' => 'Same Site', 'Rule' => 'max_length[1]'),
            'store_title'         => array('Text' => 'Store Title', 'Rule' => 'max_length[255]'),
            'api_consumer_key'    => array('Text' => 'API Consumer Key', 'Rule' => 'max_length[255]'),
            'api_consumer_secret' => array('Text' => 'API Consumer Secret', 'Rule' => 'max_length[255]'),
            'verify_email'        => array('Text' => 'Verify Email', 'Rule' => 'max_length[1]'),
            'verify_ssl'          => array('Text' => 'Verify SSL', 'Rule' => 'max_length[1]'),
            'prevent_for_c'       => array('Text' => 'Prevent for cancelled order', 'Rule' => 'max_length[1]'),
            'prevent_for_r'       => array('Text' => 'Prevent for refunded order', 'Rule' => 'max_length[1]'),
            'wh_ticket'           => array('Text' => 'Auto Create Ticket on New Order Creation', 'Rule' => 'max_length[1]'),
            'wh_ticket_cat'       => array('Text' => 'Ticket Category', 'Rule' => 'max_length[11]|integer'),
            'int_order'           => array('Text' => 'Order', 'Rule' => 'max_length[11]|integer'),
            'status'              => array('Text' => 'Status', 'Rule' => 'max_length[1]'),
        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();

        switch ($property) {
            case 'same_site':
            case 'verify_email':
            case 'verify_ssl':
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
                if ($this->is_woocommerce_active()) {
                    $returnObj = array('Y' => 'primary', 'N' => 'secondary');
                } else {
                    $returnObj = array('Y' => 'danger', 'N' => 'secondary');
                }
                break;

            case 'verify_email':
            case 'verify_ssl':
                $returnObj = array('Y' => 'success', 'N' => 'danger');
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
                `store_title` char(255) NOT NULL DEFAULT '',
                `store_url` text NOT NULL DEFAULT '',
                `api_consumer_key` char(255) NOT NULL DEFAULT '',
                `api_consumer_secret` char(255) NOT NULL DEFAULT '',
                `verify_email` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `verify_ssl` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `prevent_for_c` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `prevent_for_r` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `wh_ticket` char(1) NOT NULL COMMENT 'bool(Y=Yes,N=No)',
                `wh_ticket_cat` int(11) unsigned NOT NULL,
                `wh_ticket_title` text NOT NULL DEFAULT '',
                `wh_ticket_desc` text NOT NULL DEFAULT '',
                `int_order` int(11) unsigned NOT NULL,
                `status` char(1) NOT NULL COMMENT 'bool(A=Active,I=Inactive)',
                PRIMARY KEY (`id`)
            ) $charsetCollate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    static function TransferDBData()
    {
        $transferred = get_option('apbd_wps_woocommerce_settings_transferred', false);

        if (true === rest_sanitize_boolean($transferred)) {
            return;
        }

        $old_data = get_option('support-genix_o_Apbd_wps_woocommerce');

        if (! empty($old_data)) {
            $status = (isset($old_data['wc_status']) ? $old_data['wc_status'] : '');
            $store_url = (isset($old_data['wc_store_url']) ? $old_data['wc_store_url'] : '');
            $api_consumer_key = (isset($old_data['wc_api_consumer_key']) ? $old_data['wc_api_consumer_key'] : '');
            $api_consumer_secret = (isset($old_data['wc_api_consumer_secret']) ? $old_data['wc_api_consumer_secret'] : '');
            $verify_ssl = (isset($old_data['wc_verify_ssl']) ? $old_data['wc_verify_ssl'] : '');
            $verify_email = (isset($old_data['wc_verify_email']) ? $old_data['wc_verify_email'] : '');
            $prevent_for_c = (isset($old_data['wc_prevent_for_cancelled_order_id']) ? $old_data['wc_prevent_for_cancelled_order_id'] : '');
            $prevent_for_r = (isset($old_data['wc_prevent_for_refunded_order_id']) ? $old_data['wc_prevent_for_refunded_order_id'] : '');
            $wh_ticket_cat = (isset($old_data['wc_wh_ticket_cat']) ? $old_data['wc_wh_ticket_cat'] : '');
            $wh_ticket_title = (isset($old_data['wc_wh_ticket_title']) ? $old_data['wc_wh_ticket_title'] : '');
            $wh_ticket_desc = (isset($old_data['wc_wh_ticket_description']) ? $old_data['wc_wh_ticket_description'] : '');

            $store_title = str_replace(array('http://', 'https://', '//', '://', 'www.'), array('', '', '', '', ''), $store_url);
            $store_title = untrailingslashit($store_title);

            $new_obj = new Mapbd_wps_woocommerce();
            $new_obj->same_site('N');
            $new_obj->store_title($store_title);
            $new_obj->store_url($store_url);
            $new_obj->api_consumer_key($api_consumer_key);
            $new_obj->api_consumer_secret($api_consumer_secret);
            $new_obj->verify_email($verify_email);
            $new_obj->verify_ssl($verify_ssl);
            $new_obj->prevent_for_c($prevent_for_c);
            $new_obj->prevent_for_r($prevent_for_r);
            $new_obj->wh_ticket('N');
            $new_obj->wh_ticket_cat($wh_ticket_cat);
            $new_obj->wh_ticket_title($wh_ticket_title);
            $new_obj->wh_ticket_desc($wh_ticket_desc);
            $new_obj->int_order(1);
            $new_obj->status($status);
            $new_obj->Save();

            $is_required = (isset($old_data['wc_is_required']) ? $old_data['wc_is_required'] : 'Y');
            $in_tckt_form = (isset($old_data['wc_show_in_tckt_form']) ? $old_data['wc_show_in_tckt_form'] : 'Y');
            $in_reg_form = (isset($old_data['wc_show_in_reg_form']) ? $old_data['wc_show_in_reg_form'] : 'N');

            $options = array(
                'is_required' => $is_required,
                'in_tckt_form' => $in_tckt_form,
                'in_reg_form' => $in_reg_form,
            );

            update_option('support-genix_o_Apbd_wps_woocommerce', $options);
        }

        update_option('apbd_wps_woocommerce_settings_transferred', true);
    }

    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->tableName;
        $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`");
    }

    public static function changeOrder($id, $type)
    {
        $currentField = Mapbd_wps_woocommerce::FindBy('id', $id);

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
        $flds = Mapbd_wps_woocommerce::FetchAll('', 'id', 'ASC');
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

    public function is_woocommerce_active()
    {
        return (is_plugin_active('woocommerce/woocommerce.php') ? true : false);
    }
}
