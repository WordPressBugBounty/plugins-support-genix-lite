<?php

/**
 * Email templates.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_email_templates extends ApbdWpsModel
{
    public $k_word;
    public $grp;
    public $title;
    public $status;
    public $subject;
    public $props;
    public $content;
    // @ Dynamic
    public $action;


    /**
     *@property k_word,grp,title,status,subject,props,content
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "apbd_wps_email_templates";
        $this->primaryKey = "k_word";
        $this->uniqueKey = array(array("k_word"));
        $this->multiKey = array();
        $this->autoIncField = array();
        $this->app_base_name = "support-genix-lite";
        $this->htmlInputField = ['content'];
    }

    public function SetFromPostData($isNew = false, $data = null)
    {
        $newData = [];

        $id = sanitize_text_field(ApbdWps_GetValue("id"));

        $subject = sanitize_text_field(ApbdWps_PostValue('subject', ''));
        $content = wp_kses_post(ApbdWps_PostValue('content', ''));
        $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

        if (in_array($id, ['EOT', 'ETR', 'ETC', 'UOT', 'TRR', 'TCL'])) {
            $subject = 'Re: {{ticket_title}}';
        }

        $check__content = sanitize_text_field($content);
        $status = 'A' === $status ? 'A' : 'I';

        if (
            (1 > strlen($subject)) ||
            (1 > strlen($check__content))
        ) {
            return;
        }

        $newData['subject'] = $subject;
        $newData['content'] = $content;
        $newData['status'] = $status;

        return parent::SetFromPostData($isNew, $newData);
    }


    function SetValidation()
    {
        $this->validations = array(
            "k_word" => array("Text" => "K Word", "Rule" => "max_length[3]"),
            "grp" => array("Text" => "Grp", "Rule" => "required|max_length[100]"),
            "title" => array("Text" => "Title", "Rule" => "required|max_length[100]"),
            "status" => array("Text" => "Status", "Rule" => "max_length[1]"),
            "subject" => array("Text" => "Subject", "Rule" => "required|max_length[150]"),
            "props" => array("Text" => "Props"),
            "content" => array("Text" => "Content", "Rule" => "required")

        );
    }

    public function GetPropertyRawOptions($property, $isWithSelect = false)
    {
        $returnObj = array();
        switch ($property) {
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
            case "status":
                $returnObj = array("A" => "success", "I" => "danger");
                break;
            default:
        }
        return $returnObj;
    }

    /**
     * From version 1.1.0
     */
    static function UpdateTemplateGroup()
    {
        $k_words = ['ANT', 'AAT', 'ANR'];

        foreach ($k_words as $k_word) {
            $uo = new Mapbd_wps_email_templates();
            $uo->grp('To Admin or Agent');
            $uo->SetWhereUpdate("k_word", $k_word);
            $uo->Update();
        }
    }

    /**
     * From version 1.2.0
     */
    static function UpdateTemplateGroup2()
    {
        $k_words = ['EOT', 'ETR', 'ETC'];

        foreach ($k_words as $k_word) {
            $uo = new Mapbd_wps_email_templates();
            $uo->grp('To Customer (Email to Ticket)');
            $uo->subject('Re: {{ticket_title}}');
            $uo->SetWhereUpdate("k_word", $k_word);
            $uo->Update();
        }
    }

    /**
     * From version 1.3.1
     */
    static function UpdateTemplateGroup3()
    {
        $k_words = ['ANT', 'AAT', 'ANR', 'UOT', 'TRR', 'TCL', 'EOT', 'ETR', 'ETC'];

        foreach ($k_words as $k_word) {
            $uo = new Mapbd_wps_email_templates();
            $uo->props('');
            $uo->SetWhereUpdate("k_word", $k_word);
            $uo->Update();
        }
    }

    /**
     * From version 2.0.0
     */
    static function UpdateTemplateGroup4()
    {
        $k_words = ['UOT', 'TRR', 'TCL'];

        foreach ($k_words as $k_word) {
            $uo = new Mapbd_wps_email_templates();
            $uo->subject('Re: {{ticket_title}}');
            $uo->SetWhereUpdate("k_word", $k_word);
            $uo->Update();
        }
    }

    function Save()
    {
        if (!$this->IsSetPrperty("k_word")) {
            $k_word = $this->GetNewIncId("k_word", "AAA");
            $this->k_word($k_word);
        }
        return parent::Save();
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
                      `k_word` char(3) NOT NULL DEFAULT '',
                      `grp` char(100) NOT NULL DEFAULT '',
                      `title` char(100) NOT NULL DEFAULT '',
                      `status` char(1) NOT NULL DEFAULT 'A' COMMENT 'bool(A=Active,I=Inactive)',
                      `subject` char(150) NOT NULL DEFAULT '',
                      `props` text NOT NULL DEFAULT '',
                      `content` text NOT NULL,
                      PRIMARY KEY (`k_word`) USING BTREE,
                      UNIQUE KEY `email_keyword` (`k_word`) USING BTREE
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

    public static function AddDefaultTemplates()
    {
        //To Admin or Agent
        Mapbd_wps_email_templates::AddNewTemplate('ANT', 'To Admin or Agent', 'Ticket Created', 'A', 'New Ticket: {{ticket_title}} #{{ticket_track_id}}', '', '<p>A new ticket <strong>{{ticket_title}}</strong> has been submitted by {{ticket_user}}.</p><p>Ticket Body:<br>{{ticket_body}}</p><p>{{view_ticket_anchor}}</p><p>Thanks<br>{{site_name}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('ANR', 'To Admin or Agent', 'Ticket Replied', 'A', 'New Response: {{ticket_title}} #{{ticket_track_id}}', '', '<p>A new response has been added to ticket <strong>{{ticket_title}}</strong> by {{ticket_user}}.</p><p>Reply Text:<br>{{replied_text}}</p><p>{{view_ticket_anchor}}</p><p>Thanks<br>{{site_name}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('AAT', 'To Admin or Agent', 'Ticket Assigned', 'A', 'Ticket Assigned: {{ticket_title}} #{{ticket_track_id}}', '', '<p>A ticket <strong>{{ticket_title}}</strong> has been assigned to you.</p><p>Ticket Body:<br>{{ticket_body}}</p><p>{{view_ticket_anchor}}</p><p>Thanks<br>{{site_name}}</p>');

        //To Customer (Created by Ticket Form)
        Mapbd_wps_email_templates::AddNewTemplate('UOT', 'To Customer (Created by Ticket Form)', 'Ticket Created', 'A', 'Re: {{ticket_title}}', '', '<p>Dear {{ticket_user}},</p><p>Your request (#{{ticket_track_id}}) has been received and is being reviewed by our support staff. You will receive a response as soon as possible. To add additional comments, follow the link below:</p><p>{{view_ticket_anchor}}</p><p>Thanks,<br>{{site_name}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('TRR', 'To Customer (Created by Ticket Form)', 'Ticket Replied', 'A', 'Re: {{ticket_title}}', '', '<p>Dear {{ticket_user}},</p><p>One of our team members just replied to your ticket (#{{ticket_track_id}}). You can follow the link below to add comments.</p><p>{{view_ticket_anchor}}</p><p>Thanks,<br>{{site_name}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('TCL', 'To Customer (Created by Ticket Form)', 'Ticket Closed', 'A', 'Re: {{ticket_title}}', '', '<p>Dear {{ticket_user}},</p><p>Your ticket (#{{ticket_track_id}}) has been closed.</p><p>We hope that the ticket was resolved to your satisfaction. Please reply to the ticket if you believe that the ticket should not be closed or if it has not been resolved.</p><p>{{view_ticket_anchor}}</p><p>Thanks,<br>{{site_name}}</p>');

        //To Customer (Email to Ticket)
        Mapbd_wps_email_templates::AddNewTemplate('EOT', 'To Customer (Email to Ticket)', 'Ticket Created', 'A', 'Re: {{ticket_title}}', '', '<p>Dear {{ticket_user}},</p><p>Your request (#{{ticket_track_id}}) has been received and is being reviewed by our support staff. You will receive a response as soon as possible.</p><p>Thanks,<br>{{site_name}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('ETR', 'To Customer (Email to Ticket)', 'Ticket Replied', 'A', 'Re: {{ticket_title}}', '', '<p>{{replied_text}}</p>');
        Mapbd_wps_email_templates::AddNewTemplate('ETC', 'To Customer (Email to Ticket)', 'Ticket Closed', 'A', 'Re: {{ticket_title}}', '', '<p>Dear {{ticket_user}},</p><p>Your ticket (#{{ticket_track_id}}) has been closed.</p><p>We hope that the ticket was resolved to your satisfaction. Please reply to this email if you believe that the ticket should not be closed or if it has not been resolved.</p><p>Thanks,<br>{{site_name}}</p>');
    }

    /**
     * @param $keyword
     * @param string $props
     *
     * @return array
     */
    public static function getEmailParamList($keyword, $props = NULL)
    {
        $return_obj = array();
        $return_obj["site_name"] = get_bloginfo('name');
        $return_obj["site_url"] = home_url();

        if (!$props) {
            $props = self::getEmailParamString($keyword);
        }
        if (!$props) {
            $obj = self::FindBy("k_word", $keyword);
            $props = ! empty($obj->props) ? $obj->props : "";
        }
        if (! empty($props)) {
            $params = explode(",", $props);
            foreach ($params as $param) {
                $paramar = explode("=", $param);
                if (! empty($paramar[0]) && ! empty($paramar[1])) {
                    $return_obj[trim($paramar[0])] = trim($paramar[1]);
                }
            }
        }


        return $return_obj;
    }
    /**
     * @param $keyword
     *
     * @return array
     */
    public static function getEmailParamString($keyword)
    {
        $params = [
            'ANT' => 'ticket_user=Name of ticket user,ticket_link=Ticket link (URL),view_ticket_anchor=Ticket view anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,custom_field__slug=Ticket custom field',
            'AAT' => 'ticket_assigned_user=Name of ticket assigned user,ticket_user=Name of ticket user,ticket_link=Ticket link (URL),view_ticket_anchor=Ticket view anchor, ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,custom_field__slug=Ticket custom field',
            'ANR' => 'ticket_replied_user=User who replied,replied_text=Replied Text,ticket_status=Ticket current status,ticket_assigned_user=Name of ticket assigned user,ticket_user=Name of ticket user,ticket_link=Ticket link (URL),view_ticket_anchor=Ticket view anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,custom_field__slug=Ticket custom field',
            'UOT' => 'ticket_user=Name of ticket user,ticket_replied_user=The user who replied last,replied_text=Replied Text,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_priroty=Ticket priroty,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
            'TRR' => 'ticket_replied_user=The user who replied last,replied_text=Replied Text,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
            'TCL' => 'ticket_feedback_button=Ticket Feedback Buttons,ticket_reopen_by=The user who reopen this ticket,ticket_closing_msg=Ticket Closing Message defined in Ticket settings,ticket_user=Name of ticket user,ticket_replied_user=The user who replaied last,replied_text=Replied Text,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_priroty=Ticket priroty,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
            'EOT' => 'ticket_user=Name of ticket user,ticket_replied_user=The user who replied last,replied_text=Replied Text,ticket_track_id=Ticket track id,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_priroty=Ticket priroty,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
            'ETR' => 'ticket_replied_user=The user who replied last,replied_text=Replied Text,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
            'ETC' => 'ticket_feedback_button=Ticket Feedback Buttons,ticket_reopen_by=The user who reopen this ticket,ticket_closing_msg=Ticket Closing Message defined in Ticket settings,ticket_user=Name of ticket user,ticket_replied_user=The user who replaied last,replied_text=Replied Text,ticket_link=Ticket link (URL),ticket_hotlink=Ticket hotlink (URL),view_ticket_anchor=Ticket view anchor,view_ticket_hot_anchor=Ticket view hot anchor,ticket_track_id=Ticket track id,ticket_title=Ticket title,ticket_category_id=Ticket category id,ticket_category_title=Ticket category title,ticket_body=Ticket body,ticket_priroty=Ticket priroty,ticket_open_app_time=Ticket open time in app timezone (UTC),custom_field__slug=Ticket custom field',
        ];

        return (isset($params[$keyword]) ? $params[$keyword] : '');
    }
    public static function getEmailParamListClearData($kayword)
    {
        $return_obj = self::getEmailParamList($kayword);
        $return_obj = array_map(function ($value) {
            $value = "";
        }, $return_obj);
        $return_obj["site_name"] = get_bloginfo('name');
        $return_obj["site_url"]  = home_url();
        return $return_obj;
    }
    public static function get_all_files($path)
    {
        $attached_files = [];
        $allowed_files = Apbd_wps_settings::GetModuleAllowedFileType();
        $path = rtrim($path, '/');
        if (is_dir($path)) {
            foreach (glob($path . '/*.*', GLOB_BRACE) as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_files)) {
                    $attached_files[] = realpath($file);
                }
            }
        }
        return $attached_files;
    }
    public static function AddNewTemplate($k_word, $grp, $title, $status, $subject, $props, $content)
    {
        $obj = new self();
        if (!$obj->IsExists("k_word", $k_word)) {
            $newobj = new self();
            $newobj->k_word($k_word);
            $newobj->grp($grp);
            $newobj->title($title);
            $newobj->status($status);
            $newobj->subject($subject);
            $newobj->content($content);
            $newobj->props($props);
            return $newobj->Save();
        } else {
            return false;
        }
    }
    static  function SendEmailTemplates($keyword, $toEmail, $params = array(), $subject = "", $from_email = '', $reply_to = '', $attachments = [])
    {
        //reply-to
        if (empty($toEmail)) {
            return true;
        }
        $obj = self::FindBy("k_word", $keyword);
        if (! empty($obj)) {
            if ($obj->status != "A") {
                return true;
            }
        }
        if (!isset($params["site_name"])) {
            $params["site_name"] = get_bloginfo('name');
        }
        if (!isset($params["site_url"])) {
            $params["site_url"] = home_url();
        }
        $search = array();
        $replace = array();
        foreach ($params as $key => $value) {
            $search[] = "{{" . $key . "}}";
            $replace[] = $value;
        }
        $content = str_replace($search, $replace, $obj->content);
        if (in_array($keyword, ['UOT', 'TRO', 'TRR', 'TAC', 'EOT', 'ETR']) && ! empty($params["ticket_track_id"]) && ! empty($params["ticket_title"])) {
            $content = self::getTicketEmailText($params["ticket_track_id"], $content, $params["ticket_title"]);
        }
        if (in_array($keyword, ['EOT', 'ETR', 'ETC', 'UOT', 'TRR', 'TCL'])) {
            $subject = "Re: {{ticket_title}}";
        } elseif (empty($subject)) {
            $subject = $obj->subject;
        }
        $subject = str_replace($search, $replace, $subject);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if (! empty($from_email)) {
            $headers[] = 'From: ' . $from_email;
        }
        if (! empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        if (!wp_mail($toEmail, $subject, $content, $headers, $attachments)) {
            return false;
        } else {
            return true;
        }
    }
    static function getTicketEmailText($ticket_track_id, $content, $ticket_title = '')
    {
        $ticket_track_id = apply_filters('apbd-wps/filter/query-track-id', $ticket_track_id);
        $ticket_track_id = apply_filters('apbd-wps/filter/ref-track-id', $ticket_track_id);
        ob_start();
?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml">

        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <style>
                html {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    box-sizing: border-box;
                    font-size: 14px;
                    margin: 0;
                }

                .em-d-none {
                    display: none;
                }

                .em-reply-line {
                    color: rgb(226, 223, 223);
                    border-top: 1px dotted #ccc;
                    font-size: 12px;
                }

                .ql-align-left {
                    text-align: left;
                }

                .ql-align-right {
                    text-align: right;
                }

                .ql-align-center {
                    text-align: center;
                }

                .ql-align-justify {
                    text-align: justify;
                }
            </style>
        </head>

        <body data-start="start-here" itemscope itemtype="http://schema.org/EmailMessage">
            <div id="full-email-body">
                <div class="em-d-none">
                    <?php
                    echo ApbdWps_KsesHtml(wp_kses_no_null($ticket_title)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
                <div class="em-d-none">--start--</div>
                <div class="body-container">
                    <div class="mail-container">
                        <div class="mail-content">
                            <?php
                            echo ApbdWps_KsesHtml(wp_kses_no_null($content)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                        </div>
                    </div>
                </div>
                <div class="em-d-none">ref:<?php echo esc_attr($ticket_track_id); ?>:ref</div>
                <div class="em-d-none">--end--</div>
            </div>
        </body>

        </html>
<?php
        return ob_get_clean();
    }
}
?>