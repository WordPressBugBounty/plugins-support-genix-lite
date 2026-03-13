<?php

/**
 * Base module.
 */

defined('ABSPATH') || exit;

if (!class_exists("ApbdWpsBaseModuleLite")) {
    class ApbdWpsBaseModuleLite
    {
        public $moduleName = "";
        public $menuTitle = "";
        public $menuIcon = "";
        public $pluginBaseName;
        public $pluginFile;
        public $multiLangCode;
        public $multiLangActive;
        public $multiLangFields;
        protected $allOptions;
        protected $options;
        public $kernelObject;
        protected $formClass = "";
        protected $isMultipartForm = false;
        protected $formDataAttr = [];
        protected $dontAddDefaultForm = false;
        protected $_viewData = ["_title" => "Unknown", "_subTitle" => "", "_relaod_event" => ""];
        protected $__onTabActiveJsMethod = '';
        private static $_self = NULL;
        protected $viewPath = "";
        protected $isDisabledMenu = false;
        protected $isLastMenu = false;
        protected $isHiddenModule = false;
        // @ Dynamic
        public $_base_path = "";

        const NoticeTypeError = "E";
        const NoticeTypeInfo = "I";
        const NoticeTypeApbdWps = "A";
        const NoticeTypeNone = "N";
        /**
         * ApbdWpsBaseModuleLite constructor.
         *
         * @param $pluginBaseName
         * @param ApbdWpsKarnelLite $kernelObject
         */
        function __construct($pluginBaseName, &$kernelObject)
        {
            $this->kernelObject   = $kernelObject;
            $this->menuTitle      = $this->GetMenuTitle();
            $this->menuIcon       = $this->GetMenuIcon();
            $this->pluginBaseName = $pluginBaseName;
            $this->_base_path     = plugin_dir_path($this->kernelObject->pluginFile);
            $this->pluginFile     = $this->kernelObject->pluginFile;
            $this->SetMultiLangProps();
            $this->SetOption();
            self::$_self[get_class($this)] = $this;
            $this->SetPOPUPIconClass($this->GetMenuIcon());
            $this->viewPath = plugin_dir_path($this->kernelObject->pluginFile) . "views/";
            $this->SetReloadEvent($this->GetModuleId());
            $this->initialize();
        }

        function initialize()
        {
            add_filter("apbd-wps/multi-language", function () {
                return [
                    'code' => $this->multiLangCode,
                    'status' => $this->multiLangActive ? 'A' : 'I'
                ];
            });
        }
        function AddAdminNoticeWithBg($message, $type, $isDismissible = false, $extraClass = "")
        {
            $extraClass .= " apbd-with-bg";
            $this->AddAdminNotice($message, $type, $isDismissible, $extraClass);
        }
        function AddAdminNotice($message, $type, $isDismissible = false, $extraClass = "")
        {
            if ($type == self::NoticeTypeError) {
                $class   = 'notice apbd-notice notice-error';
            } elseif ($type == self::NoticeTypeApbdWps) {
                $class   = 'notice apbd-notice notice-apbd-wps';
            } elseif ($type == self::NoticeTypeNone) {
                $class   = '';
            } else {
                $class   = 'notice apbd-notice notice-success';
            }
            if ($isDismissible) {
                $class .= " is-dismissible";
            }
            $class .= " " . $extraClass;
            $msg = sprintf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            $this->kernelObject->AddAdminNotice($msg);
        }
        function OptionForm() {}

        function GetMenuTitle()
        {
            return '';
        }

        function GetMenuSubTitle()
        {
            return '';
        }

        function GetMenuIcon()
        {
            return '';
        }

        function GetMenuCounter()
        {
            return '';
        }

        public function getHookActionStr($str)
        {
            return $this->kernelObject->getHookActionStr($str);
        }

        public function getCustomizerControlId($name)
        {
            return $this->GetModuleId() . "_cs_" . $name;
        }
        public function getCustomizerControlIdToRealId($CustomizerId)
        {
            return substr($CustomizerId, strlen($this->GetModuleId() . "_cs_"));
        }
        /**
         * @param $title
         * @param callable $func
         * @param $cssClass
         * @param bool $isTab
         */
        function addTopMenu($title, $icon, $func, $cssClass = '', $isTab = true, $attr = [])
        {
            $this->kernelObject->AddTopMenu($title, $icon, $func, $cssClass, $isTab, $attr);
        }

        /**
         * @return static|null
         */
        public static function GetModuleInstance()
        {
            return self::$_self[static::class];
        }

        public static function GetModuleOption($key = '', $default = '')
        {
            if (! empty(self::$_self[static::class])) {
                return self::$_self[static::class]->GetOption($key, $default);
            } else {
                return $default;
            }
        }

        public static function GetModuleActionUrl($actionString = '', $params = [])
        {
            if (! empty(self::$_self[static::class])) {
                return self::$_self[static::class]->GetActionUrl($actionString, $params);
            } else {
                return esc_html(self::$_self[static::class]->__('model not initialize'));
            }
        }

        protected function AddViewData($key, $value)
        {
            $this->_viewData[$key] = $value;
        }

        protected function SetReloadEvent($event)
        {
            $this->_viewData["_relaod_event"] = $event;
        }

        function GetReloadEvent()
        {
            return $this->_viewData["_relaod_event"];
        }

        function AddError($message, $parameter = NULL, $_ = NULL)
        {
            $args    = func_get_args();
            $message = call_user_func_array([$this, "__"], $args);
            ApbdWps_AddError($message);
        }

        function AddInfo($message, $parameter = NULL, $_ = NULL)
        {
            $args    = func_get_args();
            $message = call_user_func_array([$this, "__"], $args);
            ApbdWps_AddInfo($message);
        }
        function AddDebug($obj) {}
        function AddWarning($message, $parameter = NULL, $_ = NULL)
        {
            $args    = func_get_args();
            $message = call_user_func_array([$this, "___"], $args);
            ApbdWps_AddWarning($message);
        }

        /**
         * @param string $viewPath
         */
        public function setViewPath($viewPath)
        {
            $this->viewPath = $viewPath;
        }

        /**
         * @return bool
         */
        public function isDisabledMenu()
        {
            return $this->isDisabledMenu;
        }

        /**
         * @param bool $isDisabledMenu
         */
        public function setDisabledMenu()
        {
            $this->isDisabledMenu = true;
        }

        /**
         * @return bool
         */
        public function isLastMenu()
        {
            return $this->isLastMenu;
        }

        /**
         * @param bool $isLastMenu
         */
        public function setIsLastMenu($isLastMenu)
        {
            $this->isLastMenu = $isLastMenu;
        }

        /**
         * @return bool
         */
        public function isHiddenModule()
        {
            return $this->isHiddenModule;
        }

        /**
         * @param bool $isHiddenModule
         */
        public function setIsHiddenModule($isHiddenModule)
        {
            $this->isHiddenModule = $isHiddenModule;
        }

        protected function ApbdWpsLoadDatabaseModel($modelName)
        {
            ApbdWps_LoadDatabaseModel($this->kernelObject->pluginFile, $modelName, $modelName);
        }

        function OnTableCreate() {}

        function OnVersionUpdate($current_version = "", $previous_version = "", $last_pro_version = "") {}

        function OnPluginVersionUpdated($current_version = "", $previous_version = "", $last_pro_version = "")
        {
            $this->OnVersionUpdate($current_version, $previous_version, $last_pro_version);
        }

        /**
         * @param $filter
         * @param callable $method
         */
        function AddFilter($filter, $filter_function_name)
        {
            add_filter($filter, $filter_function_name);
        }

        /**
         * @param $action
         * @param callable $action_function_name
         * @param int $priority
         * @param int $accepted_args
         */
        function AddAction($action, $action_function_name, $priority = 10, $accepted_args = 1)
        {
            add_action($action, $action_function_name, $priority, $accepted_args);
        }

        /**
         * @param $action
         * @param callable $action_function_name
         * @param int $priority
         * @param int $accepted_args
         */
        function AddAppAction($action, $action_function_name, $priority = 10, $accepted_args = 1)
        {
            $action = $this->getHookActionStr($action);
            add_action($action, $action_function_name, $priority, $accepted_args);
        }

        /**
         * @param $key
         * @param $value
         */
        function AddIntoOption($key, $value)
        {
            $this->options[$key] = $value;
        }

        function SetPOPUPIconClass($icon_class)
        {
            $this->AddViewData("__icon_class", $icon_class);
        }

        function SetPOPUPColClass($col_class)
        {
            $this->AddViewData("__col_class", $col_class);
        }
        function setDisableForm($status = true)
        {
            $this->AddViewData("__disable_form", $status);
        }

        function SetSubtitle($title, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            $this->AddViewData("_subTitle", $title);
        }

        function SetTitle($title, $parameter = NULL, $_ = NULL)
        {
            $args  = func_get_args();
            $title = call_user_func_array([$this, "___"], $args);
            $this->AddViewData("_title", $title);
        }

        function DisplayPOPUPMsg($msg = "", $autoCloseTime = 0, $redirectPage = '', $hideCloseButton = false) {}

        function DisplayPOPUp($viewName) {}

        function Display($viewName = 'main') {}

        function LoadView($viewName = 'main', $isReturn = false) {}

        final function AddClientStyle($StyleId, $StyleFileName, $isFromRoot = false, $deps = [])
        {
            $this->kernelObject->AddAdminStyle($StyleId, $StyleFileName, $isFromRoot, $deps);
        }
        final function AddClientScript($ScriptId, $ScriptFileName, $isFromRoot = false, $deps = [])
        {
            $this->kernelObject->AddAdminScript($ScriptId, $ScriptFileName, $isFromRoot, $deps);
        }
        final function AddAdminStyle($StyleId, $StyleFileName, $isFromRoot = false, $deps = [])
        {
            $this->kernelObject->AddAdminStyle($StyleId, $StyleFileName, $isFromRoot, $deps);
        }

        final function AddAdminScript($ScriptId, $ScriptFileName, $isFromRoot = false, $deps = [])
        {
            $this->kernelObject->AddAdminScript($ScriptId, $ScriptFileName, $isFromRoot, $deps);
        }

        final function AddGlobalJSVar($key, $value)
        {
            $value = $this->__($value);
            $this->kernelObject->AddAppGlobalVar($key, $value);
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddAjaxAction($actionName, $function_to_add)
        {
            $actionName = $this->GetActionName($actionName);

            add_action('wp_ajax_' . $actionName, function () use ($actionName, $function_to_add) {
                $nonce = (isset($_REQUEST['_ajax_nonce']) ? sanitize_text_field($_REQUEST['_ajax_nonce']) : '');

                $prefix = 'support-genix_AJ_Apbd_wps_';
                $endpoint = $endpoint = (0 === strpos($actionName, $prefix) ? substr($actionName, strlen($prefix)) : '');

                $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();
                $canAccessAnalytics = Apbd_wps_knowledge_base::UserCanAccessAnalytics();
                $canAccessConfig = Apbd_wps_knowledge_base::UserCanAccessConfig();

                $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();
                $isManageDocs = $canWriteDocs || $canAccessAnalytics || $canAccessConfig;

                $permission = ((current_user_can('read') && ($isAgentUser || $isManageDocs)) ? true : false);

                if ($permission) {
                    $epcapsList = [
                        // Canned message.
                        'canned_msg' => 'all',
                        'canned_msg_add' => 'all',
                        'canned_msg_edit' => 'all',
                        'canned_msg_delete_item' => 'all',
                        'canned_msg_delete_items' => 'all',
                        'canned_msg_activate_items' => 'all',
                        'canned_msg_deactivate_items' => 'all',
                        'canned_msg_confirm' => 'all',
                        'canned_msg_data' => 'read',
                        // Custom field.
                        'custom_field' => 'all',
                        'custom_field_add' => 'all',
                        'custom_field_edit' => 'all',
                        'custom_field_delete_item' => 'all',
                        'custom_field_delete_items' => 'all',
                        'custom_field_activate_items' => 'all',
                        'custom_field_deactivate_items' => 'all',
                        'custom_field_order_change' => 'all',
                        'custom_field_reset_order' => 'all',
                        'custom_field_confirm' => 'all',
                        'custom_field_data' => 'read',
                        // EDD.
                        'edd' => 'all',
                        'edd_add' => 'all',
                        'edd_edit' => 'all',
                        'edd_delete_item' => 'all',
                        'edd_delete_items' => 'all',
                        'edd_activate_items' => 'all',
                        'edd_deactivate_items' => 'all',
                        'edd_order_change' => 'all',
                        'edd_reset_order' => 'all',
                        'edd_confirm' => 'all',
                        'edd_data' => 'read',
                        // Email template.
                        'email_template' => 'all',
                        'email_template_edit' => 'all',
                        'email_template_activate_items' => 'all',
                        'email_template_deactivate_items' => 'all',
                        'email_template_confirm' => 'all',
                        'email_template_data' => 'read',
                        // Email to ticket.
                        'email_to_ticket' => 'all',
                        'email_to_ticket_add' => 'all',
                        'email_to_ticket_edit' => 'all',
                        'email_to_ticket_delete_item' => 'all',
                        'email_to_ticket_activate_item' => 'all',
                        'email_to_ticket_deactivate_item' => 'all',
                        'email_to_ticket_confirm' => 'all',
                        'email_to_ticket_data' => 'read',
                        // Envato system.
                        'envato_system' => 'all',
                        'envato_system_login' => 'all',
                        'envato_system_confirm' => 'all',
                        'envato_system_data' => 'read',
                        'envato_system_data_login' => 'read',
                        // FluentCRM.
                        'fluentcrm' => 'all',
                        'fluentcrm_add' => 'all',
                        'fluentcrm_edit' => 'all',
                        'fluentcrm_delete_item' => 'all',
                        'fluentcrm_delete_items' => 'all',
                        'fluentcrm_activate_items' => 'all',
                        'fluentcrm_deactivate_items' => 'all',
                        'fluentcrm_order_change' => 'all',
                        'fluentcrm_reset_order' => 'all',
                        'fluentcrm_confirm' => 'all',
                        'fluentcrm_data' => 'read',
                        // Incoming webhook.
                        'incoming_webhook' => 'all',
                        'incoming_webhook_add' => 'all',
                        'incoming_webhook_edit' => 'all',
                        'incoming_webhook_delete_item' => 'all',
                        'incoming_webhook_delete_items' => 'all',
                        'incoming_webhook_activate_items' => 'all',
                        'incoming_webhook_deactivate_items' => 'all',
                        'incoming_webhook_confirm' => 'all',
                        'incoming_webhook_data' => 'read',
                        // Mailbox.
                        'mailbox' => 'all',
                        'mailbox_add' => 'all',
                        'mailbox_edit' => 'all',
                        'mailbox_delete_item' => 'all',
                        'mailbox_activate_item' => 'all',
                        'mailbox_deactivate_item' => 'all',
                        'mailbox_settings_data' => 'all',
                        'mailbox_confirm' => 'all',
                        'mailbox_data' => 'read',
                        // Report email.
                        'report_email' => 'all',
                        'report_email_confirm' => 'all',
                        'report_email_data' => 'read',
                        // Report.
                        'report' => 'all',
                        'report_select' => 'all',
                        'report_generate' => 'all',
                        'report_export' => 'all',
                        'report_confirm' => 'all',
                        'report_data' => 'read',
                        // Role.
                        'role' => 'all',
                        'role_add' => 'all',
                        'role_edit' => 'all',
                        'role_delete_item' => 'all',
                        'role_delete_items' => 'all',
                        'role_confirm' => 'all',
                        'role_data' => 'read',
                        'role_data_agent_access' => 'read',
                        'role_data_for_select' => 'read',
                        'role_editable_for_select' => 'read',
                        'role_agent_for_select' => 'read',
                        'role_access_lists' => 'read',
                        // Settings.
                        'settings' => 'all',
                        'settings_logo' => 'all',
                        'settings_file' => 'all',
                        'settings_captcha' => 'all',
                        'settings_status' => 'all',
                        'settings_style' => 'all',
                        'settings_confirm' => 'all',
                        'settings_data' => 'read',
                        'settings_data_logo' => 'read',
                        'settings_data_file' => 'read',
                        'settings_data_captcha' => 'read',
                        'settings_data_status' => 'read',
                        'settings_data_style' => 'read',
                        'settings_page_for_select' => 'read',
                        // Ticket assign rule.
                        'ticket_assign_rule' => 'all',
                        'ticket_assign_rule_add' => 'all',
                        'ticket_assign_rule_edit' => 'all',
                        'ticket_assign_rule_delete_item' => 'all',
                        'ticket_assign_rule_delete_items' => 'all',
                        'ticket_assign_rule_activate_items' => 'all',
                        'ticket_assign_rule_deactivate_items' => 'all',
                        'ticket_assign_rule_confirm' => 'all',
                        'ticket_assign_rule_data' => 'read',
                        // Ticket category.
                        'ticket_category' => 'all',
                        'ticket_category_add' => 'all',
                        'ticket_category_edit' => 'all',
                        'ticket_category_delete_item' => 'all',
                        'ticket_category_delete_items' => 'all',
                        'ticket_category_activate_items' => 'all',
                        'ticket_category_deactivate_items' => 'all',
                        'ticket_category_confirm' => 'all',
                        'ticket_category_data' => 'read',
                        'ticket_category_data_for_select' => 'read',
                        // Ticket reply.
                        'ticket_reply' => 'all',
                        'ticket_reply_add' => 'ticket-reply',
                        'ticket_reply_confirm' => 'all',
                        'ticket_reply_data' => 'read',
                        // Ticket.
                        'ticket' => 'all',
                        'ticket_add' => 'read',
                        'ticket_note_add' => 'create-note',
                        'ticket_edit' => 'read',
                        'ticket_field_edit' => 'read',
                        'ticket_bulk_edit' => 'read',
                        'ticket_privacy_edit' => 'read',
                        'ticket_trash_item' => 'move-to-trash',
                        'ticket_trash_items' => 'move-to-trash',
                        'ticket_restore_item' => 'restore-ticket',
                        'ticket_restore_items' => 'restore-ticket',
                        'ticket_delete_item' => 'delete-ticket',
                        'ticket_delete_items' => 'delete-ticket',
                        'ticket_download' => 'read',
                        'ticket_confirm' => 'all',
                        'ticket_data' => 'read',
                        'ticket_data_single' => 'read',
                        'ticket_status_for_select' => 'read',
                        'ticket_priority_for_select' => 'read',
                        // Users.
                        'users' => 'all',
                        'users_add' => 'read',
                        'users_confirm' => 'all',
                        'users_data' => 'read',
                        'users_data_search' => 'read',
                        // Webhook.
                        'webhook' => 'all',
                        'webhook_add' => 'all',
                        'webhook_edit' => 'all',
                        'webhook_delete_item' => 'all',
                        'webhook_delete_items' => 'all',
                        'webhook_activate_items' => 'all',
                        'webhook_deactivate_items' => 'all',
                        'webhook_confirm' => 'all',
                        'webhook_data' => 'read',
                        // Weekend.
                        'weekend' => 'all',
                        'weekend_holiday' => 'all',
                        'weekend_confirm' => 'all',
                        'weekend_data' => 'read',
                        'weekend_data_holiday' => 'read',
                        // WooCommerce.
                        'woocommerce' => 'all',
                        'woocommerce_add' => 'all',
                        'woocommerce_edit' => 'all',
                        'woocommerce_delete_item' => 'all',
                        'woocommerce_delete_items' => 'all',
                        'woocommerce_activate_items' => 'all',
                        'woocommerce_deactivate_items' => 'all',
                        'woocommerce_settings_data' => 'all',
                        'woocommerce_order_change' => 'all',
                        'woocommerce_reset_order' => 'all',
                        'woocommerce_confirm' => 'all',
                        'woocommerce_data' => 'read',
                        // Knowledge base.
                        'knowledge_base_docs_data' => 'write_docs',
                        'knowledge_base_docs_group_data' => 'write_docs',
                        'knowledge_base_docs_group_order' => 'write_docs',
                        'knowledge_base_docs_trash_item' => 'write_docs',
                        'knowledge_base_docs_trash_items' => 'write_docs',
                        'knowledge_base_docs_restore_item' => 'write_docs',
                        'knowledge_base_docs_restore_items' => 'write_docs',
                        'knowledge_base_docs_delete_item' => 'write_docs',
                        'knowledge_base_docs_delete_items' => 'write_docs',
                        'knowledge_base_category_add' => 'access_config',
                        'knowledge_base_category_edit' => 'access_config',
                        'knowledge_base_category_data' => 'access_config',
                        'knowledge_base_category_delete_item' => 'access_config',
                        'knowledge_base_category_delete_items' => 'access_config',
                        // 'knowledge_base_category_data_for_select' => 'category_data_for_select',
                        'knowledge_base_category_order_change' => 'access_config',
                        'knowledge_base_category_reset_order' => 'access_config',
                        'knowledge_base_tag_add' => 'access_config',
                        'knowledge_base_tag_edit' => 'access_config',
                        'knowledge_base_tag_data' => 'access_config',
                        'knowledge_base_tag_delete_item' => 'access_config',
                        'knowledge_base_tag_delete_items' => 'access_config',
                        // 'knowledge_base_tag_data_for_select' => 'tag_data_for_select',
                        // 'knowledge_base_space_data_for_select' => 'space_data_for_select',
                        // 'knowledge_base_author_data_for_select' => 'author_data_for_select',
                        'knowledge_base_edit_posts_role_for_select' => 'access_config',
                        'knowledge_base_page_for_select' => 'access_config',
                        'knowledge_base_config_data' => 'access_config',
                        'knowledge_base_config_permissions_data' => 'access_config',
                        'knowledge_base_config_design_base_data' => 'access_config',
                        'knowledge_base_config_design_archive_data' => 'access_config',
                        'knowledge_base_config_design_single_data' => 'access_config',
                        'knowledge_base_config_design_style_data' => 'access_config',
                        'knowledge_base_config' => 'access_config',
                        'knowledge_base_config_permissions' => 'access_config',
                        'knowledge_base_config_design_base' => 'access_config',
                        'knowledge_base_config_design_archive' => 'access_config',
                        'knowledge_base_config_design_single' => 'access_config',
                        'knowledge_base_config_design_style' => 'access_config',
                        'knowledge_base_config_migrations_data' => 'access_config',
                        'knowledge_base_config_migration_handle' => 'access_config',
                        'knowledge_base_write_with_ai_data' => 'access_config',
                        'knowledge_base_write_with_ai' => 'access_config',
                        // 'knowledge_base_writebot_generate' => 'writebot_generate',
                        'knowledge_base_chatbot_data' => 'access_config',
                        'knowledge_base_chatbot_data_text' => 'access_config',
                        'knowledge_base_chatbot_data_style' => 'access_config',
                        'knowledge_base_chatbot' => 'access_config',
                        'knowledge_base_chatbot_text' => 'access_config',
                        'knowledge_base_chatbot_style' => 'access_config',
                        // 'knowledge_base_chatbot_get_history' => 'chatbot_get_history',
                        // 'knowledge_base_chatbot_save_history' => 'chatbot_save_history',
                        // 'knowledge_base_chatbot_save_feedback' => 'chatbot_save_feedback',
                        // 'knowledge_base_chatbot_clear_history' => 'chatbot_clear_history',
                        // 'knowledge_base_chatbot_clear_history' => 'chatbot_query',
                        // 'knowledge_base_analytics_reactions' => 'analytics_reactions',
                        'knowledge_base_analytics_top_docs_data' => 'access_analytics',
                        // 'knowledge_base_searches_event' => 'searches_event',
                        'knowledge_base_searches_top_keywords_data' => 'access_analytics',
                        'knowledge_base_searches_no_result_keywords_data' => 'access_analytics',
                        'knowledge_base_statistics_overview_data' => 'access_analytics',
                        'knowledge_base_docs_duplicate_item' => 'write_docs',
                    ];

                    $user = wp_get_current_user();

                    if (!current_user_can('manage_options') && !is_super_admin($user->ID) && !in_array('administrator', $user->roles)) {
                        $capability = (isset($epcapsList[$endpoint]) ? $epcapsList[$endpoint] : null);

                        if (
                            $capability &&
                            (
                                ('all' === $capability) ||
                                (('write_docs' === $capability) && !$canWriteDocs) ||
                                (('access_analytics' === $capability) && !$canAccessAnalytics) ||
                                (('access_config' === $capability) && !$canAccessConfig) ||
                                (
                                    !in_array($capability, ['write_docs', 'access_analytics', 'access_config'], true) &&
                                    !current_user_can($capability)
                                )
                            )
                        ) {
                            $permission = false;
                        }
                    }
                }

                if (
                    ! wp_verify_nonce($nonce, 'ajax-nonce') ||
                    ! $permission
                ) {
                    if (wp_doing_ajax()) {
                        wp_die(-1, 403);
                    } else {
                        die('-1');
                    }
                }

                call_user_func($function_to_add);
                die();
            });
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddPortalAjaxAction($actionName, $function_to_add)
        {
            $actionHook = $this->GetActionName($actionName . '_portal');

            add_action('wp_ajax_' . $actionHook, function () use ($actionName, $function_to_add) {
                $nonce = (isset($_REQUEST['_ajax_nonce']) ? sanitize_text_field($_REQUEST['_ajax_nonce']) : '');

                $prefix = 'support-genix_AJ_Apbd_wps_';
                $endpoint = $endpoint = (0 === strpos($actionName, $prefix) ? substr($actionName, strlen($prefix)) : '');
                $permission = is_user_logged_in();

                if ($permission) {
                    $epcapsList = [
                        // Role.
                        'role_data_agent_access' => false,
                        'role_agent_for_select' => false,
                        // Settings.
                        'settings_data_file' => false,
                        'settings_data_basic' => false,
                        // Ticket category.
                        'ticket_category_data_for_select' => false,
                        // Ticket reply.
                        'ticket_reply_add' => false,
                        // Ticket tag.
                        'ticket_tag_data_for_select' => false,
                        // Ticket.
                        'ticket_add' => false,
                        'ticket_note_add' => true,
                        'ticket_edit' => false,
                        'ticket_field_edit' => false,
                        'ticket_bulk_edit' => true,
                        'ticket_privacy_edit' => false,
                        'ticket_data' => false,
                        'ticket_data_single' => false,
                        'ticket_trash_item' => true,
                        'ticket_trash_items' => true,
                        'ticket_restore_item' => true,
                        'ticket_restore_items' => true,
                        'ticket_delete_item' => true,
                        'ticket_delete_items' => true,
                        'ticket_status_for_select' => false,
                        'ticket_download' => true,
                        // Users.
                        'users_add' => true,
                        'users_data_search' => true,
                        'users_logout' => false,
                        'users_update' => false,
                        'users_change_password' => false,
                    ];

                    $needmaster = (isset($epcapsList[$endpoint]) ? $epcapsList[$endpoint] : true);

                    if ($needmaster) {
                        $permission = Apbd_wps_settings::isAgentLoggedIn();
                    }
                }

                if (
                    ! wp_verify_nonce($nonce, 'ajax-nonce') ||
                    ! $permission
                ) {
                    if (wp_doing_ajax()) {
                        wp_die(-1, 403);
                    } else {
                        die('-1');
                    }
                }

                call_user_func($function_to_add);
                die();
            });
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddAjaxNoPrivAction($actionName, $function_to_add)
        {
            $actionName = $this->GetActionName($actionName);

            add_action('wp_ajax_nopriv_' . $actionName, function () use ($function_to_add) {
                $nonce = (isset($_REQUEST['_ajax_nonce']) ? sanitize_text_field($_REQUEST['_ajax_nonce']) : '');

                if (! wp_verify_nonce($nonce, 'ajax-nonce')) {
                    if (wp_doing_ajax()) {
                        wp_die(-1, 403);
                    } else {
                        die('-1');
                    }
                }

                call_user_func($function_to_add);
                die();
            });
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddPortalAjaxNoPrivAction($actionName, $function_to_add)
        {
            $actionName = $this->GetActionName($actionName . '_portal');

            add_action('wp_ajax_nopriv_' . $actionName, function () use ($function_to_add) {
                $nonce = (isset($_REQUEST['_ajax_nonce']) ? sanitize_text_field($_REQUEST['_ajax_nonce']) : '');

                if (! wp_verify_nonce($nonce, 'ajax-nonce')) {
                    if (wp_doing_ajax()) {
                        wp_die(-1, 403);
                    } else {
                        die('-1');
                    }
                }

                call_user_func($function_to_add);
                die();
            });
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddAjaxBothAction($actionName, $function_to_add)
        {
            $this->AddAjaxAction($actionName, $function_to_add);
            $this->AddAjaxNoPrivAction($actionName, $function_to_add);
        }

        /**
         * @param $actionName
         * @param callable $function_to_add
         */
        function AddPortalAjaxBothAction($actionName, $function_to_add)
        {
            $this->AddPortalAjaxAction($actionName, $function_to_add);
            $this->AddPortalAjaxNoPrivAction($actionName, $function_to_add);
        }

        function GetActionUrl($actionString = '', $params = [])
        {
            $actionName = $this->GetActionName($actionString);
            $nonceStr = "&_ajax_nonce=" . wp_create_nonce('ajax-nonce');
            $paramStr   = count($params) > 0 ? "&" . http_build_query($params) : "";

            return admin_url('admin-ajax.php') . '?action=' . $actionName . $nonceStr . $paramStr;
        }

        function GetActionUrlWithBackButton($actionString = '', $params = [], $backActionString = NULL, $backParams = [], $buttonName = "back")
        {
            $buttonName = $this->__($buttonName);
            $mainUrl    = $this->GetActionUrl($actionString, $params);
            if ($backActionString === NULL) {
                $buttonUrl = ApbdWps_CurrentUrl();
            } else {
                $buttonUrl = $this->GetActionUrl($backActionString, $backParams);
            }

            if (strpos($mainUrl, "?") !== false) {
                return $mainUrl . "&cbtn=" . urlencode($buttonUrl) . "&cbtnn=" . urlencode($buttonName);
            } else {
                return $mainUrl . "?cbtn=" . $buttonUrl . "&cbtnn=" . $buttonName;
            }
        }

        function RedirectActionUrlWithBackButton($actionString = '', $params = [], $backActionString = NULL, $backParams = [], $buttonName = "back")
        {
            $url = $this->GetActionUrlWithBackButton($actionString, $params, $backActionString, $backParams, $buttonName);
            $this->RedirectUrl($url);
        }

        function RedirectActionUrl($actionString = '', $params = [])
        {
            $url = $this->GetActionUrl($actionString, $params);
            $this->RedirectUrl($url);
        }

        function RedirectUrl($url)
        {
            if (!headers_sent()) {
                header("Location: $url");
            }
            die;
        }

        function GetAPIUrl($actionString = '', $params = [])
        {
            return home_url() . '/wp-json/' . $actionString;
        }

        function GetActionName($actionString = '')
        {
            if (! empty($actionString)) {
                $actionString = '_' . $actionString;
            }

            return $this->GetMainFormId() . $actionString;
        }

        function OptionFormHeader()
        {
            return '';
        }
        function getModuleOptionName()
        {
            $modulename    = get_class($this);
            return $this->pluginBaseName . "_o_" . $modulename;
        }

        function SetMultiLangProps()
        {
            $multiLangCode = 'en';
            $multiLangActive = false;

            if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
                $multiLangCode = apply_filters('wpml_current_language', $multiLangCode);
                $multiLangActive = true;
            } elseif (function_exists('pll_current_language')) {
                $multiLangCode = call_user_func('pll_current_language');
                $multiLangActive = true;
            }

            $multiLangCode = apply_filters('support_genix_current_language_key', $multiLangCode);
            $multiLangCode = sanitize_text_field($multiLangCode);
            $multiLangCode = (($multiLangCode && ('all' !== $multiLangCode)) ? $multiLangCode : 'en');

            $this->multiLangCode = $multiLangCode;
            $this->multiLangActive = $multiLangActive;
            $this->multiLangFields = $this->GetMultiLangFields();
        }

        function GetMultiLangFields()
        {
            return [];
        }

        function SetOption()
        {
            $optionName = $this->getModuleOptionName();
            $this->options = get_option($optionName, NULL);
        }

        function GetOption($key = '', $default = '')
        {
            if (empty($key)) {
                return $this->options;
            } else {
                if (! empty($this->options[$key])) {
                    return $this->options[$key];
                } else {
                    return $default;
                }
            }
        }

        function AddOption($key, $value)
        {
            $this->options[$key] = $value;

            return $this->UpdateOption();
        }

        function UpdateOption()
        {
            return update_option($this->pluginBaseName . "_o_" . $this, $this->options);
        }

        function SetMultiLangOption($optionKey = '')
        {
            $langFields = $this->multiLangFields;
            $langCode = $this->multiLangCode;
            $allOptions = get_option($optionKey, null);
            $options = $allOptions;

            if (is_array($langFields) && ! empty($langFields) && is_array($options) && ! empty($options)) {
                foreach ($langFields as $fieldKey => $fieldValue) {
                    if (isset($options[$fieldKey])) {
                        $option = $options[$fieldKey];

                        if (is_array($option)) {
                            $options[$fieldKey] = (isset($option[$langCode]) ? $option[$langCode] : (isset($option['en']) ? $option['en'] : $fieldValue));
                        }
                    }
                }
            }

            $this->allOptions = $allOptions;
            $this->options = $options;
        }

        function UpdateMultiLangOption($optionKey = '')
        {
            $langFields = $this->multiLangFields;
            $langCode = $this->multiLangCode;
            $allOptions = $this->allOptions;
            $options = $this->options;

            $langFields = (is_array($langFields) ? $langFields : []);
            $allOptions = (is_array($allOptions) ? $allOptions : []);
            $options = (is_array($options) ? $options : []);

            if (! empty($langFields) && ! empty($options)) {
                foreach ($options as $fieldKey => $option) {
                    if (isset($langFields[$fieldKey])) {
                        $allOption = (isset($allOptions[$fieldKey]) ? $allOptions[$fieldKey] : null);

                        if (is_array($allOption)) {
                            $allOption[$langCode] = $option;
                        } else {
                            $allOption = [$langCode => $option];
                        }

                        $allOptions[$fieldKey] = $allOption;
                    } else {
                        $allOptions[$fieldKey] = $option;
                    }
                }
            } else {
                $allOptions = $options;
            }

            $this->allOptions = $allOptions;

            return update_option($optionKey, $allOptions);
        }

        function GetModuleId()
        {
            return get_class($this);
        }

        function _e($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            echo wp_kses_post(call_user_func_array([$this->kernelObject, "__"], $args));
        }

        function _ee($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            foreach ($args as &$arg) {
                if (is_string($arg)) {
                    $arg = $this->kernelObject->__($arg);
                }
            }
            echo wp_kses_post(call_user_func_array([$this->kernelObject, "__"], $args));
        }

        function __($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();

            return call_user_func_array([$this->kernelObject, "__"], $args);
        }

        function ___($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            foreach ($args as &$arg) {
                if (is_string($arg)) {
                    $arg = $this->kernelObject->__($arg);
                }
            }

            return call_user_func_array([$this->kernelObject, "__"], $args);
        }

        function GetMainFormId()
        {
            return $this->pluginBaseName . '_AJ_' . $this;
        }

        public function __toString()
        {
            return get_class($this);
        }

        function data()
        {
            $data = new ApbdWpsAjaxDataResponse();
            die(json_encode($data));
        }

        function confirm()
        {
            $data = new ApbdWpsAjaxConfirmResponse();
            die(json_encode($data));
        }

        function AdminScriptData() {}

        function OnInit()
        {
            $this->AddAjaxAction('', [$this, 'AjaxRequestCallback']);
            $this->AddAjaxAction('data', [$this, 'data']);
            $this->AddAjaxAction('confirm', [$this, 'confirm']);
        }

        public function AjaxRequestCallback()
        {
            $response   = new ApbdWpsAjaxConfirmResponse();
            $beforeSave = $this->options;
            $postData = wp_parse_args($_POST);
            foreach ($postData as $key => $value) {
                $key = sanitize_key($key);
                if ($key == "action") {
                    continue;
                }

                $this->options[$key] = sanitize_text_field($value);
            }
            if ($beforeSave === $this->options) {
                $response->DisplayWithResponse(false, $this->__("No change for update"));
            } else {
                $response->SetResponse(false, $this->__("No change for update"));
                if ($this->UpdateOption()) {
                    $response->DisplayWithResponse(true, $this->__("Saved Successfully"));
                } else {
                    $response->DisplayWithResponse(false, $this->__("No change for update"));
                }
            }
            $response->Display();
        }

        function IsActive()
        {
            return true;
        }

        function IsPageCheck($page)
        {
            return false;
        }

        function OnActive($new_activation = true, $new_pro_activation = true) {}

        function OnDeactive() {}

        function AdminScripts() {}

        function AdminStyles() {}

        function ClientScript() {}

        function ClientStyle() {}

        public function LinksActions(&$links) {}

        public function PluginRowMeta(&$links) {}

        public function AdminSubMenu() {}

        public function OnAdminGlobalStyles() {}

        public function OnAdminMainOptionStyles() {}

        public function OnAdminGlobalScripts() {}

        public function OnAdminMainOptionScripts() {}

        /**
         * @return string
         */
        public function getFormClass()
        {
            return $this->formClass;
        }

        /**
         * @param string $formClass
         */
        public function setFormClass($formClass)
        {
            $this->formClass = $formClass;
        }

        /**
         * @return array
         */
        public function getFormDataAttr()
        {
            return $this->formDataAttr;
        }

        /**
         * @param array $formDataAttr
         */
        public function setFormDataAttr($formDataAttr)
        {
            $this->formDataAttr = $formDataAttr;
        }

        /**
         * @return bool
         */
        public function isMultipartForm()
        {
            return $this->isMultipartForm;
        }

        /**
         * @param bool $isMultipartForm
         */
        public function setIsMultipartForm($isMultipartForm)
        {
            $this->isMultipartForm = $isMultipartForm;
        }

        /**
         * @param bool $dontAddDefaultForm
         */
        public function disableDefaultForm()
        {
            $this->dontAddDefaultForm = true;
        }

        /**
         * @return bool
         */
        public function isDontAddDefaultForm()
        {
            return $this->dontAddDefaultForm;
        }
    }
}
