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
    public static function get_portal_url($link, $ver = "1.0.0")
    {
        $url = plugins_url("portal/" . $link . "?v=" . $ver, self::GetInstance()->pluginFile);

        // Adjust URL to match current request's host (fixes www/non-www CORS issues)
        return ApbdWps_AdjustUrlToCurrentHost($url);
    }
    public function initialize()
    {
        parent::initialize();
        $this->SetIsLoadJqGrid(true);
        $this->SetPluginIconClass("dashicons-logo-icon", 'dashicons-logo-icon');
        $this->setSetActionPrefix("apbd_wps");
        $this->AddModule("Apbd_wps_role");
        $this->AddModule("Apbd_wps_ticket_category");
        $this->AddModule("Apbd_wps_ticket_tag");
        $this->AddModule("Apbd_wps_ticket_assign_rule");
        $this->AddModule("Apbd_wps_email_template");
        $this->AddModule("Apbd_wps_canned_msg");
        $this->AddModule("Apbd_wps_custom_field");
        $this->AddModule("Apbd_wps_help_me_write");
        $this->AddModule("Apbd_wps_woocommerce");
        $this->AddModule("Apbd_wps_edd");
        $this->AddModule("Apbd_wps_fluentcrm");
        $this->AddModule("Apbd_wps_whatsapp");
        $this->AddModule("Apbd_wps_slack");
        $this->AddModule("Apbd_wps_envato_system");
        $this->AddModule("Apbd_wps_elite_licenser");
        $this->AddModule("Apbd_wps_tutorlms");
        $this->AddModule("Apbd_wps_betterdocs");
        $this->AddModule("Apbd_wps_webhook");
        $this->AddModule("Apbd_wps_incoming_webhook");
        $this->AddModule("Apbd_wps_ht_contact_form");
        $this->AddModule("Apbd_wps_mailbox");
        $this->AddModule("Apbd_wps_email_to_ticket");
        $this->AddModule("Apbd_wps_ticket");
        $this->AddModule("Apbd_wps_ticket_reply");
        $this->AddModule("Apbd_wps_users");
        $this->AddModule("Apbd_wps_settings");
        $this->AddModule("Apbd_wps_report");
        $this->AddModule("Apbd_wps_report_email");
        $this->AddModule("Apbd_wps_weekend");
        $this->AddModule("Apbd_wps_google");
        $this->AddModule("Apbd_wps_debug_log");
        $this->AddModule("Apbd_wps_knowledge_base");
        $this->AddModule("Apbd_wps_elevenlabs");
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
        if (!is_admin() && strpos($_SERVER['REQUEST_URI'], '/wp-json/apbd-wps/') === false) {
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
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com",
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
        $dist_path = untrailingslashit($base_path) . "/dashboard/dist";
        $dist_files = ApbdWps_GetFilesInDirectory($dist_path, 'css');

        if (is_array($dist_files) && !empty($dist_files)) {
            foreach ($dist_files as $file_name) {
                if (0 === strpos($file_name, 'main.')) {
                    $this->AddAdminStyle($this->support_genix_assets_slug . "-dashboard-main", "dashboard/dist/{$file_name}", true);
                }
            }
        } else {
            $this->AddAdminStyle($this->support_genix_assets_slug . "-dashboard-main", "dashboard/dist/main.BTLKop_i.1773217505502.css", true);
        }

        foreach ($this->moduleList as $moduleObject) {
            $moduleObject->AdminStyles();
        }
    }
    function OnAdminAppScripts()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();

        $base_path = plugin_dir_path($this->pluginFile);
        $dist_path = untrailingslashit($base_path) . "/dashboard/dist";
        $dist_files = ApbdWps_GetFilesInDirectory($dist_path, 'js');

        if (is_array($dist_files) && !empty($dist_files)) {
            foreach ($dist_files as $file_name) {
                if (0 === strpos($file_name, 'main.')) {
                    $this->AddAdminScript($this->support_genix_assets_slug . "-dashboard-main", "dashboard/dist/{$file_name}", true);
                }
            }
        } else {
            $this->AddAdminScript($this->support_genix_assets_slug . "-dashboard-main", "dashboard/dist/main.CmOl94vI.1773217505502.js", true);
        }

        $userObj = wp_get_current_user();
        $isAdminUser = current_user_can('manage_options') || is_super_admin($userObj->ID) || in_array('administrator', $userObj->roles);

        wp_localize_script($this->support_genix_assets_slug . "-dashboard-main", "support_genix_config", [
            'lite' => true,
            'demo' => $coreObject->isDemoMode(),
            'logged_id' => get_current_user_id(),
            'is_master' => Apbd_wps_settings::isAgentLoggedIn(),
            'is_admin' => $isAdminUser,
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
            'pricing_url' => 'https://supportgenix.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free',
            'texts' => Apbd_wps_settings::dashboard_texts(),
            'debug' => defined('WP_DEBUG') ? !!WP_DEBUG : false,
        ]);

        add_filter('script_loader_tag', function ($tag, $handle, $src) {
            if ('support-genix-dashboard-main' === $handle) {
                $ats = 'type="module" src="' . esc_url($src) . '" id="support-genix-dashboard-main-js"';
                $tag = '<script ' . wp_kses_post($ats) . '></script>';
            }

            return $tag;
        }, 10, 3);

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
