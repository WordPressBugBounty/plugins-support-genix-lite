<?php

/**
 * Ticket reply.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_ticket_reply extends ApbdWpsModel
{
    public $ticket_id;
    public $reply_id;
    public $asigned_by;
    public $replied_by;
    public $replied_by_type;
    public $reply_text;
    public $reply_time;
    public $ticket_status;
    public $is_private;
    public $is_user_seen;
    public $seen_time;
    public $reply_user;
    public $attached_files;
    // @ Dynamic
    public $ticket_reply_counter;
    public $last_replied_by;
    public $last_replied_by_type;


    /**
     * @property ticket_id,reply_id,asigned_by,replied_by,replied_by_type,reply_text,reply_time,ticket_status,is_private,is_user_seen,seen_time
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_ticket_reply";
        $this->primaryKey = "reply_id";
        $this->uniqueKey = array(array("ticket_id", "reply_id"));
        $this->multiKey = array(array("ticket_id"));
        $this->autoIncField = array();
        $this->app_base_name = "support-genix-lite";
        $this->htmlInputField = ['reply_text'];
    }


    function SetValidation()
    {
        $this->validations = array(
            "ticket_id" => array("Text" => "Ticket Id", "Rule" => "max_length[11]|integer"),
            "reply_id" => array("Text" => "Reply Id", "Rule" => "max_length[11]|integer"),
            "asigned_by" => array("Text" => "Asigned By", "Rule" => "max_length[11]"),
            "replied_by" => array("Text" => "Replied By", "Rule" => "max_length[11]"),
            "replied_by_type" => array("Text" => "Replied By Type", "Rule" => "max_length[1]"),
            "reply_text" => array("Text" => "Reply Text", "Rule" => "required"),
            "reply_time" => array("Text" => "Reply Time", "Rule" => "max_length[20]"),
            "ticket_status" => array("Text" => "Ticket Status", "Rule" => "max_length[1]"),
            "is_private" => array("Text" => "Is Private", "Rule" => "max_length[1]"),
            "is_user_seen" => array("Text" => "Is User Seen", "Rule" => "max_length[1]"),
            "seen_time" => array("Text" => "Seen Time", "Rule" => "max_length[20]")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        $settingsObj = Apbd_wps_settings::GetModuleInstance();
        switch ($property) {
            case "replied_by_type":
                $returnObj = array("A" => "Staff", "U" => "Ticket User", "G" => "Guest Ticke User");
                break;
            case "ticket_status":
                $returnObj = array("N" => $this->__("New"), "C" => $this->__("Closed"), "P" => $this->__("In-progress"), "R" => $this->__("Re-open"));
                break;
            case "is_user_seen":
                $returnObj = array("Y" => "Yes", "N" => "No");
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
            case "replied_by_type":
                $returnObj = array("A" => "success", "U" => "success", "G" => "success");
                break;
            case "ticket_status":
                $returnObj = array("N" => "success", "C" => "success", "P" => "info", "R" => "success");
                break;
            default:
        }
        return $returnObj;
    }

    public function GetPropertyOptionsIcon($property)
    {
        $returnObj = array();
        switch ($property) {
            case "replied_by_type":
                $returnObj = array("A" => "fa fa-check-circle-o", "U" => "", "G" => "");
                break;
            case "ticket_status":
                $returnObj = array("N" => "", "C" => "", "P" => "fa fa-hourglass-1", "R" => "");
                break;
            default:
        }
        return $returnObj;
    }

    /**
     * From version 1.1.3
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;

        if ($thisObj->db->get_var("show tables like '{$table}'") == $table) {
            $sql = "ALTER TABLE `{$table}` MODIFY `asigned_by` char(11)";
            $thisObj->db->query($sql);

            $sql = "ALTER TABLE `{$table}` MODIFY `replied_by` char(11)";
            $thisObj->db->query($sql);
        }
    }

    /**
     * From version 1.4.23
     */
    static function UpdateDBTable2()
    {
        $thisObj = new static();
        $thisObj->DBColumnAddOrModify('reply_text', 'longtext', 0, '', 'NOT NULL', '', 'textarea');
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
                      `ticket_id` int(11) NOT NULL DEFAULT 0,
                      `reply_id` int(11) NOT NULL DEFAULT 0,
                      `asigned_by` char(11) NOT NULL DEFAULT '' COMMENT 'Ticket current asigned by',
                      `replied_by` char(11) NOT NULL DEFAULT '',
                      `replied_by_type` char(1) NOT NULL DEFAULT 'A' COMMENT 'radio(A=Staff,U=Ticket User,G=Guest Ticke User)',
                      `reply_text` longtext NOT NULL COMMENT 'textarea',
                      `reply_time` timestamp NOT NULL DEFAULT current_timestamp(),
                      `ticket_status` char(1) NOT NULL DEFAULT 'P' COMMENT 'drop(N=New,C=Closed,P=In-progress,R=Re-open)',
                      `is_private` char(1) NOT NULL DEFAULT 'Y' COMMENT 'boot(Y=Yes,N=No)',
                      `is_user_seen` char(1) NOT NULL DEFAULT 'N' COMMENT 'bool(Y=Yes,N=No)',
                      `seen_time` timestamp NULL DEFAULT NULL,
                      UNIQUE KEY `ticket_id` (`ticket_id`,`reply_id`) USING BTREE,
                      KEY `ticket_id_2` (`ticket_id`) USING BTREE
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

    static function SetSeenAllReply($ticket_id, $replied_by_type)
    {
        $obj = new self();
        $obj->is_user_seen('Y');
        $obj->seen_time(gmdate('Y-m-d H:i:s'));
        $obj->SetWhereUpdate("ticket_id", $ticket_id);
        $obj->SetWhereUpdate("is_user_seen", 'N');
        $obj->SetWhereUpdate("replied_by_type", $replied_by_type);
        if ($obj->Update(true)) {
            return true;
        }
        return false;
    }

    /**
     * @param $replied_by
     * @param $replied_by_type
     * @param $payload
     * @param Mapbd_wps_ticket_reply $replyObj
     * @return bool
     */
    static function AddReplyByPayload($replied_by, $replied_by_type, $payload, &$replyObj = null, &$ticket = null)
    {
        if (!isset($payload['file_attached'])) {
            $payload['file_attached'] = [];
        }
        return self::AddReply($payload['ticket_id'], $payload['reply_text'], $replied_by, $replied_by_type, $payload['is_private'], $payload['file_attached'], $replyObj, $ticket);
    }

    /**
     * @param $ticket_id
     * @param $reply_text
     * @param $replied_by
     * @param $replied_by_type
     * @param $is_private
     * @param array $file_list
     * @param Mapbd_wps_ticket_reply $replyObj
     * @param Mapbd_wps_ticket $ticket
     *
     * @return bool
     */
    static function AddReply($ticket_id, $reply_text, $replied_by, $replied_by_type, $is_private, $file_list = [], &$replyObj = null, &$ticket = null, $dont_action = false)
    {

        $ticket = Mapbd_wps_ticket::FindBy("id", $ticket_id);
        if (! empty($ticket)) {
            $replyObj = new Mapbd_wps_ticket_reply();
            if ($ticket->is_public != 'Y' && ($replied_by_type != "A" || !Apbd_wps_settings::isAgentLoggedIn())) {
                if ($ticket->ticket_user != $replied_by) {
                    $replyObj->AddError("You do not have permission to reply ticket");
                    return false;
                }
            }
            $replyObj->ticket_id($ticket_id);
            $replyObj->ticket_status($ticket->status);
            $replyObj->is_private($is_private);
            $replyObj->reply_text($reply_text);
            $replyObj->replied_by_type($replied_by_type);
            $replyObj->replied_by($replied_by);
            $replyObj->is_user_seen('N');
            $replyObj->reply_time(gmdate("Y-m-d H:i:s"));
            $replyObj->asigned_by($ticket->assigned_on);

            $replyObj = apply_filters('apbd-wps/filter/before-ticket-reply', $replyObj);
            if ($replyObj->Save()) {
                if (Mapbd_wps_ticket::increaseReplyCounter($ticket_id, $replied_by, $replied_by_type)) {
                    $ticket = Mapbd_wps_ticket::FindBy("id", $ticket_id);
                }
                if (($replied_by_type == 'U' || $replied_by_type == 'G') && $ticket->status == 'C') {
                    $updateTicket = new Mapbd_wps_ticket();
                    $updateTicket->status('R');
                    $updateTicket->re_open_time(gmdate('Y-m-d H:i:s'));
                    $updateTicket->last_status_update_time(gmdate('Y-m-d H:i:s'));
                    $updateTicket->SetWhereUpdate('id', $ticket_id);
                    $updateTicket->UnsetAllExcepts('status,re_open_time,last_status_update_time');
                    if ($updateTicket->Update()) {
                        $updatedTicketObj = Mapbd_wps_ticket::FindBy("id", $ticket_id);
                        do_action('apbd-wps/action/ticket-status-change', $updatedTicketObj, $replied_by);
                        do_action('apbd-wps/action/ticket-property-update', $updatedTicketObj, 'status');
                        do_action('apbd-wps/action/data-change');
                    }
                }
                if (! empty($_FILES['attached'])) {
                    do_action('apbd-wps/action/attach-files', $_FILES['attached'], $ticket, $replyObj);
                }
                if (!$dont_action) {
                    do_action('apbd-wps/action/ticket-replied', $replyObj, $ticket);
                }
                $replyObj->reply_time = ApbdWps_GetWPDateTimeWithFormat($replyObj->reply_time, true);
                return true;
            } else {
                return false;
            }
        } else {
            $replyObj = new Mapbd_wps_ticket_reply();
            $replyObj->AddError("Ticket doesn't found");
            return false;
        }
    }

    static function AddReplyForEmail($ticket_id, $reply_text, $replied_by, $replied_by_type, &$replyObj = null, &$ticket = null)
    {
        return self::AddReply($ticket_id, $reply_text, $replied_by, $replied_by_type, 'N', [], $replyObj, $ticket, true);
    }

    function getNewReplyId()
    {
        $tableName = $this->db->prefix . $this->tableName;
        $obj = new self();
        $tkt = $obj->SelectQuery('select max(reply_id) as reply_id from ' . $tableName . ' where ticket_id=' . $this->ticket_id);
        if (empty($tkt[0]->reply_id)) {
            return 1;
        } else {
            return ++$tkt[0]->reply_id;
        }
    }

    public function Save()
    {
        $this->reply_id($this->getNewReplyId());
        if (parent::Save()) {
            return true;
        } else {
            return false;
        }
    }

    static function DeleteByTicketID($ticket_id)
    {
        return parent::DeleteByKeyValue("ticket_id", $ticket_id, true);
    }
}
