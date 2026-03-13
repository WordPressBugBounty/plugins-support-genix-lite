<?php

/**
 * API config.
 */

defined('ABSPATH') || exit;

class ApbdWpsAPI_Config extends Apbd_Wps_APIBase
{
    public function __construct($namespace, $register = true)
    {
        parent::__construct($namespace, $register);
    }

    function setAPIBase()
    {
        return 'basic';
    }

    function routes() {}

    public function get_nonce()
    {
        $this->response->SetResponse(true, "", [
            'rest' => wp_create_nonce('wp_rest'),
            'ajax' => wp_create_nonce('ajax-nonce'),
        ]);

        return $this->response;
    }

    function basic_settings()
    {
        global $getUser;

        // Home URL.
        $home_url = get_home_url();

        // Core object.
        $coreObject = ApbdWps_SupportLite::GetInstance();

        // Logged user.
        $getUser = wp_get_current_user();
        $logged_user = null;
        $is_master = false;

        if (is_user_logged_in()) {
            $userObj = wp_get_current_user();

            $logged_user = new stdClass();
            $logged_user->id = strval(absint($userObj->ID));
            $logged_user->first_name = $userObj->first_name;
            $logged_user->last_name = $userObj->last_name;
            $logged_user->name = trim($userObj->first_name . ' ' . $userObj->last_name);
            $logged_user->email = $userObj->user_email;
            $logged_user->img = get_user_meta($userObj->ID, 'supportgenix_avatar', true) ? get_user_meta($userObj->ID, 'supportgenix_avatar', true) : get_avatar_url($userObj->ID);

            if (empty($logged_user->name)) {
                $logged_user->name = $userObj->display_name;
            }

            $logged_user->custom_fields = apply_filters('apbd-wps/filter/user-custom-properties', [], $userObj->ID);
            $is_master = Apbd_wps_settings::isAgentLoggedIn($userObj);
        }

        // Categories.
        $catObj = new Mapbd_wps_ticket_category();
        $catRecords = $catObj->SelectAllWithKeyValue("id", "title", 'fld_order', 'ASC', '', '', '', '', ['status' => 'A']);
        $categories = [
            [
                'value' => '',
                'label' => '-- ' . $coreObject->__('Select Category') . ' --',
            ]
        ];

        if ($catRecords) {
            foreach ($catRecords as $id => $title) {
                $categories[] = [
                    'value' => strval($id),
                    'label' => $title,
                ];
            }
        }

        // File settings.
        $ticket_file_upload = Apbd_wps_settings::GetModuleOption('ticket_file_upload', 'A');
        $file_upload_size = Apbd_wps_settings::GetModuleOption('file_upload_size', 2);
        $allowed_type = Apbd_wps_settings::GetModuleOption('allowed_type', ['image', 'docs', 'text', 'pdf']);
        $file_image_popup = Apbd_wps_settings::GetModuleOption('file_image_popup', 'Y');
        $file_preview_mode = Apbd_wps_settings::GetModuleOption('file_preview_mode', 'N');

        $ticket_file_upload = ('A' === $ticket_file_upload) ? true : false;
        $file_image_popup = ('Y' === $file_image_popup) ? true : false;
        $file_preview_mode = ('Y' === $file_preview_mode) ? true : false;

        $file_upload = [
            'ticket_file_upload' => $ticket_file_upload,
            'file_upload_size' => $file_upload_size,
            'allowed_type' => $allowed_type,
            'file_image_popup' => $file_image_popup,
            'file_preview_mode' => $file_preview_mode,
        ];

        // Custom fields.
        $custom_fields = Mapbd_wps_custom_field::getCustomFieldForAPI();
        $custom_fields = apply_filters('apbd-wps/filter/before-custom-get', $custom_fields);

        // General settings.
        $tickets_auto_refresh = Apbd_wps_settings::GetModuleOption("tickets_auto_refresh", 'N');
        $tickets_auto_refresh_interval = Apbd_wps_settings::GetModuleOption("tickets_auto_refresh_interval", 60);
        $close_ticket_opt_for_customer = 'N';
        $disable_closed_ticket_reply = 'N';
        $disable_closed_ticket_reply_notice = '';
        $is_public_ticket_opt_on_creation = Apbd_wps_settings::GetModuleOption("is_public_ticket_opt_on_creation", 'N');
        $is_public_ticket_opt_on_details = Apbd_wps_settings::GetModuleOption("is_public_ticket_opt_on_details", 'N');
        $is_public_tickets_menu = Apbd_wps_settings::GetModuleOption("is_public_tickets_menu", 'N');
        $disable_registration_form = Apbd_wps_settings::GetModuleOption('disable_registration_form', 'N');
        $disable_guest_ticket_creation = Apbd_wps_settings::GetModuleOption('disable_guest_ticket_creation', 'N');
        $disable_auto_ticket_assignment = Apbd_wps_settings::GetModuleOption('disable_auto_ticket_assignment', 'N');
        $disable_need_reply_sorting = Apbd_wps_settings::GetModuleOption('disable_need_reply_sorting', 'N');
        $disable_undo_submit = Apbd_wps_settings::GetModuleOption('disable_undo_submit', 'N');
        $disable_current_viewers = Apbd_wps_settings::GetModuleOption('disable_current_viewers', 'N');

        // Login with google
        $login_with_google_url = '';

        // Login with envato
        $login_with_envato_url = '';

        // Suggested docs.
        $docs_suggestions_status = 'I';
        $betterdocs_status = 'I';

        // Finalize.
        $tickets_auto_refresh = 'Y' === $tickets_auto_refresh ? 'Y' : 'N';
        $tickets_auto_refresh_interval = max(absint($tickets_auto_refresh_interval), 5);
        $close_ticket_opt_for_customer = 'Y' === $close_ticket_opt_for_customer ? 'Y' : 'N';
        $disable_closed_ticket_reply = 'Y' === $disable_closed_ticket_reply ? 'Y' : 'N';
        $disable_closed_ticket_reply_notice = sanitize_text_field($disable_closed_ticket_reply_notice);
        $is_public_ticket_opt_on_creation = 'Y' === $is_public_ticket_opt_on_creation ? 'Y' : 'N';
        $is_public_ticket_opt_on_details = 'Y' === $is_public_ticket_opt_on_details ? 'Y' : 'N';
        $is_public_tickets_menu = 'Y' === $is_public_tickets_menu ? 'Y' : 'N';
        $disable_registration_form = 'Y' === $disable_registration_form ? 'Y' : 'N';
        $disable_guest_ticket_creation = 'Y' === $disable_guest_ticket_creation ? 'Y' : 'N';
        $disable_auto_ticket_assignment = 'Y' === $disable_auto_ticket_assignment ? 'Y' : 'N';
        $disable_need_reply_sorting = 'Y' === $disable_need_reply_sorting ? 'Y' : 'N';
        $disable_undo_submit = 'Y' === $disable_undo_submit ? 'Y' : 'N';
        $disable_current_viewers = 'Y' === $disable_current_viewers ? 'Y' : 'N';
        $docs_suggestions_status = 'A' === $docs_suggestions_status ? 'A' : 'I';
        $betterdocs_status = 'A' === $betterdocs_status ? 'A' : 'I';

        if ('Y' !== $disable_closed_ticket_reply) {
            $disable_closed_ticket_reply_notice = '';
        }

        if (!Apbd_wps_settings::RegistrationAllowed()) {
            $disable_registration_form = 'Y';
            $disable_guest_ticket_creation = 'Y';
        }

        $enable_docs_suggestions = 'N';

        if (
            ('A' === $docs_suggestions_status) ||
            (('A' === $betterdocs_status) && is_plugin_active('betterdocs/betterdocs.php'))
        ) {
            $enable_docs_suggestions = 'Y';
        }

        $settings = new stdClass();

        $settings->logged_user = $logged_user;
        $settings->is_master = $is_master;
        $settings->categories = $categories;
        $settings->file_upload = $file_upload;
        $settings->custom_fields = $custom_fields;
        $settings->tickets_auto_refresh = $tickets_auto_refresh;
        $settings->tickets_auto_refresh_interval = $tickets_auto_refresh_interval;
        $settings->close_ticket_opt_for_customer = $close_ticket_opt_for_customer;
        $settings->disable_closed_ticket_reply = $disable_closed_ticket_reply;
        $settings->disable_closed_ticket_reply_notice = $disable_closed_ticket_reply_notice;
        $settings->is_public_ticket_opt_on_creation = $is_public_ticket_opt_on_creation;
        $settings->is_public_ticket_opt_on_details = $is_public_ticket_opt_on_details;
        $settings->is_public_tickets_menu = $is_public_tickets_menu;
        $settings->disable_registration_form = $disable_registration_form;
        $settings->disable_guest_ticket_creation = $disable_guest_ticket_creation;
        $settings->disable_auto_ticket_assignment = $disable_auto_ticket_assignment;
        $settings->disable_need_reply_sorting = $disable_need_reply_sorting;
        $settings->disable_undo_submit = $disable_undo_submit;
        $settings->disable_current_viewers = $disable_current_viewers;
        $settings->enable_docs_suggestions = $enable_docs_suggestions;
        $settings->login_with_google_url = $login_with_google_url;
        $settings->login_with_envato_url = $login_with_envato_url;
        $settings->registration = Apbd_wps_settings::RegistrationAllowed();
        $settings->captcha = Apbd_wps_settings::GetCaptchaSetting();

        // AI Ticket Reply settings.
        $help_me_write_enabled = false;
        $help_me_write_tools = [];

        if ($is_master) {
            $help_me_write_module = Apbd_wps_help_me_write::GetModuleInstance();

            // Check if AI Ticket Reply is enabled
            $status = $help_me_write_module->GetOption('status', 'I');

            if ('A' === $status) {
                // Get selected AI tools
                $ai_tools = maybe_unserialize($help_me_write_module->GetOption('ai_tools', ''));

                if (!empty($ai_tools) && is_array($ai_tools)) {
                    // Check which tools are available in central settings
                    $available_tools = Apbd_wps_settings::GetAvailableAITools();

                    foreach ($ai_tools as $tool) {
                        if ('ai_proxy' === $tool && $available_tools['ai_proxy']) {
                            $help_me_write_enabled = true;
                            $help_me_write_tools[] = [
                                'value' => 'ai_proxy',
                                'label' => 'Support Genix AI'
                            ];
                        } elseif ('openai' === $tool && $available_tools['openai']) {
                            $help_me_write_enabled = true;
                            $help_me_write_tools[] = [
                                'value' => 'openai',
                                'label' => 'OpenAI'
                            ];
                        } elseif ('claude' === $tool && $available_tools['claude']) {
                            $help_me_write_enabled = true;
                            $help_me_write_tools[] = [
                                'value' => 'claude',
                                'label' => 'Claude'
                            ];
                        }
                    }
                }
            }
        }

        $settings->help_me_write = new stdClass();
        $settings->help_me_write->enabled = $help_me_write_enabled;
        $settings->help_me_write->tools = $help_me_write_tools;

        // Knowledge Base
        $settings->multiple_kb = 'N';

        $settings = apply_filters('apbd-wps/filter/settings-data', $settings);
        $settings = is_object($settings) ? $settings : new stdClass();

        $this->response->SetResponse(true, "", $settings);

        return $this->response;
    }

    function SetRoutePermission($route)
    {
        return true;
    }

    function is_valid_cf()
    {
        $fieldName = $this->GetPayload("fld_name", "");
        $fieldvalue = $this->GetPayload("fld_value", "");
        $fieldStatus = apply_filters('apbd-wps/filter/custom-field-validate', true, $fieldName, $fieldvalue);
        $msg = trim(ApbdWps_GetMsgAPI());
        if (empty($msg) && !$fieldStatus) {
            $msg = Apbd_wps_settings::GetModuleInstance()->__("Invalid input");
        }
        $this->response->SetResponse($fieldStatus, $msg, null);
        return $this->response;
    }
}
