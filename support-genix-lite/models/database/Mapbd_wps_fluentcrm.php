<?php

/**
 * FluentCRM.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_fluentcrm extends ApbdWpsModel
{

    public $id;
    public $same_site;
    public $webhook_url;
    public $list_ids;
    public $tag_ids;
    public $contact_status;
    public $trigger_events;
    public $int_order;
    public $status;
    // @ Dynamic
    public $site_url;
    public $action;

    /**
     * @property id,same_site,webhook_url,list_ids,tag_ids,contact_status,trigger_events,int_order,status
     */
    function __construct()
    {
        parent::__construct();

        $this->SetValidation();
        $this->tableName = 'apbd_wps_fluentcrm';
        $this->primaryKey = 'id';
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array('id');
        $this->app_base_name = 'support-genix-lite';
        $this->htmlInputField = ['trigger_events'];
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $same_site = sanitize_text_field(ApbdWps_PostValue('same_site', ''));
        $webhook_url = esc_url_raw(ApbdWps_PostValue('webhook_url', ''));
        $list_ids = sanitize_text_field(ApbdWps_PostValue('list_ids', ''));
        $tag_ids = sanitize_text_field(ApbdWps_PostValue('tag_ids', ''));
        $contact_status = sanitize_text_field(ApbdWps_PostValue('contact_status', ''));
        $trigger_events = sanitize_text_field(ApbdWps_PostValue('trigger_events', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $same_site = 'Y' === $same_site ? 'Y' : 'N';
        $status = 'A' === $status ? 'A' : 'I';

        // List IDs.
        $list_ids = implode(',', array_unique(array_map('absint', explode(',', $list_ids))));
        $list_ids = !empty($list_ids) ? $list_ids : '';

        // Tag IDs.
        $tag_ids = implode(',', array_unique(array_map('absint', explode(',', $tag_ids))));
        $tag_ids = !empty($tag_ids) ? $tag_ids : '';

        // Contact status.
        $contact_status = in_array($contact_status, ['P', 'S', 'U']) ? $contact_status : 'P';

        // Trigger events.
        $trigger_events = array_unique(array_map('sanitize_key', explode(',', $trigger_events)));
        $all__trigger_events = ['ticket-created', 'ticket-replied', 'ticket-closed'];
        $new__trigger_events = [];

        foreach ($trigger_events as $key) {
            if (in_array($key, $all__trigger_events)) {
                $new__trigger_events[$key] = 1;
            }
        }

        $new__trigger_events = maybe_serialize($new__trigger_events);

        $newData['same_site'] = $same_site;
        $newData['list_ids'] = $list_ids;
        $newData['tag_ids'] = $tag_ids;
        $newData['contact_status'] = $contact_status;
        $newData['trigger_events'] = $new__trigger_events;
        $newData['status'] = $status;

        if ('Y' !== $same_site) {
            if (1 > strlen($webhook_url)) {
                return;
            }

            $newData['webhook_url'] = $webhook_url;
        }

        return parent::SetFromPostData($isNew, $newData);
    }

    function SetValidation()
    {
        $this->validations = array(
            'id' => array('Text' => 'Id', 'Rule' => 'max_length[11]|integer'),
            'same_site' => array('Text' => 'Same Site', 'Rule' => 'max_length[1]'),
            'list_ids' => array('Text' => 'List IDs', 'Rule' => 'max_length[255]'),
            'tag_ids' => array('Text' => 'Tag IDs', 'Rule' => 'max_length[255]'),
            'contact_status' => array('Text' => 'Contact Status', 'Rule' => 'max_length[1]'),
            'trigger_events' => array('Text' => 'Trigger Events', 'Rule' => 'max_length[255]'),
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
                if ($this->is_fluentcrm_active()) {
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
                `webhook_url` text NOT NULL DEFAULT '',
                `list_ids` varchar(255) NOT NULL DEFAULT '',
                `tag_ids` varchar(255) NOT NULL DEFAULT '',
                `contact_status` char(1) NOT NULL DEFAULT 'P' COMMENT 'drop(P=Pending,S=Subscribed,U=Unsubscribed)',
                `trigger_events` varchar(255) NOT NULL DEFAULT '',
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
        $currentField = Mapbd_wps_fluentcrm::FindBy('id', $id);

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
        $flds = Mapbd_wps_fluentcrm::FetchAll('', 'id', 'ASC');
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

    public function is_fluentcrm_active()
    {
        return (is_plugin_active('fluent-crm/fluent-crm.php') ? true : false);
    }
}
