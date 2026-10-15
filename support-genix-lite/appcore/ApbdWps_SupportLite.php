<?php

/**
 * Support.
 */

defined('ABSPATH') || exit;

ApbdWps_LoadCore("ApbdWpsKarnelLite", "ApbdWpsKarnelLite", __FILE__);

class ApbdWps_SupportLite extends ApbdWpsKarnelLite
{
    function __construct($pluginBaseFile, $version = '1.0.0')
    {
        $this->pluginFile       = $pluginBaseFile;
        $this->pluginBaseName   = 'support-genix';
        $this->pluginName       = 'Support Genix';
        $this->pluginVersion    = $version;
        $this->bootstrapVersion = '4.6.0';
        parent::__construct($pluginBaseFile, $version);

        if (!defined('SUPPORTGENIX_DEMO')) {
            define('SUPPORTGENIX_DEMO', false);
        }
        $this->setIsDemoMode(SUPPORTGENIX_DEMO);
    }
    public function initialize()
    {
        parent::initialize();
        $this->SetIsLoadJqGrid(true);
        $this->SetPluginIconClass("dashicons-logo-icon", 'dashicons-logo-icon');
        $this->setSetActionPrefix("apbd_wps");

        $modules = [
            'Apbd_wps_role',
            'Apbd_wps_ticket_category',
            'Apbd_wps_ticket_tag',
            'Apbd_wps_ticket_assign_rule',
            'Apbd_wps_email_template',
            'Apbd_wps_canned_msg',
            'Apbd_wps_custom_field',
            'Apbd_wps_help_me_write',
            'Apbd_wps_woocommerce',
            'Apbd_wps_edd',
            'Apbd_wps_fluentcrm',
            'Apbd_wps_whatsapp',
            'Apbd_wps_slack',
            'Apbd_wps_envato_system',
            'Apbd_wps_elite_licenser',
            'Apbd_wps_tutorlms',
            'Apbd_wps_betterdocs',
            'Apbd_wps_webhook',
            'Apbd_wps_incoming_webhook',
            'Apbd_wps_ht_contact_form',
            'Apbd_wps_mailbox',
            'Apbd_wps_email_to_ticket',
            'Apbd_wps_ticket',
            'Apbd_wps_ticket_reply',
            'Apbd_wps_users',
            'Apbd_wps_settings',
            'Apbd_wps_report',
            'Apbd_wps_report_email',
            'Apbd_wps_weekend',
            'Apbd_wps_google',
            'Apbd_wps_debug_log',
            'Apbd_wps_knowledge_base',
            'Apbd_wps_elevenlabs',
            'Apbd_wps_recommended_plugins',
        ];

        // Libs the modules call unconditionally at boot.
        $libs = [
            'Apbd_Wps_AttachmentStreamer',
            'Apbd_Wps_GuestSession',
            'Apbd_Wps_RecaptchaVerify',
            'Apbd_Wps_UploadDir',
        ];

        // Traits the module files require_once; a deleted one must be reported, not fatal.
        $traits = [
            'ApbdWpsAPI_TicketTrait',
            'Mapbd_wps_ticket_trait',
            'Apbd_wps_ai_proxy_trait',
            'Apbd_wps_help_me_write_generate_trait',
            'Apbd_wps_knowledge_base_analytics_trait',
            'Apbd_wps_knowledge_base_chatbot_trait',
            'Apbd_wps_knowledge_base_chatquery_trait',
            'Apbd_wps_knowledge_base_chatticket_trait',
            'Apbd_wps_knowledge_base_duplicator_trait',
            'Apbd_wps_knowledge_base_hierarchy_trait',
            'Apbd_wps_knowledge_base_metabox_trait',
            'Apbd_wps_knowledge_base_migrations_trait',
            'Apbd_wps_knowledge_base_searches_trait',
            'Apbd_wps_knowledge_base_shortcode_trait',
            'Apbd_wps_knowledge_base_statistics_trait',
            'Apbd_wps_knowledge_base_writebot_trait',
            'Apbd_wps_settings_blocks_trait',
            'Apbd_wps_ticket_migration_create_trait',
            'Apbd_wps_ticket_migration_trait',
        ];

        // Check every boot file first; a partial boot would fatal later.
        if (! $this->PreflightModules($modules, $libs, $traits)) {
            return;
        }

        foreach ($modules as $module) {
            $this->AddModule($module);
        }
    }
    function _myautoload_method($class)
    {
        $basepath = plugin_dir_path($this->pluginFile);

        $filename = $basepath . "api/{$class}.php";
        if (file_exists($filename)) {
            ApbdWps_LoadAny($filename, $class);
        } else {
            $isFound = false;
            foreach (['v1'] as $subpath) {
                $filename = $basepath . "api/{$subpath}/{$class}.php";

                if (file_exists($filename)) {
                    $isFound = true;
                    ApbdWps_LoadPluginAPI($class, $subpath);
                }
            }
            if (!$isFound) {
                parent::_myautoload_method($class);
            }
        }
    }
    public function OnInit()
    {
        parent::OnInit();

        // Add security headers including CSP to prevent XSS attacks
        add_action('send_headers', array($this, 'add_security_headers'));

        add_action('rest_api_init', function () {
            if (!headers_sent()) {
                header("Access-Control-Allow-Origin: *");
            }
            $namespace = self::getNamespaceStr();
            new ApbdWpsAPI_User($namespace);
            new ApbdWpsAPI_Ticket($namespace);
            new ApbdWpsAPI_Config($namespace);
            new ApbdWpsAPI_Portal($namespace);
            new ApbdWpsAPI_Chatbot($namespace);
        });
    }

