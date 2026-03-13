<?php

/**
 * Notes.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_notes extends ApbdWpsModel
{
    public $id;
    public $ticket_id;
    public $entry_date;
    public $added_by;
    public $note_text;
    // @ Dynamic
    public $added_by_obj;


    /**
     *@property id,ticket_id,entry_date,added_by,note_text
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_notes";
        $this->primaryKey = "id";
        $this->uniqueKey = array();
        $this->multiKey = array();
        $this->autoIncField = array("id");
        $this->app_base_name = "support-genix-lite";
        $this->htmlInputField = ['note_text'];
    }


    function SetValidation()
    {
        $this->validations = array(
            "id" => array("Text" => "Id", "Rule" => "max_length[11]|integer"),
            "ticket_id" => array("Text" => "Ticket Id", "Rule" => "required|max_length[11]|integer"),
            "added_by" => array("Text" => "Added By", "Rule" => "required|max_length[11]|integer"),
            "note_text" => array("Text" => "Note Text", "Rule" => "required")

        );
    }

    static function getNoteData($note)
    {
        $user = new WP_User($note->added_by);

        $getUser = new stdClass();
        $getUser->first_name = $user->first_name;
        $getUser->last_name = $user->last_name;
        $getUser->display_name = ! empty($user->display_name) ? $user->display_name : $user->user_login;
        $getUser->img = get_user_meta($note->added_by, 'supportgenix_avatar', true) ? get_user_meta($note->added_by, 'supportgenix_avatar', true) : get_avatar_url($user->user_email);

        $note->added_by_obj = $getUser;

        return $note;
    }
    static function getNoteString($note)
    {
        $nUser = new WP_User($note->added_by);
        return sprintf("%s  %s", $note->note_text, '<i>-' . ($nUser->first_name ? $nUser->first_name . ' ' . $nUser->last_name : $nUser->user_login)) . '</i>';
    }
    /**
     * @param $ticket_id
     *
     * @return array
     */
    static function getAllNotesBy($ticket_id, $string = true)
    {
        $returnNotes = [];

        $ticket = Mapbd_wps_ticket::FindBy("id", $ticket_id);

        if (!empty($ticket)) {
            $obj = new Mapbd_wps_notes();
            $obj->ticket_id($ticket_id);
            $notes = $obj->SelectAllGridData('', 'entry_date', 'asc');

            if (is_array($notes) && !empty($notes)) {
                $ticketUserId = absint($ticket->ticket_user);
                $currentUserId = get_current_user_id();
                $selfUser = !!($ticketUserId === $currentUserId);

                foreach ($notes as $note) {
                    $addedBy = absint($note->added_by);
                    $shouldShow = $selfUser ? ($addedBy === $ticketUserId) : ($addedBy !== $ticketUserId);

                    if ($shouldShow) {
                        $returnNotes[] = $string ? self::getNoteString($note) : self::getNoteData($note);
                    }
                }
            }
        }

        return $returnNotes;
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

    /**
     * From version 1.4.23
     */
    static function UpdateDBTable()
    {
        $thisObj = new static();
        $thisObj->DBColumnAddOrModify('note_text', 'longtext', 0);
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                      `ticket_id` int(11) unsigned NOT NULL,
                      `entry_date` timestamp NOT NULL DEFAULT current_timestamp(),
                      `added_by` int(11) unsigned NOT NULL,
                      `note_text` longtext NOT NULL,
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
}
