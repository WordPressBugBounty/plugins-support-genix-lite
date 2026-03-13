<?php

/**
 * Ticket assign rule.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_ticket_assign_rule extends ApbdWpsModel
{
    public $id;
    public $cat_ids;
    public $rule_type;
    public $rule_id;
    public $rule_extra;
    public $status;
    // @ Dynamic
    public $action;


    /**
     *@property id,cat_ids,rule_type,rule_id,rule_extra,status
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_ticket_assign_rule";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[10]|integer"),
            "cat_ids" => array("Text" => "Cat Ids", "Rule" => "max_length[255]"),
            "rule_type" => array("Text" => "Rule Type", "Rule" => "max_length[1]"),
            "rule_id" => array("Text" => "Rule Id", "Rule" => "required|max_length[10]"),
            "rule_extra" => array("Text" => "Rule Extra", "Rule" => "max_length[255]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]")

        );
    }
    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $rule_type = sanitize_text_field(ApbdWps_PostValue('rule_type', ''));
        $rule_id = absint(ApbdWps_PostValue('rule_id', ''));
        $category_arr = sanitize_text_field(ApbdWps_PostValue('category_arr', ''));
        $rule_extra = sanitize_text_field(ApbdWps_PostValue('rule_extra', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        $rule_id = strval($rule_id);
        $status = 'A' === $status ? 'A' : 'I';

        $cat_ids = array_unique(array_map('absint', explode(',', $category_arr)));
        $cat_ids = in_array(0, $cat_ids, true) ? [0] : $cat_ids;
        $cat_ids = implode(',', $cat_ids);

        if (
            !in_array($rule_type, ['A', 'S', 'N', 'P'], true) ||
            (empty($rule_id) && empty($rule_extra))
        ) {
            return;
        }

        $newData['rule_type'] = $rule_type;
        $newData['rule_id'] = $rule_id;
        $newData['cat_ids'] = $cat_ids;
        $newData['rule_extra'] = $rule_extra;
        $newData['status'] = $status;

        return parent::SetFromPostData($isNew, $newData);
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
            case "rule_type":
                $returnObj = array("A" => "Assign To Role", "S" => "Assign Specific Agent", "N" => "Notify to agent", "M" => "Add to mailbox");
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
            case "rule_type":
                $returnObj = array("A" => "success", "S" => "success", "N" => "success", "M" => "success");
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
            case "rule_type":
                $returnObj = array("A" => "fa fa-check-circle-o", "S" => "fa fa-check-circle-o", "N" => "fa fa-bell");
                break;
            default:
        }
        return $returnObj;
    }

    static function SetDefaultAssignRole()
    {
        $added = get_option('apbd_wps_default_assign_role_added', false);

        if (true === rest_sanitize_boolean($added)) {
            return;
        }

        $agentSlug = sanitize_title_with_dashes('awps-support-agent');
        $managerSlug = sanitize_title_with_dashes('awps-support-manager');

        $rolesSlug = [$agentSlug, $managerSlug];

        foreach ($rolesSlug as $roleSlug) {
            $roleObj = Mapbd_wps_role::FindBy('slug', $roleSlug, []);
            $roleId = ((is_object($roleObj) && isset($roleObj->id)) ? absint($roleObj->id) : 0);

            if (! empty($roleId)) {
                $assignRuleObj = new Mapbd_wps_ticket_assign_rule();
                $assignRuleObj->cat_ids('0');
                $assignRuleObj->rule_type('A');
                $assignRuleObj->rule_id($roleId);
                $assignRuleObj->rule_extra('');
                $assignRuleObj->status('A');
                $assignRuleObj->Save();
            }
        }

        update_option('apbd_wps_default_assign_role_added', true);
    }

    /**
     * From version 1.0.9
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") == $table) {
            $sql = "ALTER TABLE `{$table}` MODIFY `rule_id` char(10)";
            $thisObj->db->query($sql);
        }
    }

    /**
     * From version 1.4.23
     */
    static function UpdateDBTable2()
    {
        $thisObj = new static();
        $thisObj->DBColumnAddOrModify('rule_extra', 'char', 255, '', 'NOT NULL', 'rule_id');
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
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `cat_ids` char(255) NOT NULL DEFAULT '' COMMENT 'FK(wp_apbd_wps_ticket_category,id,title)',
                      `rule_type` char(1) NOT NULL DEFAULT 'A' COMMENT 'radio(A=Assign,S=Assign Specific Agent,N=Notifiy,M=Mailbox)',
                      `rule_id` char(10) NOT NULL,
                      `rule_extra` char(255) NOT NULL DEFAULT '',
                      `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'bool(A=Active,I=Inactive)',
                      PRIMARY KEY (`id`) USING BTREE
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
    static function DeleteById($id)
    {
        return  parent::DeleteByKeyValue("id", $id);
    }
    static function getAllRuleByCategory($cat_ids)
    {
        $roles = [];
        $obj = new self();
        $obj->status('A');
        $allCategories = $obj->SelectAllGridData();
        foreach ($allCategories as $single_rule) {
            $rule_categories = null !== $single_rule->cat_ids ? explode(',', $single_rule->cat_ids) : array();
            foreach ($rule_categories as $rule_category) {
                if ($rule_category == 0 || in_array($rule_category, $cat_ids)) {
                    $roles[] = $single_rule;
                    break;
                }
            }
        }
        return $roles;
    }
    static function getNextAgent($rule_id)
    {
        global $wpdb;
        $role = Mapbd_wps_role::FindBy("id", $rule_id);
        if (empty($role)) {
            return;
        }
        $users = ApbdWps_GetRoleUsers($role->slug, "ID", "ASC");
        if (! empty($users) && is_array($users)) {
            if (count($users) == 1 && ! empty($users[0]) && $users[0] instanceof WP_User) {
                return $users[0]->ID;
            } else {
                $user_ids = [];
                $data_counter = [];
                foreach ($users as $user) {
                    $user_ids[] = $user->ID;
                    $data_counter[$user->ID] = 0;
                }
                $mticket = new Mapbd_wps_ticket();
                $stat = $mticket->SelectQuery("SELECT assigned_on as user_id, count(*) total FROM " . $wpdb->prefix . "apbd_wps_ticket WHERE assigned_on IN (9,2) group by assigned_on");
                if (! empty($stat)) {
                    foreach ($stat as $st) {
                        $data_counter[$st->user_id] = $st->total;
                    }
                }
                asort($data_counter);
                if (is_array($data_counter) && count($data_counter) > 0) {
                    return array_keys($data_counter)[0];
                }
            }
        }
        return null;
    }

    /**
     * @param $ticketObj
     * @return Mapbd_wps_ticket_assign_rule []
     */
    static function getRuleBy(&$ticketObj)
    {
        $allCategories = Mapbd_wps_ticket_category::getAllCategoriesWithParents($ticketObj->cat_id);
        $Rules = Mapbd_wps_ticket_assign_rule::getAllRuleByCategory($allCategories);
        return $Rules;
    }
    /**
     * @param Mapbd_wps_ticket $ticketObj
     */
    static function ProcessRuleByCategory(&$ticketObj)
    {
        $Rules = self::getRuleBy($ticketObj);
        foreach ($Rules as $rule) {
            self::ProcessRule($rule, $ticketObj);
        }
    }
    /**
     * @param Mapbd_wps_ticket_assign_rule $rule
     * @param Mapbd_wps_ticket $ticketObj
     */
    static function ProcessRule($rule, &$ticketObj)
    {
        if ($rule->rule_type == "S") {
            //Assign Specific
            if (empty($ticketObj->assigned_on)) {
                Mapbd_wps_ticket::AssignOn($ticketObj, $rule->rule_id);
            }
        } elseif ($rule->rule_type == "A") {
            //team
            $nextAgant = self::getNextAgent($rule->rule_id);
            if (! empty($nextAgant)) {
                Mapbd_wps_ticket::AssignOn($ticketObj, $nextAgant);
            }
        } elseif ($rule->rule_type == "N") {
            //Notify
            Mapbd_wps_notification::AddNotification($rule->rule_id, "New Ticket Received", "A new ticket has been received", "", "/ticket/" . $ticketObj->id, false, "T", "A", $ticketObj->id);
            Mapbd_wps_ticket::Send_ticket_open_admin_notify_email($ticketObj, $rule->rule_id);
        } elseif ($rule->rule_type == "P") {
            //Priority
            self::ProcessAssignTicketPriority($rule, $ticketObj);
        }
    }

    /**
     * @param Mapbd_wps_ticket_reply $reply_obj
     * @param Mapbd_wps_ticket $ticketObj
     */
    static function ProcessReplyNotificationAndEmail(&$reply_obj, &$ticketObj)
    {
        if (($reply_obj instanceof Mapbd_wps_ticket_reply) && ($ticketObj instanceof Mapbd_wps_ticket)) {
            $rules = Mapbd_wps_ticket_assign_rule::getRuleBy($ticketObj);
            $ticket_replied_user = ApbdWps_GetUserTitleById($reply_obj->replied_by);
            //send assigned user notification
            if ($reply_obj->replied_by != $ticketObj->assigned_on) {
                Mapbd_wps_notification::AddNotification($ticketObj->assigned_on, "Ticket replied", "Ticket replied by %s", $ticket_replied_user, "/ticket/" . $ticketObj->id, false, "T", "A", $ticketObj->id);
                $assigned_user = get_user_by("id", $ticketObj->assigned_on);
                if ($assigned_user instanceof WP_User) {
                    Mapbd_wps_ticket::Send_ticket_replied_email_admin($assigned_user->user_email, $reply_obj, $ticketObj);
                }
            }

            if ($reply_obj->replied_by_type == 'A') {
                //send email to user
                Mapbd_wps_notification::AddNotification($ticketObj->ticket_user, "Ticket replied", "Ticket replied by %s", $ticket_replied_user, "/ticket/" . $ticketObj->id, false, "T", "A", $ticketObj->id);
                Mapbd_wps_ticket::Send_ticket_replied_email_user($reply_obj, $ticketObj);
            }
            foreach ($rules as $rule) {
                if ($rule->rule_type == "N") {
                    Mapbd_wps_notification::AddNotification($rule->rule_id, "Ticket replied", "Ticket replied by %s", $ticket_replied_user, "/ticket/" . $ticketObj->id, false, "T", "A", $ticketObj->id);
                    //send notification users
                    $notify_user = get_user_by("id", $rule->rule_id);
                    if ($notify_user instanceof WP_User) {
                        Mapbd_wps_ticket::Send_ticket_replied_email_admin($notify_user->user_email, $reply_obj, $ticketObj);
                    }
                }
            }
        }
    }

    static function ProcessAssignTicketPriority($rule, &$ticketObj)
    {
        $priority = isset($rule->rule_extra) ? sanitize_text_field($rule->rule_extra) : 'N';
        $priority = in_array($priority, array('N', 'M', 'H')) ? $priority : 'N';

        $ex_priority = isset($ticketObj->priority) ? sanitize_text_field($ticketObj->priority) : '';
        $ex_priority = in_array($ex_priority, array('N', 'M', 'H')) ? $ex_priority : '';

        if ($priority !== $ex_priority) {
            $newobj = new Mapbd_wps_ticket();
            $newobj->priority($priority);
            $newobj->SetWhereUpdate('id', $ticketObj->id);

            if ($newobj->Update()) {
                $ticketObj->priority = $priority;
            }
        }
    }
}
