<?php

/**
 * Role access.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_role_access extends ApbdWpsModel
{
    public $id;
    public $role_slug;
    public $resource_id;
    public $role_access;
    // @ Dynamic
    public $action;


    /**
     *@property id,role_id,resource_id,role_access
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_role_access";
        $this->primaryKey = "id";
        $this->uniqueKey = array(array("resource_id", "role_slug"));
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "role_slug" => array("Text" => "Role Slug", "Rule" => "required|max_length[100]|integer"),
            "resource_id" => array("Text" => "Resource Id", "Rule" => "required|max_length[100]"),
            "role_access" => array("Text" => "Role Access", "Rule" => "max_length[1]")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "role_access":
                $returnObj = array("Y" => "<i class='grid-icon  fa fa-check text-success'></i>", "N" => "<i class='grid-icon  fa fa-times text-danger'></i>");
                break;
            default:
        }
        if ($isWithSelect) {
            return array_merge(array("" => "Select"), $returnObj);
        }
        return $returnObj;
    }
    static function DeleteByRoleSlug($role_slug)
    {
        return parent::DeleteByKeyValue('role_slug', $role_slug, true);
    }
    static function UpdateStatus($role_slug, $res_id, $role_access)
    {
        $up = new self();
        $up->role_access($role_access);
        $up->SetWhereUpdate("role_slug", $role_slug);
        $up->SetWhereUpdate("resource_id", $res_id);
        return $up->Update();
    }
    static function AddAccessStatus($role_slug, $res_id)
    {
        $n = new self();
        $n->role_slug($role_slug);
        $n->resource_id($res_id);
        $n->role_access('Y');
        return $n->Save();
    }
    static function AddAccessIfNotExits($role_slug, $res_id)
    {
        $s = new self();
        $s->role_slug($role_slug);
        $s->resource_id($res_id);
        if (!$s->Select()) {
            $n = new self();
            $n->role_slug($role_slug);
            $n->resource_id($res_id);
            $n->role_access('Y');
            return $n->Save();
        } else {
            return false;
        }
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
                  `role_slug` char(100) NOT NULL COMMENT 'FK(wp_apbd_wps_role,role_slug,name)',
                  `resource_id` char(100) NOT NULL,
                  `role_access` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(Y=Yes,N=No)',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `role_resource` (`resource_id`,`role_slug`) USING BTREE
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

    public static function GetAccessList()
    {
        $access = [
            'assign-me',
            'ticket-reply',
            'manage-unassigned-ticket',
            'manage-other-agents-ticket',
            'manage-self-created-ticket',
            'closed-ticket-list',
            'edit-status',
            'change-privacy',
            'edit-assigned',
            'edit-category',
            'move-to-trash',
            'create-note',
            'edit-custom-field',
            'show-ticket-email',
            'show-ticket-hotlink',
            'create-ticket',
            'create-ticket-user',
            'change-ticket-user',
            'trash-ticket-menu',
            'restore-ticket',
            'delete-ticket',
            'edit-wc-order-source',
            'edit-envato-purchase-code',
            'edit-elite-purchase-code',
        ];

        return apply_filters('elte-wps/role-access-list', $access);
    }

    public static function GetAgentAccessList()
    {
        $access = [
            'assign-me',
            'ticket-reply',
            'manage-unassigned-ticket',
            'manage-other-agents-ticket',
            'manage-self-created-ticket',
            'closed-ticket-list',
            'edit-status',
            'change-privacy',
            'edit-assigned',
            'edit-category',
            'move-to-trash',
            'create-note',
            'edit-custom-field',
            'show-ticket-email',
            'show-ticket-hotlink',
            'create-ticket',
            'create-ticket-user',
            'change-ticket-user',
            'trash-ticket-menu',
            'edit-wc-order-source',
            'edit-elite-purchase-code',
            'edit-envato-purchase-code',
        ];

        return apply_filters('elte-wps/role-agent-access-list', $access);
    }

    public static function GetManagerAccessList()
    {
        $access = array_merge(self::GetAgentAccessList(), [
            'delete-ticket',
            'restore-ticket'
        ]);

        return apply_filters('elte-wps/role-agent-access-list', $access);
    }
}