    /**
     * Add security headers including Content Security Policy to prevent XSS attacks
     *
     * @since 1.4.30
     */
    public function add_security_headers()
    {
        // Only apply to plugin pages and REST API endpoints
        if (!is_admin() && strpos((isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : ''), '/wp-json/apbd-wps/') === false) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        // X-Content-Type-Options: Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // X-Frame-Options: Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // X-XSS-Protection: Enable browser XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer-Policy: Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content-Security-Policy: Comprehensive XSS protection
        // Note: This is a moderate policy that allows necessary functionality while blocking XSS
        $csp_directives = array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com", // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- CSP allowlist entry (reCAPTCHA); nothing is loaded from these hosts here.
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https:",
            "frame-src 'self' https://www.google.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        );

        $csp = implode('; ', $csp_directives);
        header("Content-Security-Policy: {$csp}");
    }


    function OnAdminAppStyles()
    {
        wp_enqueue_media();

        $base_path = plugin_dir_path($this->pluginFile);
        $dist_path = untrailingslashit($base_path) . "/assets/apps/dashboard";
        $manifest = ApbdWps_GetAppManifest($dist_path);

        if ('' !== $manifest['css']) {
            $this->AddAdminStyle($this->support_genix_assets_slug . "-dashboard-main", "assets/apps/dashboard/{$manifest['css']}", true);
        }

        foreach ($this->moduleList as $moduleObject) {
            $moduleObject->AdminStyles();
        }
    }
    function OnAdminAppScripts()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();

        $base_path = plugin_dir_path($this->pluginFile);
        $dist_path = untrailingslashit($base_path) . "/assets/apps/dashboard";
        $manifest = ApbdWps_GetAppManifest($dist_path);

        if ('' !== $manifest['js']) {
            $this->AddAdminScript($this->support_genix_assets_slug . "-dashboard-main", "assets/apps/dashboard/{$manifest['js']}", true);
        }

        $userObj = wp_get_current_user();
        $isAdminUser = current_user_can('manage_options') || is_super_admin($userObj->ID) || in_array('administrator', $userObj->roles);

        wp_localize_script($this->support_genix_assets_slug . "-dashboard-main", "support_genix_config", [
            'lite' => true,
            'demo' => $coreObject->isDemoMode(),
            'logged_id' => get_current_user_id(),
            'is_master' => Apbd_wps_settings::isAgentLoggedIn(),
            'is_admin' => $isAdminUser,
            'ai_proxy_visible' => Apbd_wps_settings::IsAIProxyVisible(),
            'admin_url' => admin_url(),
            'post_url' => admin_url('admin-ajax.php'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('ajax-nonce'),
            'license_nonce' => wp_create_nonce('apbd-el-license-r'),
            'license_email' => get_option("apbd_wps_license_email", get_bloginfo('admin_email')),
            'admin_email' => get_bloginfo('admin_email'),
            'multi_lang' => apply_filters("apbd-wps/multi-language", ['code' => 'en', 'status' => 'I']),
            'wp_timezone' => ApbdWps_TimezoneString(),
            'wp_settings_url' => admin_url('options-general.php'),
            'tinymce_base' => includes_url('js/tinymce'),
            'wp_version' => get_bloginfo('version'),
            'version' => $this->pluginVersion,
            'is_rtl' => is_rtl(),
            'wp_locale' => is_textdomain_loaded('support-genix-lite') ? get_user_locale() : '',
            'pricing_url' => 'https://supportgenix.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free',
            'texts' => Apbd_wps_settings::dashboard_texts(),
            'debug' => defined('WP_DEBUG') ? !!WP_DEBUG : false,
        ]);

        add_filter('wp_script_attributes', function ($attributes) {
            if (isset($attributes['id']) && 'support-genix-dashboard-main-js' === $attributes['id']) {
                $attributes['type'] = 'module';
            }

            return $attributes;
        });

        if (version_compare(get_bloginfo('version'), '6.3', '<')) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                if ('support-genix-dashboard-main' === $handle) {
                    $ats = 'type="module" src="' . esc_url($src) . '" id="support-genix-dashboard-main-js"';
                    $tag = '<script ' . wp_kses_post($ats) . '></script>';
                }

                return $tag;
            }, 10, 3);
        }

        foreach ($this->moduleList as $moduleObject) {
            $moduleObject->AdminScripts();
        }
    }
    static function getNamespaceStr()
    {
        return "apbd-wps/v1";
    }

    function GetHeaderHtml()
    {
        // TODO: Implement GetHeaderHtml() method.
    }

    function GetFooterHtml()
    {
        // TODO: Implement GetFooterHtml() method.
    }


    function WPAdminCheckDefaultCssScript($src)
    {
        if (!parent::WPAdminCheckDefaultCssScript($src)) {
            if (empty($src) || $src == 1 || preg_match("/\/plugins\/query-monitor\//", $src)) {
                return true;
            }
        } else {
            return true;
        }
    }
    public function OnAdminGlobalStyles()
    {
        parent::OnAdminGlobalStyles();
    }
    static function StartApp($fileName) {}

    function OptionFormBase()
    {
        echo '<div id="support-genix"></div>';
    }
}
