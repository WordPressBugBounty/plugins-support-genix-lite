<?php

/**
 * Settings.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_settings_blocks_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_ai_proxy_trait.php';

class Apbd_wps_settings extends ApbdWpsBaseModuleLite
{
    use Apbd_wps_settings_blocks_trait;
    use Apbd_wps_ai_proxy_trait;

    /**
     * @var string
     */
    private static $uploadBasePath = WP_CONTENT_DIR . "/uploads/support-genix/";

    function initialize()
    {
        parent::initialize();
        $this->initialize__blocks();

        $this->disableDefaultForm();
        $this->AddAjaxAction("data_logo", [$this, "dataLogo"]);
        $this->AddAjaxAction("data_file", [$this, "dataFile"]);
        $this->AddAjaxAction("data_captcha", [$this, "dataCaptcha"]);
        $this->AddAjaxAction("data_status", [$this, "dataStatus"]);
        $this->AddAjaxAction("data_style", [$this, "dataStyle"]);
        $this->AddAjaxAction("data_auto_close", [$this, "dataAutoClose"]);
        $this->AddAjaxAction("data_api_keys_openai", [$this, "dataApiKeysOpenAI"]);
        $this->AddAjaxAction("data_api_keys_claude", [$this, "dataApiKeysClaude"]);
        $this->AddAjaxAction("data_api_keys_ai_proxy", [$this, "dataApiKeysAIProxy"]);
        $this->AddAjaxAction("data_ai_proxy_credits", [$this, "dataAIProxyCredits"]);
        $this->AddAjaxAction("data_basic", [$this, "dataBasic"]);
        $this->AddAjaxAction("data_setup_wizard", [$this, "dataSetupWizard"]);
        $this->AddAjaxAction("page_for_select", [$this, "page_for_select"]);
        $this->AddAjaxAction("logo", [$this, "AjaxRequestCallbackLogo"]);
        $this->AddAjaxAction("file", [$this, "AjaxRequestCallbackFile"]);
        $this->AddAjaxAction("captcha", [$this, "AjaxRequestCallbackCaptcha"]);
        $this->AddAjaxAction("api_keys_openai", [$this, "AjaxRequestCallbackApiKeysOpenAI"]);
        $this->AddAjaxAction("api_keys_claude", [$this, "AjaxRequestCallbackApiKeysClaude"]);
        $this->AddAjaxAction("api_keys_ai_proxy", [$this, "AjaxRequestCallbackApiKeysAIProxy"]);
        $this->AddAjaxAction("setup_wizard", [$this, "AjaxRequestCallbackSetupWizard"]);
        $this->AddAjaxAction("data_api_keys_elevenlabs", [$this, "dataApiKeysElevenLabs"]);
        $this->AddAjaxAction("api_keys_elevenlabs", [$this, "AjaxRequestCallbackApiKeysElevenLabs"]);
        $this->AddAjaxAction("elevenlabs_voices", [$this, "AjaxRequestCallbackElevenLabsVoices"]);
        $this->AddAjaxAction("elevenlabs_agents", [$this, "AjaxRequestCallbackElevenLabsAgents"]);

        $this->AddPortalAjaxAction("data_file", [$this, "dataFile"]);

        $this->AddPortalAjaxBothAction("data_basic", [$this, "dataBasic"]);

        self::$uploadBasePath = apply_filters('apbd-wps/filter/set-upload-path', self::$uploadBasePath);

        //filters
        add_filter("apbd-wps/filter/ticket-read-attached-files", [$this, 'set_ticket_attached_files'], 2, 2);
        add_filter("apbd-wps/filter/reply-read-attached-files", [$this, 'set_ticket_reply_attached_files'], 2, 2);
        add_filter("apbd-wps/filter/ticket-custom-properties", [$this, 'ticketCustomFields'], 2, 2);
        add_filter("apbd-wps/filter/user-custom-properties", [$this, 'userCustomFields'], 2, 2);

        //actions
        add_action("apbd-wps/action/download-file", [$this, 'download_file'], 8, 3);
        add_action("apbd-wps/action/ticket-created", [$this, 'save_ticket_meta'], 8, 2);
        add_action("apbd-wps/action/user-created", [$this, 'save_user_meta'], 8, 2);
        add_action("apbd-wps/action/user-updated", [$this, 'save_user_meta'], 8, 2);
        add_action("apbd-wps/action/download-file", [$this, 'download_file'], 8, 3);
        add_action("apbd-wps/action/ticket-custom-field-update", [$this, 'update_ticket_meta'], 10, 3);

        add_action('apbd-wps/action/ticket-created', [$this, "ticket_assign"], 8, 2);
        add_action('apbd-wps/action/ticket-created', [$this, "send_ticket_email"], 9, 2);
        add_action('apbd-wps/action/ticket-assigned', [$this, "notify_user_on_ticket_assigned"], 9, 1);
        add_action('apbd-wps/action/ticket-replied', [$this, "send_reply_notification"], 9, 2);
        add_action('apbd-wps/action/ticket-status-change', [$this, "send_close_ticket_email"], 9, 2);
        add_action('apbd-wps/action/ticket-status-change', [$this, "add_status_ticket_log"], 9, 2);
        add_action('apbd-wps/action/ticket-email-notification-change', [$this, "add_email_notification_ticket_log"], 9, 2);

        add_action('wp_mail_failed', [$this, "mail_send_failed"], 9, 1);

        add_filter("apbd-wps/filter/incoming-webhook-custom-field-valid", [$this, 'valid_incoming_webhook_custom_field'], 10, 5);
        add_filter("apbd-wps/filter/ht-contact-form-custom-field-valid", [$this, 'valid_ht_contact_form_custom_field'], 10, 5);
        add_filter("apbd-wps/filter/ticket-details-custom-properties", [$this, 'final_filter_custom_field'], 10, 3);
        add_filter('display_post_states', [$this, "post_states"], 10, 2);
        add_filter('wp_kses_allowed_html', [$this, 'custom_wpkses_post_tags'], 10, 2);
        add_filter('apbd-wps/filter/track-id-type', [$this, 'track_id_type'], 10);
        add_filter('apbd-wps/filter/display-track-id', [$this, 'display_track_id'], 10);
        add_filter('apbd-wps/filter/query-track-id', [$this, 'query_track_id'], 10);
        add_filter('apbd-wps/filter/ref-track-id', [$this, 'ref_track_id'], 10);
        add_action('apbd-wps/action/portal-header', [$this, "portal_header_custom"]);

        add_action('apbd-wps/action/ticket-created', function ($ticket) {
            do_action('apbd-wps/action/ticket-assigned-notice', $ticket);
        }, 98);

        add_action('show_user_profile', [$this, 'ProfileEditAction'], -99999);
        add_action('edit_user_profile', [$this, 'ProfileEditAction'], -99999);
        add_action('personal_options_update', [$this, 'ProfileUpdateAction']);
        add_action('edit_user_profile_update', [$this, 'ProfileUpdateAction']);

        add_action('template_redirect', [$this, 'portal_redirect'], ~PHP_INT_MAX);
        add_action('template_redirect', [$this, 'portal_templates'], ~PHP_INT_MAX);
        add_shortcode('supportgenix', [$this, 'portal_shortcodes']);
    }
    function portal_redirect()
    {
        if (is_user_logged_in()) {
            return;
        }

        global $post;

        $currentUrl = get_permalink($post);
        $currentUrl = esc_url_raw($currentUrl);

        $ticketPage = $this->GetOption("ticket_page", "");

        if (
            is_object($post) &&
            isset($post->post_content) &&
            (
                (!empty($ticketPage) && is_page($ticketPage)) ||
                has_shortcode($post->post_content, 'supportgenix')
            )
        ) {
            $is_wp_login_reg = sanitize_text_field($this->GetOption('is_wp_login_reg', 'N'));

            if ('Y' === $is_wp_login_reg) {
                $login_page = esc_url_raw($this->GetOption('login_page', ''));
                $login_page = empty($login_page) ? wp_login_url($currentUrl) : $login_page;

                if (home_url($_SERVER['REQUEST_URI']) !== $login_page) {
                    wp_safe_redirect($login_page);
                    exit;
                }
            }
        }
    }
    function portal_templates()
    {
        if (wp_validate_boolean(get_query_var('sgnix'))) {
            $this->guest_ticket_login();
        }

        $ticketPage = $this->GetOption("ticket_page", "");
        $shortcodeMode = $this->GetOption("ticket_page_shortcode", "N");

        if (! empty($ticketPage)) {
            if (is_page($ticketPage) && ('Y' !== $shortcodeMode)) {
                // Redirect to canonical URL if host doesn't match (e.g. www vs non-www).
                // This prevents cookie/session issues since login cookies are bound to the configured host.
                $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
                $current_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';

                if (! empty($site_host) && ! empty($current_host) && strtolower($site_host) !== strtolower($current_host)) {
                    $canonical_url = get_permalink($ticketPage);

                    if (! empty($canonical_url)) {
                        wp_safe_redirect($canonical_url, 301);
                        exit;
                    }
                }
?>
                <!DOCTYPE html>
                <html lang="">

                <head>
                    <meta charset="utf-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width,initial-scale=1">
                    <link rel="icon" href="<?php echo esc_url($this->GetOption("app_favicon", $this->get_portal_url("dist/img/favicon32x32.png"))); ?>">
                    <link rel="icon" type="image/png" href="<?php echo esc_url($this->GetOption("app_favicon", $this->get_portal_url("dist/img/favicon180x180.png"))); ?>">
                    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($this->GetOption("app_favicon", $this->get_portal_url("dist/img/favicon180x180.png"))); ?>">
                    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url($this->GetOption("app_favicon", $this->get_portal_url("dist/img/favicon32x32.png"))); ?>">
                    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url($this->GetOption("app_favicon", $this->get_portal_url("dist/img/favicon16x16.png"))); ?>">
                    <title><?php echo esc_html(get_the_title()); ?></title>
                    <?php do_action('apbd-wps/action/portal-header'); ?>
                </head>

                <body class="support-genix-portal">
                    <noscript>
                        <strong>
                            <?php $this->_e("We're sorry but Support Genix doesn't work properly without JavaScript enabled."); ?>
                        </strong>
                    </noscript>
                    <div id="support-genix"></div>
                    <?php
                    $coreObject = ApbdWps_SupportLite::GetInstance();
                    $pluginPath = untrailingslashit(plugin_dir_path($coreObject->pluginFile));

                    if (Apbd_wps_knowledge_base::is_portal_chatbot_active()) {
                        include_once $pluginPath . '/views/knowledge_base/chatbot/main.php';
                    }
                    ?>
                </body>

                </html>
        <?php
                exit;
            }
        }
    }
    function portal_shortcodes()
    {
        ob_start();
        do_action('apbd-wps/action/portal-header', true);
        ?>
        <noscript>
            <strong>
                <?php $this->_e("We're sorry but Support Genix doesn't work properly without JavaScript enabled."); ?>
            </strong>
        </noscript>
        <div id="support-genix" class="support-shortcode"></div>
        <?php
        return ob_get_clean();
    }
    function portal_header_custom($shortcode = false)
    {
        global $post;

        $currentUrl = get_permalink($post);
        $currentUrl = esc_url_raw($currentUrl);

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $base_path = plugin_dir_path($coreObject->pluginFile);
        $dist_path = untrailingslashit($base_path) . "/portal/dist";
        $dist_css_files = ApbdWps_GetFilesInDirectory($dist_path, 'css');
        $dist_js_files = ApbdWps_GetFilesInDirectory($dist_path, 'js');

        // Main CSS.
        if (is_array($dist_css_files) && !empty($dist_css_files)) {
            foreach ($dist_css_files as $file_name) {
                if (0 === strpos($file_name, 'main.')) {
                    $ats = 'rel="stylesheet" id="support-genix-portal-main-css" href="' . esc_url($this->get_portal_url("dist/{$file_name}")) . '" media=""';
        ?>
                    <link <?php echo wp_kses_post($ats); ?> />
            <?php
                }
            }
        } else {
            $ats = 'rel="stylesheet" id="support-genix-portal-main-css" href="' . esc_url($this->get_portal_url("dist/main.BipHL1nv.1773217519036.css")) . '" media=""';
            ?>
            <link <?php echo wp_kses_post($ats); ?> />
        <?php
        }

        // Primary color.
        if (!empty($this->get_primary_brand_color())) {
        ?>
            <style>
                <?php echo wp_kses_post($this->set_primary_color_css()); ?>
            </style>
        <?php
        }

        // Secondary color.
        if (!empty($this->get_secondary_brand_color())) {
        ?>
            <style>
                <?php echo wp_kses_post($this->set_secondary_color_css()); ?>
            </style>
        <?php
        }

        // Custom CSS.
        if (!empty($this->get_custom_css())) {
        ?>
            <style>
                <?php echo ApbdWps_KsesCss($this->get_custom_css()); ?>
            </style>
        <?php
        }

        // Logo.
        $logo_url = esc_url_raw($this->GetOption('app_logo', ''));
        $logo_url = empty($logo_url) ? $this->get_portal_url("dist/img/logo.png", false) : $logo_url;

        // WP Login Reg.
        $reg_url = '';
        $login_url = '';
        $profile_url = '';

        $logout_url = wp_logout_url($currentUrl);
        $logout_url = is_string($logout_url) ? htmlspecialchars_decode($logout_url) : '#';

        $is_wp_login_reg = sanitize_text_field($this->GetOption('is_wp_login_reg', 'N'));
        $is_wp_profile_link = sanitize_text_field($this->GetOption('is_wp_profile_link', 'N'));

        if ('Y' === $is_wp_login_reg) {
            $reg_url = esc_url_raw($this->GetOption('reg_page', ''));
            $reg_url = empty($reg_url) ? wp_registration_url() : $reg_url;

            $login_url = esc_url_raw($this->GetOption('login_page', ''));
            $login_url = empty($login_url) ? wp_login_url($currentUrl) : $login_url;
        }

        if ('Y' === $is_wp_profile_link) {
            $profile_url = esc_url_raw($this->GetOption('wp_profile_link', ''));
            $profile_url = empty($profile_url) ? admin_url("profile.php") : $profile_url;
        }

        // JS Config.
        $user_settings = [
            'url'    => (string) SITECOOKIEPATH,
            'uid'    => (string) get_current_user_id(),
            'time'   => (string) time(),
            'secure' => (string) ('https' === wp_parse_url(site_url(), PHP_URL_SCHEME)),
        ];

        $support_genix_config = [
            'lite' => true,
            'demo' => $coreObject->isDemoMode(),
            'shortcode' => $shortcode,
            'logo_url' => $logo_url,
            'reg_url' => $reg_url,
            'login_url' => $login_url,
            'profile_url' => $profile_url,
            'logout_url' => $logout_url,
            'logged_in' => is_user_logged_in(),
            'logged_id' => get_current_user_id(),
            'is_master' => Apbd_wps_settings::isAgentLoggedIn(),
            'home_url' => ApbdWps_AdjustUrlToCurrentHost(home_url()),
            'rest_url' => ApbdWps_AdjustUrlToCurrentHost(rest_url('apbd-wps/v1/portal/')),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('ajax-nonce'),
            'copy_text' => $this->copyright_text(),
            'primary_color' => $this->get_primary_brand_color(),
            'weekend_notice' => '',
            'tinymce_base' => includes_url('js/tinymce'),
            'wp_version' => get_bloginfo('version'),
            'version' => $coreObject->pluginVersion,
            'is_rtl' => is_rtl(),
            'texts' => Apbd_wps_settings::portal_texts(),
            'debug' => defined('WP_DEBUG') ? !!WP_DEBUG : false,
        ];
        ?>
        <script id="utils-js-extra">
            var userSettings = <?php echo json_encode($user_settings); ?>;
        </script>
        <?php
        $ats = 'type="text/javascript" src="' . esc_url(includes_url('js/utils.min.js')) . '"';
        ?>
        <script <?php echo wp_kses_post($ats); ?>></script>
        <script id="support-genix-portal-main-js-extra">
            var support_genix_config = <?php echo json_encode($support_genix_config); ?>;
        </script>
        <?php

        // Main JS.
        if (is_array($dist_js_files) && !empty($dist_js_files)) {
            foreach ($dist_js_files as $file_name) {
                if (0 === strpos($file_name, 'main.')) {
                    $ats = 'type="module" src="' . esc_url($this->get_portal_url("dist/{$file_name}")) . '" id="support-genix-portal-main-js"';
        ?>
                    <script <?php echo wp_kses_post($ats); ?>></script>
            <?php
                }
            }
        } else {
            $ats = 'type="module" src="' . esc_url($this->get_portal_url("dist/main.DRU-CvRX.1773217519036.js")) . '" id="support-genix-portal-main-js"';
            ?>
            <script <?php echo wp_kses_post($ats); ?>></script>
        <?php
        }
    }
    function set_primary_color_css()
    {
        $color = $this->get_primary_brand_color();
        $css = '#support-genix .sg-editor-container .mce-btn.mce-active button,#support-genix .sg-editor-container .mce-btn.mce-active i,#support-genix .sg-editor-container .mce-btn.mce-active:hover button,#support-genix .sg-editor-container .mce-btn.mce-active:hover i,#support-genix a.sg-anchor,#support-genix a.sg-anchor:focus,#support-genix a.sg-anchor:hover,.mce-floatpanel.mce-window .mce-foot .mce-btn button:hover,.sgenix-ant-modal .sg-editor-container .mce-btn.mce-active button,.sgenix-ant-modal .sg-editor-container .mce-btn.mce-active i,.sgenix-ant-modal .sg-editor-container .mce-btn.mce-active:hover button,.sgenix-ant-modal .sg-editor-container .mce-btn.mce-active:hover i,.sgenix-ant-modal a.sg-anchor,.sgenix-ant-modal a.sg-anchor:focus,.sgenix-ant-modal a.sg-anchor:hover{color:' . $color . '}#support-genix .sgenix-ant-form input.sgenix-ant-input:hover,#support-genix input.sgenix-ant-input:hover,.sgenix-ant-modal .sgenix-ant-form input.sgenix-ant-input:hover,.sgenix-ant-modal input.sgenix-ant-input:hover{border-color:' . $color . '}#support-genix .sgenix-ant-form input.sgenix-ant-input:focus,#support-genix .sgenix-ant-form input.sgenix-ant-input:focus-within,#support-genix input.sgenix-ant-input:focus,#support-genix input.sgenix-ant-input:focus-within,.sgenix-ant-modal .sgenix-ant-form input.sgenix-ant-input:focus,.sgenix-ant-modal .sgenix-ant-form input.sgenix-ant-input:focus-within,.sgenix-ant-modal input.sgenix-ant-input:focus,.sgenix-ant-modal input.sgenix-ant-input:focus-within{border-color:' . $color . '}#support-genix .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn.mce-active,#support-genix .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:active,#support-genix .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:focus,#support-genix .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:hover,#support-genix .sg-editor-container .qt-dfw.active,#support-genix .sg-editor-container .qt-dfw:focus,#support-genix .sg-editor-container .qt-dfw:hover,.mce-floatpanel.mce-window .mce-foot .mce-btn:hover,.mce-floatpanel.mce-window .mce-window-body .mce-formitem .mce-btn.mce-active,.mce-floatpanel.mce-window .mce-window-body .mce-formitem .mce-btn.mce-active:focus,.mce-floatpanel.mce-window .mce-window-body .mce-formitem .mce-btn.mce-active:hover,.mce-floatpanel.mce-window .mce-window-body .mce-formitem .mce-btn:focus,.mce-floatpanel.mce-window .mce-window-body .mce-formitem .mce-btn:hover,.mce-floatpanel.mce-window .mce-window-body .mce-formitem input.mce-textbox:focus,.mce-floatpanel.mce-window .mce-window-body .mce-formitem input.mce-textbox:hover,.sgenix-ant-modal .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn.mce-active,.sgenix-ant-modal .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:active,.sgenix-ant-modal .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:focus,.sgenix-ant-modal .sg-editor-container .mce-toolbar .mce-btn-group .mce-btn:hover,.sgenix-ant-modal .sg-editor-container .qt-dfw.active,.sgenix-ant-modal .sg-editor-container .qt-dfw:focus,.sgenix-ant-modal .sg-editor-container .qt-dfw:hover{border:1px solid ' . $color . '}.mce-floatpanel.mce-window .mce-foot .mce-btn.mce-primary,.mce-floatpanel.mce-window .mce-foot .mce-btn.mce-primary:hover{background-color:' . $color . ';border:1px solid ' . $color . '}.mce-floatpanel.mce-tinymce-inline .mce-toolbar .mce-btn-group .mce-btn.mce-active:active,.mce-floatpanel.mce-tinymce-inline .mce-toolbar .mce-btn-group .mce-btn.mce-active:hover,.mce-floatpanel.mce-tinymce-inline .mce-toolbar .mce-btn-group .mce-btn:active,.mce-floatpanel.mce-tinymce-inline .mce-toolbar .mce-btn-group .mce-btn:hover{border-color:' . $color . '}';
        // Quill
        $css .= '#support-genix .quill .ql-container .ql-editor a,#support-genix .quill .ql-container .ql-editor a:focus,#support-genix .quill .ql-container .ql-editor a:hover,#support-genix .quill .ql-toolbar .ql-replies:hover,.quill .ql-container .ql-editor a,.quill .ql-container .ql-editor a:focus,.quill .ql-container .ql-editor a:hover,.quill .ql-toolbar .ql-replies:hover{color:' . $color . '}#support-genix .cm-editor.cm-focused,#support-genix .cm-editor:hover,.cm-editor.cm-focused,.cm-editor:hover{border:1px solid ' . $color . '}#support-genix .quill .ql-toolbar.ql-snow .ql-active,#support-genix .quill .ql-toolbar.ql-snow .ql-picker-label:hover,#support-genix .quill .ql-toolbar.ql-snow button:hover,.quill .ql-toolbar.ql-snow .ql-active,.quill .ql-toolbar.ql-snow .ql-picker-label:hover,.quill .ql-toolbar.ql-snow button:hover{color:' . $color . '!important}#support-genix .quill .ql-toolbar.ql-snow .ql-active .ql-stroke,#support-genix .quill .ql-toolbar.ql-snow .ql-picker-label:hover .ql-stroke,#support-genix .quill .ql-toolbar.ql-snow button:hover .ql-stroke,.quill .ql-toolbar.ql-snow .ql-active .ql-stroke,.quill .ql-toolbar.ql-snow .ql-picker-label:hover .ql-stroke,.quill .ql-toolbar.ql-snow button:hover .ql-stroke{stroke:' . $color . '!important}#support-genix .quill .ql-toolbar.ql-snow .ql-active .ql-fill,#support-genix .quill .ql-toolbar.ql-snow .ql-picker-label:hover .ql-fill,#support-genix .quill .ql-toolbar.ql-snow button:hover .ql-fill,.quill .ql-toolbar.ql-snow .ql-active .ql-fill,.quill .ql-toolbar.ql-snow .ql-picker-label:hover .ql-fill,.quill .ql-toolbar.ql-snow button:hover .ql-fill{fill:' . $color . '!important}';
        return $css;
    }
    function set_secondary_color_css()
    {
        $color = $this->get_secondary_brand_color();
        $css = '';
        return $css;
    }
    function get_profile_link()
    {
        if ($this->GetOption('is_wp_profile_link', 'N') == 'Y') {
            $profileLink = $this->GetOption('wp_profile_link', '');
            if (! empty($profileLink)) {
                return $profileLink;
            } else {
                return admin_url("profile.php");
            }
        } else {
            return '';
        }
    }
    function get_custom_css()
    {
        return '';
    }
    function get_primary_brand_color()
    {
        return '#0bbc5c';
    }
    function get_secondary_brand_color()
    {
        return '#ff6e30';
    }
    function track_id_type($track_id)
    {
        $seq_track_id = 'N';

        if ($seq_track_id == "Y") {
            $track_id = "S";
        }
        return $track_id;
    }
    function display_track_id($track_id)
    {
        $prefix = substr($track_id, 0, 2);
        if ("S-" == $prefix) {
            $track_id = substr($track_id, 2);
            $seq_track_id = 'N';

            if ('Y' === $seq_track_id) {
                $setted_length = $this->GetOption('track_id_min_len');
                $setted_length = absint($setted_length);
                $setted_length = min(10, $setted_length);

                $setted_prefix = $this->GetOption('track_id_prefix');
                $setted_prefix = sanitize_text_field($setted_prefix);

                if ($setted_length) {
                    $track_id = str_pad($track_id, $setted_length, '0', STR_PAD_LEFT);
                }

                if ($setted_prefix) {
                    $track_id = $setted_prefix . $track_id;
                }
            }
        }
        return $track_id;
    }

    function query_track_id($track_id)
    {
        $seq_track_id = 'N';

        if ('Y' === $seq_track_id) {
            $setted_length = $this->GetOption('track_id_min_len');
            $setted_length = absint($setted_length);
            $setted_length = min(10, $setted_length);

            $setted_prefix = $this->GetOption('track_id_prefix');
            $setted_prefix = sanitize_text_field($setted_prefix);

            if ($setted_prefix && (0 === strpos($track_id, $setted_prefix))) {
                $track_id = ltrim($track_id, $setted_prefix);
                $track_id = absint($track_id);
            }

            if (is_numeric($track_id)) {
                $track_id = "S-" . $track_id;
            }
        }

        return $track_id;
    }

    function ref_track_id($track_id)
    {
        $prefix = substr($track_id, 0, 2);
        if ("S-" == $prefix) {
            $track_id = substr($track_id, 2);
        }
        return $track_id;
    }
    function custom_wpkses_post_tags($tags, $context)
    {
        if ('post' === $context) {
            $tags['iframe'] = array(
                'src'             => true,
                'height'          => true,
                'width'           => true,
                'frameborder'     => true,
                'allowfullscreen' => true,
            );
        }
        return $tags;
    }

    function GetMultiLangFields()
    {
        return [
            'ticket_page' => '',
            'login_page' => '',
            'reg_page' => '',
            'wp_profile_link' => '',
            'footer_cp_text' => '',
            'disable_closed_ticket_reply_notice' => '',
            'app_logo' => '',
            'tkt_status_new' => '',
            'tkt_status_active' => '',
            'tkt_status_inactive' => '',
            'tkt_status_closed' => '',
            'tkt_status_in_progress' => '',
            'tkt_status_re_open' => '',
            'tkt_status_deleted' => '',
        ];
    }

    function SetOption()
    {
        $optionName = $this->getModuleOptionName();
        $this->SetMultiLangOption($optionName);
    }

    function UpdateOption()
    {
        $optionName = $this->getModuleOptionName();
        return $this->UpdateMultiLangOption($optionName);
    }

    public function OnInit()
    {
        parent::OnInit();
        add_filter('apbd-wps/filter/attached-file', [$this, "fileCheck"], 10, 5);
        add_action('apbd-wps/action/attach-files', [$this, "attach_file"], 10, 3);
        $this->add_support_genix_rewrite();
        add_filter('query_vars', [$this, 'register_query_var']);
        add_action('admin_bar_menu', [$this, 'support_genix_admin_bar_button'], 999);
    }
    function support_genix_admin_bar_button(\WP_Admin_Bar $wp_admin_bar)
    {
        $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();
        $canAccessAnalytics = Apbd_wps_knowledge_base::UserCanAccessAnalytics();
        $canAccessConfig = Apbd_wps_knowledge_base::UserCanAccessConfig();

        $userObj = wp_get_current_user();
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn($userObj);
        $isManageDocs = $canWriteDocs || $canAccessAnalytics || $canAccessConfig;
        $isAdminUser = current_user_can('manage_options') || is_super_admin($userObj->ID) || in_array('administrator', $userObj->roles);
        $isAdminPanel = is_admin();

        $pageId = $this->GetOption("ticket_page", "");
        $adminUrl = admin_url('admin.php?page=support-genix');
        $portalUrl = (!empty($pageId) && ('page' === get_post_type($pageId))) ? get_page_link($pageId) : '';

        $pageUrl = $isAdminPanel && ($isAgentUser || $isManageDocs) ? $adminUrl : $portalUrl;
        $pageLabel = ($isAdminPanel && ($isAgentUser || $isManageDocs) && ($isAdminUser || $portalUrl) ? $this->__("Support Genix") : $this->__("Support Tickets"));
        $pageIcon = '<span class="dashicons-logo-icon"></span> ';

        if (!$isAdminPanel || (!$isAgentUser && !$isManageDocs)) {
            $pageIcon = '<style>#wpadminbar #wp-admin-bar-support-genix > .ab-item:before {content: "\f333";top: 2px;}</style>';
        }

        $separatorStyle = '';

        if ($isAgentUser) {
            $separatorSlug = '';

            if ($canWriteDocs) {
                $separatorSlug = 'docs';
            } else if ($canAccessAnalytics) {
                $separatorSlug = 'docs-analytics';
            } else if ($canAccessConfig) {
                $separatorSlug = 'docs-config';
            }

            if ($separatorSlug) {
                $separatorStyle = '<style>#wpadminbar li#wp-admin-bar-support-genix ul.ab-submenu li#wp-admin-bar-support-genix-' . $separatorSlug . ' {border-top: 2px solid rgba(240, 246, 252, .2); margin-top: 3px; padding-top: 1px;}</style>';
            }
        }

        if (!empty($pageUrl)) {
            $wp_admin_bar->add_node([
                'id'    => 'support-genix',
                'title' => $separatorStyle . $pageIcon . $pageLabel,
                'href'  => $pageUrl,
                'target'  => "_blank"
            ]);

            if ($isAdminPanel) {
                if ($isAgentUser) {
                    if ($isAdminUser || $portalUrl || $isManageDocs) {
                        $wp_admin_bar->add_menu([
                            'parent' => 'support-genix',
                            'id' => 'support-genix-tickets',
                            'title' => $this->__("Support Tickets"),
                            'href' => $pageUrl . '#/tickets',
                        ]);
                    }

                    if ($isAdminUser) {
                        $wp_admin_bar->add_menu([
                            'parent' => 'support-genix',
                            'id' => 'support-genix-reports',
                            'title' => $this->__("Reports"),
                            'href' => $pageUrl . '#/reports',
                        ]);
                    }
                }

                if ($canWriteDocs) {
                    $wp_admin_bar->add_menu([
                        'parent' => 'support-genix',
                        'id' => 'support-genix-docs',
                        'title' => $this->__("Knowledge Base"),
                        'href' => $pageUrl . '#/docs',
                    ]);
                }

                if ($canAccessAnalytics) {
                    $wp_admin_bar->add_menu([
                        'parent' => 'support-genix',
                        'id' => 'support-genix-chat-history',
                        'title' => $this->__("Chat History"),
                        'href' => $pageUrl . '#/chat-history',
                    ]);

                    $wp_admin_bar->add_menu([
                        'parent' => 'support-genix',
                        'id' => 'support-genix-docs-analytics',
                        'title' => $this->__("Analytics"),
                        'href' => $pageUrl . '#/docs/analytics',
                    ]);
                }

                if ($isAdminUser || $canAccessConfig) {
                    $wp_admin_bar->add_menu([
                        'parent' => 'support-genix',
                        'id' => 'support-genix-settings',
                        'title' => $this->__("Settings"),
                        'href' => $pageUrl . '#/settings',
                    ]);
                }

                if ($isAgentUser) {
                    if ($portalUrl) {
                        $wp_admin_bar->add_menu([
                            'parent' => 'support-genix',
                            'id' => 'support-genix-portal',
                            'title' => $this->__("Visit Portal"),
                            'href' => $portalUrl,
                        ]);
                    }
                }
            }
        }
    }
    function copyright_text()
    {
        $site_url = get_site_url();
        $site_title = get_bloginfo('name');
        $year = date('Y');

        $default_cp_text = sprintf($this->__("Copyright %s © %s"), '[site_link]', '[year]');

        $shortcode_mode = $this->GetOption("ticket_page_shortcode", "N");
        $shortcode_mode = 'Y' === $shortcode_mode ? 'Y' : 'N';

        $footer_cp_text = $this->GetOption("footer_cp_text", "");
        $footer_cp_text = stripslashes($footer_cp_text);
        $footer_cp_text = trim($footer_cp_text);

        if ("" === $footer_cp_text) {
            $footer_cp_text = $default_cp_text;
        }

        $footer_cp_text = str_replace("[site_title]", $site_title, $footer_cp_text);
        $footer_cp_text = str_replace("[site_url]", $site_url, $footer_cp_text);
        $footer_cp_text = str_replace("[site_link]", sprintf('<a href="%s">%s</a>', $site_url, $site_title), $footer_cp_text);
        $footer_cp_text = str_replace("[year]", $year, $footer_cp_text);

        $hide_pb_text = $this->GetOption("is_hide_cp_text", "N");

        if ("Y" !== $shortcode_mode) {
            if ("Y" !== $hide_pb_text) {
                $footer_cp_text = sprintf('%s | %s', $footer_cp_text, sprintf($this->__('Powered by %s'), '<a target="_blank" href="https://supportgenix.com">Support Genix</a>'));
            }
        } elseif ("Y" !== $hide_pb_text) {
            $footer_cp_text = sprintf($this->__('Powered by %s'), '<a target="_blank" href="https://supportgenix.com">Support Genix</a>');
        } else {
            $footer_cp_text = '';
        }

        return $footer_cp_text;
    }

    function post_states($post_states, $post)
    {
        if ($this->GetOption('ticket_page') == $post->ID) {
            $post_states['support_genix'] = esc_html__('Support Genix', 'support-genix-lite');
        }
        return $post_states;
    }
    function register_query_var($vars)
    {
        $vars[] = 'sg_ticket';
        $vars[] = 'sgnix';
        return $vars;
    }
    function add_support_genix_rewrite()
    {

        add_rewrite_rule('^sgnix/?([^/]*)/?', 'index.php?sgnix=true&sg_ticket=$matches[1]', 'top');
        if (! empty(get_transient('supportgenix_rwrite_rule'))) {
            flush_rewrite_rules(true);
            delete_transient('supportgenix_rwrite_rule');
        }
    }

    public function guest_ticket_login()
    {
        $ticket_param = rtrim(ApbdWps_GetValue('p', ''), '/');

        if (! empty($ticket_param)) {
            $encKey = Apbd_wps_settings::GetEncryptionKey();
            $encObj = Apbd_Wps_EncryptionLib::getInstance($encKey);
            $requestParam = $encObj->decryptObj($ticket_param);

            if (! empty($requestParam->ticket_id) && ! empty($requestParam->ticket_user)) {
                $ticket = Mapbd_wps_ticket::FindBy("id", $requestParam->ticket_id);

                if (! empty($ticket) && $ticket->ticket_user == $requestParam->ticket_user) {
                    $is_guest_user = get_user_meta($ticket->ticket_user, "is_guest", true) == "Y";
                    $disable_hotlink = Apbd_wps_settings::GetModuleOption('disable_ticket_hotlink', 'N');

                    if ($is_guest_user || 'Y' !== $disable_hotlink) {
                        $ticket_link = Mapbd_wps_ticket::getTicketAdminLink($ticket);

                        if (is_user_logged_in()) {
                            wp_logout();
                        }

                        wp_clear_auth_cookie();
                        wp_set_current_user($ticket->ticket_user);
                        wp_set_auth_cookie($ticket->ticket_user);
                        wp_safe_redirect($ticket_link);
                        exit;
                    }
                }
            }
        }
    }

    public static function RegistrationAllowed()
    {
        if (get_option('users_can_register')) {
            return true;
        }

        $override_register = Apbd_wps_settings::GetModuleOption('override_wp_users_can_register', 'Y');

        if ('Y' === $override_register) {
            return true;
        }

        return false;
    }

    public static function DisableSetupWizard()
    {
        $settingsObj = Apbd_wps_settings::GetModuleInstance();

        $settingsObj->AddIntoOption('setup_wizard_step', 0);
        $settingsObj->AddIntoOption('setup_wizard_finished', true);
        $settingsObj->UpdateOption();
    }

    public static function ConvertOldSettings()
    {
        $migrated = get_option('apbd_support_genix_migrated', false);

        if ($migrated) {
            return;
        }

        global $wpdb;

        $options = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM `" . esc_sql($wpdb->options) . "` WHERE option_name LIKE %s", '%apbd-wp-support%'));

        if (!empty($options)) {
            foreach ($options as $option) {
                $option_name = $option->option_name;
                $option_value = $option->option_value;

                $new_option_name = str_replace('apbd-wp-support', 'support-genix', $option_name);
                $new_option_value = is_serialized($option_value) ? unserialize($option_value) : $option_value;

                update_option($new_option_name, $new_option_value);
            }
        }

        update_option('apbd_support_genix_migrated', true);
    }

    public static function CreateEncryptionKey()
    {
        $encryption_key = get_option('apbd_wps_encryption_key', '');
        if (empty($encryption_key)) {
            $encryption_key = ApbdWps_EncryptionKey();
            if (! empty($encryption_key)) {
                update_option('apbd_wps_encryption_key', $encryption_key);
            }
        }
    }

    public static function GetEncryptionKey()
    {
        $encryption_key = get_option('apbd_wps_encryption_key', 'WPS_ABD_enc');
        $encryption_key = (! empty($encryption_key) ? $encryption_key : 'WPS_ABD_enc');
        return $encryption_key;
    }

    public function get_portal_url($link, $withVersion = true)
    {
        if (!$withVersion) {
            $url = plugins_url("portal/" . $link, $this->pluginFile);
        } else {
            $version = $this->kernelObject->pluginVersion;

            $base_path = plugin_dir_path($this->kernelObject->pluginFile);
            $file_path = realpath($base_path . "portal/" . $link);

            if (file_exists($file_path)) {
                $version .= '-';
                $version .= filemtime($file_path);

                if (defined('WP_DEBUG') && !!WP_DEBUG) {
                    $version .= '-';
                    $version .= time();
                }
            }

            $url = plugins_url("portal/" . $link . "?v=" . $version, $this->pluginFile);
        }

        // Adjust URL to match current request's host (fixes www/non-www CORS issues)
        return ApbdWps_AdjustUrlToCurrentHost($url);
    }

    public function get_chatbot_url($link, $withVersion = true)
    {
        if (!$withVersion) {
            $url = plugins_url("chatbot/" . $link, $this->pluginFile);
        } else {
            $version = $this->kernelObject->pluginVersion;

            $base_path = plugin_dir_path($this->kernelObject->pluginFile);
            $file_path = realpath($base_path . "chatbot/" . $link);

            if (file_exists($file_path)) {
                $version .= '-';
                $version .= filemtime($file_path);

                if (defined('WP_DEBUG') && !!WP_DEBUG) {
                    $version .= '-';
                    $version .= time();
                }
            }

            $url = plugins_url("chatbot/" . $link . "?v=" . $version, $this->pluginFile);
        }

        // Adjust URL to match current request's host (fixes www/non-www CORS issues)
        return ApbdWps_AdjustUrlToCurrentHost($url);
    }

    public static function get_upload_path()
    {
        return self::$uploadBasePath;
    }

    public static function isClientLoggedIn($user = null)
    {
        return !self::isAgentLoggedIn($user);
    }

    public static function isAgentLoggedIn($user = null)
    {
        if (empty($user)) {
            $user = wp_get_current_user();
        }
        if (empty($user)) {
            return false;
        }
        if (current_user_can('manage_options') || is_super_admin($user->ID) || in_array('administrator', $user->roles)) {
            return true;
        }
        $agent_roles = Mapbd_wps_role::FindAllBy("status", "A", ["is_agent" => "Y"]);
        foreach ($agent_roles as $agent_role) {
            if (in_array($agent_role->slug, $user->roles)) {
                return true;
            }
        }

        return false;
    }

    public static function getSupportGenixRole($user)
    {
        if (is_super_admin($user->ID) || in_array('administrator', $user->roles)) {
            return self::GetModuleInstance()->__("Administrator");
        }
        $agent_roles = Mapbd_wps_role::FindAllBy("status", "A", ["is_agent" => "Y"]);
        foreach ($agent_roles as $agent_role) {
            if (in_array($agent_role->slug, $user->roles)) {
                return $agent_role->name;
            }
        }
    }

    public static function GetCaptchaSetting()
    {
        $rc_set = new stdClass();
        $rc_set->status = Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A";
        if ($rc_set->status) {
            $rc_set->hide_badge       = Apbd_wps_settings::GetModuleOption("recaptcha_v3_hide_badge", "N") == "Y";
            $rc_set->site_key         = Apbd_wps_settings::GetModuleOption("recaptcha_v3_site_key", "");
            $rc_set->on_login_form    = Apbd_wps_settings::GetModuleOption("captcha_on_login_form", "Y") == "Y";
            $rc_set->on_create_ticket = Apbd_wps_settings::GetModuleOption("captcha_on_create_tckt", "Y") == "Y";
            $rc_set->on_reg_form      = Apbd_wps_settings::GetModuleOption("captcha_on_reg_form", "Y") == "Y";
        }
        $rc_set = apply_filters('apbd-wps/captcha-settings', $rc_set);
        return $rc_set;
    }

    public function GetAllowedFileType()
    {
        $key = "allowed_type";
        $options = $this->options;
        $defaultType = ["image", "docs", "text", "pdf"];
        $allowedType = ((isset($options[$key]) && is_array($options[$key])) ? array_map('sanitize_text_field', $options[$key]) : $defaultType);
        $allowedType = (! empty($allowedType) ? array_map('strtolower', $allowedType) : $defaultType);
        $defaultExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'pdf'];
        $allowedExts = [];

        foreach ($allowedType as $type) {
            switch ($type) {
                case 'image':
                    $allowedExts = array_merge($allowedExts, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                    break;

                case 'video':
                    $allowedExts = array_merge($allowedExts, ['mp4', 'webm', 'mov', 'avi', 'ogv']);
                    break;

                case 'audio':
                    $allowedExts = array_merge($allowedExts, ['mp3', 'wav', 'aac', 'ogg', 'flac', 'm4a', 'wma']);
                    break;

                case 'docs':
                    $allowedExts = array_merge($allowedExts, ['doc', 'docx', 'xls', 'xlsx']);
                    break;

                case 'text':
                    $allowedExts = array_merge($allowedExts, ['txt']);
                    break;

                case 'csv':
                    $allowedExts = array_merge($allowedExts, ['csv']);
                    break;

                case 'pdf':
                    $allowedExts = array_merge($allowedExts, ['pdf']);
                    break;

                case 'zip':
                    $allowedExts = array_merge($allowedExts, ['zip']);
                    break;

                case 'json':
                    $allowedExts = array_merge($allowedExts, ['json']);
                    break;

                case 'three_d_model':
                    $allowedExts = array_merge($allowedExts, ['stl']);
                    break;

                case 'medical_image':
                    $allowedExts = array_merge($allowedExts, ['dcm']);
                    break;
            }
        }

        $allowedExts = array_unique($allowedExts);
        $allowedExts = (! empty($allowedExts) ? $allowedExts : $defaultExts);

        return $allowedExts;
    }

    public function GetAllowedFileTypeStr()
    {
        return implode(",", $this->GetAllowedFileType());
    }

    public static function GetModuleAllowedFileType()
    {
        $_self = self::GetModuleInstance();
        $extns = $_self->GetAllowedFileType();

        return $extns;
    }

    public static function GetModuleAllowedFileTypeStr()
    {
        $_self = self::GetModuleInstance();
        $extns = $_self->GetAllowedFileTypeStr();

        return $extns;
    }

    public static function CheckCaptcha($token)
    {
        if (Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A") {
            $secret = Apbd_wps_settings::GetModuleOption("recaptcha_v3_secret_key", "");
            return self::isValid($token, $secret);
        } else {
            return true;
        }
    }

    protected  static function isValid($token, $secret = "")
    {
        if (empty($secret) || empty($token)) {
            return false;
        }
        try {
            $response = wp_remote_get(add_query_arg(array(
                'secret'   => $secret,
                'response' => $token,
            ), 'https://www.google.com/recaptcha/api/siteverify'));

            if (is_wp_error($response) || empty($response['body']) || ! ($json = json_decode($response['body'])) || ! $json->success) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get OpenAI API configuration from central settings
     *
     * @return array|null ['api_key' => string, 'model' => string, 'max_tokens' => int] or null if not configured
     */
    public static function GetOpenAIConfig()
    {
        $status = self::GetModuleOption('openai_status', 'I');
        if ('A' !== $status) {
            return null;
        }

        $api_key = sanitize_text_field(self::GetModuleOption('openai_api_key', ''));
        if (empty($api_key)) {
            return null;
        }

        $model = sanitize_text_field(self::GetModuleOption('openai_model', 'gpt-4o-mini'));
        $max_tokens = min(max(1, absint(self::GetModuleOption('openai_max_tokens', 1500))), 8192);

        return [
            'api_key' => $api_key,
            'model' => $model,
            'max_tokens' => $max_tokens,
        ];
    }

    /**
     * Get Claude API configuration from central settings
     *
     * @return array|null ['api_key' => string, 'model' => string, 'max_tokens' => int] or null if not configured
     */
    public static function GetClaudeConfig()
    {
        $status = self::GetModuleOption('claude_status', 'I');
        if ('A' !== $status) {
            return null;
        }

        $api_key = sanitize_text_field(self::GetModuleOption('claude_api_key', ''));
        if (empty($api_key)) {
            return null;
        }

        $model = sanitize_text_field(self::GetModuleOption('claude_model', 'claude-3-haiku-20240307'));
        $max_tokens = min(max(1, absint(self::GetModuleOption('claude_max_tokens', 1500))), 8192);

        return [
            'api_key' => $api_key,
            'model' => $model,
            'max_tokens' => $max_tokens,
        ];
    }

    /**
     * Check if any AI tool is configured
     *
     * @return bool
     */
    public static function HasAnyAIConfigured()
    {
        return (null !== self::GetOpenAIConfig() || null !== self::GetClaudeConfig());
    }

    /**
     * Get available AI tools
     *
     * @return array ['ai_proxy' => bool, 'openai' => bool, 'claude' => bool]
     */
    public static function GetAvailableAITools()
    {
        return [
            'ai_proxy' => self::HasAIProxyConfigured(),
            'openai' => (null !== self::GetOpenAIConfig()),
            'claude' => (null !== self::GetClaudeConfig()),
        ];
    }

    /**
     * Get the AI Proxy license key for free version.
     * This key is auto-generated when user first enables AI Proxy.
     *
     * @return string License key or empty string if not yet registered
     */
    public static function GetLicenseKey()
    {
        return get_option('apbd_wps_ai_proxy_license_key', '');
    }

    /**
     * Get AI Proxy Server configuration
     *
     * @return array|null ['server_url', 'product_slug', 'license_key', 'domain'] or null if not configured
     */
    public static function GetAIProxyConfig()
    {
        $status = self::GetModuleOption('ai_proxy_status', 'I');
        if ('A' !== $status) {
            return null;
        }

        return [
            'server_url' => self::$ai_proxy_server_url,
            'product_slug' => self::$ai_proxy_product_slug,
            'license_key' => self::GetLicenseKey(), // May be empty - will trigger free registration
            'domain' => ApbdWps_CleanDomainName(site_url()),
        ];
    }

    /**
     * Check if AI Proxy is configured and active
     *
     * @return bool
     */
    public static function HasAIProxyConfigured()
    {
        return (null !== self::GetAIProxyConfig());
    }

    public function user_login()
    {
        $payload = ApbdWps_ReadPHPInputStream();
        if (! empty($payload)) {
            $payload = json_decode($payload, true);
        }
        $credentials = [];
        $credentials['user_login'] = $payload['username'];
        $credentials['user_password'] = $payload['password'];
        if (is_user_logged_in()) {
            wp_logout();
        }
        $response = new Apbd_Wps_APIResponse();
        $user = wp_signon($credentials);
        if (is_wp_error($user)) {
            $response->SetResponse(false, wp_strip_all_tags($user->get_error_message()), $credentials);
            return $response;
        } else {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            $responseData = new stdClass();
            $responseData->id = $user->ID;
            $responseData->wp_rest_nonce = wp_create_nonce("wp_rest");
            $responseData->username = $user->user_login;
            $responseData->email = $user->user_email;
            $responseData->name = $user->first_name . ' ' . $user->last_name;
            $responseData->loggedIn = is_user_logged_in();
            $responseData->isAgent = Apbd_wps_settings::isAgentLoggedIn();
            if (empty(trim($responseData->name))) {
                $responseData->name = $user->display_name;
            }
            $responseData->caps = $user->caps;
            $responseData->img  = get_user_meta($user->ID, 'supportgenix_avatar', true) ? get_user_meta($user->ID, 'supportgenix_avatar', true) : get_avatar_url($user->ID);
            $response->SetResponse(true, "logged in Successfully", $responseData);
            wp_send_json($response);
        }
    }

    public function OnVersionUpdate($current_version = "", $previous_version = "", $last_pro_version = "")
    {
        parent::OnVersionUpdate($current_version, $previous_version, $last_pro_version);

        if (empty($previous_version)) {
            if (! empty($last_pro_version)) {
                // When pro version is less than 1.3.4
                if (1 === version_compare('1.3.4', $last_pro_version)) {
                    // From version 1.0.9
                    Mapbd_wps_custom_field::UpdateDBTable();
                }

                // When pro version is less than 1.4.0
                if (1 === version_compare('1.4.0', $last_pro_version)) {
                    // From version 1.0.9
                    Mapbd_wps_ticket_assign_rule::UpdateDBTable();
                }

                // When pro version is less than 1.4.2
                if (1 === version_compare('1.4.2', $last_pro_version)) {
                    // From version 1.1.0
                    Mapbd_wps_role::UpdateExStatus();;
                    Mapbd_wps_ticket::UpdateDBTable();
                    Mapbd_wps_email_templates::UpdateTemplateGroup();
                }

                // When pro version is less than 1.4.4
                if (1 === version_compare('1.4.4', $last_pro_version)) {
                    Mapbd_wps_woocommerce::CreateDBTable();
                    Mapbd_wps_woocommerce::TransferDBData();
                    Mapbd_wps_support_meta::TransferDBData();

                    // From version 1.1.2
                    Mapbd_wps_role::UpdateDBTableCharset();
                    Mapbd_wps_role_access::UpdateDBTableCharset();
                    Mapbd_wps_ticket_assign_rule::UpdateDBTableCharset();
                    Mapbd_wps_ticket::UpdateDBTableCharset();
                    Mapbd_wps_ticket_category::UpdateDBTableCharset();
                    Mapbd_wps_ticket_log::UpdateDBTableCharset();
                    Mapbd_wps_ticket_reply::UpdateDBTableCharset();
                    Mapbd_wps_notification::UpdateDBTableCharset();
                    Mapbd_wps_custom_field::UpdateDBTableCharset();
                    Mapbd_wps_email_templates::UpdateDBTableCharset();
                    Mapbd_wps_support_meta::UpdateDBTableCharset();
                    Mapbd_wps_debug_log::UpdateDBTableCharset();
                    Mapbd_wps_canned_msg::UpdateDBTableCharset();
                    Mapbd_wps_notes::UpdateDBTableCharset();
                }

                // When pro version is less than 1.4.5
                if (1 === version_compare('1.4.5', $last_pro_version)) {
                    // From version 1.1.3
                    Mapbd_wps_ticket_reply::UpdateDBTable();
                }

                // When pro version is less than 1.5.4
                if (1 === version_compare('1.5.4', $last_pro_version)) {
                    // From version 1.2.0
                    Mapbd_wps_email_templates::UpdateTemplateGroup2();
                }

                // When pro version is less than 1.5.8
                if (1 === version_compare('1.5.8', $last_pro_version)) {
                    // From version 1.2.3
                    $this->UpdateAllowedFileType();
                }

                // When pro version is less than 1.6.6
                if (1 === version_compare('1.6.6', $last_pro_version)) {
                    // From version 1.3.1
                    Mapbd_wps_email_templates::UpdateTemplateGroup3();
                    Mapbd_wps_role::UpdateExAccess();
                    Mapbd_wps_role::AddNewAccess();
                }

                // When pro version is less than 1.7.0
                if (1 === version_compare('1.7.0', $last_pro_version)) {
                    // From version 1.3.5
                    Mapbd_wps_ticket::UpdateDBTable2();
                }

                // When pro version is less than 1.7.1
                if (1 === version_compare('1.7.1', $last_pro_version)) {
                    // From version 1.3.6
                    Mapbd_wps_role::AddNewAccess2();
                }

                // When pro version is less than 1.7.3
                if (1 === version_compare('1.7.3', $last_pro_version)) {
                    // From version 1.3.8
                    Mapbd_wps_ticket_log::UpdateDBTable();
                }

                // When pro version is less than 1.8.0
                if (1 === version_compare('1.8.0', $last_pro_version)) {
                    // From version 1.4.0
                    Mapbd_wps_email_templates::UpdateTemplateGroup4();
                }

                // When pro version is less than 1.8.11
                if (1 === version_compare('1.8.11', $last_pro_version)) {
                    // From version 1.4.11
                    Apbd_wps_settings::DisableSetupWizard();
                    Mapbd_wps_role::AddNewAccess3();
                    Mapbd_wps_webhook::UpdateDBTable();
                }

                // When pro version is less than 1.8.13
                if (1 === version_compare('1.8.13', $last_pro_version)) {
                    // From version 1.4.13
                    Mapbd_wps_role::UpdateDBTable();
                    Mapbd_wps_role::AddNewAccess4();
                    Mapbd_wps_ticket::UpdateDBTable3();
                }

                // When pro version is less than 1.8.15
                if (1 === version_compare('1.8.15', $last_pro_version)) {
                    // From version 1.4.30
                    Mapbd_wps_chatbot_history::CreateDBTable();
                }

                // When pro version is less than 1.8.17
                if (1 === version_compare('1.8.17', $last_pro_version)) {
                    // From version 1.4.30
                    Mapbd_wps_docs_analytics::CreateDBTable();
                    Mapbd_wps_docs_search_keywords::CreateDBTable();
                    Mapbd_wps_docs_searches_events::CreateDBTable();
                }

                // When pro version is less than 1.8.19
                if (1 === version_compare('1.8.19', $last_pro_version)) {
                    // From version 1.4.19
                    Mapbd_wps_ticket::UpdateDBTable4();
                }

                // When pro version is less than 1.8.20
                if (1 === version_compare('1.8.20', $last_pro_version)) {
                    // From version 1.4.20
                    Mapbd_wps_ticket_category::UpdateDBTable();
                    Mapbd_wps_ticket_category::ResetOrder();
                }

                // When pro version is less than 1.8.23
                if (1 === version_compare('1.8.23', $last_pro_version)) {
                    // From version 1.4.23
                    Mapbd_wps_ticket::UpdateDBTable5();
                    Mapbd_wps_ticket_reply::UpdateDBTable2();
                    Mapbd_wps_notes::UpdateDBTable();
                    Mapbd_wps_imap_api_settings::UpdateDBTable();
                    Mapbd_wps_imap_settings::UpdateDBTable();
                    Mapbd_wps_ticket_assign_rule::UpdateDBTable2();
                }

                // When pro version is less than 1.8.24
                if (1 === version_compare('1.8.24', $last_pro_version)) {
                    // From version 1.4.24
                    $this->PermitRegistration();
                    Mapbd_wps_ticket::UpdateDBTable6();
                    Mapbd_wps_custom_field::UpdateDBTable2();
                }

                // When pro version is less than 1.8.26
                if (1 === version_compare('1.8.26', $last_pro_version)) {
                    // From version 1.4.26
                    $this->PermitRegistration2();

                    // From version 1.4.30
                    $chatbotHistory = new Mapbd_wps_chatbot_history();
                    $chatbotHistory->DropDBTable();

                    Mapbd_wps_chatbot_history::CreateDBTable();
                    Mapbd_wps_chatbot_keywords::CreateDBTable();
                    Mapbd_wps_chatbot_events::CreateDBTable();
                    Apbd_wps_knowledge_base::TransferSettings();
                }

                // When pro version is less than 1.8.30
                if (1 === version_compare('1.8.30', $last_pro_version)) {
                    // From version 1.4.30
                    $this->TransferApiKeys();
                }

                // When pro version is less than 1.8.31
                if (1 === version_compare('1.8.31', $last_pro_version)) {
                    // From version 1.4.31
                    $this->UpdateBaseFolder();
                }

                // When pro version is less than 1.8.34
                if (1 === version_compare('1.8.34', $last_pro_version)) {
                    // From version 1.4.34
                    $this->FixChangedKBMeta2();
                }

                // When pro version is less than 1.8.36
                if (1 === version_compare('1.8.36', $last_pro_version)) {
                    // From version 1.4.36
                    Mapbd_wps_chatbot_session::CreateDBTable();
                    Mapbd_wps_chatbot_history::UpdateDBTable();
                }

                // When pro version is less than 1.8.37
                if (1 === version_compare('1.8.37', $last_pro_version)) {
                    // From version 1.4.37
                    Mapbd_wps_chatbot_embed_token::CreateDBTable();
                    Mapbd_wps_chatbot_session::UpdateDBTable();
                }

                // When pro version is less than 1.8.38
                if (1 === version_compare('1.8.38', $last_pro_version)) {
                    // From version 1.4.38
                    Mapbd_wps_role::AddNewAccess5();
                }

                // When pro version is less than 1.8.39
                if (1 === version_compare('1.8.39', $last_pro_version)) {
                    // From version 1.4.39
                    Mapbd_wps_chatbot_session::UpdateDBTable2();
                }

                // When pro version is less than 1.8.41
                if (1 === version_compare('1.8.41', $last_pro_version)) {
                    // From version 1.4.41
                    Mapbd_wps_chatbot_session::UpdateDBTable3();
                    Mapbd_wps_ticket::UpdateDBTable7();
                    Apbd_wps_woocommerce::UpdateDefaultOpts();
                }

                // When pro version is less than 1.8.42
                if (1 === version_compare('1.8.42', $last_pro_version)) {
                    // From version 1.4.42
                    Mapbd_wps_docs_analytics::UpdateDBTable();
                    Mapbd_wps_chatbot_events::UpdateDBTable();
                    Mapbd_wps_docs_searches_events::UpdateDBTable();
                    Apbd_wps_woocommerce::MigrateDisplayOpts();
                    Apbd_wps_envato_system::MigrateDisplayOpts();
                }
            } else {
                // From version 1.1.0
                $this->CreateTicketPage();
            }

            // From version 1.4.0
            Apbd_wps_settings::CreateEncryptionKey();
        } else {
            // From version 1.0.9
            if (1 === version_compare('1.0.9', $previous_version)) {
                // When pro version is empty or less than 1.3.4
                if (empty($last_pro_version) || (1 === version_compare('1.3.4', $last_pro_version))) {
                    Mapbd_wps_custom_field::UpdateDBTable();
                }

                // When pro version is empty or less than 1.4.0
                if (empty($last_pro_version) || (1 === version_compare('1.4.0', $last_pro_version))) {
                    Mapbd_wps_ticket_assign_rule::UpdateDBTable();
                }
            }

            // From version 1.1.0
            if (1 === version_compare('1.1.0', $previous_version)) {
                // When pro version is empty or less than 1.4.2
                if (empty($last_pro_version) || (1 === version_compare('1.4.2', $last_pro_version))) {
                    Mapbd_wps_role::UpdateExStatus();
                    Mapbd_wps_ticket::UpdateDBTable();
                    Mapbd_wps_email_templates::UpdateTemplateGroup();
                }
            }

            // From version 1.1.2
            if (1 === version_compare('1.1.2', $previous_version)) {
                // When pro version is empty or less than 1.4.4
                if (empty($last_pro_version) || (1 === version_compare('1.4.4', $last_pro_version))) {
                    Mapbd_wps_role::UpdateDBTableCharset();
                    Mapbd_wps_role_access::UpdateDBTableCharset();
                    Mapbd_wps_ticket_assign_rule::UpdateDBTableCharset();
                    Mapbd_wps_ticket::UpdateDBTableCharset();
                    Mapbd_wps_ticket_category::UpdateDBTableCharset();
                    Mapbd_wps_ticket_log::UpdateDBTableCharset();
                    Mapbd_wps_ticket_reply::UpdateDBTableCharset();
                    Mapbd_wps_notification::UpdateDBTableCharset();
                    Mapbd_wps_custom_field::UpdateDBTableCharset();
                    Mapbd_wps_email_templates::UpdateDBTableCharset();
                    Mapbd_wps_support_meta::UpdateDBTableCharset();
                    Mapbd_wps_debug_log::UpdateDBTableCharset();
                    Mapbd_wps_canned_msg::UpdateDBTableCharset();
                    Mapbd_wps_notes::UpdateDBTableCharset();
                }
            }

            // From version 1.1.3
            if (1 === version_compare('1.1.3', $previous_version)) {
                Mapbd_wps_ticket_reply::UpdateDBTable();
            }

            // From version 1.2.0
            if (1 === version_compare('1.2.0', $previous_version)) {
                // When pro version is empty or less than 1.5.4
                if (empty($last_pro_version) || (1 === version_compare('1.5.4', $last_pro_version))) {
                    Mapbd_wps_email_templates::UpdateTemplateGroup2();
                }
            }

            // From version 1.2.2
            if (1 === version_compare('1.2.2', $previous_version)) {
                $this->ConvertToMultiLangOptions();
            }

            // From version 1.2.3
            if (1 === version_compare('1.2.3', $previous_version)) {
                $this->UpdateAllowedFileType();
            }

            // From version 1.3.1
            if (1 === version_compare('1.3.1', $previous_version)) {
                // When pro version is empty or less than 1.6.6
                if (empty($last_pro_version) || (1 === version_compare('1.6.6', $last_pro_version))) {
                    Apbd_wps_settings::CreateEncryptionKey();
                    Mapbd_wps_email_templates::UpdateTemplateGroup3();
                    Mapbd_wps_role::UpdateExAccess();
                    Mapbd_wps_role::AddNewAccess();
                }
            }

            // From version 1.3.5
            if (1 === version_compare('1.3.5', $previous_version)) {
                // When pro version is empty or less than 1.7.0
                if (empty($last_pro_version) || (1 === version_compare('1.7.0', $last_pro_version))) {
                    Mapbd_wps_ticket::UpdateDBTable2();
                }
            }

            // From version 1.3.6
            if (1 === version_compare('1.3.6', $previous_version)) {
                // When pro version is empty or less than 1.7.1
                if (empty($last_pro_version) || (1 === version_compare('1.7.1', $last_pro_version))) {
                    Mapbd_wps_role::AddNewAccess2();
                }
            }

            // From version 1.3.8
            if (1 === version_compare('1.3.8', $previous_version)) {
                // When pro version is empty or less than 1.7.3
                if (empty($last_pro_version) || (1 === version_compare('1.7.3', $last_pro_version))) {
                    Mapbd_wps_ticket_log::UpdateDBTable();
                }
            }

            // From version 1.4.0
            if (1 === version_compare('1.4.0', $previous_version)) {
                // When pro version is empty or less than 1.8.0
                if (empty($last_pro_version) || (1 === version_compare('1.8.0', $last_pro_version))) {
                    Mapbd_wps_email_templates::UpdateTemplateGroup4();
                }
            }

            // From version 1.4.11
            if (1 === version_compare('1.4.11', $previous_version)) {
                Mapbd_wps_ticket_tag::CreateDBTable();

                // When pro version is empty or less than 1.8.11
                if (empty($last_pro_version) || (1 === version_compare('1.8.11', $last_pro_version))) {
                    Apbd_wps_settings::DisableSetupWizard();
                    Mapbd_wps_role::AddNewAccess3();
                    Mapbd_wps_webhook::UpdateDBTable();
                }
            }

            // From version 1.4.13
            if (1 === version_compare('1.4.13', $previous_version)) {
                Mapbd_wps_ticket_tag::CreateDBTable();

                // When pro version is empty or less than 1.8.13
                if (empty($last_pro_version) || (1 === version_compare('1.8.13', $last_pro_version))) {
                    Mapbd_wps_role::UpdateDBTable();
                    Mapbd_wps_role::AddNewAccess4();
                    Mapbd_wps_ticket::UpdateDBTable3();
                }
            }

            // From version 1.4.19
            if (1 === version_compare('1.4.19', $previous_version)) {
                // When pro version is empty or less than 1.8.19
                if (empty($last_pro_version) || (1 === version_compare('1.8.19', $last_pro_version))) {
                    Mapbd_wps_ticket::UpdateDBTable4();
                }
            }

            // From version 1.4.20
            if (1 === version_compare('1.4.20', $previous_version)) {
                // When pro version is empty or less than 1.8.20
                if (empty($last_pro_version) || (1 === version_compare('1.8.20', $last_pro_version))) {
                    Mapbd_wps_ticket_category::UpdateDBTable();
                    Mapbd_wps_ticket_category::ResetOrder();
                }
            }

            // From version 1.4.23
            if (1 === version_compare('1.4.23', $previous_version)) {
                // When pro version is empty or less than 1.8.23
                if (empty($last_pro_version) || (1 === version_compare('1.8.23', $last_pro_version))) {
                    Mapbd_wps_ticket::UpdateDBTable5();
                    Mapbd_wps_ticket_reply::UpdateDBTable2();
                    Mapbd_wps_notes::UpdateDBTable();
                    Mapbd_wps_imap_api_settings::UpdateDBTable();
                    Mapbd_wps_imap_settings::UpdateDBTable();
                    Mapbd_wps_ticket_assign_rule::UpdateDBTable2();
                }
            }

            // From version 1.4.24
            if (1 === version_compare('1.4.24', $previous_version)) {
                // When pro version is empty or less than 1.8.24
                if (empty($last_pro_version) || (1 === version_compare('1.8.24', $last_pro_version))) {
                    $this->PermitRegistration();
                    Mapbd_wps_ticket::UpdateDBTable6();
                    Mapbd_wps_custom_field::UpdateDBTable2();
                }
            }

            // From version 1.4.26
            if (1 === version_compare('1.4.26', $previous_version)) {
                // When pro version is empty or less than 1.8.26
                if (empty($last_pro_version) || (1 === version_compare('1.8.26', $last_pro_version))) {
                    $this->PermitRegistration2();
                }
            }

            // From version 1.4.30
            if (1 === version_compare('1.4.30', $previous_version)) {
                // When pro version is empty or less than 1.8.15
                if (empty($last_pro_version) || (1 === version_compare('1.8.15', $last_pro_version))) {
                    Mapbd_wps_chatbot_history::CreateDBTable();
                }

                // When pro version is empty or less than 1.8.17
                if (empty($last_pro_version) || (1 === version_compare('1.8.17', $last_pro_version))) {
                    Mapbd_wps_docs_analytics::CreateDBTable();
                    Mapbd_wps_docs_search_keywords::CreateDBTable();
                    Mapbd_wps_docs_searches_events::CreateDBTable();
                }

                // When pro version is empty or less than 1.8.26
                if (empty($last_pro_version) || (1 === version_compare('1.8.26', $last_pro_version))) {
                    $chatbotHistory = new Mapbd_wps_chatbot_history();
                    $chatbotHistory->DropDBTable();

                    Mapbd_wps_chatbot_history::CreateDBTable();
                    Mapbd_wps_chatbot_keywords::CreateDBTable();
                    Mapbd_wps_chatbot_events::CreateDBTable();
                    Apbd_wps_knowledge_base::TransferSettings();
                }

                // When pro version is empty or less than 1.8.30
                if (empty($last_pro_version) || (1 === version_compare('1.8.30', $last_pro_version))) {
                    $this->TransferApiKeys();
                }
            }

            // From version 1.4.31
            if (1 === version_compare('1.4.31', $previous_version)) {
                // When pro version is empty or less than 1.8.31
                if (empty($last_pro_version) || (1 === version_compare('1.8.31', $last_pro_version))) {
                    $this->UpdateBaseFolder();
                }
            }

            // From version 1.4.34
            if (1 === version_compare('1.4.34', $previous_version)) {
                // When pro version is empty or less than 1.8.34
                if (empty($last_pro_version) || (1 === version_compare('1.8.34', $last_pro_version))) {
                    $this->FixChangedKBMeta2();
                }
            }

            // From version 1.4.36
            if (1 === version_compare('1.4.36', $previous_version)) {
                // When pro version is empty or less than 1.8.36
                if (empty($last_pro_version) || (1 === version_compare('1.8.36', $last_pro_version))) {
                    Mapbd_wps_chatbot_session::CreateDBTable();
                    Mapbd_wps_chatbot_history::UpdateDBTable();
                }
            }

            // From version 1.4.37
            if (1 === version_compare('1.4.37', $previous_version)) {
                // When pro version is empty or less than 1.8.37
                if (empty($last_pro_version) || (1 === version_compare('1.8.37', $last_pro_version))) {
                    Mapbd_wps_chatbot_embed_token::CreateDBTable();
                    Mapbd_wps_chatbot_session::UpdateDBTable();
                }
            }

            // From version 1.4.38
            if (1 === version_compare('1.4.38', $previous_version)) {
                // When pro version is empty or less than 1.8.38
                if (empty($last_pro_version) || (1 === version_compare('1.8.38', $last_pro_version))) {
                    Mapbd_wps_role::AddNewAccess5();
                }
            }

            // From version 1.4.39
            if (1 === version_compare('1.4.39', $previous_version)) {
                // When pro version is empty or less than 1.8.39
                if (empty($last_pro_version) || (1 === version_compare('1.8.39', $last_pro_version))) {
                    Mapbd_wps_chatbot_session::UpdateDBTable2();
                }
            }

            // From version 1.4.41
            if (1 === version_compare('1.4.41', $previous_version)) {
                // WooCommerce table (new in lite from 1.4.41; was pro-only before).
                Mapbd_wps_woocommerce::CreateDBTable();

                // When pro version is empty or less than 1.8.41
                if (empty($last_pro_version) || (1 === version_compare('1.8.41', $last_pro_version))) {
                    Mapbd_wps_chatbot_session::UpdateDBTable3();
                    Mapbd_wps_ticket::UpdateDBTable7();
                    Apbd_wps_woocommerce::UpdateDefaultOpts();
                }
            }

            // From version 1.4.42
            if (1 === version_compare('1.4.42', $previous_version)) {
                // When pro version is empty or less than 1.8.42
                if (empty($last_pro_version) || (1 === version_compare('1.8.42', $last_pro_version))) {
                    Mapbd_wps_docs_analytics::UpdateDBTable();
                    Mapbd_wps_chatbot_events::UpdateDBTable();
                    Mapbd_wps_docs_searches_events::UpdateDBTable();
                    Apbd_wps_woocommerce::MigrateDisplayOpts();
                    Apbd_wps_envato_system::MigrateDisplayOpts();
                }
            }
        }

        // From version 1.4.4
        Apbd_wps_settings::ConvertOldSettings();
    }

    public function OnActive($new_activation = true, $new_pro_activation = true)
    {
        parent::OnActive($new_activation, $new_pro_activation);

        // Set re-write rule
        set_transient('supportgenix_rwrite_rule', "Yes");

        // Create tables
        Mapbd_wps_ticket::CreateDBTable();
        Mapbd_wps_ticket_category::CreateDBTable();
        Mapbd_wps_ticket_tag::CreateDBTable();
        Mapbd_wps_ticket_log::CreateDBTable();
        Mapbd_wps_ticket_reply::CreateDBTable();
        Mapbd_wps_notification::CreateDBTable();
        Mapbd_wps_custom_field::CreateDBTable();
        Mapbd_wps_woocommerce::CreateDBTable();
        Mapbd_wps_edd::CreateDBTable();
        Mapbd_wps_fluentcrm::CreateDBTable();
        Mapbd_wps_support_meta::CreateDBTable();
        Mapbd_wps_debug_log::CreateDBTable();
        Mapbd_wps_webhook::CreateDBTable();
        Mapbd_wps_incoming_webhook::CreateDBTable();
        Mapbd_wps_canned_msg::CreateDBTable();
        Mapbd_wps_imap_settings::CreateDBTable();
        Mapbd_wps_imap_api_settings::CreateDBTable();
        Mapbd_wps_notes::CreateDBTable();
        Mapbd_wps_role::CreateDBTable();
        Mapbd_wps_role_access::CreateDBTable();
        Mapbd_wps_ticket_assign_rule::CreateDBTable();
        Mapbd_wps_email_templates::CreateDBTable();
        Mapbd_wps_chatbot_history::CreateDBTable();
        Mapbd_wps_chatbot_session::CreateDBTable();
        Mapbd_wps_chatbot_keywords::CreateDBTable();
        Mapbd_wps_chatbot_events::CreateDBTable();
        Mapbd_wps_chatbot_embed_token::CreateDBTable();
        Mapbd_wps_docs_analytics::CreateDBTable();
        Mapbd_wps_docs_search_keywords::CreateDBTable();
        Mapbd_wps_docs_searches_events::CreateDBTable();

        // Add default data
        Mapbd_wps_role::SetDefaultRole();
        Mapbd_wps_ticket_assign_rule::SetDefaultAssignRole();
        Mapbd_wps_email_templates::AddDefaultTemplates();
    }

    function CreateTicketPage()
    {
        $pageId = absint(get_option('apbd_wps_ticket_page_id'));
        $currentPageId = absint($this->GetOption("ticket_page", "0"));

        if (('page' !== get_post_type($pageId)) && ('page' !== get_post_type($currentPageId))) {
            $pageArgs = array(
                'post_title'   => $this->__('Ticket'),
                'post_content' => '<!-- wp:shortcode -->[supportgenix]<!-- /wp:shortcode -->',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );

            $createdPageId = wp_insert_post($pageArgs);

            if ($createdPageId) {
                update_option('apbd_wps_ticket_page_id', $createdPageId);
                $this->AddOption("ticket_page", $createdPageId);
            }
        }
    }

    function ConvertToMultiLangOptions()
    {
        $status = get_option($this->pluginBaseName . "_o_tkt_status", null);
        $status = (is_array($status) ? $status : []);

        if (! empty($status)) {
            $options = $this->options;
            $options = (is_array($options) ? $options : []);
            $options = array_merge($options, $status);

            $this->options = $options;
        }

        $this->multiLangCode = 'en';

        if ($this->UpdateOption()) {
            delete_option($this->pluginBaseName . "_o_tkt_status");
        }
    }

    function GenerateSecretKey()
    {
        $random_key = md5(wp_rand(10, 99) . wp_rand(10, 99) . time() . wp_rand(10, 99));
        $secret_key = substr($random_key, 20, 8) . '-' . substr($random_key, 28, 4);

        return $secret_key;
    }

    function UpdateAllowedFileType()
    {
        $allowedType = $this->GetOption('allowed_type', 'jpg,png,txt,pdf,docs');
        $allowedType = explode(',', strtolower(sanitize_text_field($allowedType)));
        $allowedType = array_map('trim', $allowedType);
        $updatedType = [];

        if (! empty($allowedType)) {
            $allowedType = array_unique($allowedType);

            foreach ($allowedType as $type) {
                switch ($type) {
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'webp':
                    case 'gif':
                        $updatedType[] = 'image';
                        break;

                    case 'mp4':
                    case 'webm':
                    case 'mov':
                    case 'avi':
                    case 'ogv':
                        $updatedType[] = 'video';
                        break;

                    case 'mp3':
                    case 'wav':
                    case 'aac':
                    case 'ogg':
                    case 'flac':
                    case 'm4a':
                    case 'wma':
                        $updatedType[] = 'audio';
                        break;

                    case 'doc':
                    case 'docx':
                    case 'xls':
                    case 'xlsx':
                        $updatedType[] = 'docs';
                        break;

                    case 'txt':
                        $updatedType[] = 'text';
                        break;

                    case 'csv':
                        $updatedType[] = 'csv';
                        break;

                    case 'pdf':
                        $updatedType[] = 'pdf';
                        break;

                    case 'zip':
                        $updatedType[] = 'zip';
                        break;

                    case 'json':
                        $updatedType[] = 'json';
                        break;

                    case 'stl':
                        $updatedType[] = 'three_d_model';
                        break;

                    case 'dcm':
                        $updatedType[] = 'medical_image';
                        break;
                }
            }
        }

        $this->options['allowed_type'] = $updatedType;
        $this->UpdateOption();
    }

    function userCustomFields($customFieldWithValue, $ticket_id)
    {
        $ticketMetas = Mapbd_wps_support_meta::getUserMeta($ticket_id);
        $ticketMetas = apply_filters('apbd-wps/filter/custom-field-metadata', $ticketMetas);

        $custom_fileds = Mapbd_wps_custom_field::getCustomFieldForAPI();
        $custom_fileds = apply_filters('apbd-wps/filter/before-custom-get', $custom_fileds);
        $custom_fileds = apply_filters('apbd-wps/filter/display-properties', $custom_fileds);

        $customFieldWithValue = [];
        if (! empty($custom_fileds->reg_form)) {
            foreach ($custom_fileds->reg_form as $custom_filed) {
                $custom_filed->field_value = ! empty($ticketMetas[$custom_filed->input_name]) ? $ticketMetas[$custom_filed->input_name] : "";
                $custom_filed->is_editable = true;
                $customFieldWithValue[] = $custom_filed;
            }
        }
        $customFieldWithValue = apply_filters('apbd-wps/filter/custom-additional-fields', $customFieldWithValue);
        return $customFieldWithValue;
    }

    function ticketCustomFields($customFieldWithValue, $ticket_id)
    {
        $ticketMetas = Mapbd_wps_support_meta::getTicketMeta($ticket_id);
        $ticketMetas = apply_filters('apbd-wps/filter/custom-field-metadata', $ticketMetas);

        $custom_fileds = Mapbd_wps_custom_field::getCustomFieldForTicketDetailsAPI($ticket_id);
        $custom_fileds = apply_filters('apbd-wps/filter/before-custom-get', $custom_fileds);
        $custom_fileds = apply_filters('apbd-wps/filter/display-properties', $custom_fileds);

        if (! empty($custom_fileds->ticket_form)) {
            foreach ($custom_fileds->ticket_form as $custom_filed) {
                $custom_filed->field_value = ! empty($ticketMetas[$custom_filed->input_name]) ? $ticketMetas[$custom_filed->input_name] : "";
                if ($custom_filed->field_type == 'S') {
                    $custom_filed->field_value = ! empty($custom_filed->field_value);
                }
                $custom_filed->is_editable = true;
                $customFieldWithValue[] = $custom_filed;
            }
        }
        $customFieldWithValue = apply_filters('apbd-wps/filter/custom-additional-fields', $customFieldWithValue);
        return $customFieldWithValue;
    }

    /**
     * @param Mapbd_wps_ticket $ticket
     * @param $custom_fields
     */
    function save_ticket_meta($ticket, $custom_fields)
    {
        if (! empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $key => $custom_field) {
                if (substr($key, 0, 1) == "D") {
                    $n = new Mapbd_wps_support_meta();
                    $n->item_id($ticket->id);
                    $n->item_type('T');
                    $n->meta_key(preg_replace("#[^0-9]#", '', $key));
                    $n->meta_type('D');
                    $n->meta_value($custom_field);
                    if (!$n->Save()) {
                        Mapbd_wps_debug_log::AddGeneralLog("Custom field save failed", print_r($n, true) . "\n" . ApbdWps_GetMsgAPI());
                    }
                }
            }
        }
    }

    /**
     * @param Apbd_Wps_User $ticket
     * @param $custom_fields
     */
    function save_user_meta($userObj, $custom_fields)
    {
        if (! empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $key => $custom_field) {
                if (substr($key, 0, 1) == "D") {
                    $c = new Mapbd_wps_support_meta();
                    $c->item_id($userObj->id);
                    $c->item_type('U');
                    $c->meta_key(preg_replace("#[^0-9]#", '', $key));
                    $c->meta_type('D');
                    if ($c->Select()) {
                        $u = new Mapbd_wps_support_meta();
                        $u->SetWhereUpdate("id", $c->id);
                        $u->meta_value($custom_field);
                        $u->Update();
                    } else {
                        $n = new Mapbd_wps_support_meta();
                        $n->item_id($userObj->id);
                        $n->item_type('U');
                        $n->meta_key(preg_replace("#[^0-9]#", '', $key));
                        $n->meta_type('D');
                        $n->meta_value($custom_field);
                        if (!$n->Save()) {
                            Mapbd_wps_debug_log::AddGeneralLog("Custom field save failed on user meta", $n);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Mapbd_wps_ticket $ticket
     * @param $custom_fields
     */
    function update_ticket_meta($ticket_id, $pro_name, $value)
    {
        if (strtoupper(substr($pro_name, 0, 1)) == "D") {
            $s = new Mapbd_wps_support_meta();
            $s->item_id($ticket_id);
            $s->meta_key(preg_replace("#[^0-9]#", '', $pro_name));
            $s->meta_type('D');
            if ($s->Select()) {
                $n = new Mapbd_wps_support_meta();
                $n->meta_value($value);
                $n->SetWhereUpdate("item_id", $ticket_id);
                $n->SetWhereUpdate("meta_key", preg_replace("#[^0-9]#", '', $pro_name));
                $n->SetWhereUpdate("meta_type", 'D');
                if (!$n->Update()) {
                    Mapbd_wps_debug_log::AddGeneralLog("Custom field update failed", ApbdWps_GetMsgAPI() . "\nTicket ID: $ticket_id, Custom Name: $pro_name, value:$value");
                }
            } else {
                $n = new Mapbd_wps_support_meta();
                $n->meta_value($value);
                $n->item_id($ticket_id);
                $n->item_type('T');
                $n->meta_key(preg_replace("#[^0-9]#", '', $pro_name));
                $n->meta_type('D');
                $n->meta_value($value);
                if (!$n->Save()) {
                    Mapbd_wps_debug_log::AddGeneralLog("Custom field update failed", ApbdWps_GetMsgAPI() . "\nTicket ID: $ticket_id, Custom Name: $pro_name, value:$value");
                }
            }
        }
    }

    /**
     * @param [] $attached_files
     * @param Mapbd_wps_ticket $ticket
     */
    function set_ticket_attached_files($attached_files, $ticket)
    {
        $ticketDir = self::$uploadBasePath;

        if (empty($ticket->id)) {
            return $attached_files;
        } else {
            $ticketDir = $ticketDir . $ticket->id . "/attached_files/";
        }
        $this->read_all_file($attached_files, $ticketDir, "T", $ticket->id);
        return $attached_files;
    }

    /**
     * @param $attached_files
     * @param Mapbd_wps_ticket_reply $ticket_reply
     * @return mixed
     */
    function set_ticket_reply_attached_files($attached_files, $ticket_reply)
    {
        $ticketDir = self::$uploadBasePath;

        if (empty($ticket_reply->reply_id)) {
            return $attached_files;
        } else {
            $ticketDir = $ticketDir . $ticket_reply->ticket_id . "/replied/" . $ticket_reply->reply_id . "/attached_files/";
        }
        $this->read_all_file($attached_files, $ticketDir, "R", $ticket_reply->ticket_id, $ticket_reply->reply_id);
        return $attached_files;
    }

    function download_file($type, $ticket_or_reply_id, $file) {}

    function read_all_file(&$attached_files, $path, $tType, $ticket_id, $ticket_reply_id = null)
    {
        $allowed_files = $this->GetAllowedFileType();
        $path = rtrim($path, '/');
        if ($tType == 'R') {
            $ticket_id .= '_' . $ticket_reply_id;
        }
        $namespace = ApbdWps_SupportLite::getNamespaceStr();
        if (is_dir($path)) {
            foreach (glob($path . '/*.*', GLOB_BRACE) as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_files)) {
                    $fileProperty = new stdClass();
                    $relative_path = str_replace(WP_CONTENT_DIR, '', $file);
                    $fileProperty->url = content_url($relative_path);
                    $fileProperty->type = ApbdWps_GetMimeType($file);
                    $fileProperty->ext = $ext;
                    $attached_files[] = $fileProperty;
                }
            }
        }
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $client_role = $this->GetOption('client_role', 'subscriber');
        $ticket_page = $this->GetOption('ticket_page', '');
        $ticket_page_shortcode = $this->GetOption('ticket_page_shortcode', 'N');
        $footer_cp_text = '';
        $is_wp_login_reg = $this->GetOption('is_wp_login_reg', 'N');
        $login_page = $this->GetOption('login_page', '');
        $reg_page = $this->GetOption('reg_page', '');
        $is_wp_profile_link = $this->GetOption('is_wp_profile_link', 'N');
        $wp_profile_link = $this->GetOption('wp_profile_link', '');
        $user_multi_role_field = $this->GetOption('user_multi_role_field', 'N');
        $is_seq_track_id = 'N';
        $track_id_prefix = $this->GetOption('track_id_prefix', '');
        $track_id_min_len = $this->GetOption('track_id_min_len', '');
        $tickets_auto_refresh = $this->GetOption('tickets_auto_refresh', 'N');
        $tickets_auto_refresh_interval = $this->GetOption('tickets_auto_refresh_interval', 60);
        $disable_closed_ticket_reply = 'N';
        $disable_closed_ticket_reply_notice = '';
        $is_hide_cp_text = $this->GetOption('is_hide_cp_text', 'N');
        $is_public_ticket_opt_on_creation = $this->GetOption('is_public_ticket_opt_on_creation', 'N');
        $is_public_ticket_opt_on_details = $this->GetOption('is_public_ticket_opt_on_details', 'N');
        $is_public_tickets_menu = $this->GetOption('is_public_tickets_menu', 'N');
        $override_wp_users_can_register = $this->GetOption('override_wp_users_can_register', 'Y');
        $disable_registration_form = $this->GetOption('disable_registration_form', 'N');
        $disable_guest_ticket_creation = $this->GetOption('disable_guest_ticket_creation', 'N');
        $disable_guest_email_to_ticket_creation = 'N';
        $disable_guest_chatbot_ticket_creation = 'N';
        $email_to_ticket_rich_html = 'N';
        $close_ticket_opt_for_customer = 'N';
        $disable_auto_ticket_assignment = $this->GetOption('disable_auto_ticket_assignment', 'N');
        $disable_need_reply_sorting = $this->GetOption('disable_need_reply_sorting', 'N');
        $disable_ticket_hotlink = $this->GetOption('disable_ticket_hotlink', 'N');
        $disable_undo_submit = $this->GetOption('disable_undo_submit', 'N');
        $disable_current_viewers = $this->GetOption('disable_current_viewers', 'N');

        $ticket_page_shortcode = ('Y' === $ticket_page_shortcode) ? true : false;
        $is_wp_login_reg = ('Y' === $is_wp_login_reg) ? true : false;
        $is_wp_profile_link = ('Y' === $is_wp_profile_link) ? true : false;
        $user_multi_role_field = ('Y' === $user_multi_role_field) ? true : false;
        $is_seq_track_id = ('Y' === $is_seq_track_id) ? true : false;
        $tickets_auto_refresh = ('Y' === $tickets_auto_refresh) ? true : false;
        $disable_closed_ticket_reply = ('Y' === $disable_closed_ticket_reply) ? true : false;
        $is_hide_cp_text = ('Y' === $is_hide_cp_text) ? true : false;
        $is_public_ticket_opt_on_creation = ('Y' === $is_public_ticket_opt_on_creation) ? true : false;
        $is_public_ticket_opt_on_details = ('Y' === $is_public_ticket_opt_on_details) ? true : false;
        $is_public_tickets_menu = ('Y' === $is_public_tickets_menu) ? true : false;
        $override_wp_users_can_register = ('Y' === $override_wp_users_can_register) ? true : false;
        $disable_registration_form = ('Y' === $disable_registration_form) ? true : false;
        $disable_guest_ticket_creation = ('Y' === $disable_guest_ticket_creation) ? true : false;
        $disable_guest_email_to_ticket_creation = ('Y' === $disable_guest_email_to_ticket_creation) ? true : false;
        $disable_guest_chatbot_ticket_creation = ('Y' === $disable_guest_chatbot_ticket_creation) ? true : false;
        $email_to_ticket_rich_html = ('Y' === $email_to_ticket_rich_html) ? true : false;
        $close_ticket_opt_for_customer = ('Y' === $close_ticket_opt_for_customer) ? true : false;
        $disable_auto_ticket_assignment = ('Y' === $disable_auto_ticket_assignment) ? true : false;
        $disable_need_reply_sorting = ('Y' === $disable_need_reply_sorting) ? true : false;
        $disable_ticket_hotlink = ('Y' === $disable_ticket_hotlink) ? true : false;
        $disable_undo_submit = ('Y' === $disable_undo_submit) ? true : false;
        $disable_current_viewers = ('Y' === $disable_current_viewers) ? true : false;

        $client_role = !empty($client_role) ? $client_role : 'subscriber';
        $ticket_page = strval($ticket_page);

        $tickets_auto_refresh_interval = max(absint($tickets_auto_refresh_interval), 5);

        $data = [
            'client_role' => $client_role,
            'ticket_page' => $ticket_page,
            'ticket_page_shortcode' => $ticket_page_shortcode,
            'footer_cp_text' => $footer_cp_text,
            'is_wp_login_reg' => $is_wp_login_reg,
            'login_page' => $login_page,
            'reg_page' => $reg_page,
            'is_wp_profile_link' => $is_wp_profile_link,
            'wp_profile_link' => $wp_profile_link,
            'user_multi_role_field' => $user_multi_role_field,
            'is_seq_track_id' => $is_seq_track_id,
            'track_id_prefix' => $track_id_prefix,
            'track_id_min_len' => $track_id_min_len,
            'tickets_auto_refresh' => $tickets_auto_refresh,
            'tickets_auto_refresh_interval' => $tickets_auto_refresh_interval,
            'disable_closed_ticket_reply' => $disable_closed_ticket_reply,
            'disable_closed_ticket_reply_notice' => $disable_closed_ticket_reply_notice,
            'is_hide_cp_text' => $is_hide_cp_text,
            'is_public_ticket_opt_on_creation' => $is_public_ticket_opt_on_creation,
            'is_public_ticket_opt_on_details' => $is_public_ticket_opt_on_details,
            'is_public_tickets_menu' => $is_public_tickets_menu,
            'override_wp_users_can_register' => $override_wp_users_can_register,
            'disable_registration_form' => $disable_registration_form,
            'disable_guest_ticket_creation' => $disable_guest_ticket_creation,
            'disable_guest_email_to_ticket_creation' => $disable_guest_email_to_ticket_creation,
            'disable_guest_chatbot_ticket_creation' => $disable_guest_chatbot_ticket_creation,
            'email_to_ticket_rich_html' => $email_to_ticket_rich_html,
            'close_ticket_opt_for_customer' => $close_ticket_opt_for_customer,
            'disable_auto_ticket_assignment' => $disable_auto_ticket_assignment,
            'disable_need_reply_sorting' => $disable_need_reply_sorting,
            'disable_ticket_hotlink' => $disable_ticket_hotlink,
            'disable_undo_submit' => $disable_undo_submit,
            'disable_current_viewers' => $disable_current_viewers,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataSetupWizard()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $setup_wizard_step = absint($this->GetOption('setup_wizard_step', 0));
        $setup_wizard_finished = rest_sanitize_boolean($this->GetOption('setup_wizard_finished', false));

        $data = [
            'setup_wizard_step' => $setup_wizard_step,
            'setup_wizard_finished' => $setup_wizard_finished,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataLogo()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $default = [
            'app_favicon' => $this->get_portal_url("dist/img/favicon180x180.png", false),
            'app_logo' => $this->get_portal_url("dist/img/logo.png", false),
        ];

        $app_favicon = $this->GetOption('app_favicon', $default['app_favicon']);
        $app_logo = $this->GetOption('app_logo', $default['app_logo']);

        $data = [
            'default' => $default,
            'app_favicon' => $app_favicon,
            'app_logo' => $app_logo,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataFile()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $ticket_file_upload = $this->GetOption('ticket_file_upload', 'A');
        $file_upload_size = $this->GetOption('file_upload_size', 2);
        $allowed_type = $this->GetOption('allowed_type', ['image', 'docs', 'text', 'pdf']);
        $file_image_popup = $this->GetOption('file_image_popup', 'Y');
        $file_preview_mode = $this->GetOption('file_preview_mode', 'N');

        $ticket_file_upload = ('A' === $ticket_file_upload) ? true : false;
        $file_image_popup = ('Y' === $file_image_popup) ? true : false;
        $file_preview_mode = ('Y' === $file_preview_mode) ? true : false;

        $data = [
            'ticket_file_upload' => $ticket_file_upload,
            'file_upload_size' => $file_upload_size,
            'allowed_type' => $allowed_type,
            'file_image_popup' => $file_image_popup,
            'file_preview_mode' => $file_preview_mode,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataCaptcha()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $recaptcha_v3_status = $this->GetOption('recaptcha_v3_status', 'I');
        $recaptcha_v3_site_key = $this->GetOption('recaptcha_v3_site_key', '');
        $recaptcha_v3_secret_key = $this->GetOption('recaptcha_v3_secret_key', '');
        $captcha_on_login_form = $this->GetOption('captcha_on_login_form', 'Y');
        $captcha_on_create_tckt = $this->GetOption('captcha_on_create_tckt', 'Y');
        $captcha_on_reg_form = $this->GetOption('captcha_on_reg_form', 'Y');
        $recaptcha_v3_hide_badge = $this->GetOption('recaptcha_v3_hide_badge', 'N');

        $recaptcha_v3_status = ('A' === $recaptcha_v3_status) ? true : false;
        $recaptcha_v3_hide_badge = ('Y' === $recaptcha_v3_hide_badge) ? true : false;

        // Secret key.
        $recaptcha_v3_secret_key = ApbdWps_SecretFieldValue($recaptcha_v3_secret_key);

        // Display options.
        $recaptcha_v3_display_opts = [];

        if ('Y' === $captcha_on_login_form) {
            $recaptcha_v3_display_opts[] = 'captcha_on_login_form';
        }

        if ('Y' === $captcha_on_create_tckt) {
            $recaptcha_v3_display_opts[] = 'captcha_on_create_tckt';
        }

        if ('Y' === $captcha_on_reg_form) {
            $recaptcha_v3_display_opts[] = 'captcha_on_reg_form';
        }

        $data = [
            'recaptcha_v3_status' => $recaptcha_v3_status,
            'recaptcha_v3_site_key' => $recaptcha_v3_site_key,
            'recaptcha_v3_secret_key' => $recaptcha_v3_secret_key,
            'recaptcha_v3_display_opts' => $recaptcha_v3_display_opts,
            'recaptcha_v3_hide_badge' => $recaptcha_v3_hide_badge,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataStatus()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, "", []);

        echo wp_json_encode($apiResponse);
    }

    public function dataStyle()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, "", []);

        echo wp_json_encode($apiResponse);
    }

    public function dataAutoClose()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, "", []);

        echo wp_json_encode($apiResponse);
    }

    public function dataApiKeysOpenAI()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $openai_status = $this->GetOption('openai_status', 'I');
        $openai_api_key = $this->GetOption('openai_api_key', '');
        $openai_model = $this->GetOption('openai_model', 'gpt-4o-mini');
        $openai_max_tokens = $this->GetOption('openai_max_tokens', 1500);

        // Status.
        $openai_status = ('A' === $openai_status) ? true : false;

        // API key.
        $openai_api_key = ApbdWps_SecretFieldValue($openai_api_key);

        $data = [
            'openai_status' => $openai_status,
            'openai_api_key' => $openai_api_key,
            'openai_model' => $openai_model,
            'openai_max_tokens' => $openai_max_tokens,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataApiKeysClaude()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $claude_status = $this->GetOption('claude_status', 'I');
        $claude_api_key = $this->GetOption('claude_api_key', '');
        $claude_model = $this->GetOption('claude_model', 'claude-3-haiku-20240307');
        $claude_max_tokens = $this->GetOption('claude_max_tokens', 1500);

        // Status.
        $claude_status = ('A' === $claude_status) ? true : false;

        // API key.
        $claude_api_key = ApbdWps_SecretFieldValue($claude_api_key);

        $data = [
            'claude_status' => $claude_status,
            'claude_api_key' => $claude_api_key,
            'claude_model' => $claude_model,
            'claude_max_tokens' => $claude_max_tokens,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function dataBasic()
    {
        $namespace = ApbdWps_SupportLite::getNamespaceStr();
        $apiObj = new ApbdWpsAPI_Config($namespace, false);

        $apiResponse = $apiObj->basic_settings();

        echo wp_json_encode($apiResponse);
    }

    public function page_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $pages = get_pages();

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Page') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Pages'),
            ];
        }

        foreach ($pages as $page) {
            $id = $page->ID;
            $title = $page->post_title;

            $id = absint($id);

            if ($id !== $except_id) {
                $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                $result[] = [
                    $valkey => strval($id),
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallback()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $client_role = sanitize_text_field(ApbdWps_PostValue('client_role', ''));
            $ticket_page = sanitize_text_field(ApbdWps_PostValue('ticket_page', ''));
            $ticket_page_shortcode = sanitize_text_field(ApbdWps_PostValue('ticket_page_shortcode', ''));
            $footer_cp_text = sanitize_text_field($this->GetOption('footer_cp_text', ''));
            $is_wp_login_reg = sanitize_text_field(ApbdWps_PostValue('is_wp_login_reg', ''));
            $login_page = esc_url_raw(ApbdWps_PostValue('login_page', ''));
            $reg_page = esc_url_raw(ApbdWps_PostValue('reg_page', ''));
            $is_wp_profile_link = sanitize_text_field(ApbdWps_PostValue('is_wp_profile_link', ''));
            $wp_profile_link = esc_url_raw(ApbdWps_PostValue('wp_profile_link', ''));
            $user_multi_role_field = sanitize_text_field(ApbdWps_PostValue('user_multi_role_field', ''));
            $is_seq_track_id = sanitize_text_field($this->GetOption('is_seq_track_id', 'N'));
            $track_id_prefix = sanitize_text_field(ApbdWps_PostValue('track_id_prefix', ''));
            $track_id_min_len = sanitize_text_field(ApbdWps_PostValue('track_id_min_len', ''));
            $tickets_auto_refresh = sanitize_text_field(ApbdWps_PostValue('tickets_auto_refresh', ''));
            $tickets_auto_refresh_interval = absint(ApbdWps_PostValue('tickets_auto_refresh_interval', 60));
            $disable_closed_ticket_reply = sanitize_text_field($this->GetOption('disable_closed_ticket_reply', 'N'));
            $disable_closed_ticket_reply_notice = sanitize_text_field($this->GetOption('disable_closed_ticket_reply_notice', ''));
            $is_hide_cp_text = sanitize_text_field(ApbdWps_PostValue('is_hide_cp_text', ''));
            $is_public_ticket_opt_on_creation = sanitize_text_field(ApbdWps_PostValue('is_public_ticket_opt_on_creation', ''));
            $is_public_ticket_opt_on_details = sanitize_text_field(ApbdWps_PostValue('is_public_ticket_opt_on_details', ''));
            $is_public_tickets_menu = sanitize_text_field(ApbdWps_PostValue('is_public_tickets_menu', ''));
            $override_wp_users_can_register = sanitize_text_field(ApbdWps_PostValue('override_wp_users_can_register', ''));
            $disable_registration_form = sanitize_text_field(ApbdWps_PostValue('disable_registration_form', ''));
            $disable_guest_ticket_creation = sanitize_text_field(ApbdWps_PostValue('disable_guest_ticket_creation', ''));
            $disable_guest_email_to_ticket_creation = sanitize_text_field($this->GetOption('disable_guest_email_to_ticket_creation', 'N'));
            $disable_guest_chatbot_ticket_creation = sanitize_text_field($this->GetOption('disable_guest_chatbot_ticket_creation', 'N'));
            $email_to_ticket_rich_html = sanitize_text_field($this->GetOption('email_to_ticket_rich_html', 'N'));
            $close_ticket_opt_for_customer = sanitize_text_field($this->GetOption('close_ticket_opt_for_customer', 'N'));
            $disable_auto_ticket_assignment = sanitize_text_field(ApbdWps_PostValue('disable_auto_ticket_assignment', ''));
            $disable_need_reply_sorting = sanitize_text_field(ApbdWps_PostValue('disable_need_reply_sorting', ''));
            $disable_ticket_hotlink = sanitize_text_field(ApbdWps_PostValue('disable_ticket_hotlink', ''));
            $disable_undo_submit = sanitize_text_field(ApbdWps_PostValue('disable_undo_submit', ''));
            $disable_current_viewers = sanitize_text_field(ApbdWps_PostValue('disable_current_viewers', ''));

            $ticket_page_shortcode = 'Y' === $ticket_page_shortcode ? 'Y' : 'N';
            $is_wp_login_reg = 'Y' === $is_wp_login_reg ? 'Y' : 'N';
            $is_wp_profile_link = 'Y' === $is_wp_profile_link ? 'Y' : 'N';
            $is_seq_track_id = 'Y' === $is_seq_track_id ? 'Y' : 'N';
            $tickets_auto_refresh = 'Y' === $tickets_auto_refresh ? 'Y' : 'N';
            $user_multi_role_field = 'Y' === $user_multi_role_field ? 'Y' : 'N';
            $disable_closed_ticket_reply = 'Y' === $disable_closed_ticket_reply ? 'Y' : 'N';
            $is_hide_cp_text = 'Y' === $is_hide_cp_text ? 'Y' : 'N';
            $is_public_ticket_opt_on_creation = 'Y' === $is_public_ticket_opt_on_creation ? 'Y' : 'N';
            $is_public_ticket_opt_on_details = 'Y' === $is_public_ticket_opt_on_details ? 'Y' : 'N';
            $is_public_tickets_menu = 'Y' === $is_public_tickets_menu ? 'Y' : 'N';
            $override_wp_users_can_register = 'Y' === $override_wp_users_can_register ? 'Y' : 'N';
            $disable_registration_form = 'Y' === $disable_registration_form ? 'Y' : 'N';
            $disable_guest_ticket_creation = 'Y' === $disable_guest_ticket_creation ? 'Y' : 'N';
            $disable_guest_email_to_ticket_creation = 'Y' === $disable_guest_email_to_ticket_creation ? 'Y' : 'N';
            $disable_guest_chatbot_ticket_creation = 'Y' === $disable_guest_chatbot_ticket_creation ? 'Y' : 'N';
            $email_to_ticket_rich_html = 'Y' === $email_to_ticket_rich_html ? 'Y' : 'N';
            $close_ticket_opt_for_customer = 'Y' === $close_ticket_opt_for_customer ? 'Y' : 'N';
            $disable_auto_ticket_assignment = 'Y' === $disable_auto_ticket_assignment ? 'Y' : 'N';
            $disable_need_reply_sorting = 'Y' === $disable_need_reply_sorting ? 'Y' : 'N';
            $disable_ticket_hotlink = 'Y' === $disable_ticket_hotlink ? 'Y' : 'N';
            $disable_undo_submit = 'Y' === $disable_undo_submit ? 'Y' : 'N';
            $disable_current_viewers = 'Y' === $disable_current_viewers ? 'Y' : 'N';

            // Client role.
            $client_role = !empty($client_role) ? $client_role : 'subscriber';

            // Ticket page.
            $ticket_page = intval($ticket_page);
            $ticket_page = ('page' === get_post_type($ticket_page)) ? $ticket_page : 0;

            // Track id min length.
            $track_id_min_len = max(1, intval($track_id_min_len));

            // Auto refresh interval.
            if ('Y' !== $tickets_auto_refresh) {
                $tickets_auto_refresh_interval = absint($this->GetOption('tickets_auto_refresh_interval', 60));
            }

            $tickets_auto_refresh_interval = max($tickets_auto_refresh_interval, 5);

            $this->AddIntoOption('client_role', $client_role);
            $this->AddIntoOption('ticket_page', $ticket_page);
            $this->AddIntoOption('ticket_page_shortcode', $ticket_page_shortcode);
            $this->AddIntoOption('footer_cp_text', $footer_cp_text);
            $this->AddIntoOption('is_wp_login_reg', $is_wp_login_reg);
            $this->AddIntoOption('login_page', $login_page);
            $this->AddIntoOption('reg_page', $reg_page);
            $this->AddIntoOption('is_wp_profile_link', $is_wp_profile_link);
            $this->AddIntoOption('user_multi_role_field', $user_multi_role_field);
            $this->AddIntoOption('wp_profile_link', $wp_profile_link);
            $this->AddIntoOption('is_seq_track_id', $is_seq_track_id);
            $this->AddIntoOption('track_id_prefix', $track_id_prefix);
            $this->AddIntoOption('track_id_min_len', $track_id_min_len);
            $this->AddIntoOption('tickets_auto_refresh', $tickets_auto_refresh);
            $this->AddIntoOption('tickets_auto_refresh_interval', $tickets_auto_refresh_interval);
            $this->AddIntoOption('disable_closed_ticket_reply', $disable_closed_ticket_reply);
            $this->AddIntoOption('disable_closed_ticket_reply_notice', $disable_closed_ticket_reply_notice);
            $this->AddIntoOption('is_hide_cp_text', $is_hide_cp_text);
            $this->AddIntoOption('is_public_ticket_opt_on_creation', $is_public_ticket_opt_on_creation);
            $this->AddIntoOption('is_public_ticket_opt_on_details', $is_public_ticket_opt_on_details);
            $this->AddIntoOption('is_public_tickets_menu', $is_public_tickets_menu);
            $this->AddIntoOption('override_wp_users_can_register', $override_wp_users_can_register);
            $this->AddIntoOption('disable_registration_form', $disable_registration_form);
            $this->AddIntoOption('disable_guest_ticket_creation', $disable_guest_ticket_creation);
            $this->AddIntoOption('disable_guest_email_to_ticket_creation', $disable_guest_email_to_ticket_creation);
            $this->AddIntoOption('disable_guest_chatbot_ticket_creation', $disable_guest_chatbot_ticket_creation);
            $this->AddIntoOption('email_to_ticket_rich_html', $email_to_ticket_rich_html);
            $this->AddIntoOption('close_ticket_opt_for_customer', $close_ticket_opt_for_customer);
            $this->AddIntoOption('disable_auto_ticket_assignment', $disable_auto_ticket_assignment);
            $this->AddIntoOption('disable_need_reply_sorting', $disable_need_reply_sorting);
            $this->AddIntoOption('disable_ticket_hotlink', $disable_ticket_hotlink);
            $this->AddIntoOption('disable_undo_submit', $disable_undo_submit);
            $this->AddIntoOption('disable_current_viewers', $disable_current_viewers);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackSetupWizard()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $setup_wizard_step = absint(ApbdWps_PostValue('setup_wizard_step', 0));
            $setup_wizard_finished = rest_sanitize_boolean(ApbdWps_PostValue('setup_wizard_finished', false));

            $this->AddIntoOption('setup_wizard_step', $setup_wizard_step);
            $this->AddIntoOption('setup_wizard_finished', $setup_wizard_finished);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackLogo()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $app_favicon = esc_url_raw(ApbdWps_PostValue('app_favicon', ''));
            $app_logo = esc_url_raw(ApbdWps_PostValue('app_logo', ''));

            if (
                (1 > strlen($app_favicon)) ||
                (1 > strlen($app_logo))
            ) {
                $hasError = true;
            }

            $this->AddIntoOption('app_favicon', $app_favicon);
            $this->AddIntoOption('app_logo', $app_logo);

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackFile()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $ticket_file_upload = sanitize_text_field(ApbdWps_PostValue('ticket_file_upload', ''));

            if ('A' === $ticket_file_upload) {
                $file_upload_size = sanitize_text_field(ApbdWps_PostValue('file_upload_size', ''));
                $allowed_type = sanitize_text_field(ApbdWps_PostValue('allowed_type', ''));
                $file_image_popup = sanitize_text_field(ApbdWps_PostValue('file_image_popup', ''));
                $file_preview_mode = sanitize_text_field(ApbdWps_PostValue('file_preview_mode', ''));

                $file_upload_size = max(1, intval($file_upload_size));

                // Type.
                $allowed_type = explode(',', $allowed_type);
                $all__allowed_type = ['image', 'video', 'audio', 'docs', 'text', 'csv', 'pdf', 'zip', 'json', 'three_d_model', 'medical_image'];
                $def__allowed_type = ['image', 'docs', 'text', 'pdf'];
                $new__allowed_type = [];

                foreach ($allowed_type as $key) {
                    if (in_array($key, $all__allowed_type, true)) {
                        $new__allowed_type[] = $key;
                    }
                }

                if (empty($new__allowed_type)) {
                    $new__allowed_type = $def__allowed_type;
                }

                $file_image_popup = 'Y' === $file_image_popup ? 'Y' : 'N';
                $file_preview_mode = 'Y' === $file_preview_mode ? 'Y' : 'N';

                $this->AddIntoOption('ticket_file_upload', 'A');
                $this->AddIntoOption('file_upload_size', $file_upload_size);
                $this->AddIntoOption('allowed_type', $new__allowed_type);
                $this->AddIntoOption('file_image_popup', $file_image_popup);
                $this->AddIntoOption('file_preview_mode', $file_preview_mode);
            } else {
                $this->AddIntoOption('ticket_file_upload', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackCaptcha()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $recaptcha_v3_status = sanitize_text_field(ApbdWps_PostValue('recaptcha_v3_status', ''));

            if ('A' === $recaptcha_v3_status) {
                $recaptcha_v3_site_key = sanitize_text_field(ApbdWps_PostValue('recaptcha_v3_site_key', ''));
                $recaptcha_v3_secret_key = sanitize_text_field(ApbdWps_PostValue('recaptcha_v3_secret_key', ''));
                $recaptcha_v3_display_opts = sanitize_text_field(ApbdWps_PostValue('recaptcha_v3_display_opts', ''));
                $recaptcha_v3_hide_badge = sanitize_text_field(ApbdWps_PostValue('recaptcha_v3_hide_badge', ''));

                $recaptcha_v3_hide_badge = 'Y' === $recaptcha_v3_hide_badge ? 'Y' : 'N';

                // Secret key.
                if (str_contains($recaptcha_v3_secret_key, '*')) {
                    $recaptcha_v3_secret_key = $this->GetOption('recaptcha_v3_secret_key', '');
                }

                // Display options.
                $recaptcha_v3_display_opts = explode(',', $recaptcha_v3_display_opts);
                $all__recaptcha_v3_display_opts = ['captcha_on_login_form', 'captcha_on_create_tckt', 'captcha_on_reg_form'];

                foreach ($all__recaptcha_v3_display_opts as $opt) {
                    if (in_array($opt, $recaptcha_v3_display_opts, true)) {
                        $this->AddIntoOption($opt, 'Y');
                    } else {
                        $this->AddIntoOption($opt, 'N');
                    }
                }

                if (
                    (1 > strlen($recaptcha_v3_site_key)) ||
                    (1 > strlen($recaptcha_v3_secret_key))
                ) {
                    $hasError = true;
                }

                $this->AddIntoOption('recaptcha_v3_status', 'A');
                $this->AddIntoOption('recaptcha_v3_site_key', $recaptcha_v3_site_key);
                $this->AddIntoOption('recaptcha_v3_secret_key', $recaptcha_v3_secret_key);
                $this->AddIntoOption('recaptcha_v3_hide_badge', $recaptcha_v3_hide_badge);
            } else {
                $this->AddIntoOption('recaptcha_v3_status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackApiKeysOpenAI()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;
        $hasError = false;

        // Valid OpenAI models.
        $valid_openai_models = ['gpt-5.1', 'gpt-5', 'gpt-5-mini', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano', 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'];

        if (ApbdWps_IsPostBack) {
            $openai_status = sanitize_text_field(ApbdWps_PostValue('openai_status', ''));

            if ('A' === $openai_status) {
                $openai_api_key = sanitize_text_field(ApbdWps_PostValue('openai_api_key', ''));
                $openai_model = sanitize_text_field(ApbdWps_PostValue('openai_model', 'gpt-4o-mini'));
                $openai_max_tokens = absint(ApbdWps_PostValue('openai_max_tokens', ''));

                if (str_contains($openai_api_key, '*')) {
                    $openai_api_key = $this->GetOption('openai_api_key', '');
                }

                // Validate model.
                if (!in_array($openai_model, $valid_openai_models, true)) {
                    $openai_model = 'gpt-4o-mini';
                }

                if (
                    (1 > strlen($openai_api_key)) ||
                    (1 > strlen($openai_max_tokens))
                ) {
                    $hasError = true;
                }

                $this->AddIntoOption('openai_status', 'A');
                $this->AddIntoOption('openai_api_key', $openai_api_key);
                $this->AddIntoOption('openai_model', $openai_model);
                $this->AddIntoOption('openai_max_tokens', $openai_max_tokens);
            } else {
                $this->AddIntoOption('openai_status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackApiKeysClaude()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;
        $hasError = false;

        // Valid Claude models.
        $valid_claude_models = ['claude-opus-4-5-20251101', 'claude-haiku-4-5-20251001', 'claude-sonnet-4-5-20250929', 'claude-opus-4-1-20250805', 'claude-sonnet-4-20250514', 'claude-3-7-sonnet-20250219', 'claude-3-5-haiku-20241022', 'claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 'claude-3-haiku-20240307'];

        if (ApbdWps_IsPostBack) {
            $claude_status = sanitize_text_field(ApbdWps_PostValue('claude_status', ''));

            if ('A' === $claude_status) {
                $claude_api_key = sanitize_text_field(ApbdWps_PostValue('claude_api_key', ''));
                $claude_model = sanitize_text_field(ApbdWps_PostValue('claude_model', 'claude-3-haiku-20240307'));
                $claude_max_tokens = absint(ApbdWps_PostValue('claude_max_tokens', ''));

                if (str_contains($claude_api_key, '*')) {
                    $claude_api_key = $this->GetOption('claude_api_key', '');
                }

                // Validate model.
                if (!in_array($claude_model, $valid_claude_models, true)) {
                    $claude_model = 'claude-3-haiku-20240307';
                }

                if (
                    (1 > strlen($claude_api_key)) ||
                    (1 > strlen($claude_max_tokens))
                ) {
                    $hasError = true;
                }

                $this->AddIntoOption('claude_status', 'A');
                $this->AddIntoOption('claude_api_key', $claude_api_key);
                $this->AddIntoOption('claude_model', $claude_model);
                $this->AddIntoOption('claude_max_tokens', $claude_max_tokens);
            } else {
                $this->AddIntoOption('claude_status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function dataApiKeysAIProxy()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $ai_proxy_status = $this->GetOption('ai_proxy_status', 'I');

        // Status.
        $ai_proxy_status = ('A' === $ai_proxy_status) ? true : false;

        // For free version: license key is auto-generated during first use
        // Always indicate license is available since it's handled automatically
        $data = [
            'ai_proxy_status' => $ai_proxy_status,
            'has_license_key' => true, // Free version auto-registers, no manual license needed
            'license_key_masked' => '', // No license key to display for free version
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackApiKeysAIProxy()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        if (ApbdWps_IsPostBack) {
            $ai_proxy_status = sanitize_text_field(ApbdWps_PostValue('ai_proxy_status', ''));

            if ('A' === $ai_proxy_status) {
                // Free version: no license key required - will auto-register on first use
                $this->AddIntoOption('ai_proxy_status', 'A');
            } else {
                $this->AddIntoOption('ai_proxy_status', 'I');
            }

            if ($beforeSave !== $this->options) {
                if ($this->UpdateOption()) {
                    $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    /**
     * Fetch AI Proxy credits balance
     * This works without saving the toggle - allows "Check AI Credits" to work immediately
     */
    public function dataAIProxyCredits()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        // Build config directly - don't check ai_proxy_status
        // This allows "Check AI Credits" to work without saving toggle
        $config = [
            'server_url' => self::$ai_proxy_server_url,
            'product_slug' => self::$ai_proxy_product_slug,
            'license_key' => self::GetLicenseKey(), // May be empty - will trigger free registration
            'domain' => ApbdWps_CleanDomainName(site_url()),
        ];

        // Authenticate with AI Proxy (will auto-register free if needed)
        $auth_result = $this->ai_proxy_authenticate($config);

        if (isset($auth_result['error'])) {
            $apiResponse->SetResponse(false, $auth_result['error']);
            echo wp_json_encode($apiResponse);
            return;
        }

        // Refresh license key from database in case it was just registered
        // This is needed because $config is passed by value, not reference
        $config['license_key'] = self::GetLicenseKey();

        // Get credits balance
        $credits_result = $this->ai_proxy_get_credits($config, $auth_result['token']);

        if (isset($credits_result['error'])) {
            $apiResponse->SetResponse(false, $credits_result['error']);
            echo wp_json_encode($apiResponse);
            return;
        }

        $data = [
            'credits_balance' => $credits_result['credits_balance'],
            'credits_used' => $credits_result['credits_used'],
            'max_credits' => $credits_result['max_credits'],
            'unlimited' => $credits_result['unlimited'],
            'renewal_date' => $credits_result['renewal_date'],
            'tier' => $credits_result['tier'],
            'checkout_url' => $this->ai_proxy_get_checkout_url($config),
        ];

        $apiResponse->SetResponse(true, '', $data);

        echo wp_json_encode($apiResponse);
    }

    function CreateBaseFolder()
    {
        // Create directory if it doesn't exist
        if (!is_dir(self::$uploadBasePath)) {
            wp_mkdir_p(self::$uploadBasePath);
        }

        // Create .htaccess if it doesn't exist
        $htaccessFile = self::$uploadBasePath . "/.htaccess";
        if (!file_exists($htaccessFile)) {
            ApbdWps_FilePutContents(
                $htaccessFile,
                '# Block access to hidden/system files
<FilesMatch "^\.">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>'
            );
        }

        // Create index.php if it doesn't exist
        $indexFile = self::$uploadBasePath . "/index.php";
        if (!file_exists($indexFile)) {
            ApbdWps_FilePutContents(
                $indexFile,
                '<?php
// Silence is golden.'
            );
        }
    }

    /**
     * From version 1.4.31
     */
    function UpdateBaseFolder()
    {
        // Create directory if it doesn't exist
        if (!is_dir(self::$uploadBasePath)) {
            wp_mkdir_p(self::$uploadBasePath);
        }

        // Always update .htaccess with latest security rules
        $htaccessFile = self::$uploadBasePath . "/.htaccess";
        ApbdWps_FilePutContents(
            $htaccessFile,
            '# Block access to hidden/system files
<FilesMatch "^\.">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>'
        );

        // Create index.php if it doesn't exist
        $indexFile = self::$uploadBasePath . "/index.php";
        if (!file_exists($indexFile)) {
            ApbdWps_FilePutContents(
                $indexFile,
                '<?php
// Silence is golden.'
            );
        }
    }

    /**
     * Fix corrupted KB meta values from BetterDocs migration.
     *
     * This function fixes meta values that were corrupted during BetterDocs migration.
     * It handles the following types of corruption:
     * 1. Single values wrapped in arrays: 'value' → ['value']
     * 2. Double-serialization: [] → 'a:0:{}' → ['a:0:{}']
     * 3. Multiple identical elements (array fields): ['a:0:{}', 'a:0:{}'] → []
     * 4. Multiple identical elements (scalar fields): ['', ''] → ''
     *
     * @since 1.4.38
     * @return int Number of fixed entries
     */
    function FixChangedKBMeta2()
    {
        // Skip if sgkb-docs post type doesn't exist
        if (!post_type_exists('sgkb-docs')) {
            return 0;
        }

        // Skip if this fix has already been run
        $fix_completed = get_option('sgkb_meta_fix_v2_completed');
        if ($fix_completed === 'yes') {
            return 0;
        }

        $batch_size = 100;
        $offset = 0;
        $fixed_count = 0;

        /**
         * Comprehensive list of meta keys that should ALWAYS be arrays.
         * These fields store array data and should never be converted to scalars.
         *
         * For these fields, we handle:
         * - Nested array unwrapping: [['a','b']] → ['a','b']
         * - Double-serialization fix: ['a:0:{}'] → []
         * - Multiple identical double-serialized elements: ['a:0:{}', 'a:0:{}', 'a:0:{}'] → []
         */
        $must_be_array_fields = [
            // WordPress Core
            '_wp_attachment_metadata',
            '_wp_attachment_backup_sizes',
            '_menu_item_classes',
            '_menu_item_widget_list',

            // Support Genix
            '_sg_spaces',
            '_sg_docs_reusable_block_ids',
            '_sg_docs_meta_impression_per_day',
            '_sg_docs_related_articles',
            '_sg_docs_attachments',
            '_sg_docs_feelings',

            // Astra Addon - Advanced Hooks
            'ast-advanced-hook-location',
            'ast-advanced-hook-exclusion',
            'ast-advanced-hook-users',
            'ast-advanced-hook-padding',
            'ast-advanced-hook-header',
            'ast-advanced-hook-footer',
            'ast-404-page',
            'ast-advanced-hook-content',
            'ast-advanced-display-device',
            'ast-advanced-time-duration',

            // Astra Addon - Advanced Headers
            'ast-advanced-headers-layout',
            'ast-advanced-headers-design',
            'ast-advanced-headers-location',
            'ast-advanced-headers-exclusion',
            'ast-advanced-headers-users',

            // Stackable
            'stackable_optimized_css_raw',

            // Rank Math SEO
            'rank_math_robots',
            'rank_math_advanced_robots',
            // Note: rank_math_auto_redirect is intentionally NOT here - it stores integer post ID

            // BetterDocs (original source)
            '_betterdocs_reusable_block_ids',
            '_betterdocs_attachments',
            '_betterdocs_related_articles',

            // Link Whisper - Data fields store arrays of link objects
            'wpil_links_inbound_internal_count_data',
            'wpil_links_outbound_internal_count_data',
            'wpil_links_outbound_external_count_data',

            // WooCommerce
            '_product_attributes',
            '_crosssell_ids',
            '_upsell_ids',
            '_children',

            // Yoast SEO
            '_yoast_wpseo_focuskeywords',

            // ACF (Advanced Custom Fields)
            '_acf_field_group_categories',

            // Elementor
            '_elementor_css',
            '_elementor_page_assets',

            // Disclaimify Plugin
            'disclaimify_settings',

            // WordPress User Meta stored on posts
            'closedpostboxes_page',
            'closedpostboxes_post',
            'metaboxhidden_page',
            'metaboxhidden_post',

            // WP Rocket
            'rocket_boxes',

            // Elementor
            'elementor_dismissed_editor_notices',
        ];

        /**
         * Meta key prefixes that should ALWAYS be arrays.
         */
        $must_be_array_prefixes = [
            'rank_math_schema_',
            '_acf_',
        ];

        /**
         * Meta keys to completely SKIP - don't touch at all.
         * These store complex data structures that shouldn't be modified.
         */
        $skip_entirely = [
            '_elementor_data',
            '_elementor_page_settings',
            '_elementor_controls_usage',
            '_wp_page_template',
        ];

        /**
         * Meta key prefixes to skip entirely.
         */
        $skip_prefixes = [
            '_oembed_',
            '_edit_',
            '_pingme',
            '_encloseme',
        ];

        do {
            $posts = get_posts([
                'post_type' => 'sgkb-docs',
                'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash'],
                'numberposts' => $batch_size,
                'offset' => $offset,
                'fields' => 'ids',
            ]);

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post_id) {
                $post_meta = get_post_meta($post_id);

                foreach ($post_meta as $meta_key => $meta_values) {
                    // Skip meta keys entirely based on exact match
                    if (in_array($meta_key, $skip_entirely, true)) {
                        continue;
                    }

                    // Skip meta keys based on prefix
                    $should_skip = false;
                    foreach ($skip_prefixes as $prefix) {
                        if (strpos($meta_key, $prefix) === 0) {
                            $should_skip = true;
                            break;
                        }
                    }
                    if ($should_skip) {
                        continue;
                    }

                    // Check if this is a "must be array" field
                    $must_be_array = in_array($meta_key, $must_be_array_fields, true);

                    // Check prefixes for "must be array"
                    if (!$must_be_array) {
                        foreach ($must_be_array_prefixes as $prefix) {
                            if (strpos($meta_key, $prefix) === 0) {
                                $must_be_array = true;
                                break;
                            }
                        }
                    }

                    // Get the actual stored value (with $single=true)
                    $stored_value = get_post_meta($post_id, $meta_key, true);

                    // Only process if it's an array with exactly one element at index 0
                    // This is the signature of the buggy migration pattern: ['value'] instead of 'value'
                    if (
                        is_array($stored_value) &&
                        count($stored_value) === 1 &&
                        array_key_exists(0, $stored_value)
                    ) {
                        $inner_value = $stored_value[0];
                        $new_value = null;

                        if ($must_be_array) {
                            // For "must be array" fields
                            if (is_array($inner_value)) {
                                // Nested array: [['a','b']] → ['a','b']
                                $new_value = $inner_value;
                            } elseif (is_string($inner_value)) {
                                // Check for double-serialization: ['a:0:{}'] → []
                                // The inner string might be a serialized array
                                $maybe_unserialized = @unserialize($inner_value);
                                if ($maybe_unserialized !== false || $inner_value === 'b:0;') {
                                    // Successfully unserialized - use the unserialized value
                                    $new_value = $maybe_unserialized;
                                } elseif ($inner_value === 'a:0:{}') {
                                    // Special case: serialized empty array
                                    $new_value = [];
                                }
                                // If unserialization failed and it's not a known pattern,
                                // leave it as is (don't update)
                            }
                            // For other types (null, etc.), leave alone
                        } else {
                            // For normal (non-array) fields
                            if (is_scalar($inner_value)) {
                                // Simple scalar unwrap: ['string'] → 'string'
                                $new_value = $inner_value;
                            } elseif (is_array($inner_value)) {
                                // Nested array unwrap: [['a','b']] → ['a','b']
                                $new_value = $inner_value;
                            } elseif (is_object($inner_value)) {
                                // Object unwrap: [Object] → Object
                                $new_value = $inner_value;
                            }
                            // For null, leave alone
                        }

                        // Update if we determined a new value
                        if ($new_value !== null) {
                            update_post_meta($post_id, $meta_key, $new_value);
                            $fixed_count++;
                        }
                    } elseif (
                        $must_be_array &&
                        is_array($stored_value) &&
                        count($stored_value) > 1 &&
                        array_keys($stored_value) === range(0, count($stored_value) - 1)
                    ) {
                        // Handle arrays with multiple identical double-serialized string elements
                        // e.g., ['a:0:{}', 'a:0:{}', 'a:0:{}'] → []
                        // Only fix if ALL elements are exactly the same string
                        // AND keys are sequential numeric (0, 1, 2, ...) - skip associative arrays
                        $all_same = true;
                        $first_value = null;
                        $is_first = true;

                        foreach ($stored_value as $element) {
                            if (!is_string($element)) {
                                $all_same = false;
                                break;
                            }
                            if ($is_first) {
                                $first_value = $element;
                                $is_first = false;
                            } elseif ($element !== $first_value) {
                                $all_same = false;
                                break;
                            }
                        }

                        if ($all_same && $first_value !== null) {
                            // All elements are the same string - try to unserialize
                            $maybe_unserialized = @unserialize($first_value);
                            if ($maybe_unserialized !== false || $first_value === 'b:0;') {
                                update_post_meta($post_id, $meta_key, $maybe_unserialized);
                                $fixed_count++;
                            } elseif ($first_value === 'a:0:{}') {
                                update_post_meta($post_id, $meta_key, []);
                                $fixed_count++;
                            }
                        }
                    } elseif (
                        !$must_be_array &&
                        is_array($stored_value) &&
                        count($stored_value) > 1 &&
                        array_keys($stored_value) === range(0, count($stored_value) - 1)
                    ) {
                        // Handle scalar fields with multiple identical elements
                        // e.g., ['', ''] → '' or ['value', 'value'] → 'value'
                        // Only fix if ALL elements are exactly the same
                        // AND keys are sequential numeric (0, 1, 2, ...) - skip associative arrays
                        $all_same = true;
                        $first_value = null;
                        $is_first = true;

                        foreach ($stored_value as $element) {
                            if ($is_first) {
                                $first_value = $element;
                                $is_first = false;
                            } elseif ($element !== $first_value) {
                                $all_same = false;
                                break;
                            }
                        }

                        if ($all_same && !$is_first) {
                            // All elements are the same - unwrap to single value
                            update_post_meta($post_id, $meta_key, $first_value);
                            $fixed_count++;
                        }
                    }
                }
            }

            $offset += $batch_size;

            // Clear cache periodically to manage memory
            if ($offset % 500 === 0) {
                wp_cache_flush();
            }
        } while (count($posts) === $batch_size);

        // Mark this fix as completed so it doesn't run again
        update_option('sgkb_meta_fix_v2_completed', 'yes', false);

        if ($fixed_count > 0) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(sprintf('[Support Genix] FixChangedKBMeta2: Fixed %d corrupted KB meta values.', $fixed_count));
        }

        return $fixed_count;
    }

    function getTicketAttachedPath($ticket_id, $reply_id = '')
    {
        $this->CreateBaseFolder();
        $ticketDir = self::$uploadBasePath;
        if (! empty($ticket_id)) {
            $ticketDir = $ticketDir . $ticket_id;
            if (! empty($reply_id)) {
                $ticketDir = $ticketDir . "/replied/" . $reply_id . "/attached_files/";
            } else {
                $ticketDir = $ticketDir . "/attached_files/";
            }
        }
        if (!is_dir($ticketDir)) {
            if (!wp_mkdir_p($ticketDir)) {
                $this->AddError("System couldn't create directory");
                return false;
            }
        }
        return $ticketDir;
    }
    function attach_file($ticket_files, $ticketObj, $reply_obj = null)
    {
        if ($this->kernelObject->isDemoMode()) {
            $this->AddError("File upload has been disabled in demomode");
            return false;
        }
        if (Apbd_wps_settings::GetModuleOption("ticket_file_upload", 'A') == 'A') {
            $this->CreateBaseFolder();
            $ticketDir = self::$uploadBasePath;
            if (! empty($ticketObj->id)) {
                $ticketDir = $ticketDir . $ticketObj->id;
                if (! empty($reply_obj->reply_id)) {
                    $ticketDir = $ticketDir . "/replied/" . $reply_obj->reply_id . "/attached_files/";
                } else {
                    $ticketDir = $ticketDir . "/attached_files/";
                }
            }


            if (!is_dir($ticketDir)) {
                if (!wp_mkdir_p($ticketDir)) {
                    $this->AddError("System couldn't create directory");
                    return false;
                }
            }

            if (is_dir($ticketDir)) {
                foreach ($ticket_files['name'] as $ind => $name) {
                    $fname = md5(uniqid(rand())) . '___' . sanitize_file_name($name);

                    global $wp_filesystem;

                    if (empty($wp_filesystem)) {
                        require_once(ABSPATH . '/wp-admin/includes/file.php');
                        WP_Filesystem();
                    }

                    // Copy the uploaded file to its destination
                    if (copy($ticket_files['tmp_name'][$ind], $ticketDir . $fname)) {
                        // Set proper permissions on the new file
                        $wp_filesystem->chmod($ticketDir . $fname, FS_CHMOD_FILE);
                    }
                }
            }
        }
    }

    /**
     * @param boolean $isOk
     * @param string $name
     * @param int $error
     * @param string $type
     * @param int $size
     * @return boolean
     */

    function fileCheck($isOk, $name, $error, $type, $size)
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = Apbd_wps_settings::GetModuleAllowedFileType();
        if (in_array($ext, ['php', 'js', 'sh', 'bash', 'cgi'])) {
            return false;
        }
        if (!in_array($ext, $allowed)) {
            $isOk = false;
        }
        return $isOk;
    }

    /**
     * @param Mapbd_wps_ticket $ticketObj
     * @param $customFields
     */
    public function ticket_assign($ticketObj, $customFields = [])
    {

        Mapbd_wps_ticket_assign_rule::ProcessRuleByCategory($ticketObj);
    }

    /**
     * @param Mapbd_wps_ticket_reply $replyObj
     */
    public function send_reply_notification($replyObj)
    {
        $ticketObj = Mapbd_wps_ticket::FindBy("id", $replyObj->ticket_id);
        if (! empty($replyObj) && ! empty($ticketObj)) {
            Mapbd_wps_ticket_assign_rule::ProcessReplyNotificationAndEmail($replyObj, $ticketObj);
        }
    }

    /**
     * @param Mapbd_wps_ticket $ticketObj
     * @param $customFields
     */
    public function send_ticket_email($ticketObj, $customFields)
    {
        Mapbd_wps_ticket::Send_ticket_open_email($ticketObj);
    }
    /**
     * @param Mapbd_wps_ticket $ticketObj
     */
    public function send_close_ticket_email($ticketObj)
    {
        if ($ticketObj->status == "C") {
            Mapbd_wps_ticket::Send_ticket_close_email($ticketObj);
        }
    }
    /**
     * @param Mapbd_wps_ticket $ticketObj
     */
    public function add_status_ticket_log($ticketObj, $logBy = 0)
    {
        $logBy = absint($logBy);
        if (! empty($logBy)) {
            $logByType = Apbd_wps_settings::isClientLoggedIn() ? 'U' : 'A';
            $statusArray = $ticketObj->GetPropertyRawOptions('status');
            $statusName = $statusArray[$ticketObj->status];
            Mapbd_wps_ticket_log::AddTicketLog($ticketObj->id, $logBy, $logByType, $ticketObj->___("Ticket status changed to %s", $statusName), $ticketObj->status);
        } else {
            $logBy = isset($ticketObj->assigned_on) ? absint($ticketObj->assigned_on) : 0;
            if (empty($logBy)) {
                $logBy = isset($ticketObj->last_replied_by) ? absint($ticketObj->last_replied_by) : 0;
            }
            $statusArray = $ticketObj->GetPropertyRawOptions('status');
            $statusName = $statusArray[$ticketObj->status];
            Mapbd_wps_ticket_log::AddTicketLog($ticketObj->id, $logBy, 'A', $ticketObj->___("Ticket status changed to %s Automatically", $statusName), $ticketObj->status);
        }
    }
    /**
     * @param Mapbd_wps_ticket $ticketObj
     */
    public function add_email_notification_ticket_log($ticketObj, $logBy = 0)
    {
        $logBy = absint($logBy);
        $isAgent = Apbd_wps_settings::isAgentLoggedIn();

        if ($isAgent && ! empty($logBy)) {
            $ticketId = $ticketObj->id;
            $ticketStatus = $ticketObj->status;
            $notification = $ticketObj->email_notification;

            if ('Y' === $notification) {
                Mapbd_wps_ticket_log::AddTicketLog($ticketId, $logBy, 'A', $ticketObj->___("Email notification enabled by"), $ticketStatus, 'A');
            } elseif ('N' === $notification) {
                Mapbd_wps_ticket_log::AddTicketLog($ticketId, $logBy, 'A', $ticketObj->___("Email notification disabled by"), $ticketStatus, 'A');
            }
        }
    }
    /**
     *@param Mapbd_wps_ticket $ticketObj
     */
    public function notify_user_on_ticket_assigned($ticketObj)
    {
        if (! empty($ticketObj->assigned_on)) {
            $title = ApbdWps_GetUserTitleByUser($ticketObj->assigned_on);
            Mapbd_wps_ticket_log::AddTicketLog($ticketObj->id, $ticketObj->assigned_on, "A", $ticketObj->___("Ticket assigned on %s", $title), $ticketObj->status);
        }

        Mapbd_wps_notification::AddNotification($ticketObj->assigned_on, "Assigned Ticket", "Ticket has been assigned to you", "", "/ticket/" . $ticketObj->id, false, "T", "A", $ticketObj->id);
        Mapbd_wps_ticket::Send_ticket_assigned_email($ticketObj);
    }

    /**
     * @param WP_Error $error
     */
    public function mail_send_failed($error)
    {
        Mapbd_wps_debug_log::AddEmailLog("Email send failed", $error);
    }

    function valid_incoming_webhook_custom_field($response, $custom_fields, $user_email = '', $user_exists = false, $ticket_category_id = 0)
    {
        if (empty($response) && ! empty($custom_fields)) {
            $predfn_custom_fields = Mapbd_wps_custom_field::FindAllBy("status", "A");

            foreach ($predfn_custom_fields as $predfn_custom_field) {
                $id = $predfn_custom_field->id;
                $field_label = $predfn_custom_field->field_label;
                $categories = $predfn_custom_field->choose_category;
                $fld_option = $predfn_custom_field->fld_option;
                $field_type = $predfn_custom_field->field_type;
                $where_to_create = $predfn_custom_field->where_to_create;
                $create_for = $predfn_custom_field->create_for;
                $is_required = $predfn_custom_field->is_required;

                $field_key = sprintf('D%1$d', $id);

                $categories = trim($categories);
                $categories = (0 < strlen($categories) ? explode(',', $categories) : array());
                $categories = array_map(function ($value) {
                    return trim($value);
                }, $categories);

                $fld_option = trim($fld_option);
                $fld_option = (0 < strlen($fld_option) ? explode(',', $fld_option) : array());
                $fld_option = array_map(function ($value) {
                    return trim($value);
                }, $fld_option);

                if (('A' === $create_for) || ('E' === $field_type) || (! empty($categories) && ! in_array('0', $categories) && ! in_array($ticket_category_id, $categories))) {
                    continue;
                };

                if (empty($response) && isset($custom_fields[$field_key])) {
                    $field_value = $custom_fields[$field_key];

                    $response = array(
                        'status' => true,
                        'msg' => '',
                    );

                    if (! empty($field_value)) {
                        if ('N' === $field_type) {
                            if (! is_numeric($field_value)) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s must contain number.'), $field_label),
                                );
                            }
                        } elseif ('U' === $field_type) {
                            $new_field_value = esc_url($field_value);

                            if ($field_value !== $new_field_value) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s is invalid.'), $field_label),
                                );
                            }
                        } elseif (('R' === $field_type) || ('W' === $field_type)) {
                            if (! empty($fld_option) && ! in_array($field_value, $fld_option, true)) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s is invalid.'), $field_label),
                                );
                            }
                        }
                    } elseif (('Y' === $is_required) && (('T' === $where_to_create) || (('I' === $where_to_create) && (false === $user_exists)))) {
                        $response = array(
                            'status' => false,
                            'msg' => sprintf($this->__('%1$s is required.'), $field_label),
                        );
                    }

                    $response = ((isset($response['status']) && (true !== $response['status'])) ? $response : array());
                }
            }
        }

        return $response;
    }

    function valid_ht_contact_form_custom_field($response, $custom_fields, $user_email = '', $user_exists = false, $ticket_category_id = 0)
    {
        if (empty($response) && ! empty($custom_fields)) {
            $predfn_custom_fields = Mapbd_wps_custom_field::FindAllBy("status", "A");

            foreach ($predfn_custom_fields as $predfn_custom_field) {
                $id = $predfn_custom_field->id;
                $field_label = $predfn_custom_field->field_label;
                $categories = $predfn_custom_field->choose_category;
                $fld_option = $predfn_custom_field->fld_option;
                $field_type = $predfn_custom_field->field_type;
                $where_to_create = $predfn_custom_field->where_to_create;
                $create_for = $predfn_custom_field->create_for;
                $is_required = $predfn_custom_field->is_required;

                $field_key = sprintf('D%1$d', $id);

                $categories = trim($categories);
                $categories = (0 < strlen($categories) ? explode(',', $categories) : array());
                $categories = array_map(function ($value) {
                    return trim($value);
                }, $categories);

                $fld_option = trim($fld_option);
                $fld_option = (0 < strlen($fld_option) ? explode(',', $fld_option) : array());
                $fld_option = array_map(function ($value) {
                    return trim($value);
                }, $fld_option);

                if (('A' === $create_for) || ('E' === $field_type) || (! empty($categories) && ! in_array('0', $categories) && ! in_array($ticket_category_id, $categories))) {
                    continue;
                };

                if (empty($response) && isset($custom_fields[$field_key])) {
                    $field_value = $custom_fields[$field_key];

                    $response = array(
                        'status' => true,
                        'msg' => '',
                    );

                    if (! empty($field_value)) {
                        if ('N' === $field_type) {
                            if (! is_numeric($field_value)) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s must contain number.'), $field_label),
                                );
                            }
                        } elseif ('U' === $field_type) {
                            $new_field_value = esc_url($field_value);

                            if ($field_value !== $new_field_value) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s is invalid.'), $field_label),
                                );
                            }
                        } elseif (('R' === $field_type) || ('W' === $field_type)) {
                            if (! empty($fld_option) && ! in_array($field_value, $fld_option, true)) {
                                $response = array(
                                    'status' => false,
                                    'msg' => sprintf($this->__('%1$s is invalid.'), $field_label),
                                );
                            }
                        }
                    } elseif (('Y' === $is_required) && (('T' === $where_to_create) || (('I' === $where_to_create) && (false === $user_exists)))) {
                        $response = array(
                            'status' => false,
                            'msg' => sprintf($this->__('%1$s is required.'), $field_label),
                        );
                    }

                    $response = ((isset($response['status']) && (true !== $response['status'])) ? $response : array());
                }
            }
        }

        return $response;
    }

    function final_filter_custom_field($custom_fields, $ticket_or_user_id = '')
    {
        $isClient = Apbd_wps_settings::isClientLoggedIn();
        if ($isClient) {
            foreach ($custom_fields as &$custom_field) {
                if (substr($custom_field->input_name, 0, 1) == "D" && ! empty($custom_field->field_value)) {
                    $custom_field->is_editable = false;
                }
            }
        } elseif (! current_user_can('edit-custom-field')) {
            foreach ($custom_fields as &$custom_field) {
                if (substr($custom_field->input_name, 0, 1) == "D") {
                    $custom_field->is_editable = false;
                }
            }
        }
        return $custom_fields;
    }

    public function ProfileEditAction($user)
    {
        $user_id = (isset($user->ID) ? absint($user->ID) : 0);

        if (empty($user_id) || ! current_user_can('edit_user', $user_id)) {
            return;
        }

        $options = apply_filters('apbd-wps/filter/profile-edit-options', array());

        if (! is_array($options) || empty($options)) {
            return;
        }
        ?>
        <h2 style="padding-top: 15px;"><?php $this->_e('Support Genix Options'); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <?php
                foreach ($options as $option_key => $option) {
                    $option_key = 'support_genix_' . sanitize_key(strval($option_key));
                    $option_label = (isset($option['label']) ? sanitize_text_field($option['label']) : '');
                    $option_description = (isset($option['description']) ? sanitize_text_field($option['description']) : '');
                    $option_value = sanitize_text_field(get_user_meta($user_id, $option_key, true));
                ?>
                    <tr class="user-<?php echo esc_attr($option_key); ?>-wrap">
                        <th><label for="<?php echo esc_attr($option_key); ?>"><?php echo esc_html($option_label); ?></label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($option_key); ?>" id="<?php echo esc_attr($option_key); ?>" aria-describedby="<?php echo esc_attr($option_key); ?>-description" value="<?php echo esc_attr($option_value); ?>" class="regular-text ltr">
                            <p class="description" id="<?php echo esc_attr($option_key); ?>-description"><?php echo esc_html($option_description); ?></p>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
<?php
    }

    public function ProfileUpdateAction($user_id)
    {
        if (empty($user_id) || ! current_user_can('edit_user', $user_id)) {
            return;
        }

        $options = apply_filters('apbd-wps/filter/profile-edit-options', array());

        if (! is_array($options) || empty($options)) {
            return;
        }

        foreach ($options as $option_key => $option) {
            $option_key = 'support_genix_' . sanitize_key(strval($option_key));
            $option_value = (isset($_POST[$option_key]) ? sanitize_text_field($_POST[$option_key]) : '');

            update_user_meta($user_id, $option_key, $option_value);
        }
    }

    /**
     * From version 1.8.24
     */
    public function PermitRegistration()
    {
        $this->AddIntoOption('override_wp_users_can_register', 'Y');
        $this->UpdateOption();
    }

    /**
     * From version 1.8.26
     */
    public function PermitRegistration2()
    {
        $override_wp_users_can_register = $this->GetOption('override_wp_users_can_register', 'Y');
        $override_wp_users_can_register = 'Y' === $override_wp_users_can_register ? 'Y' : 'N';

        $this->AddIntoOption('override_wp_users_can_register', $override_wp_users_can_register);
        $this->UpdateOption();
    }

    /**
     * From version 1.4.30
     */
    public function TransferApiKeys()
    {
        // OpenAI
        $openai_status = 'I';
        $openai_api_key = '';
        $openai_max_tokens = 1500;

        $help_me_write_openai_status = sanitize_text_field(Apbd_wps_help_me_write::GetModuleOption('openai_status', 'I'));
        $help_me_write_openai_api_key = trim(strval(Apbd_wps_help_me_write::GetModuleOption('openai_api_key', '')));
        $help_me_write_openai_max_tokens = min(max(1, absint(Apbd_wps_help_me_write::GetModuleOption('openai_max_tokens', 1500))), 8192);

        if ('A' === $help_me_write_openai_status) {
            if (!empty($help_me_write_openai_api_key)) {
                $openai_status = 'A';
                $openai_api_key = $help_me_write_openai_api_key;
                $openai_max_tokens = $help_me_write_openai_max_tokens;
            }
        }

        if ('I' === $openai_status) {
            $write_with_ai_openai_status = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('openai_status', 'I'));
            $write_with_ai_openai_api_key = trim(strval(Apbd_wps_knowledge_base::GetModuleOption('openai_api_key', '')));
            $write_with_ai_openai_max_tokens = min(max(1, absint(Apbd_wps_knowledge_base::GetModuleOption('openai_max_tokens', 1500))), 8192);

            if ('A' === $write_with_ai_openai_status) {
                if (!empty($write_with_ai_openai_api_key)) {
                    $openai_status = 'A';
                    $openai_api_key = $write_with_ai_openai_api_key;
                    $openai_max_tokens = $write_with_ai_openai_max_tokens;
                }
            }
        }

        if ('I' === $openai_status) {
            $chatbot_status = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('chatbot_status', 'I'));
            $chatbot_ai_tool = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('chatbot_ai_tool', ''));
            $chatbot_openai_api_key = trim(strval(Apbd_wps_knowledge_base::GetModuleOption('chatbot_openai_api_key', '')));
            $chatbot_openai_max_tokens = min(max(1, absint(Apbd_wps_knowledge_base::GetModuleOption('chatbot_openai_max_tokens', 1500))), 8192);

            if ('A' === $chatbot_status && 'openai' === $chatbot_ai_tool) {
                if (!empty($chatbot_openai_api_key)) {
                    $openai_status = 'A';
                    $openai_api_key = $chatbot_openai_api_key;
                    $openai_max_tokens = $chatbot_openai_max_tokens;
                }
            }
        }

        // Claude
        $claude_status = 'I';
        $claude_api_key = '';
        $claude_max_tokens = 1500;

        $help_me_write_claude_status = sanitize_text_field(Apbd_wps_help_me_write::GetModuleOption('claude_status', 'I'));
        $help_me_write_claude_api_key = trim(strval(Apbd_wps_help_me_write::GetModuleOption('claude_api_key', '')));
        $help_me_write_claude_max_tokens = min(max(1, absint(Apbd_wps_help_me_write::GetModuleOption('claude_max_tokens', 1500))), 8192);

        if ('A' === $help_me_write_claude_status) {
            if (!empty($help_me_write_claude_api_key)) {
                $claude_status = 'A';
                $claude_api_key = $help_me_write_claude_api_key;
                $claude_max_tokens = $help_me_write_claude_max_tokens;
            }
        }

        if ('I' === $claude_status) {
            $write_with_ai_claude_status = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('claude_status', 'I'));
            $write_with_ai_claude_api_key = trim(strval(Apbd_wps_knowledge_base::GetModuleOption('claude_api_key', '')));
            $write_with_ai_claude_max_tokens = min(max(1, absint(Apbd_wps_knowledge_base::GetModuleOption('claude_max_tokens', 1500))), 8192);

            if ('A' === $write_with_ai_claude_status) {
                if (!empty($write_with_ai_claude_api_key)) {
                    $claude_status = 'A';
                    $claude_api_key = $write_with_ai_claude_api_key;
                    $claude_max_tokens = $write_with_ai_claude_max_tokens;
                }
            }
        }

        if ('I' === $claude_status) {
            $chatbot_status = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('chatbot_status', 'I'));
            $chatbot_ai_tool = sanitize_text_field(Apbd_wps_knowledge_base::GetModuleOption('chatbot_ai_tool', ''));
            $chatbot_claude_api_key = trim(strval(Apbd_wps_knowledge_base::GetModuleOption('chatbot_claude_api_key', '')));
            $chatbot_claude_max_tokens = min(max(1, absint(Apbd_wps_knowledge_base::GetModuleOption('chatbot_claude_max_tokens', 1500))), 8192);

            if ('A' === $chatbot_status && 'claude' === $chatbot_ai_tool) {
                if (!empty($chatbot_claude_api_key)) {
                    $claude_status = 'A';
                    $claude_api_key = $chatbot_claude_api_key;
                    $claude_max_tokens = $chatbot_claude_max_tokens;
                }
            }
        }

        // Update
        if ('A' === $openai_status || 'A' === $claude_status) {
            if ('A' === $openai_status) {
                $this->AddIntoOption('openai_status', $openai_status);
                $this->AddIntoOption('openai_api_key', $openai_api_key);
                $this->AddIntoOption('openai_max_tokens', $openai_max_tokens);
            }

            if ('A' === $claude_status) {
                $this->AddIntoOption('claude_status', $claude_status);
                $this->AddIntoOption('claude_api_key', $claude_api_key);
                $this->AddIntoOption('claude_max_tokens', $claude_max_tokens);
            }

            $this->UpdateOption();

            // Clean up old options from Help Me Write
            $help_me_write_module = Apbd_wps_help_me_write::GetModuleInstance();

            if ($help_me_write_module) {
                $help_me_write_options = $help_me_write_module->options;

                $help_me_write_openai_status = isset($help_me_write_options['openai_status'])
                    ? sanitize_text_field($help_me_write_options['openai_status'])
                    : 'I';
                $help_me_write_claude_status = isset($help_me_write_options['claude_status'])
                    ? sanitize_text_field($help_me_write_options['claude_status'])
                    : 'I';

                if (
                    ('A' === $help_me_write_openai_status) ||
                    ('A' === $help_me_write_claude_status)
                ) {
                    $help_me_write_ai_tools = [];

                    if ('A' === $help_me_write_openai_status) {
                        $help_me_write_ai_tools[] = 'openai';
                    }

                    if ('A' === $help_me_write_claude_status) {
                        $help_me_write_ai_tools[] = 'claude';
                    }

                    $help_me_write_options['status'] = 'A';
                    $help_me_write_options['ai_tools'] = maybe_serialize($help_me_write_ai_tools);
                }

                if (is_array($help_me_write_options)) {
                    unset($help_me_write_options['openai_status']);
                    unset($help_me_write_options['openai_api_key']);
                    unset($help_me_write_options['openai_max_tokens']);
                    unset($help_me_write_options['claude_status']);
                    unset($help_me_write_options['claude_api_key']);
                    unset($help_me_write_options['claude_max_tokens']);

                    $help_me_write_module->options = $help_me_write_options;
                    $help_me_write_module->UpdateOption();
                }
            }

            // Clean up old options from Write With AI (Knowledge Base)
            $knowledge_base_module = Apbd_wps_knowledge_base::GetModuleInstance();

            if ($knowledge_base_module) {
                $kb_options = $knowledge_base_module->options;

                $kb_openai_status = isset($kb_options['openai_status'])
                    ? sanitize_text_field($kb_options['openai_status'])
                    : 'I';
                $kb_claude_status = isset($kb_options['claude_status'])
                    ? sanitize_text_field($kb_options['claude_status'])
                    : 'I';

                if (
                    ('A' === $kb_openai_status) ||
                    ('A' === $kb_claude_status)
                ) {
                    $write_with_ai_tools = [];

                    if ('A' === $kb_openai_status) {
                        $write_with_ai_tools[] = 'openai';
                    }

                    if ('A' === $kb_claude_status) {
                        $write_with_ai_tools[] = 'claude';
                    }

                    $kb_options['write_with_ai_status'] = 'A';
                    $kb_options['write_with_ai_tools'] = maybe_serialize($write_with_ai_tools);
                }

                if (is_array($kb_options)) {
                    // Clean up Write With AI old keys
                    unset($kb_options['openai_status']);
                    unset($kb_options['openai_api_key']);
                    unset($kb_options['openai_max_tokens']);
                    unset($kb_options['claude_status']);
                    unset($kb_options['claude_api_key']);
                    unset($kb_options['claude_max_tokens']);

                    // Clean up AI Chatbot old keys
                    unset($kb_options['chatbot_openai_api_key']);
                    unset($kb_options['chatbot_openai_max_tokens']);
                    unset($kb_options['chatbot_claude_api_key']);
                    unset($kb_options['chatbot_claude_max_tokens']);

                    $knowledge_base_module->options = $kb_options;
                    $knowledge_base_module->UpdateOption();
                }
            }
        }
    }

    public static function dashboard_texts()
    {
        $core = ApbdWps_SupportLite::GetInstance();

        $texts = [
            'Tickets' => $core->__('Tickets'),
            'Reports' => $core->__('Reports'),
            'Settings' => $core->__('Settings'),
            'Saved Replies' => $core->__('Saved Replies'),
            'All Tickets' => $core->__('All Tickets'),
            'My Tickets' => $core->__('My Tickets'),
            'Unassigned' => $core->__('Unassigned'),
            'Trashed' => $core->__('Trashed'),
            'Sort: Reply Date (Newest First)' => $core->__('Sort: Reply Date (Newest First)'),
            'Sort: Reply Date (Oldest First)' => $core->__('Sort: Reply Date (Oldest First)'),
            'Sort: Opening Date (Newest First)' => $core->__('Sort: Opening Date (Newest First)'),
            'Sort: Opening Date (Oldest First)' => $core->__('Sort: Opening Date (Oldest First)'),
            'Bulk Actions' => $core->__('Bulk Actions'),
            'Quick Edit' => $core->__('Quick Edit'),
            'Move to Trash' => $core->__('Move to Trash'),
            'Restore' => $core->__('Restore'),
            'Delete' => $core->__('Delete'),
            'All Agents' => $core->__('All Agents'),
            'All Categories' => $core->__('All Categories'),
            'Ticket' => $core->__('Ticket'),
            'Add New %s' => $core->__('Add New %s'),
            'Add Ticket' => $core->__('Add Ticket'),
            'Need Reply' => $core->__('Need Reply'),
            'Search keyword' => $core->__('Search keyword'),
            'Reset Filters' => $core->__('Reset Filters'),
            'Select Category' => $core->__('Select Category'),
            'Title' => $core->__('Title'),
            'Reply' => $core->__('Reply'),
            'Agent' => $core->__('Agent'),
            'Date' => $core->__('Date'),
            'Showing %1$d - %2$d of %3$d' => $core->__('Showing %1$d - %2$d of %3$d'),
            'Apply' => $core->__('Apply'),
            'Trash' => $core->__('Trash'),
            'Are you sure want to move to trash?' => $core->__('Are you sure want to move to trash?'),
            'Are you sure want to delete?' => $core->__('Are you sure want to delete?'),
            'Are you sure want to restore?' => $core->__('Are you sure want to restore?'),
            'Activate' => $core->__('Activate'),
            'Are you sure want to activate?' => $core->__('Are you sure want to activate?'),
            'Deactivate' => $core->__('Deactivate'),
            'Are you sure want to deactivate?' => $core->__('Are you sure want to deactivate?'),
            'Re-open' => $core->__('Re-open'),
            'Are you sure want to re-open?' => $core->__('Are you sure want to re-open?'),
            'Close' => $core->__('Close'),
            'Are you sure want to close?' => $core->__('Are you sure want to close?'),
            'Public' => $core->__('Public'),
            'Are you sure want to make public?' => $core->__('Are you sure want to make public?'),
            'Private' => $core->__('Private'),
            'Are you sure want to make private?' => $core->__('Are you sure want to make private?'),
            'by %s' => $core->__('by %s'),
            'Agent:' => $core->__('Agent:'),
            'Replied:' => $core->__('Replied:'),
            '%1$s at %2$s' => $core->__('%1$s at %2$s'),
            'Created:' => $core->__('Created:'),
            'Status' => $core->__('Status'),
            'Ticket Track ID' => $core->__('Ticket Track ID'),
            'Search User' => $core->__('Search User'),
            'Select User' => $core->__('Select User'),
            'Create User' => $core->__('Create User'),
            'Choose User' => $core->__('Choose User'),
            'First Name' => $core->__('First Name'),
            'First name' => $core->__('First name'),
            '%s is required.' => $core->__('%s is required.'),
            'Last Name' => $core->__('Last Name'),
            'Last name' => $core->__('Last name'),
            'Email Address' => $core->__('Email Address'),
            'Email address' => $core->__('Email address'),
            'Send the new user an email about their account.' => $core->__('Send the new user an email about their account.'),
            'Back' => $core->__('Back'),
            'Create' => $core->__('Create'),
            'User' => $core->__('User'),
            'Ticket User' => $core->__('Ticket User'),
            'Change User' => $core->__('Change User'),
            'Category' => $core->__('Category'),
            'Subject' => $core->__('Subject'),
            'Description' => $core->__('Description'),
            'Click or drag file to upload' => $core->__('Click or drag file to upload'),
            'Cancel' => $core->__('Cancel'),
            'Insert %s' => $core->__('Insert %s'),
            'Export Ticket' => $core->__('Export Ticket'),
            'Private Ticket' => $core->__('Private Ticket'),
            'Click to make it public.' => $core->__('Click to make it public.'),
            'Click to make it private.' => $core->__('Click to make it private.'),
            'Email notification' => $core->__('Email notification'),
            'Are you sure want to enable email notification to customer for this ticket?' => $core->__('Are you sure want to enable email notification to customer for this ticket?'),
            'Yes' => $core->__('Yes'),
            'No' => $core->__('No'),
            'Email notification to customer for this ticket.' => $core->__('Email notification to customer for this ticket.'),
            'Email notification.' => $core->__('Email notification.'),
            'Copy Hotlink' => $core->__('Copy Hotlink'),
            'Are you sure want to disable email notification to customer for this ticket?' => $core->__('Are you sure want to disable email notification to customer for this ticket?'),
            'Information' => $core->__('Information'),
            'Edit' => $core->__('Edit'),
            'Category:' => $core->__('Category:'),
            'N/A' => $core->__('N/A'),
            'Status:' => $core->__('Status:'),
            'Note' => $core->__('Note'),
            'Assigned on:' => $core->__('Assigned on:'),
            'Ticket Data' => $core->__('Ticket Data'),
            'Additional Data' => $core->__('Additional Data'),
            'Edit %s' => $core->__('Edit %s'),
            'Starter' => $core->__('Starter'),
            'Ticket Logs (%d)' => $core->__('Ticket Logs (%d)'),
            'Save Changes' => $core->__('Save Changes'),
            'Content' => $core->__('Content'),
            'Add Internal Note' => $core->__('Add Internal Note'),
            'Submit Reply' => $core->__('Submit Reply'),
            'Are you sure want to submit reply and close ticket?' => $core->__('Are you sure want to submit reply and close ticket?'),
            'Submit & Close Ticket' => $core->__('Submit & Close Ticket'),
            'Summary Data' => $core->__('Summary Data'),
            'Responses' => $core->__('Responses'),
            'Closed' => $core->__('Closed'),
            'Line chart' => $core->__('Line chart'),
            'Clear filters' => $core->__('Clear filters'),
            'Export' => $core->__('Export'),
            'Reload' => $core->__('Reload'),
            'Agents with this role will be limited to handling tickets in the selected categories only.' => $core->__('Agents with this role will be limited to handling tickets in the selected categories only.'),
            'This count represents the total number of tickets currently requiring a response, and is not constrained by the date range filter.' => $core->__('This count represents the total number of tickets currently requiring a response, and is not constrained by the date range filter.'),
            'This count reflects the total number of times tickets have been marked as closed.' => $core->__('This count reflects the total number of times tickets have been marked as closed.'),
            'These are tickets that have not yet been categorized.' => $core->__('These are tickets that have not yet been categorized.'),
            'These are tickets that have not yet been assigned.' => $core->__('These are tickets that have not yet been assigned.'),
            'Bar chart' => $core->__('Bar chart'),
            'General' => $core->__('General'),
            'User Roles' => $core->__('User Roles'),
            'Categories' => $core->__('Categories'),
            'Assign Rules' => $core->__('Assign Rules'),
            'Custom Fields' => $core->__('Custom Fields'),
            'Email to Ticket' => $core->__('Email to Ticket'),
            'Modern' => $core->__('Modern'),
            'Traditional' => $core->__('Traditional'),
            'Webhooks' => $core->__('Webhooks'),
            'Incoming' => $core->__('Incoming'),
            'Outgoing' => $core->__('Outgoing'),
            'Integrations' => $core->__('Integrations'),
            'WooCommerce' => $core->__('WooCommerce'),
            'EDD' => $core->__('EDD'),
            'FluentCRM' => $core->__('FluentCRM'),
            'WhatsApp' => $core->__('WhatsApp'),
            'Slack' => $core->__('Slack'),
            'Tutor LMS' => $core->__('Tutor LMS'),
            'BetterDocs' => $core->__('BetterDocs'),
            'Envato' => $core->__('Envato'),
            'Elite Licenser' => $core->__('Elite Licenser'),
            'Manage License' => $core->__('Manage License'),
            'Main' => $core->__('Main'),
            'Logo' => $core->__('Logo'),
            'File' => $core->__('File'),
            'reCAPTCHA (v3)' => $core->__('reCAPTCHA (v3)'),
            'Style' => $core->__('Style'),
            'Login with Envato' => $core->__('Login with Envato'),
            'Learn more' => $core->__('Learn more'),
            'Documentation' => $core->__('Documentation'),
            'Ticket Page' => $core->__('Ticket Page'),
            'Enable shortcode mode for ticket page.' => $core->__('Enable shortcode mode for ticket page.'),
            'Footer Copyright Text' => $core->__('Footer Copyright Text'),
            'Remove powered-by.' => $core->__('Remove powered-by.'),
            'Enable Wordpress Login Register.' => $core->__('Enable Wordpress Login Register.'),
            'Enable Wordpress Profile Link.' => $core->__('Enable Wordpress Profile Link.'),
            'Enable Sequential Ticket Track ID.' => $core->__('Enable Sequential Ticket Track ID.'),
            'Disable closed ticket reply.' => $core->__('Disable closed ticket reply.'),
            'Enable to show public tickets.' => $core->__('Enable to show public tickets.'),
            'Disable registration form.' => $core->__('Disable registration form.'),
            'Disable guest ticket creation.' => $core->__('Disable guest ticket creation.'),
            'Enable ticket close option for customer.' => $core->__('Enable ticket close option for customer.'),
            'Disable ticket hotlink (except guest ticket).' => $core->__('Disable ticket hotlink (except guest ticket).'),
            'Discard' => $core->__('Discard'),
            'Translatable' => $core->__('Translatable'),
            'Portal Icon' => $core->__('Portal Icon'),
            'Portal Logo' => $core->__('Portal Logo'),
            'Nothing selected' => $core->__('Nothing selected'),
            'Upload' => $core->__('Upload'),
            'Click to enable file upload and setup.' => $core->__('Click to enable file upload and setup.'),
            'Max file size' => $core->__('Max file size'),
            'Allowed File Types' => $core->__('Allowed File Types'),
            'Photos %s' => $core->__('Photos %s'),
            'Videos %s' => $core->__('Videos %s'),
            'Audios %s' => $core->__('Audios %s'),
            'Docs %s' => $core->__('Docs %s'),
            'Text %s' => $core->__('Text %s'),
            'CSV %s' => $core->__('CSV %s'),
            'PDF %s' => $core->__('PDF %s'),
            'Zip %s' => $core->__('Zip %s'),
            'JSON %s' => $core->__('JSON %s'),
            '3D Models %s' => $core->__('3D Models %s'),
            'Medical Images %s' => $core->__('Medical Images %s'),
            'Click to enable and setup.' => $core->__('Click to enable and setup.'),
            'Site Key' => $core->__('Site Key'),
            'Site key' => $core->__('Site key'),
            'Secret Key' => $core->__('Secret Key'),
            'Secret key' => $core->__('Secret key'),
            'Value containing any asterisk (*) will not be updated.' => $core->__('Value containing any asterisk (*) will not be updated.'),
            'Display Options' => $core->__('Display Options'),
            'Show in Login Form' => $core->__('Show in Login Form'),
            'Show in Ticket Form (If not logged in)' => $core->__('Show in Ticket Form (If not logged in)'),
            'Show in Registration Form' => $core->__('Show in Registration Form'),
            'Hide reCAPTCHA Badge.' => $core->__('Hide reCAPTCHA Badge.'),
            'New' => $core->__('New'),
            'Active' => $core->__('Active'),
            'Inactive' => $core->__('Inactive'),
            'In-progress' => $core->__('In-progress'),
            '%s (Status Label)' => $core->__('%s (Status Label)'),
            '%s (status label)' => $core->__('%s (status label)'),
            'Primary Brand Color' => $core->__('Primary Brand Color'),
            'primary' => $core->__('primary'),
            'secondary' => $core->__('secondary'),
            'Custom CSS' => $core->__('Custom CSS'),
            'Click to disable file upload.' => $core->__('Click to disable file upload.'),
            'Login Page Link' => $core->__('Login Page Link'),
            'Please enter a valid URL.' => $core->__('Please enter a valid URL.'),
            'Registration Link' => $core->__('Registration Link'),
            'WP Profile Link' => $core->__('WP Profile Link'),
            'Ticket track ID prefix' => $core->__('Ticket track ID prefix'),
            'Disable reply notice text' => $core->__('Disable reply notice text'),
            'Click to disable.' => $core->__('Click to disable.'),
            'Add New' => $core->__('Add New'),
            'User Role' => $core->__('User Role'),
            'ID' => $core->__('ID'),
            'Name' => $core->__('Name'),
            'Action' => $core->__('Action'),
            'Built-in' => $core->__('Built-in'),
            'Support Agent or Manager.' => $core->__('Support Agent or Manager.'),
            'Capabilities' => $core->__('Capabilities'),
            'All Capabilities' => $core->__('All Capabilities'),
            'Manager' => $core->__('Manager'),
            'Preset' => $core->__('Preset'),
            'Assign me' => $core->__('Assign me'),
            'Ticket reply' => $core->__('Ticket reply'),
            'Manage unassigned tickets' => $core->__('Manage unassigned tickets'),
            'Manage other agent\'s tickets' => $core->__('Manage other agent\'s tickets'),
            'Manage self created tickets' => $core->__('Manage self created tickets'),
            'Closed ticket list' => $core->__('Closed ticket list'),
            'Ticket Details' => $core->__('Ticket Details'),
            'Change status' => $core->__('Change status'),
            'Change privacy' => $core->__('Change privacy'),
            'Assign agent' => $core->__('Assign agent'),
            'Change category' => $core->__('Change category'),
            'Move to trash' => $core->__('Move to trash'),
            'Create note' => $core->__('Create note'),
            'Edit custom field value' => $core->__('Edit custom field value'),
            'Show ticket user email' => $core->__('Show ticket user email'),
            'Show ticket hotlink' => $core->__('Show ticket hotlink'),
            'Trashed Ticket' => $core->__('Trashed Ticket'),
            'Trashed ticket list' => $core->__('Trashed ticket list'),
            'Restore ticket' => $core->__('Restore ticket'),
            'Delete ticket' => $core->__('Delete ticket'),
            'Edit order source' => $core->__('Edit order source'),
            'Edit Purchase Code' => $core->__('Edit Purchase Code'),
            'Update' => $core->__('Update'),
            'Parent Category' => $core->__('Parent Category'),
            'Assign Rule' => $core->__('Assign Rule'),
            'Rule Type' => $core->__('Rule Type'),
            'Assign to role' => $core->__('Assign to role'),
            'Assign to agent' => $core->__('Assign to agent'),
            'Rule type' => $core->__('Rule type'),
            'Notify to agent' => $core->__('Notify to agent'),
            'Select Role' => $core->__('Select Role'),
            'Role' => $core->__('Role'),
            'Choose Category' => $core->__('Choose Category'),
            'Ticket Created' => $core->__('Ticket Created'),
            'Ticket Replied' => $core->__('Ticket Replied'),
            'Ticket Assigned' => $core->__('Ticket Assigned'),
            'Ticket Closed' => $core->__('Ticket Closed'),
            'Admin or Agent' => $core->__('Admin or Agent'),
            'Customer (Ticket Portal)' => $core->__('Customer (Ticket Portal)'),
            'Customer (Email to Ticket)' => $core->__('Customer (Email to Ticket)'),
            'Recipient' => $core->__('Recipient'),
            'Saved Reply' => $core->__('Saved Reply'),
            'Custom Field' => $core->__('Custom Field'),
            'Label' => $core->__('Label'),
            'Slug' => $core->__('Slug'),
            'Type' => $core->__('Type'),
            'Field Type' => $core->__('Field Type'),
            'Field type' => $core->__('Field type'),
            'Textbox' => $core->__('Textbox'),
            'Numeric' => $core->__('Numeric'),
            'Switch' => $core->__('Switch'),
            'Radio' => $core->__('Radio'),
            'Dropdown' => $core->__('Dropdown'),
            'Instruction Text' => $core->__('Instruction Text'),
            'URL Input' => $core->__('URL Input'),
            'Field Label' => $core->__('Field Label'),
            'Field label' => $core->__('Field label'),
            'Field Slug' => $core->__('Field Slug'),
            'Field slug' => $core->__('Field slug'),
            'Placeholder' => $core->__('Placeholder'),
            'Form Options' => $core->__('Form Options'),
            'Required Field' => $core->__('Required Field'),
            'Half Field' => $core->__('Half Field'),
            'Create Where' => $core->__('Create Where'),
            'Ticket Form' => $core->__('Ticket Form'),
            'Registration Form' => $core->__('Registration Form'),
            'Create For' => $core->__('Create For'),
            'Admin Only' => $core->__('Admin Only'),
            'Both (Clients & Admin)' => $core->__('Both (Clients & Admin)'),
            'Field Options' => $core->__('Field Options'),
            'Comma-separated options (example: Option A, Option B, Option C).' => $core->__('Comma-separated options (example: Option A, Option B, Option C).'),
            'Mailboxes (Modern)' => $core->__('Mailboxes (Modern)'),
            'Mailbox' => $core->__('Mailbox'),
            'Mailboxes (Traditional)' => $core->__('Mailboxes (Traditional)'),
            'The mailbox address will be generated here automatically!' => $core->__('The mailbox address will be generated here automatically!'),
            'Connected Email Address' => $core->__('Connected Email Address'),
            'Connected email address' => $core->__('Connected email address'),
            'Please enter a valid email.' => $core->__('Please enter a valid email.'),
            'I agree with the Support Genix email to ticket %sterms and conditions%s.' => $core->__('I agree with the Support Genix email to ticket %sterms and conditions%s.'),
            'Please make sure that support emails from connected address are forwared to mailbox address.' => $core->__('Please make sure that support emails from connected address are forwared to mailbox address.'),
            'Address:' => $core->__('Address:'),
            'Connected:' => $core->__('Connected:'),
            'Host' => $core->__('Host'),
            'Port' => $core->__('Port'),
            'User Email' => $core->__('User Email'),
            'User email' => $core->__('User email'),
            'User Password' => $core->__('User Password'),
            'User password' => $core->__('User password'),
            'Secure protocol (SSL/TLS).' => $core->__('Secure protocol (SSL/TLS).'),
            'Secure Protocol Type' => $core->__('Secure Protocol Type'),
            'Mailboxes Settings' => $core->__('Mailboxes Settings'),
            'Cron Job Command' => $core->__('Cron Job Command'),
            'or' => $core->__('or'),
            'Email Reply Start Text' => $core->__('Email Reply Start Text'),
            'Incoming Webhooks' => $core->__('Incoming Webhooks'),
            'Incoming Webhook' => $core->__('Incoming Webhook'),
            'Secret' => $core->__('Secret'),
            'The incoming webhook URL will be generated here automatically!' => $core->__('The incoming webhook URL will be generated here automatically!'),
            'Field' => $core->__('Field'),
            'Email' => $core->__('Email'),
            'Required' => $core->__('Required'),
            'Ticket Subject' => $core->__('Ticket Subject'),
            'Text' => $core->__('Text'),
            'Ticket Description' => $core->__('Ticket Description'),
            'User First Name' => $core->__('User First Name'),
            'Optional' => $core->__('Optional'),
            'User Last Name' => $core->__('User Last Name'),
            'Ticket Category ID' => $core->__('Ticket Category ID'),
            'Number' => $core->__('Number'),
            'Ticket Attachment(s)' => $core->__('Ticket Attachment(s)'),
            'URL' => $core->__('URL'),
            'URLs array (or comma separated URLs)' => $core->__('URLs array (or comma separated URLs)'),
            'WooCommerce Store ID' => $core->__('WooCommerce Store ID'),
            'If enabled' => $core->__('If enabled'),
            'WooCommerce Order ID' => $core->__('WooCommerce Order ID'),
            'Envato Purchase Code' => $core->__('Envato Purchase Code'),
            'Elite Licenser Purchase Code' => $core->__('Elite Licenser Purchase Code'),
            'Ticket Custom Fields' => $core->__('Ticket Custom Fields'),
            'Based on settings' => $core->__('Based on settings'),
            'Outgoing Webhooks' => $core->__('Outgoing Webhooks'),
            'Outgoing Webhook' => $core->__('Outgoing Webhook'),
            'Events' => $core->__('Events'),
            'Remote URL' => $core->__('Remote URL'),
            'Trigger Events' => $core->__('Trigger Events'),
            'On Ticket Creation' => $core->__('On Ticket Creation'),
            'On Ticket Replied' => $core->__('On Ticket Replied'),
            'On Client Creation' => $core->__('On Client Creation'),
            'WooCommerce Integration' => $core->__('WooCommerce Integration'),
            'WooCommerce Integrations' => $core->__('WooCommerce Integrations'),
            'Store' => $core->__('Store'),
            'WooCommerce Integrations Settings' => $core->__('WooCommerce Integrations Settings'),
            'Get Support' => $core->__('Get Support'),
            'Order Info Required' => $core->__('Order Info Required'),
            'Show in Ticket Form' => $core->__('Show in Ticket Form'),
            'Show support menu in my account page.' => $core->__('Show support menu in my account page.'),
            'Menu Title' => $core->__('Menu Title'),
            'Menu title' => $core->__('Menu title'),
            'Order #{{order_id}} has been placed by {{user_full_name}} at {{store_title}}' => $core->__('Order #{{order_id}} has been placed by {{user_full_name}} at {{store_title}}'),
            'A new Order #{{order_id}} has been placed by {{user_full_name}} in your store {{store_title}}.' => $core->__('A new Order #{{order_id}} has been placed by {{user_full_name}} in your store {{store_title}}.'),
            'Collect order info from customer.' => $core->__('Collect order info from customer.'),
            'When disabled, customer won\'t need to provide order info. Agents will automatically see the customer\'s latest orders.' => $core->__('When disabled, customer won\'t need to provide order info. Agents will automatically see the customer\'s latest orders.'),
            'WooCommerce Orders (%d)' => $core->__('WooCommerce Orders (%d)'),
            'WooCommerce in same site' => $core->__('WooCommerce in same site'),
            'WooCommerce in external site' => $core->__('WooCommerce in external site'),
            'Store Title' => $core->__('Store Title'),
            'Store title' => $core->__('Store title'),
            'Disallow Options' => $core->__('Disallow Options'),
            'Disallow cancelled order ID' => $core->__('Disallow cancelled order ID'),
            'Disallow refunded order ID' => $core->__('Disallow refunded order ID'),
            'Verify Options' => $core->__('Verify Options'),
            'Verify customer email address' => $core->__('Verify customer email address'),
            'Verify external store SSL' => $core->__('Verify external store SSL'),
            'Auto-create ticket on new order.' => $core->__('Auto-create ticket on new order.'),
            'Ticket Category' => $core->__('Ticket Category'),
            'Ticket subject' => $core->__('Ticket subject'),
            'Available placeholders: {{store_id}}, {{store_title}}, {{store_url}}, {{order_id}}, {{user_email}}, {{user_first_name}}, {{user_last_name}} and {{user_full_name}}.' => $core->__('Available placeholders: {{store_id}}, {{store_title}}, {{store_url}}, {{order_id}}, {{user_email}}, {{user_first_name}}, {{user_last_name}} and {{user_full_name}}.'),
            'Ticket description' => $core->__('Ticket description'),
            'Store URL' => $core->__('Store URL'),
            'Home URL of the store (example: https://example.com).' => $core->__('Home URL of the store (example: https://example.com).'),
            'API Consumer Key' => $core->__('API Consumer Key'),
            'API consumer key' => $core->__('API consumer key'),
            'API Consumer Secret' => $core->__('API Consumer Secret'),
            'API consumer secret' => $core->__('API consumer secret'),
            'Edd Integrations' => $core->__('Edd Integrations'),
            'Edd Integration' => $core->__('Edd Integration'),
            'Site' => $core->__('Site'),
            'EDD in same site' => $core->__('EDD in same site'),
            'EDD in external site' => $core->__('EDD in external site'),
            'Show order details button.' => $core->__('Show order details button.'),
            'API Endpoint' => $core->__('API Endpoint'),
            'API endpoint' => $core->__('API endpoint'),
            'API endpoint URL (example: https://example.com/edd-api/)' => $core->__('API endpoint URL (example: https://example.com/edd-api/)'),
            'API Public Key' => $core->__('API Public Key'),
            'API public key' => $core->__('API public key'),
            'API Token' => $core->__('API Token'),
            'API token' => $core->__('API token'),
            'Admin URL' => $core->__('Admin URL'),
            'FluentCRM Integrations' => $core->__('FluentCRM Integrations'),
            'FluentCRM Integration' => $core->__('FluentCRM Integration'),
            'FluentCRM in same site' => $core->__('FluentCRM in same site'),
            'FluentCRM in external site' => $core->__('FluentCRM in external site'),
            'List IDs' => $core->__('List IDs'),
            'Comma-separated IDs of list (example: 1,2,3,4).' => $core->__('Comma-separated IDs of list (example: 1,2,3,4).'),
            'Tag IDs' => $core->__('Tag IDs'),
            'Comma-separated IDs of tag (example: 1,2,3,4).' => $core->__('Comma-separated IDs of tag (example: 1,2,3,4).'),
            'Contact status' => $core->__('Contact status'),
            'Pending' => $core->__('Pending'),
            'Subscribed' => $core->__('Subscribed'),
            'Unsubscribed' => $core->__('Unsubscribed'),
            'Webhook URL' => $core->__('Webhook URL'),
            '%s Integration' => $core->__('%s Integration'),
            'Twilio Account SID' => $core->__('Twilio Account SID'),
            'Twilio Auth Token' => $core->__('Twilio Auth Token'),
            'Twilio WhatsApp Number' => $core->__('Twilio WhatsApp Number'),
            'Twilio WhatsApp number' => $core->__('Twilio WhatsApp number'),
            'Notification Events' => $core->__('Notification Events'),
            'Response from WhatsApp.' => $core->__('Response from WhatsApp.'),
            'Please use this URL into your Twilio settings to enable your agent to respond to tickets via WhatsApp.' => $core->__('Please use this URL into your Twilio settings to enable your agent to respond to tickets via WhatsApp.'),
            'Slack Bot User OAuth Token' => $core->__('Slack Bot User OAuth Token'),
            'Slack Channel Name' => $core->__('Slack Channel Name'),
            'Slack Channel name' => $core->__('Slack Channel name'),
            'Slack Channel ID' => $core->__('Slack Channel ID'),
            'Response from Slack.' => $core->__('Response from Slack.'),
            'Please use this URL into your Slack settings to enable your agent to respond to tickets via Slack.' => $core->__('Please use this URL into your Slack settings to enable your agent to respond to tickets via Slack.'),
            'Click to enable.' => $core->__('Click to enable.'),
            'Suggested Docs Heading' => $core->__('Suggested Docs Heading'),
            'Suggested Docs heading' => $core->__('Suggested Docs heading'),
            'Number of Suggested Docs' => $core->__('Number of Suggested Docs'),
            'Enter a value between 1 and 20.' => $core->__('Enter a value between 1 and 20.'),
            'Envato API Token' => $core->__('Envato API Token'),
            'Envato API token' => $core->__('Envato API token'),
            'License Required' => $core->__('License Required'),
            'Check Support Expiry' => $core->__('Check Support Expiry'),
            'Please use this Confirmation URL while Register Envato App.' => $core->__('Please use this Confirmation URL while Register Envato App.'),
            'Loading...' => $core->__('Loading...'),
            'Envato Username' => $core->__('Envato Username'),
            'Envato username' => $core->__('Envato username'),
            'App Client ID' => $core->__('App Client ID'),
            'App client ID' => $core->__('App client ID'),
            'App Client Secret' => $core->__('App Client Secret'),
            'App client secret' => $core->__('App client secret'),
            'API endpoint URL (example: https://example.com/wp-json/licensor/).' => $core->__('API endpoint URL (example: https://example.com/wp-json/licensor/).'),
            'API Key' => $core->__('API Key'),
            'API key' => $core->__('API key'),
            'Enable cache response.' => $core->__('Enable cache response.'),
            'If you enable this, license code checking request will be cache for 5 minutes.' => $core->__('If you enable this, license code checking request will be cache for 5 minutes.'),
            'License Code' => $core->__('License Code'),
            'License code' => $core->__('License code'),
            'Activate License' => $core->__('Activate License'),
            'License Status' => $core->__('License Status'),
            'Valid' => $core->__('Valid'),
            'License Type' => $core->__('License Type'),
            'License Expired on' => $core->__('License Expired on'),
            'Support Expired on' => $core->__('Support Expired on'),
            'Your License Key' => $core->__('Your License Key'),
            'Are you sure want to deactivate license?' => $core->__('Are you sure want to deactivate license?'),
            'Deactivate License' => $core->__('Deactivate License'),
            'Deactivate license' => $core->__('Deactivate license'),
            'Order' => $core->__('Order'),
            'Order Up' => $core->__('Order Up'),
            'Are you sure want to change order?' => $core->__('Are you sure want to change order?'),
            'Order Down' => $core->__('Order Down'),
            'Slug:' => $core->__('Slug:'),
            'Type:' => $core->__('Type:'),
            '%s:' => $core->__('%s:'),
            'This field is required.' => $core->__('This field is required.'),
            'Are you sure want to reset order?' => $core->__('Are you sure want to reset order?'),
            'Reset Order' => $core->__('Reset Order'),
            'Same-site' => $core->__('Same-site'),
            'Select' => $core->__('Select'),
            'Categories:' => $core->__('Categories:'),
            'Reply and close ticket' => $core->__('Reply and close ticket'),
            'Assign Agent' => $core->__('Assign Agent'),
            'Set Category' => $core->__('Set Category'),
            'Set Status' => $core->__('Set Status'),
            'Select Agent' => $core->__('Select Agent'),
            'Select Status' => $core->__('Select Status'),
            'Pro Edition' => $core->__('Pro Edition'),
            'Performance Insights' => $core->__('Performance Insights'),
            'Weekend & Holiday' => $core->__('Weekend & Holiday'),
            'Weekend' => $core->__('Weekend'),
            'Holiday' => $core->__('Holiday'),
            'Other Tickets (%d)' => $core->__('Other Tickets (%d)'),
            'Host:' => $core->__('Host:'),
            'Email:' => $core->__('Email:'),
            'Report Schedule' => $core->__('Report Schedule'),
            'Report schedule' => $core->__('Report schedule'),
            'Custom Minutes' => $core->__('Custom Minutes'),
            'Hourly' => $core->__('Hourly'),
            'Daily' => $core->__('Daily'),
            'Weekly' => $core->__('Weekly'),
            'Monthly' => $core->__('Monthly'),
            'Time' => $core->__('Time'),
            'Recipients' => $core->__('Recipients'),
            'Comma separated email addresses.' => $core->__('Comma separated email addresses.'),
            'Custom minutes' => $core->__('Custom minutes'),
            'Enter a value between 5 and 60.' => $core->__('Enter a value between 5 and 60.'),
            'Day of Week' => $core->__('Day of Week'),
            'Day of week' => $core->__('Day of week'),
            'Monday' => $core->__('Monday'),
            'Tuesday' => $core->__('Tuesday'),
            'Wednesday' => $core->__('Wednesday'),
            'Thursday' => $core->__('Thursday'),
            'Friday' => $core->__('Friday'),
            'Saturday' => $core->__('Saturday'),
            'Sunday' => $core->__('Sunday'),
            'Day of Month' => $core->__('Day of Month'),
            'Day of month' => $core->__('Day of month'),
            'Enter a value between 1 and 31.' => $core->__('Enter a value between 1 and 31.'),
            'Please note that our support team is currently out of office for the weekend. While you\'re welcome to submit your ticket, it will be reviewed when we return on the next business day. We appreciate your patience and will address your inquiry as soon as possible.' => $core->__('Please note that our support team is currently out of office for the weekend. While you\'re welcome to submit your ticket, it will be reviewed when we return on the next business day. We appreciate your patience and will address your inquiry as soon as possible.'),
            'Weekend Days' => $core->__('Weekend Days'),
            'Enable portal notice.' => $core->__('Enable portal notice.'),
            'Enable email notification.' => $core->__('Enable email notification.'),
            'Day' => $core->__('Day'),
            'Time range' => $core->__('Time range'),
            'Add More' => $core->__('Add More'),
            'Our office is currently closed for the holiday. While you\'re welcome to submit your ticket, please be aware that our team will review it when we return to the office. We thank you for your understanding and will respond to your request promptly upon our return.' => $core->__('Our office is currently closed for the holiday. While you\'re welcome to submit your ticket, please be aware that our team will review it when we return to the office. We thank you for your understanding and will respond to your request promptly upon our return.'),
            'Date Ranges' => $core->__('Date Ranges'),
            'Date range' => $core->__('Date range'),
            'Portal Notice Content' => $core->__('Portal Notice Content'),
            'Portal notice content' => $core->__('Portal notice content'),
            'Email Notification Content' => $core->__('Email Notification Content'),
            'Email notification content' => $core->__('Email notification content'),
            'Saved reply inserted.' => $core->__('Saved reply inserted.'),
            'System Timezone: %s' => $core->__('System Timezone: %s'),
            'All' => $core->__('All'),
            'All Tags' => $core->__('All Tags'),
            '%d Category' => $core->__('%d Category'),
            '%d Tag' => $core->__('%d Tag'),
            '%d Agent' => $core->__('%d Agent'),
            'Security' => $core->__('Security'),
            'Tags' => $core->__('Tags'),
            'Email Notifications' => $core->__('Email Notifications'),
            'Login Systems' => $core->__('Login Systems'),
            'Login with Google' => $core->__('Login with Google'),
            'Tag' => $core->__('Tag'),
            'Please use this Confirmation URL while Register Google App.' => $core->__('Please use this Confirmation URL while Register Google App.'),
            'Tag:' => $core->__('Tag:'),
            'Select Tag' => $core->__('Select Tag'),
            'Set Tag' => $core->__('Set Tag'),
            'Tags:' => $core->__('Tags:'),
            '%d Tags' => $core->__('%d Tags'),
            'Please ensure you add the shortcode %s to your designated ticket page for proper functionality.' => $core->__('Please ensure you add the shortcode %s to your designated ticket page for proper functionality.'),
            'Days' => $core->__('Days'),
            'Create ticket' => $core->__('Create ticket'),
            'Create ticket user' => $core->__('Create ticket user'),
            'Change ticket user' => $core->__('Change ticket user'),
            'Email Notification' => $core->__('Email Notification'),
            'On Ticket Closed' => $core->__('On Ticket Closed'),
            'Change' => $core->__('Change'),
            'Feature' => $core->__('Feature'),
            'Free' => $core->__('Free'),
            'Pro' => $core->__('Pro'),
            'EDD Integration' => $core->__('EDD Integration'),
            'WhatsApp Integration' => $core->__('WhatsApp Integration'),
            'Slack Integration' => $core->__('Slack Integration'),
            'Tutor LMS Integration' => $core->__('Tutor LMS Integration'),
            'BetterDocs Integration' => $core->__('BetterDocs Integration'),
            'Elite Licenser Integration' => $core->__('Elite Licenser Integration'),
            'Weekend & Holiday Settings' => $core->__('Weekend & Holiday Settings'),
            'Style Customization' => $core->__('Style Customization'),
            'Report' => $core->__('Report'),
            'Ticket Hotlink' => $core->__('Ticket Hotlink'),
            'Auto Close Ticket' => $core->__('Auto Close Ticket'),
            'Unlimited Tickets' => $core->__('Unlimited Tickets'),
            'Unlimited Agents' => $core->__('Unlimited Agents'),
            'Unlimited Customers/Clients' => $core->__('Unlimited Customers/Clients'),
            'Tickets on Behalf of Customers' => $core->__('Tickets on Behalf of Customers'),
            'Google reCAPTCHA (v3)' => $core->__('Google reCAPTCHA (v3)'),
            'User Roles & Permissions' => $core->__('User Roles & Permissions'),
            'Envato Integration' => $core->__('Envato Integration'),
            'Welcome' => $core->__('Welcome'),
            'Finished' => $core->__('Finished'),
            'Welcome to Support Genix' => $core->__('Welcome to Support Genix'),
            'Thank you for choosing Support Genix, your powerful solution for managing support tickets with ease! This quick setup wizard will guide you through the basic configuration.' => $core->__('Thank you for choosing Support Genix, your powerful solution for managing support tickets with ease! This quick setup wizard will guide you through the basic configuration.'),
            'Enter Your License Key' => $core->__('Enter Your License Key'),
            'Please enter your license key to activate.' => $core->__('Please enter your license key to activate.'),
            'Skip Setup' => $core->__('Skip Setup'),
            'Activate & Continue' => $core->__('Activate & Continue'),
            'Create %s' => $core->__('Create %s'),
            'Just enter title of the categories and click save & next!' => $core->__('Just enter title of the categories and click save & next!'),
            'Skip' => $core->__('Skip'),
            'Save & Next' => $core->__('Save & Next'),
            'Add More Category' => $core->__('Add More Category'),
            'Just enter title of the tags and click save & next!' => $core->__('Just enter title of the tags and click save & next!'),
            'Add More Tag' => $core->__('Add More Tag'),
            'Your setup has been successfully completed!' => $core->__('Your setup has been successfully completed!'),
            'Go to Tickets' => $core->__('Go to Tickets'),
            'Category %s' => $core->__('Category %s'),
            'EG: %s' => $core->__('EG: %s'),
            'Tag %s' => $core->__('Tag %s'),
            'License Activated & Valid' => $core->__('License Activated & Valid'),
            'You can manage your license settings after completing the setup.' => $core->__('You can manage your license settings after completing the setup.'),
            'Continue Setup' => $core->__('Continue Setup'),
            'Go Pro' => $core->__('Go Pro'),
            'Support Genix is a powerful, user-friendly help desk plugin, designed to simplify customer support management. Manage support tickets in one place, boost productivity, and enhance customer satisfaction effortlessly with this comprehensive customer ticketing system.' => $core->__('Support Genix is a powerful, user-friendly help desk plugin, designed to simplify customer support management. Manage support tickets in one place, boost productivity, and enhance customer satisfaction effortlessly with this comprehensive customer ticketing system.'),
            'Upgrade to Pro Version' => $core->__('Upgrade to Pro Version'),
            'Discover all the premium features Support Genix offers and see how it can transform your business.' => $core->__('Discover all the premium features Support Genix offers and see how it can transform your business.'),
            'Get Started' => $core->__('Get Started'),
            'Unlock Premium Features' => $core->__('Unlock Premium Features'),
            'Take your experience to the next level with our Pro features' => $core->__('Take your experience to the next level with our Pro features'),
            'Upgrade Now' => $core->__('Upgrade Now'),
            'Disable email to ticket creation for non-registered user.' => $core->__('Disable email to ticket creation for non-registered user.'),
            'Enable rich HTML for email to ticket content.' => $core->__('Enable rich HTML for email to ticket content.'),
            'Disable chatbot ticket creation for non-registered user.' => $core->__('Disable chatbot ticket creation for non-registered user.'),
            'Text Editor' => $core->__('Text Editor'),
            'Reset' => $core->__('Reset'),
            'Agents:' => $core->__('Agents:'),
            'Public Ticket' => $core->__('Public Ticket'),
            'Create Ticket' => $core->__('Create Ticket'),
            'Uncategorized' => $core->__('Uncategorized'),
            'Participant' => $core->__('Participant'),
            'Support Tickets' => $core->__('Support Tickets'),
            'Docs' => $core->__('Docs'),
            'Analytics' => $core->__('Analytics'),
            'Chat History' => $core->__('Chat History'),
            'Configuration' => $core->__('Configuration'),
            'Knowledge Base' => $core->__('Knowledge Base'),
            'License' => $core->__('License'),
            'Quick Reply' => $core->__('Quick Reply'),
            'Quick Note' => $core->__('Quick Note'),
            'Duplicate' => $core->__('Duplicate'),
            'Are you sure want to duplicate?' => $core->__('Are you sure want to duplicate?'),
            'Last 30 Days' => $core->__('Last 30 Days'),
            'Last 14 Days' => $core->__('Last 14 Days'),
            'Last 7 Days' => $core->__('Last 7 Days'),
            'Keyword' => $core->__('Keyword'),
            'Count' => $core->__('Count'),
            'All Statuses' => $core->__('All Statuses'),
            'Published' => $core->__('Published'),
            'Scheduled' => $core->__('Scheduled'),
            'Draft' => $core->__('Draft'),
            'Add Docs' => $core->__('Add Docs'),
            'All Docs' => $core->__('All Docs'),
            'List View' => $core->__('List View'),
            'Hide Empty' => $core->__('Hide Empty'),
            'All Authors' => $core->__('All Authors'),
            '%d Author' => $core->__('%d Author'),
            '%d Docs' => $core->__('%d Docs'),
            'These are docs that have not yet been categorized.' => $core->__('These are docs that have not yet been categorized.'),
            'Author' => $core->__('Author'),
            'Views' => $core->__('Views'),
            'Reactions' => $core->__('Reactions'),
            'Actions' => $core->__('Actions'),
            'Group View' => $core->__('Group View'),
            'Classic UI' => $core->__('Classic UI'),
            'Open in WordPress classic interface' => $core->__('Open in WordPress classic interface'),
            'Sort: Added Date (Newest First)' => $core->__('Sort: Added Date (Newest First)'),
            'Sort: Added Date (Oldest First)' => $core->__('Sort: Added Date (Oldest First)'),
            'Sort: Modified Date (Newest First)' => $core->__('Sort: Modified Date (Newest First)'),
            'Sort: Modified Date (Oldest First)' => $core->__('Sort: Modified Date (Oldest First)'),
            'Positive: %d' => $core->__('Positive: %d'),
            'Negative: %d' => $core->__('Negative: %d'),
            'Neutral: %d' => $core->__('Neutral: %d'),
            'Updated:' => $core->__('Updated:'),
            'View' => $core->__('View'),
            'Total Views' => $core->__('Total Views'),
            'vs previous period' => $core->__('vs previous period'),
            'Unique' => $core->__('Unique'),
            'Returning' => $core->__('Returning'),
            'Previous' => $core->__('Previous'),
            'Satisfaction Rate' => $core->__('Satisfaction Rate'),
            'Based on %d reactions' => $core->__('Based on %d reactions'),
            'Positive' => $core->__('Positive'),
            'Negative' => $core->__('Negative'),
            'Neutral' => $core->__('Neutral'),
            'Search performance are filtered by the selected date range, but not by category, tag, or author.' => $core->__('Search performance are filtered by the selected date range, but not by category, tag, or author.'),
            'Search Performance' => $core->__('Search Performance'),
            'Search success rate' => $core->__('Search success rate'),
            'Searches' => $core->__('Searches'),
            'With Results' => $core->__('With Results'),
            'No Results' => $core->__('No Results'),
            'Score' => $core->__('Score'),
            'Top Performing Docs' => $core->__('Top Performing Docs'),
            'Top search queries are filtered by the selected date range, but not by category, tag, or author.' => $core->__('Top search queries are filtered by the selected date range, but not by category, tag, or author.'),
            'Top Search Queries' => $core->__('Top Search Queries'),
            'No result search queries are filtered by the selected date range, but not by category, tag, or author.' => $core->__('No result search queries are filtered by the selected date range, but not by category, tag, or author.'),
            'No Result Search Queries' => $core->__('No Result Search Queries'),
            'Permissions' => $core->__('Permissions'),
            'Design Layout' => $core->__('Design Layout'),
            'Base' => $core->__('Base'),
            'Archive' => $core->__('Archive'),
            'Single' => $core->__('Single'),
            'AI Docs Writer' => $core->__('AI Docs Writer'),
            'AI Chatbot' => $core->__('AI Chatbot'),
            'OpenAI' => $core->__('OpenAI'),
            'Claude' => $core->__('Claude'),
            'Enable Docs Archive.' => $core->__('Enable Docs Archive.'),
            'Docs Single Slug' => $core->__('Docs Single Slug'),
            'Available tags:' => $core->__('Available tags:'),
            'Category Base' => $core->__('Category Base'),
            'Tag Base' => $core->__('Tag Base'),
            'Docs Archive Slug' => $core->__('Docs Archive Slug'),
            'Can Write Docs' => $core->__('Can Write Docs'),
            'Can write docs' => $core->__('Can write docs'),
            'Can Access Analytics' => $core->__('Can Access Analytics'),
            'Can access analytics' => $core->__('Can access analytics'),
            'Can Access Configuration' => $core->__('Can Access Configuration'),
            'Can access configuration' => $core->__('Can access configuration'),
            'Track Analytics For' => $core->__('Track Analytics For'),
            'Track analytics for' => $core->__('Track analytics for'),
            'Everyone' => $core->__('Everyone'),
            'Guest users only' => $core->__('Guest users only'),
            'Logged-in users only' => $core->__('Logged-in users only'),
            'Track Analytics For Roles' => $core->__('Track Analytics For Roles'),
            'Track analytics for roles' => $core->__('Track analytics for roles'),
            'Icon' => $core->__('Icon'),
            'Parent' => $core->__('Parent'),
            'Color' => $core->__('Color'),
            'Doc' => $core->__('Doc'),
            'Masonry' => $core->__('Masonry'),
            'Grid' => $core->__('Grid'),
            'List' => $core->__('List'),
            'Number of Columns' => $core->__('Number of Columns'),
            'Enter a value between 2 and 4. Default is 3.' => $core->__('Enter a value between 2 and 4. Default is 3.'),
            'Default' => $core->__('Default'),
            'None' => $core->__('None'),
            'Docs orderby' => $core->__('Docs orderby'),
            'Created date' => $core->__('Created date'),
            'Modified date' => $core->__('Modified date'),
            'Random' => $core->__('Random'),
            'Comment count' => $core->__('Comment count'),
            'Docs layout' => $core->__('Docs layout'),
            'Docs per page' => $core->__('Docs per page'),
            'Docs order' => $core->__('Docs order'),
            'Ascending' => $core->__('Ascending'),
            'descending' => $core->__('descending'),
            'Breadcrumb.' => $core->__('Breadcrumb.'),
            'Doc title.' => $core->__('Doc title.'),
            'Tags.' => $core->__('Tags.'),
            'Thumbnail.' => $core->__('Thumbnail.'),
            'Reaction.' => $core->__('Reaction.'),
            'Modified date.' => $core->__('Modified date.'),
            'Image lightbox.' => $core->__('Image lightbox.'),
            'Comment.' => $core->__('Comment.'),
            'AI Docs Writer' => $core->__('AI Docs Writer'),
            'Set Max Tokens' => $core->__('Set Max Tokens'),
            'Set max token' => $core->__('Set max token'),
            'AI Tool' => $core->__('AI Tool'),
            'AI tool' => $core->__('AI tool'),
            'Show in Whole Site' => $core->__('Show in Whole Site'),
            'Show in Ticket Page' => $core->__('Show in Ticket Page'),
            'Hello! How can I help you today?' => $core->__('Hello! How can I help you today?'),
            'Was this answer helpful?' => $core->__('Was this answer helpful?'),
            'Thank you for your feedback.' => $core->__('Thank you for your feedback.'),
            'Thank you for your feedback!' => $core->__('Thank you for your feedback!'),
            'Related documents:' => $core->__('Related documents:'),
            'Contact Support for Help' => $core->__('Contact Support for Help'),
            'Ask a question...' => $core->__('Ask a question...'),
            'Nothing matched your query!' => $core->__('Nothing matched your query!'),
            'Sorry, I encountered an error!' => $core->__('Sorry, I encountered an error!'),
            'Chatbot Title' => $core->__('Chatbot Title'),
            'Chatbot title' => $core->__('Chatbot title'),
            'Welcome Message' => $core->__('Welcome Message'),
            'Welcome message' => $core->__('Welcome message'),
            'Feedback Message' => $core->__('Feedback Message'),
            'Feedback message' => $core->__('Feedback message'),
            'Helpful Response Message' => $core->__('Helpful Response Message'),
            'Helpful response message' => $core->__('Helpful response message'),
            'Not Helpful Response Message' => $core->__('Not Helpful Response Message'),
            'Not helpful response message' => $core->__('Not helpful response message'),
            'Related Docs Title' => $core->__('Related Docs Title'),
            'Related docs title' => $core->__('Related docs title'),
            'Create Ticket Link Text' => $core->__('Create Ticket Link Text'),
            'Create ticket link text' => $core->__('Create ticket link text'),
            'Input Placeholder' => $core->__('Input Placeholder'),
            'Input placeholder' => $core->__('Input placeholder'),
            'Nothing Found Message' => $core->__('Nothing Found Message'),
            'Nothing found message' => $core->__('Nothing found message'),
            'Error Message' => $core->__('Error Message'),
            'Error message' => $core->__('Error message'),
            'Primary Color' => $core->__('Primary Color'),
            'Enable popup for view photos.' => $core->__('Enable popup for view photos.'),
            'Enable attachments preview.' => $core->__('Enable attachments preview.'),
            'Migrate' => $core->__('Migrate'),
            'Are you sure want to migrate?' => $core->__('Are you sure want to migrate?'),
            'Docs Suggestions' => $core->__('Docs Suggestions'),
            'Migrations' => $core->__('Migrations'),
            'Visit:' => $core->__('Visit:'),
            'Docs Archive' => $core->__('Docs Archive'),
            'Please confirm before proceeding.' => $core->__('Please confirm before proceeding.'),
            "Once you migrate from %s to %s, all your docs will be fully transferred (your post slugs for docs will remain the same). It will transfer up to 100 docs (posts) at a time to ensure a smooth process and prevent errors. If you have more than 100 docs (posts), you'll need to migrate more than once." => $core->__("Once you migrate from %s to %s, all your docs will be fully transferred (your post slugs for docs will remain the same). It will transfer up to 100 docs (posts) at a time to ensure a smooth process and prevent errors. If you have more than 100 docs (posts), you'll need to migrate more than once."),
            'Migration cannot be undone. So, we strongly recommend creating a full backup before starting the migration.' => $core->__('Migration cannot be undone. So, we strongly recommend creating a full backup before starting the migration.'),
            'System Requirements' => $core->__('System Requirements'),
            'Setting' => $core->__('Setting'),
            'Current' => $core->__('Current'),
            'Available Contents to Migrate' => $core->__('Available Contents to Migrate'),
            'Content Type' => $core->__('Content Type'),
            'I understand and agree to proceed with the migration.' => $core->__('I understand and agree to proceed with the migration.'),
            'Migrate from BetterDocs' => $core->__('Migrate from BetterDocs'),
            'Brand Color' => $core->__('Brand Color'),
            'Hero Background' => $core->__('Hero Background'),
            'Main Container Width' => $core->__('Main Container Width'),
            'Default is 1140px.' => $core->__('Default is 1140px.'),
            'All Mailboxes' => $core->__('All Mailboxes'),
            'Select Mailbox' => $core->__('Select Mailbox'),
            'Mailbox:' => $core->__('Mailbox:'),
            'Set Mailbox' => $core->__('Set Mailbox'),
            'Fluent Support' => $core->__('Fluent Support'),
            'Rule Pointer' => $core->__('Rule Pointer'),
            'Add to mailbox' => $core->__('Add to mailbox'),
            'Connected' => $core->__('Connected'),
            'From email:' => $core->__('From email:'),
            'Mailbox Address' => $core->__('Mailbox Address'),
            'Send Email From' => $core->__('Send Email From'),
            'Send email from' => $core->__('Send email from'),
            'Default Email Address' => $core->__('Default Email Address'),
            'From Email Address' => $core->__('From Email Address'),
            'From email address' => $core->__('From email address'),
            'Ensure your website is configured to send emails from this email address.' => $core->__('Ensure your website is configured to send emails from this email address.'),
            'Use the mailbox title as the sender name.' => $core->__('Use the mailbox title as the sender name.'),
            'Assign tickets to category.' => $core->__('Assign tickets to category.'),
            'User:' => $core->__('User:'),
            'Default Email' => $core->__('Default Email'),
            'From Email' => $core->__('From Email'),
            'From email' => $core->__('From email'),
            "Once you migrate from %s to %s, all your support tickets will be fully copied or transferred (including responses, notes, and attachments). It will copy or transfer up to 100 support tickets at a time to ensure a smooth process and prevent errors. If you have more than 100 support tickets, you'll need to migrate more than once." => $core->__("Once you migrate from %s to %s, all your support tickets will be fully copied or transferred (including responses, notes, and attachments). It will copy or transfer up to 100 support tickets at a time to ensure a smooth process and prevent errors. If you have more than 100 support tickets, you'll need to migrate more than once."),
            'Please note that integration-related data will not be migrated.' => $core->__('Please note that integration-related data will not be migrated.'),
            'Delete the successfully migrated tickets from Fluent Support (optional).' => $core->__('Delete the successfully migrated tickets from Fluent Support (optional).'),
            'Migrate from Fluent Support' => $core->__('Migrate from Fluent Support'),
            'Mailbox Requirements' => $core->__('Mailbox Requirements'),
            'Knowledge Base Assistant' => $core->__('Knowledge Base Assistant'),
            'All Priorities' => $core->__('All Priorities'),
            '%d Ticket' => $core->__('%d Ticket'),
            '%d Tickets' => $core->__('%d Tickets'),
            '%d Priority' => $core->__('%d Priority'),
            'Priorities:' => $core->__('Priorities:'),
            'Priority:' => $core->__('Priority:'),
            '%d Priorities' => $core->__('%d Priorities'),
            'Select date' => $core->__('Select date'),
            '%s Priority' => $core->__('%s Priority'),
            'Priority' => $core->__('Priority'),
            'Select Priority' => $core->__('Select Priority'),
            'Set Priority' => $core->__('Set Priority'),
            'Auto Close & Delete' => $core->__('Auto Close & Delete'),
            'Enable multiple role selector for user.' => $core->__('Enable multiple role selector for user.'),
            'Disable undo for reply & note submission.' => $core->__('Disable undo for reply & note submission.'),
            'Conditional Field' => $core->__('Conditional Field'),
            'Enable conditional logics.' => $core->__('Enable conditional logics.'),
            'Conditions' => $core->__('Conditions'),
            'Match all conditions' => $core->__('Match all conditions'),
            'Match any conditions' => $core->__('Match any conditions'),
            'Select Field' => $core->__('Select Field'),
            'Equal' => $core->__('Equal'),
            'Not equal' => $core->__('Not equal'),
            'Operator' => $core->__('Operator'),
            'Compare value' => $core->__('Compare value'),
            'Select Value' => $core->__('Select Value'),
            'Contain' => $core->__('Contain'),
            'Not contain' => $core->__('Not contain'),
            'Select Date' => $core->__('Select Date'),
            'Tickets:' => $core->__('Tickets:'),
            'Transfer Tickets' => $core->__('Transfer Tickets'),
            'Transfer Mailbox Tickets' => $core->__('Transfer Mailbox Tickets'),
            'Choose Mailbox' => $core->__('Choose Mailbox'),
            'From which address notification emails for tickets associated with this mailbox will be sent.' => $core->__('From which address notification emails for tickets associated with this mailbox will be sent.'),
            'Notification emails for tickets associated with this mailbox will be sent from this address.' => $core->__('Notification emails for tickets associated with this mailbox will be sent from this address.'),
            'Enable auto close for tickets.' => $core->__('Enable auto close for tickets.'),
            'Enable auto trash for tickets.' => $core->__('Enable auto trash for tickets.'),
            'Enable auto delete for tickets.' => $core->__('Enable auto delete for tickets.'),
            'Auto close tickets after' => $core->__('Auto close tickets after'),
            'The ticket will be automatically closed after the specified number of days if the customer does not respond to the agent\'s last reply. The default is 30 days.' => $core->__('The ticket will be automatically closed after the specified number of days if the customer does not respond to the agent\'s last reply. The default is 30 days.'),
            'Prevent auto close for selected tags' => $core->__('Prevent auto close for selected tags'),
            'Tickets with the selected tags will be excluded from auto close.' => $core->__('Tickets with the selected tags will be excluded from auto close.'),
            'Auto trash tickets after' => $core->__('Auto trash tickets after'),
            'Tickets will be automatically moved to trash the specified number of days after being closed. The default is 30 days.' => $core->__('Tickets will be automatically moved to trash the specified number of days after being closed. The default is 30 days.'),
            'Auto delete tickets after' => $core->__('Auto delete tickets after'),
            'Tickets will be automatically deleted the specified number of days after being moved to trash. The default is 30 days.' => $core->__('Tickets will be automatically deleted the specified number of days after being moved to trash. The default is 30 days.'),
            'Disable single view of only for Chatbot docs.' => $core->__('Disable single view of only for Chatbot docs.'),
            'Override WordPress registration setting.' => $core->__('Override WordPress registration setting.'),
            'Allow Support Genix to create user accounts even if WordPress "Anyone can register" is off.' => $core->__('Allow Support Genix to create user accounts even if WordPress "Anyone can register" is off.'),
            'Turn OFF to strictly follow WordPress\'s global setting.' => $core->__('Turn OFF to strictly follow WordPress\'s global setting.'),
            'Client User Default Role' => $core->__('Client User Default Role'),
            'Ticket Portal Page' => $core->__('Ticket Portal Page'),
            'Ticket track ID minimum length' => $core->__('Ticket track ID minimum length'),
            'Email to ticket' => $core->__('Email to ticket'),
            'Incoming webhook' => $core->__('Incoming webhook'),
            'Login system' => $core->__('Login system'),
            'Auto-create ticket on new order.' => $core->__('Auto-create ticket on new order.'),
            '%s requires user registration to be enabled to create tickets for non-registered users.' => $core->__('%s requires user registration to be enabled to create tickets for non-registered users.'),
            '%s requires user registration to be enabled for non-registered users.' => $core->__('%s requires user registration to be enabled for non-registered users.'),
            'Please enable "Override WordPress registration setting" or allow WordPress registration to use this feature.' => $core->__('Please enable "Override WordPress registration setting" or allow WordPress registration to use this feature.'),
            'Go to Settings' => $core->__('Go to Settings'),
            'AI Ticket Reply' => $core->__('AI Ticket Reply'),
            'Professional' => $core->__('Professional'),
            'Friendly' => $core->__('Friendly'),
            'Formal' => $core->__('Formal'),
            'Casual' => $core->__('Casual'),
            'Empathetic' => $core->__('Empathetic'),
            'Generate' => $core->__('Generate'),
            'What would you like to say?' => $core->__('What would you like to say?'),
            'E.g., Thank the customer for their patience and explain that we are investigating the issue...' => $core->__('E.g., Thank the customer for their patience and explain that we are investigating the issue...'),
            'Tone' => $core->__('Tone'),
            'Generate Reply' => $core->__('Generate Reply'),
            'Refine' => $core->__('Refine'),
            'How would you like to refine the reply?' => $core->__('How would you like to refine the reply?'),
            'E.g., Make it shorter, add more details about the refund policy, be more empathetic...' => $core->__('E.g., Make it shorter, add more details about the refund policy, be more empathetic...'),
            'Refine Reply' => $core->__('Refine Reply'),
            'History' => $core->__('History'),
            'No history' => $core->__('No history'),
            'Tool' => $core->__('Tool'),
            'Undo' => $core->__('Undo'),
            'Submit Now' => $core->__('Submit Now'),
            'Support Tickets Report' => $core->__('Support Tickets Report'),
            'Track ticket activity, response performance, and user interactions to improve support quality and team efficiency.' => $core->__('Track ticket activity, response performance, and user interactions to improve support quality and team efficiency.'),
            'Based On' => $core->__('Based On'),
            'This doc will not be visible on the website, but it will provide data only to the chatbot.' => $core->__('This doc will not be visible on the website, but it will provide data only to the chatbot.'),
            'Only for Chatbot' => $core->__('Only for Chatbot'),
            'Knowledge Base Analytics' => $core->__('Knowledge Base Analytics'),
            'Track user engagement, content performance, and search behavior to optimize your documentation and improve user experience.' => $core->__('Track user engagement, content performance, and search behavior to optimize your documentation and improve user experience.'),
            'Top chatbot queries are filtered by the selected date range, but not by category, tag, or author.' => $core->__('Top chatbot queries are filtered by the selected date range, but not by category, tag, or author.'),
            'Top Chatbot Queries' => $core->__('Top Chatbot Queries'),
            'No result chatbot queries are filtered by the selected date range, but not by category, tag, or author.' => $core->__('No result chatbot queries are filtered by the selected date range, but not by category, tag, or author.'),
            'No Result Chatbot Queries' => $core->__('No Result Chatbot Queries'),
            'AI Tools' => $core->__('AI Tools'),
            'API Keys' => $core->__('API Keys'),
            'Text Editor Height' => $core->__('Text Editor Height'),
            'Set the initial height of the text editor. Default is 180px. The minimum value is 100px. You can also manually resize the editor by dragging its corner.' => $core->__('Set the initial height of the text editor. Default is 180px. The minimum value is 100px. You can also manually resize the editor by dragging its corner.'),
            'Set ticket priority' => $core->__('Set ticket priority'),
            'Main Page' => $core->__('Main Page'),
            'How can we help?' => $core->__('How can we help?'),
            'Search our knowledge base or browse categories below' => $core->__('Search our knowledge base or browse categories below'),
            'Show Hero Section' => $core->__('Show Hero Section'),
            'Show Statistics Bar' => $core->__('Show Statistics Bar'),
            'Show Featured Section' => $core->__('Show Featured Section'),
            'Show Category Icons' => $core->__('Show Category Icons'),
            'Show Category Descriptions' => $core->__('Show Category Descriptions'),
            'Docs per Category' => $core->__('Docs per Category'),
            'Number of docs to show per category (1-20)' => $core->__('Number of docs to show per category (1-20)'),
            'Show Recently Updated Section' => $core->__('Show Recently Updated Section'),
            'Hero Title' => $core->__('Hero Title'),
            'Hero Subtitle' => $core->__('Hero Subtitle'),
            'AI Providers' => $core->__('AI Providers'),
            'Manage your API keys centrally in the API Keys settings.' => $core->__('Manage your API keys centrally in the API Keys settings.'),
            'Manage' => $core->__('Manage'),
            'Feature Options' => $core->__('Feature Options'),
            'Enable create ticket' => $core->__('Enable create ticket'),
            'Enable docs resources' => $core->__('Enable docs resources'),
            'Allow smart search for related docs.' => $core->__('Allow smart search for related docs.'),
            'Respond to greetings even when no resource matches' => $core->__('Respond to greetings even when no resource matches'),
            'Disable auto ticket assignment to first responder.' => $core->__('Disable auto ticket assignment to first responder.'),
            'Disable smart need reply sorting.' => $core->__('Disable smart need reply sorting.'),
            'Ticket Mailbox ID' => $core->__('Ticket Mailbox ID'),
            'Ticket Mailbox Type' => $core->__('Ticket Mailbox Type'),
            'Use "M" for modern or "T" for traditional. Default is modern.' => $core->__('Use "M" for modern or "T" for traditional. Default is modern.'),
            'Ticket Priority' => $core->__('Ticket Priority'),
            'Use "N" for normal, "M" for medium, or "H" for high. Default is normal.' => $core->__('Use "N" for normal, "M" for medium, or "H" for high. Default is normal.'),
            'Enable tickets auto refresh.' => $core->__('Enable tickets auto refresh.'),
            'Auto refresh interval' => $core->__('Auto refresh interval'),
            'Minimum value is 5 seconds.' => $core->__('Minimum value is 5 seconds.'),
            'Seconds' => $core->__('Seconds'),
            'All Knowledge Base' => $core->__('All Knowledge Base'),
            'Knowledge Bases:' => $core->__('Knowledge Bases:'),
            'Knowledge Base:' => $core->__('Knowledge Base:'),
            'Multiple KB' => $core->__('Multiple KB'),
            'Multiple Knowledge Base.' => $core->__('Multiple Knowledge Base.'),
            'Select Knowledge Base' => $core->__('Select Knowledge Base'),
            'Multiple Knowledge Base' => $core->__('Multiple Knowledge Base'),
            'Parent Space' => $core->__('Parent Space'),
            'First Question' => $core->__('First Question'),
            'Messages' => $core->__('Messages'),
            'Helpful' => $core->__('Helpful'),
            'Started' => $core->__('Started'),
            'Conversations' => $core->__('Conversations'),
            'Chatbot Conversations' => $core->__('Chatbot Conversations'),
            'View and analyze chatbot conversation history to improve knowledge base content.' => $core->__('View and analyze chatbot conversation history to improve knowledge base content.'),
            'Storage Settings' => $core->__('Storage Settings'),
            'All Users' => $core->__('All Users'),
            'Logged In' => $core->__('Logged In'),
            'Guests' => $core->__('Guests'),
            'All Feedback' => $core->__('All Feedback'),
            'Unhelpful' => $core->__('Unhelpful'),
            'No Feedback' => $core->__('No Feedback'),
            'Last 90 Days' => $core->__('Last 90 Days'),
            'Total Sessions' => $core->__('Total Sessions'),
            'Today' => $core->__('Today'),
            'This Week' => $core->__('This Week'),
            'Helpful Rate' => $core->__('Helpful Rate'),
            'Conversations Over Time' => $core->__('Conversations Over Time'),
            'Peak Hours' => $core->__('Peak Hours'),
            'Conversation Detail' => $core->__('Conversation Detail'),
            'Conversation not found' => $core->__('Conversation not found'),
            'Keep Conversations For' => $core->__('Keep Conversations For'),
            'How long to store conversation history. Older conversations are automatically removed.' => $core->__('How long to store conversation history. Older conversations are automatically removed.'),
            '7 Days' => $core->__('7 Days'),
            '30 Days' => $core->__('30 Days'),
            '90 Days' => $core->__('90 Days'),
            'Forever (No Auto-Delete)' => $core->__('Forever (No Auto-Delete)'),
            'Max Messages Per User' => $core->__('Max Messages Per User'),
            'Maximum messages to keep per user. Oldest are deleted when exceeded. Set 0 for unlimited.' => $core->__('Maximum messages to keep per user. Oldest are deleted when exceeded. Set 0 for unlimited.'),
            'Custom Cleanup Schedule' => $core->__('Custom Cleanup Schedule'),
            'Set a custom cleanup threshold. The shorter period between this and retention above will be used.' => $core->__('Set a custom cleanup threshold. The shorter period between this and retention above will be used.'),
            'Save Settings' => $core->__('Save Settings'),
            'Delete this conversation?' => $core->__('Delete this conversation?'),
            'Delete Conversations Older Than' => $core->__('Delete Conversations Older Than'),
            'Conversations older than this will be deleted. Cleanup runs automatically twice daily.' => $core->__('Conversations older than this will be deleted. Cleanup runs automatically twice daily.'),
            'days' => $core->__('days'),
            'Related Documentation:' => $core->__('Related Documentation:'),
            'Support Genix AI (Recommended)' => $core->__('Support Genix AI (Recommended)'),
            'Voice Chat' => $core->__('Voice Chat'),
            'Voice features require ElevenLabs API key configuration in Settings > API Keys > ElevenLabs.' => $core->__('Voice features require ElevenLabs API key configuration in Settings > API Keys > ElevenLabs.'),
            'Voice Agent Mode: Click once for continuous voice-to-voice conversation using ElevenLabs Conversational AI. Requires Agent ID configured in ElevenLabs settings.' => $core->__('Voice Agent Mode: Click once for continuous voice-to-voice conversation using ElevenLabs Conversational AI. Requires Agent ID configured in ElevenLabs settings.'),
            'Enable voice agent (live conversation)' => $core->__('Enable voice agent (live conversation)'),
            'Push-to-Talk: Click mic → speak → click again to submit. Uses your chatbot AI for responses.' => $core->__('Push-to-Talk: Click mic → speak → click again to submit. Uses your chatbot AI for responses.'),
            'Enable voice input (speak to type)' => $core->__('Enable voice input (speak to type)'),
            'Show speaker button on bot messages to play them aloud using ElevenLabs text-to-speech.' => $core->__('Show speaker button on bot messages to play them aloud using ElevenLabs text-to-speech.'),
            'Enable voice playback (listen to responses)' => $core->__('Enable voice playback (listen to responses)'),
            'Enable auto-play new responses' => $core->__('Enable auto-play new responses'),
            'Automatically play text-to-speech audio for new chatbot responses. Requires "Enable voice playback" to be enabled.' => $core->__('Automatically play text-to-speech audio for new chatbot responses. Requires "Enable voice playback" to be enabled.'),
            'Allow users to clear chat history' => $core->__('Allow users to clear chat history'),
            'Documentation Title' => $core->__('Documentation Title'),
            'Documentation title' => $core->__('Documentation title'),
            'Support Genix AI' => $core->__('Support Genix AI'),
            'ElevenLabs' => $core->__('ElevenLabs'),
            'License Key' => $core->__('License Key'),
            'Check AI Credits' => $core->__('Check AI Credits'),
            'Checking Credits...' => $core->__('Checking Credits...'),
            'AI Credits' => $core->__('AI Credits'),
            'Refresh credits' => $core->__('Refresh credits'),
            'Purchase Credits' => $core->__('Purchase Credits'),
            'Available' => $core->__('Available'),
            'Used' => $core->__('Used'),
            'Total' => $core->__('Total'),
            'Usage' => $core->__('Usage'),
            'remaining' => $core->__('remaining'),
            'Select AI Voice' => $core->__('Select AI Voice'),
            'Select Voice Agent' => $core->__('Select Voice Agent'),
            'AI Voice' => $core->__('AI Voice'),
            'Select a voice for TTS or fetch voices from your account.' => $core->__('Select a voice for TTS or fetch voices from your account.'),
            'Fetch Voices' => $core->__('Fetch Voices'),
            'Voice Agent Settings (Conversational AI)' => $core->__('Voice Agent Settings (Conversational AI)'),
            'Voice Agent' => $core->__('Voice Agent'),
            'Select an agent for voice-to-voice conversations or fetch agents from your account.' => $core->__('Select an agent for voice-to-voice conversations or fetch agents from your account.'),
            'Fetch Agents' => $core->__('Fetch Agents'),
            'Enable Voice Override' => $core->__('Enable Voice Override'),
            'Use the AI Voice selected above instead of agent\'s default voice. Requires "Voice" override to be enabled in ElevenLabs Agent Security settings.' => $core->__('Use the AI Voice selected above instead of agent\'s default voice. Requires "Voice" override to be enabled in ElevenLabs Agent Security settings.'),
            'Public Agent' => $core->__('Public Agent'),
            'Enable this if your ElevenLabs agent is public and does not require authentication. Disable for private agents that require server-side authentication.' => $core->__('Enable this if your ElevenLabs agent is public and does not require authentication. Disable for private agents that require server-side authentication.'),
            'Note: ElevenLabs API key is not configured. Please configure it in Settings > API Keys > ElevenLabs.' => $core->__('Note: ElevenLabs API key is not configured. Please configure it in Settings > API Keys > ElevenLabs.'),
            'Add' => $core->__('Add'),
            'Add Domain' => $core->__('Add Domain'),
            'Add New Script' => $core->__('Add New Script'),
            'Add Note' => $core->__('Add Note'),
            'Add Now' => $core->__('Add Now'),
            'All Knowledge Bases' => $core->__('All Knowledge Bases'),
            'All domains' => $core->__('All domains'),
            'Allow Ticket Creation' => $core->__('Allow Ticket Creation'),
            'Allowed Domains' => $core->__('Allowed Domains'),
            'Restrict docs browsing to selected KBs and Categories' => $core->__('Restrict docs browsing to selected KBs and Categories'),
            'Author:' => $core->__('Author:'),
            'Authors:' => $core->__('Authors:'),
            'Auto-fit' => $core->__('Auto-fit'),
            'Back Home' => $core->__('Back Home'),
            'Choose Knowledge Base' => $core->__('Choose Knowledge Base'),
            'Choose Knowledge Base Category' => $core->__('Choose Knowledge Base Category'),
            'Clear History' => $core->__('Clear History'),
            'Code' => $core->__('Code'),
            'Code:' => $core->__('Code:'),
            'Copied' => $core->__('Copied'),
            'Copied to clipboard' => $core->__('Copied to clipboard'),
            'Copy' => $core->__('Copy'),
            'Count:' => $core->__('Count:'),
            'Custom fields' => $core->__('Custom fields'),
            'Delete failed' => $core->__('Delete failed'),
            'Deleted successfully' => $core->__('Deleted successfully'),
            'Docs (Posts)' => $core->__('Docs (Posts)'),
            'Domain is required' => $core->__('Domain is required'),
            'Domains' => $core->__('Domains'),
            'Domains:' => $core->__('Domains:'),
            'Duration' => $core->__('Duration'),
            'Embed' => $core->__('Embed'),
            'Embed Code' => $core->__('Embed Code'),
            'Embed Script' => $core->__('Embed Script'),
            'Embed Scripts' => $core->__('Embed Scripts'),
            'Enable Voice Features' => $core->__('Enable Voice Features'),
            'Enrolled' => $core->__('Enrolled'),
            'Events:' => $core->__('Events:'),
            'Failed to fetch agents.' => $core->__('Failed to fetch agents.'),
            'Failed to fetch agents. Please check your ElevenLabs API key configuration.' => $core->__('Failed to fetch agents. Please check your ElevenLabs API key configuration.'),
            'Failed to fetch credits.' => $core->__('Failed to fetch credits.'),
            'Failed to fetch voices.' => $core->__('Failed to fetch voices.'),
            'Failed to fetch voices. Please check your ElevenLabs API key configuration.' => $core->__('Failed to fetch voices. Please check your ElevenLabs API key configuration.'),
            'Failed to generate reply.' => $core->__('Failed to generate reply.'),
            'Failed to refine reply.' => $core->__('Failed to refine reply.'),
            'Failed to save settings' => $core->__('Failed to save settings'),
            'Features' => $core->__('Features'),
            'Features:' => $core->__('Features:'),
            'Free Plan' => $core->__('Free Plan'),
            'Generated' => $core->__('Generated'),
            'Generated Content:' => $core->__('Generated Content:'),
            'Generated Reply' => $core->__('Generated Reply'),
            'Generating...' => $core->__('Generating...'),
            'Get Code' => $core->__('Get Code'),
            'Get Embed Code' => $core->__('Get Embed Code'),
            'Grid Columns' => $core->__('Grid Columns'),
            'Guest' => $core->__('Guest'),
            'Helpful:' => $core->__('Helpful:'),
            'History cleared.' => $core->__('History cleared.'),
            'Insert Reply' => $core->__('Insert Reply'),
            'Insert Saved Replies' => $core->__('Insert Saved Replies'),
            'Instructions:' => $core->__('Instructions:'),
            'Invalid' => $core->__('Invalid'),
            'Invalid data.' => $core->__('Invalid data.'),
            'Invalid file type.' => $core->__('Invalid file type.'),
            'Key' => $core->__('Key'),
            'Leave empty to use global chatbot color' => $core->__('Leave empty to use global chatbot color'),
            'License:' => $core->__('License:'),
            'Low Credits' => $core->__('Low Credits'),
            'Maximum 255 character.' => $core->__('Maximum 255 character.'),
            'Messages:' => $core->__('Messages:'),
            'Migrate More' => $core->__('Migrate More'),
            'Migrating...' => $core->__('Migrating...'),
            'Model' => $core->__('Model'),
            'Navigation' => $core->__('Navigation'),
            'No additional data.' => $core->__('No additional data.'),
            'No content to refine.' => $core->__('No content to refine.'),
            'No messages' => $core->__('No messages'),
            'Number of columns in the grid layout' => $core->__('Number of columns in the grid layout'),
            'Order Details' => $core->__('Order Details'),
            'Others' => $core->__('Others'),
            'Parent:' => $core->__('Parent:'),
            'Payment' => $core->__('Payment'),
            'Placeholders' => $core->__('Placeholders'),
            'Plan' => $core->__('Plan'),
            'Please enter instructions for generating the reply.' => $core->__('Please enter instructions for generating the reply.'),
            'Please enter refinement instructions.' => $core->__('Please enter refinement instructions.'),
            'Power up your support with AI' => $core->__('Power up your support with AI'),
            'Products' => $core->__('Products'),
            'Purchase:' => $core->__('Purchase:'),
            'Reactions:' => $core->__('Reactions:'),
            'Recipient:' => $core->__('Recipient:'),
            'Refined' => $core->__('Refined'),
            'Refinement Instructions:' => $core->__('Refinement Instructions:'),
            'Refining...' => $core->__('Refining...'),
            'Renews' => $core->__('Renews'),
            'Reply generated successfully.' => $core->__('Reply generated successfully.'),
            'Reply inserted from history.' => $core->__('Reply inserted from history.'),
            'Reply refined successfully.' => $core->__('Reply refined successfully.'),
            'Restrict chatbot to search docs only from selected categories. Select "All" to search all categories.' => $core->__('Restrict chatbot to search docs only from selected categories. Select "All" to search all categories.'),
            'Restrict chatbot to search docs only from selected knowledge bases. Select "All" to search all knowledge bases.' => $core->__('Restrict chatbot to search docs only from selected knowledge bases. Select "All" to search all knowledge bases.'),
            'Retry' => $core->__('Retry'),
            'Rule Pointer:' => $core->__('Rule Pointer:'),
            'Score:' => $core->__('Score:'),
            'Secret:' => $core->__('Secret:'),
            'Select Model' => $core->__('Select Model'),
            'Select categories' => $core->__('Select categories'),
            'Select knowledge bases' => $core->__('Select knowledge bases'),
            'Select option' => $core->__('Select option'),
            'Settings saved' => $core->__('Settings saved'),
            'Shipping' => $core->__('Shipping'),
            'Show Docs Resources' => $core->__('Show Docs Resources'),
            'Sorry, something went wrong.' => $core->__('Sorry, something went wrong.'),
            'Sorry, the page you visited does not exist.' => $core->__('Sorry, the page you visited does not exist.'),
            'Sorry, you are not authorized to access this page.' => $core->__('Sorry, you are not authorized to access this page.'),
            'Start using AI instantly' => $core->__('Start using AI instantly'),
            'Started:' => $core->__('Started:'),
            'Statuses:' => $core->__('Statuses:'),
            'Successfully Migrated!' => $core->__('Successfully Migrated!'),
            'Support Genix AI is powered by OpenAI language models to deliver intelligent assistance. We recommend reviewing AI-generated responses to ensure they meet your specific needs.' => $core->__('Support Genix AI is powered by OpenAI language models to deliver intelligent assistance. We recommend reviewing AI-generated responses to ensure they meet your specific needs.'),
            'Support:' => $core->__('Support:'),
            'Totals' => $core->__('Totals'),
            'Try Support Genix AI' => $core->__('Try Support Genix AI'),
            'Unable to fetch agents. Configuration missing.' => $core->__('Unable to fetch agents. Configuration missing.'),
            'Unable to fetch credits' => $core->__('Unable to fetch credits'),
            'Unable to fetch voices. Configuration missing.' => $core->__('Unable to fetch voices. Configuration missing.'),
            'Unlimited' => $core->__('Unlimited'),
            'Unnamed' => $core->__('Unnamed'),
            'Upgrade' => $core->__('Upgrade'),
            'Use *.example.com for wildcards. Leave empty to allow all domains.' => $core->__('Use *.example.com for wildcards. Leave empty to allow all domains.'),
            'View Details' => $core->__('View Details'),
            'Views:' => $core->__('Views:'),
            'Voice' => $core->__('Voice'),
            'Voice Chat Detail' => $core->__('Voice Chat Detail'),
            '2 Columns' => $core->__('2 Columns'),
            '3 Columns' => $core->__('3 Columns'),
            '4 Columns' => $core->__('4 Columns'),
            'Quill Editor' => $core->__('Quill Editor'),
            '403' => $core->__('403'),
            '404' => $core->__('404'),
            '500' => $core->__('500'),
            'Enable public ticket option (on creation).' => $core->__('Enable public ticket option (on creation).'),
            'Enable public ticket option (on details).' => $core->__('Enable public ticket option (on details).'),
            'attribute to customize the z-index (default: 999999).' => $core->__('attribute to customize the z-index (default: 999999).'),
            '%d Agents' => $core->__('%d Agents'),
            '%d Authors' => $core->__('%d Authors'),
            '%d Categories' => $core->__('%d Categories'),
            '%d of the selected conversations are starred.' => $core->__('%d of the selected conversations are starred.'),
            '%d of the selected conversations is starred.' => $core->__('%d of the selected conversations is starred.'),
            '%s is not valid.' => $core->__('%s is not valid.'),
            'Agents fetched successfully! Found %s agents.' => $core->__('Agents fetched successfully! Found %s agents.'),
            'All Stars' => $core->__('All Stars'),
            'Are you sure want to delete all selected?' => $core->__('Are you sure want to delete all selected?'),
            'Are you sure want to star?' => $core->__('Are you sure want to star?'),
            'Are you sure want to unstar?' => $core->__('Are you sure want to unstar?'),
            'Attachments (%d)' => $core->__('Attachments (%d)'),
            'Disable current viewers on ticket details.' => $core->__('Disable current viewers on ticket details.'),
            'EDD Orders (%d)' => $core->__('EDD Orders (%d)'),
            'Embed Code for: %s' => $core->__('Embed Code for: %s'),
            'Envato Items (%d)' => $core->__('Envato Items (%d)'),
            'Failed to update' => $core->__('Failed to update'),
            'Not starred' => $core->__('Not starred'),
            'Note by %s' => $core->__('Note by %s'),
            'Note:' => $core->__('Note:'),
            'Starred' => $core->__('Starred'),
            'Starred conversations are protected from automatic cleanup.' => $core->__('Starred conversations are protected from automatic cleanup.'),
            'This conversation is starred.' => $core->__('This conversation is starred.'),
            'Tutor LMS Courses (%d)' => $core->__('Tutor LMS Courses (%d)'),
            'Unstarred' => $core->__('Unstarred'),
            'Voices fetched successfully! Found %s voices.' => $core->__('Voices fetched successfully! Found %s voices.'),
            'Also viewing:' => $core->__('Also viewing:'),
            'Please select at least one tool.' => $core->__('Please select at least one tool.'),
            'All Sources' => $core->__('All Sources'),
            'Learn from related docs via smart search' => $core->__('Learn from related docs via smart search'),
            'Learn from ticket replies' => $core->__('Learn from ticket replies'),
            'Main Site' => $core->__('Main Site'),
            'Select ticket categories' => $core->__('Select ticket categories'),
            'Select ticket statuses' => $core->__('Select ticket statuses'),
            'WooCommerce Integration (Same Site)' => $core->__('WooCommerce Integration (Same Site)'),
            'WooCommerce External Store' => $core->__('WooCommerce External Store'),
            'WooCommerce Auto-Create Ticket' => $core->__('WooCommerce Auto-Create Ticket'),
            'Ticket Categories' => $core->__('Ticket Categories'),
            'Ticket Statuses' => $core->__('Ticket Statuses'),
            'Recent Orders (%d)' => $core->__('Recent Orders (%d)'),
            'File size must be smaller than %s.' => $core->__('File size must be smaller than %s.'),
            'You\'ve selected %d tickets to reply to at once.' => $core->__('You\'ve selected %d tickets to reply to at once.'),
            'You\'ve selected %d tickets to add internal note to at once.' => $core->__('You\'ve selected %d tickets to add internal note to at once.'),
            'For the best performance and reliability, we recommend selecting no more than 20 tickets at a time.' => $core->__('For the best performance and reliability, we recommend selecting no more than 20 tickets at a time.'),
            'Add this script tag to your HTML, preferably before the closing </body> tag. The chatbot will automatically appear as a floating widget.' => $core->__('Add this script tag to your HTML, preferably before the closing </body> tag. The chatbot will automatically appear as a floating widget.'),
            'Upon saving, when the mailbox address is generated, please forward your support emails from connected email address to the mailbox address.' => $core->__('Upon saving, when the mailbox address is generated, please forward your support emails from connected email address to the mailbox address.'),
            'No license key found. Please activate your Support Genix license in the License tab first.' => $core->__('No license key found. Please activate your Support Genix license in the License tab first.'),
            'Your AI credits are running low. Purchase more credits to continue enjoying AI features.' => $core->__('Your AI credits are running low. Purchase more credits to continue enjoying AI features.'),
            'When enabled, the chatbot will also use agent replies from support tickets to answer questions.' => $core->__('When enabled, the chatbot will also use agent replies from support tickets to answer questions.'),
            'Customer data is automatically stripped before sending to AI.' => $core->__('Customer data is automatically stripped before sending to AI.'),
            'Choose which ticket categories to learn from. Select "All" to include every category.' => $core->__('Choose which ticket categories to learn from. Select "All" to include every category.'),
            'Choose which ticket statuses to learn from. Closed tickets are recommended as they contain verified solutions.' => $core->__('Choose which ticket statuses to learn from. Closed tickets are recommended as they contain verified solutions.'),
            'Create, edit, trash, restore, delete, and duplicate knowledge base docs.' => $core->__('Create, edit, trash, restore, delete, and duplicate knowledge base docs.'),
            'View KB analytics (top docs, search keywords, statistics) and manage chat history (conversations, feedback, storage settings).' => $core->__('View KB analytics (top docs, search keywords, statistics) and manage chat history (conversations, feedback, storage settings).'),
            'Manage categories, tags, spaces, design settings, chatbot configuration, migrations, and AI writing tools.' => $core->__('Manage categories, tags, spaces, design settings, chatbot configuration, migrations, and AI writing tools.'),
            'We migrate a maximum of 100 docs (posts) at a time to ensure a smooth process and prevent errors.' => $core->__('We migrate a maximum of 100 docs (posts) at a time to ensure a smooth process and prevent errors.'),
            'If you have more than 100 docs (posts), please repeat the migration until all are transferred.' => $core->__('If you have more than 100 docs (posts), please repeat the migration until all are transferred.'),
            'We migrate a maximum of 100 support tickets at a time to ensure a smooth process and prevent errors.' => $core->__('We migrate a maximum of 100 support tickets at a time to ensure a smooth process and prevent errors.'),
            'If you have more than 100 support tickets, please repeat the migration until all are copied or transferred.' => $core->__('If you have more than 100 support tickets, please repeat the migration until all are copied or transferred.'),
            'If "Allow users to clear chat history" is enabled in chatbot settings, users can clear all their conversations including starred ones.' => $core->__('If "Allow users to clear chat history" is enabled in chatbot settings, users can clear all their conversations including starred ones.'),
            'At least one category title is required to save.' => $core->__('At least one category title is required to save.'),
            'At least one tag title is required to save.' => $core->__('At least one tag title is required to save.'),
            'Change Ticket User' => $core->__('Change Ticket User'),
            'Edit License Key' => $core->__('Edit License Key'),
            'Edit Ticket User' => $core->__('Edit Ticket User'),
            'Edit User' => $core->__('Edit User'),
            'More' => $core->__('More'),
            'This ticket already belongs to the selected user.' => $core->__('This ticket already belongs to the selected user.'),
            'attribute to set the page URL when embedding inside nested iframes.' => $core->__('attribute to set the page URL when embedding inside nested iframes.'),
        ];

        return $texts;
    }

    public static function portal_texts()
    {
        $core = ApbdWps_SupportLite::GetInstance();

        $texts = [
            'Home' => $core->__('Home'),
            'Tickets' => $core->__('Tickets'),
            'Create Ticket as a Guest' => $core->__('Create Ticket as a Guest'),
            'Login' => $core->__('Login'),
            'Username or Email Address' => $core->__('Username or Email Address'),
            'Username or email address' => $core->__('Username or email address'),
            '%s is required.' => $core->__('%s is required.'),
            'Password' => $core->__('Password'),
            'Remember me.' => $core->__('Remember me.'),
            'Lost your password?' => $core->__('Lost your password?'),
            'Reset Password' => $core->__('Reset Password'),
            'Get New Password' => $core->__('Get New Password'),
            'Don\'t have an account?' => $core->__('Don\'t have an account?'),
            'Register Now' => $core->__('Register Now'),
            'Register' => $core->__('Register'),
            'First Name' => $core->__('First Name'),
            'First name' => $core->__('First name'),
            'Last Name' => $core->__('Last Name'),
            'Last name' => $core->__('Last name'),
            'Email Address' => $core->__('Email Address'),
            'Email address' => $core->__('Email address'),
            'Confirm Password' => $core->__('Confirm Password'),
            'Confirm password' => $core->__('Confirm password'),
            'This field is required.' => $core->__('This field is required.'),
            'Saved Replies' => $core->__('Saved Replies'),
            'Returning User? Login' => $core->__('Returning User? Login'),
            'Category' => $core->__('Category'),
            'Subject' => $core->__('Subject'),
            'Description' => $core->__('Description'),
            'Click or drag file to upload' => $core->__('Click or drag file to upload'),
            'Make this ticket public.' => $core->__('Make this ticket public.'),
            'Create' => $core->__('Create'),
            'Insert %s' => $core->__('Insert %s'),
            'All Tickets' => $core->__('All Tickets'),
            'Sort: Reply Date (Newest First)' => $core->__('Sort: Reply Date (Newest First)'),
            'Sort: Reply Date (Oldest First)' => $core->__('Sort: Reply Date (Oldest First)'),
            'Sort: Opening Date (Newest First)' => $core->__('Sort: Opening Date (Newest First)'),
            'Sort: Opening Date (Oldest First)' => $core->__('Sort: Opening Date (Oldest First)'),
            'Bulk Actions' => $core->__('Bulk Actions'),
            'All Agents' => $core->__('All Agents'),
            'All Categories' => $core->__('All Categories'),
            'Add Ticket' => $core->__('Add Ticket'),
            'Search keyword' => $core->__('Search keyword'),
            'Reset Filters' => $core->__('Reset Filters'),
            'Ticket' => $core->__('Ticket'),
            'Add New %s' => $core->__('Add New %s'),
            'Select Category' => $core->__('Select Category'),
            'Profile' => $core->__('Profile'),
            'Change Password' => $core->__('Change Password'),
            'Logout' => $core->__('Logout'),
            'Title' => $core->__('Title'),
            'Date' => $core->__('Date'),
            'Showing %1$d - %2$d of %3$d' => $core->__('Showing %1$d - %2$d of %3$d'),
            'by %s' => $core->__('by %s'),
            'Replied:' => $core->__('Replied:'),
            '%1$s at %2$s' => $core->__('%1$s at %2$s'),
            'Created:' => $core->__('Created:'),
            'Status' => $core->__('Status'),
            'Ticket Track ID' => $core->__('Ticket Track ID'),
            'Reply Count' => $core->__('Reply Count'),
            'Export Ticket' => $core->__('Export Ticket'),
            'Information' => $core->__('Information'),
            'Category:' => $core->__('Category:'),
            'N/A' => $core->__('N/A'),
            'Status:' => $core->__('Status:'),
            'Reply' => $core->__('Reply'),
            'Ticket Data' => $core->__('Ticket Data'),
            'Additional Data' => $core->__('Additional Data'),
            'Edit %s' => $core->__('Edit %s'),
            'Starter' => $core->__('Starter'),
            'Back to Tickets' => $core->__('Back to Tickets'),
            'Update' => $core->__('Update'),
            'Current Password' => $core->__('Current Password'),
            'Current password' => $core->__('Current password'),
            'New Password' => $core->__('New Password'),
            'New password' => $core->__('New password'),
            'Confirm New Password' => $core->__('Confirm New Password'),
            'Confirm new password' => $core->__('Confirm new password'),
            'Cancel' => $core->__('Cancel'),
            'Content' => $core->__('Content'),
            'Submit Reply' => $core->__('Submit Reply'),
            'Reply and close ticket' => $core->__('Reply and close ticket'),
            'Are you sure want to submit reply and close ticket?' => $core->__('Are you sure want to submit reply and close ticket?'),
            'Yes' => $core->__('Yes'),
            'No' => $core->__('No'),
            'Submit & Close Ticket' => $core->__('Submit & Close Ticket'),
            'Edit' => $core->__('Edit'),
            '%s:' => $core->__('%s:'),
            'Save Changes' => $core->__('Save Changes'),
            '%s is not valid.' => $core->__('%s is not valid.'),
            'My Tickets' => $core->__('My Tickets'),
            'Unassigned' => $core->__('Unassigned'),
            'Trashed' => $core->__('Trashed'),
            'Quick Edit' => $core->__('Quick Edit'),
            'Move to Trash' => $core->__('Move to Trash'),
            'Restore' => $core->__('Restore'),
            'Delete' => $core->__('Delete'),
            'Need Reply' => $core->__('Need Reply'),
            'Agent' => $core->__('Agent'),
            'Apply' => $core->__('Apply'),
            'Trash' => $core->__('Trash'),
            'Are you sure want to move to trash?' => $core->__('Are you sure want to move to trash?'),
            'Are you sure want to delete?' => $core->__('Are you sure want to delete?'),
            'Are you sure want to restore?' => $core->__('Are you sure want to restore?'),
            'Activate' => $core->__('Activate'),
            'Are you sure want to activate?' => $core->__('Are you sure want to activate?'),
            'Deactivate' => $core->__('Deactivate'),
            'Are you sure want to deactivate?' => $core->__('Are you sure want to deactivate?'),
            'Re-open' => $core->__('Re-open'),
            'Are you sure want to re-open?' => $core->__('Are you sure want to re-open?'),
            'Close' => $core->__('Close'),
            'Are you sure want to close?' => $core->__('Are you sure want to close?'),
            'Public' => $core->__('Public'),
            'Are you sure want to make public?' => $core->__('Are you sure want to make public?'),
            'Private' => $core->__('Private'),
            'Are you sure want to make private?' => $core->__('Are you sure want to make private?'),
            'Order Up' => $core->__('Order Up'),
            'Are you sure want to change order?' => $core->__('Are you sure want to change order?'),
            'Order Down' => $core->__('Order Down'),
            'Reset Order' => $core->__('Reset Order'),
            'Are you sure want to reset order?' => $core->__('Are you sure want to reset order?'),
            'Email notification' => $core->__('Email notification'),
            'Are you sure want to enable email notification to customer for this ticket?' => $core->__('Are you sure want to enable email notification to customer for this ticket?'),
            'Email notification to customer for this ticket.' => $core->__('Email notification to customer for this ticket.'),
            'Email notification.' => $core->__('Email notification.'),
            'Copy Hotlink' => $core->__('Copy Hotlink'),
            'Are you sure want to disable email notification to customer for this ticket?' => $core->__('Are you sure want to disable email notification to customer for this ticket?'),
            'Other Tickets (%d)' => $core->__('Other Tickets (%d)'),
            'Agent:' => $core->__('Agent:'),
            'Note' => $core->__('Note'),
            'Ticket Logs (%d)' => $core->__('Ticket Logs (%d)'),
            'Search User' => $core->__('Search User'),
            'Select User' => $core->__('Select User'),
            'Create User' => $core->__('Create User'),
            'Choose User' => $core->__('Choose User'),
            'Ticket User' => $core->__('Ticket User'),
            'Change User' => $core->__('Change User'),
            'Send the new user an email about their account.' => $core->__('Send the new user an email about their account.'),
            'Back' => $core->__('Back'),
            'User' => $core->__('User'),
            'Add Internal Note' => $core->__('Add Internal Note'),
            'Saved reply inserted.' => $core->__('Saved reply inserted.'),
            'Active' => $core->__('Active'),
            'Inactive' => $core->__('Inactive'),
            'Closed' => $core->__('Closed'),
            'All' => $core->__('All'),
            'All Tags' => $core->__('All Tags'),
            '%d Categor' => $core->__('%d Categor'),
            '%d Tag' => $core->__('%d Tag'),
            '%d Agent' => $core->__('%d Agent'),
            'Tags:' => $core->__('Tags:'),
            'Tag:' => $core->__('Tag:'),
            'Select Status' => $core->__('Select Status'),
            'Select Agent' => $core->__('Select Agent'),
            'Select Tag' => $core->__('Select Tag'),
            'Assign Agent' => $core->__('Assign Agent'),
            'Set Category' => $core->__('Set Category'),
            'Set Tag' => $core->__('Set Tag'),
            'Set Status' => $core->__('Set Status'),
            'Tag' => $core->__('Tag'),
            'Login with Google' => $core->__('Login with Google'),
            'Login with Envato' => $core->__('Login with Envato'),
            'or' => $core->__('or'),
            'Register with Google' => $core->__('Register with Google'),
            'Register with Envato' => $core->__('Register with Envato'),
            'Public Ticket' => $core->__('Public Ticket'),
            'Private Ticket' => $core->__('Private Ticket'),
            'Click to make it public.' => $core->__('Click to make it public.'),
            'Click to make it private.' => $core->__('Click to make it private.'),
            'Agents:' => $core->__('Agents:'),
            'Participant' => $core->__('Participant'),
            'Quick Reply' => $core->__('Quick Reply'),
            'Quick Note' => $core->__('Quick Note'),
            'All Mailboxes' => $core->__('All Mailboxes'),
            'Select Mailbox' => $core->__('Select Mailbox'),
            'Set Mailbox' => $core->__('Set Mailbox'),
            'Mailbox:' => $core->__('Mailbox:'),
            'Assigned on:' => $core->__('Assigned on:'),
            'Mailbox' => $core->__('Mailbox'),
            'All Priorities' => $core->__('All Priorities'),
            '%d Ticket' => $core->__('%d Ticket'),
            '%d Tickets' => $core->__('%d Tickets'),
            '%d Priority' => $core->__('%d Priority'),
            'Categories:' => $core->__('Categories:'),
            'Priorities:' => $core->__('Priorities:'),
            'Priority:' => $core->__('Priority:'),
            '%d Priorities' => $core->__('%d Priorities'),
            'Select date' => $core->__('Select date'),
            '%s Priority' => $core->__('%s Priority'),
            'Priority' => $core->__('Priority'),
            'Select Priority' => $core->__('Select Priority'),
            'Set Priority' => $core->__('Set Priority'),
            'No additional data.' => $core->__('No additional data.'),
            'AI Ticket Reply' => $core->__('AI Ticket Reply'),
            'Professional' => $core->__('Professional'),
            'Friendly' => $core->__('Friendly'),
            'Formal' => $core->__('Formal'),
            'Casual' => $core->__('Casual'),
            'Empathetic' => $core->__('Empathetic'),
            'Generate' => $core->__('Generate'),
            'What would you like to say?' => $core->__('What would you like to say?'),
            'E.g., Thank the customer for their patience and explain that we are investigating the issue...' => $core->__('E.g., Thank the customer for their patience and explain that we are investigating the issue...'),
            'Tone' => $core->__('Tone'),
            'Generate Reply' => $core->__('Generate Reply'),
            'Refine' => $core->__('Refine'),
            'How would you like to refine the reply?' => $core->__('How would you like to refine the reply?'),
            'E.g., Make it shorter, add more details about the refund policy, be more empathetic...' => $core->__('E.g., Make it shorter, add more details about the refund policy, be more empathetic...'),
            'Refine Reply' => $core->__('Refine Reply'),
            'History' => $core->__('History'),
            'No history' => $core->__('No history'),
            'Tool' => $core->__('Tool'),
            '%d Agents' => $core->__('%d Agents'),
            '%d Categories' => $core->__('%d Categories'),
            '%d Tags' => $core->__('%d Tags'),
            '403' => $core->__('403'),
            '404' => $core->__('404'),
            '500' => $core->__('500'),
            'Add Note' => $core->__('Add Note'),
            'Add Now' => $core->__('Add Now'),
            'Already have an account?' => $core->__('Already have an account?'),
            'Attachments (%d)' => $core->__('Attachments (%d)'),
            'Back Home' => $core->__('Back Home'),
            'Clear History' => $core->__('Clear History'),
            'Code:' => $core->__('Code:'),
            'Copied' => $core->__('Copied'),
            'Copy' => $core->__('Copy'),
            'EDD Orders (%d)' => $core->__('EDD Orders (%d)'),
            'Enrolled' => $core->__('Enrolled'),
            'Envato Items (%d)' => $core->__('Envato Items (%d)'),
            'Failed to generate reply.' => $core->__('Failed to generate reply.'),
            'Failed to refine reply.' => $core->__('Failed to refine reply.'),
            'File size must be smaller than %s.' => $core->__('File size must be smaller than %s.'),
            'Generated' => $core->__('Generated'),
            'Generated Content:' => $core->__('Generated Content:'),
            'Generated Reply' => $core->__('Generated Reply'),
            'Generating...' => $core->__('Generating...'),
            'History cleared.' => $core->__('History cleared.'),
            'Insert Reply' => $core->__('Insert Reply'),
            'Instructions:' => $core->__('Instructions:'),
            'Invalid data.' => $core->__('Invalid data.'),
            'Invalid file type.' => $core->__('Invalid file type.'),
            'License:' => $core->__('License:'),
            'No content to refine.' => $core->__('No content to refine.'),
            'Note by %s' => $core->__('Note by %s'),
            'Order Details' => $core->__('Order Details'),
            'Others' => $core->__('Others'),
            'Password do not match!' => $core->__('Password do not match!'),
            'Payment' => $core->__('Payment'),
            'Placeholders' => $core->__('Placeholders'),
            'Please enter instructions for generating the reply.' => $core->__('Please enter instructions for generating the reply.'),
            'Please enter refinement instructions.' => $core->__('Please enter refinement instructions.'),
            'Products' => $core->__('Products'),
            'Purchase:' => $core->__('Purchase:'),
            'Refined' => $core->__('Refined'),
            'Refinement Instructions:' => $core->__('Refinement Instructions:'),
            'Refining...' => $core->__('Refining...'),
            'Reply generated successfully.' => $core->__('Reply generated successfully.'),
            'Reply inserted from history.' => $core->__('Reply inserted from history.'),
            'Reply refined successfully.' => $core->__('Reply refined successfully.'),
            'Select' => $core->__('Select'),
            'Select option' => $core->__('Select option'),
            'Shipping' => $core->__('Shipping'),
            'Sorry, something went wrong.' => $core->__('Sorry, something went wrong.'),
            'Sorry, the page you visited does not exist.' => $core->__('Sorry, the page you visited does not exist.'),
            'Sorry, you are not authorized to access this page.' => $core->__('Sorry, you are not authorized to access this page.'),
            'Submit Now' => $core->__('Submit Now'),
            'Support:' => $core->__('Support:'),
            'Thank you for reaching out!' => $core->__('Thank you for reaching out!'),
            'Totals' => $core->__('Totals'),
            'Tutor LMS Courses (%d)' => $core->__('Tutor LMS Courses (%d)'),
            'Undo' => $core->__('Undo'),
            'View Details' => $core->__('View Details'),
            'reCAPTCHA verification failed' => $core->__('reCAPTCHA verification failed'),
            'Recent Orders (%d)' => $core->__('Recent Orders (%d)'),
            'Success! Your ticket has been created.' => $core->__('Success! Your ticket has been created.'),
            'Our support team will review it and respond soon.' => $core->__('Our support team will review it and respond soon.'),
            'You\'ve selected %d tickets to reply to at once.' => $core->__('You\'ve selected %d tickets to reply to at once.'),
            'You\'ve selected %d tickets to add internal note to at once.' => $core->__('You\'ve selected %d tickets to add internal note to at once.'),
            'For the best performance and reliability, we recommend selecting no more than 20 tickets at a time.' => $core->__('For the best performance and reliability, we recommend selecting no more than 20 tickets at a time.'),
            'Also viewing:' => $core->__('Also viewing:'),
            'Change Ticket User' => $core->__('Change Ticket User'),
            'Edit Ticket User' => $core->__('Edit Ticket User'),
            'Edit User' => $core->__('Edit User'),
            'More' => $core->__('More'),
            'This ticket already belongs to the selected user.' => $core->__('This ticket already belongs to the selected user.'),
        ];

        return $texts;
    }

    /**
     * ElevenLabs settings data (Pro only).
     */
    public function dataApiKeysElevenLabs()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('ElevenLabs is a Pro feature.'));
        echo wp_json_encode($apiResponse);
    }

    /**
     * Save ElevenLabs settings (Pro only).
     */
    public function AjaxRequestCallbackApiKeysElevenLabs()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('ElevenLabs is a Pro feature.'));
        echo wp_json_encode($apiResponse);
    }

    /**
     * Fetch ElevenLabs voices (Pro only).
     */
    public function AjaxRequestCallbackElevenLabsVoices()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('ElevenLabs is a Pro feature.'));
        echo wp_json_encode($apiResponse);
    }

    /**
     * Fetch ElevenLabs agents (Pro only).
     */
    public function AjaxRequestCallbackElevenLabsAgents()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('ElevenLabs is a Pro feature.'));
        echo wp_json_encode($apiResponse);
    }
}
