<?php

/**
 * Users.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_users extends ApbdWpsModel
{
    public $ID;
    public $user_login;
    public $user_pass;
    public $user_nicename;
    public $user_email;
    public $user_url;
    public $user_registered;
    public $user_activation_key;
    public $user_status;
    public $display_name;


    /**
     *@property ID,user_login,user_pass,user_nicename,user_email,user_url,user_registered,user_activation_key,user_status,display_name
     */
    function __construct()
    {
        parent::__construct();
        $this->SetValidation();
        $this->tableName = "users";
        $this->primaryKey = "ID";
        $this->uniqueKey = array();
        $this->multiKey = [];
        $this->autoIncField = array("ID");
        $this->app_base_name = "support-genix-lite";
    }


    function SetValidation()
    {
        $this->validations = array(
            "ID" => array("Text" => "ID", "Rule" => "max_length[20]"),
            "user_login" => array("Text" => "User Login", "Rule" => "max_length[60]"),
            "user_pass" => array("Text" => "User Pass", "Rule" => "max_length[255]"),
            "user_nicename" => array("Text" => "User Nicename", "Rule" => "max_length[50]"),
            "user_email" => array("Text" => "User Email", "Rule" => "max_length[100]|valid_email"),
            "user_url" => array("Text" => "User Url", "Rule" => "max_length[100]"),
            "user_activation_key" => array("Text" => "User Activation Key", "Rule" => "max_length[255]"),
            "user_status" => array("Text" => "User Status", "Rule" => "max_length[11]|integer"),
            "display_name" => array("Text" => "Display Name", "Rule" => "max_length[250]")

        );
    }

    static function CreateDBTable()
    {
        $thisObj = new static();
        $table = $thisObj->db->base_prefix . $thisObj->tableName;
        $charsetCollate = $thisObj->db->has_cap('collation') ? $thisObj->db->get_charset_collate() : '';

        if ($thisObj->db->get_var("show tables like '{$table}'") != $table) {
            $sql = "CREATE TABLE `{$table}` (
                      `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `user_login` varchar(60) NOT NULL DEFAULT '',
                      `user_pass` varchar(255) NOT NULL DEFAULT '',
                      `user_nicename` varchar(50) NOT NULL DEFAULT '',
                      `user_email` varchar(100) NOT NULL DEFAULT '',
                      `user_url` varchar(100) NOT NULL DEFAULT '',
                      `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                      `user_activation_key` varchar(255) NOT NULL DEFAULT '',
                      `user_status` int(11) NOT NULL DEFAULT 0,
                      `display_name` varchar(250) NOT NULL DEFAULT '',
                      PRIMARY KEY (`ID`),
                      KEY `user_login_key` (`user_login`),
                      KEY `user_nicename` (`user_nicename`),
                      KEY `user_email` (`user_email`)
                    ) $charsetCollate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    function DropDBTable()
    {
        global $wpdb;

        $table_name = $wpdb->base_prefix . $this->tableName;
        $wpdb->query("DROP TABLE IF EXISTS `" . esc_sql($table_name) . "`");
    }
}
