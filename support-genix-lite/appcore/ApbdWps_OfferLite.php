<?php

/**
 * Offer.
 */

// If this file is accessed directly, exit.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class.
 */
if (! class_exists('ApbdWps_OfferLite')) {
    final class ApbdWps_OfferLite
    {

        /**
         * Prefix.
         */
        public $prefix;

        /**
         * Pro file.
         */
        public $pro_file;

        /**
         * Data center.
         */
        public $data_center;

        /**
         * Initial page.
         */
        public $initial_page;

        /**
         * Screen base.
         */
        public $screen_base;

        /**
         * Instance.
         */
        public static $_instance = null;

        /**
         * Get instance.
         */
        public static function get_instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Constructor.
         */
        public function __construct()
        {
            $this->includes();

            $this->prefix = 'support_genix_lite';
            $this->pro_file = 'support-genix/support-genix.php';
            $this->data_center = 'https://feed.hasthemes.com/support-genix-lite/tw/';
            $this->initial_page = admin_url('admin.php?page=support-genix');
            $this->screen_base = 'toplevel_page_support-genix';

            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('admin_init', [$this, 'run_offer'], 999999);

            $this->check_transient_deletion();
        }

        /**
         * Includes.
         */
        public function includes()
        {
            if (! function_exists('is_plugin_active') || ! function_exists('get_plugins') || ! function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
        }

        /**
         * Enqueue scripts.
         */
        public function enqueue_scripts()
        {
            if (! $this->is_plugin_screen()) {
                return;
            }

            add_thickbox();
        }

        /**
         * Run offer.
         */
        public function run_offer()
        {
            if ($this->is_pro_installed() || ! $this->is_capable_user()) {
                return;
            }

            $this->set_offer();
            $this->show_offer();
        }

        /**
         * Is pro installed.
         */
        public function is_pro_installed()
        {
            $plugins = get_plugins();
            $result = (isset($plugins[$this->pro_file]) ? true : false);

            if ($result) {
                update_option($this->prefix . '_htiop', 'no');
                update_option($this->prefix . '_htiop_bar', 'no');
                update_option($this->prefix . '_htiop_popup', 'no');
                update_option($this->prefix . '_htiop_redirect', 'no');
            }

            return $result;
        }

        /**
         * Is capable user.
         */
        public function is_capable_user()
        {
            $result = false;

            if (current_user_can('manage_options')) {
                $result = true;
            }

            return $result;
        }

        /**
         * Is show offer bar.
         */
        public function is_show_offer_bar()
        {
            $result = get_option($this->prefix . '_htiop_bar');
            $result = 'yes' === $result ? true : false;

            return $result;
        }

        /**
         * Is show offer popup.
         */
        public function is_show_offer_popup()
        {
            $result = get_option($this->prefix . '_htiop_popup');
            $result = 'yes' === $result ? true : false;

            return $result;
        }

        /**
         * Is plugin screen.
         */
        public function is_plugin_screen()
        {
            $screen = get_current_screen();
            $base = isset($screen->base) ? $screen->base : "";
            $result = ($this->screen_base === $base) ? true : false;

            return $result;
        }

        /**
         * Is valid JSON.
         */
        public function is_valid_json($json = '')
        {
            if (is_string($json) && ! empty($json)) {
                @json_decode($json);
                return (json_last_error() === JSON_ERROR_NONE);
            }

            return false;
        }

        /**
         * Set offer data.
         */
        public function set_offer_data()
        {
            $setted = get_option($this->prefix . '_htiop_data');
            $setted = 'yes' === $setted ? true : false;

            if ($setted) {
                return;
            }

            $ex_data = get_transient($this->prefix . '_htiop_data');
            $ex_data = $this->is_valid_json($ex_data) ? json_decode($ex_data, true) : $ex_data;

            if (is_array($ex_data) && ! empty($ex_data)) {
                return;
            }

            $response = wp_remote_get($this->data_center, ['timeout' => 120, 'headers' => ['Countme' => 1]]);
            $code = wp_remote_retrieve_response_code($response);

            if (200 !== $code) {
                return;
            }

            // $body = wp_remote_retrieve_body($response);
            $body = '{"data":{"bar":{"style":".htiop-bar-notice,.htiop-bar-notice *,.htiop-bar-notice ::after,.htiop-bar-notice ::before,.htiop-bar-notice::after,.htiop-bar-notice::before{box-sizing:border-box!important}.htiop-bar-notice{overflow:hidden!important;background:#fff!important;border-width:0 0 1px 0!important;border-style:solid!important;border-color:rgba(0,0,0,.125)!important;border-radius:0!important;box-shadow:none!important;padding:0!important;margin:0!important}.htiop-bar-main{display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;padding:12px!important}.htiop-bar-main .htiop-d1{display:none!important;width:100%!important}.htiop-bar-main .htiop-message{flex:0 0 auto!important;width:auto!important;max-width:100%!important;min-height:1px!important;display:inline-block!important;padding:0 18px 0 0!important}.htiop-bar-main .htiop-p1{font-size:20px!important;font-weight:400!important;line-height:30px!important;text-align:center!important;color:#4d6074!important;margin:0!important}.htiop-bar-main .htiop-p1-s1,.htiop-bar-main .htiop-p1-s2{color:#ff6e30!important}.htiop-bar-main .htiop-cta{flex:0 0 250px!important;width:100%!important;max-width:250px!important;min-height:1px!important;padding:0 0 0 18px!important}.htiop-bar-main .htiop-action-btn,.htiop-bar-main .htiop-action-btn:focus{display:block!important;background:#ff6e30!important;border:1px solid #ff6e30!important;border-radius:6px!important;font-size:14px!important;font-weight:600!important;line-height:18px!important;text-decoration:none!important;text-align:center!important;color:#fff!important;padding:8px 10px!important;box-shadow:none!important;outline:0!important}.htiop-bar-main .htiop-action-btn:hover{background:#ec5c1e!important;border-color:#ec5c1e!important;text-decoration:none!important;box-shadow:none!important;outline:0!important}@media only screen and (max-width:1295px){.htiop-bar-main .htiop-d1{display:block!important}.htiop-bar-main .htiop-message{padding:0!important;margin:0 0 12px!important}.htiop-bar-main .htiop-cta{padding:0!important}}@media only screen and (max-width:895px){.htiop-bar-main .htiop-p1{font-size:18px!important;line-height:23px!important}}","content":"<div class=\"htiop-bar-notice\"><div class=\"htiop-bar-main\"><div class=\"htiop-message\"><p class=\"htiop-p1\">Try the Support Genix Pro version <span class=\"htiop-p1-s1\">for just $1<\/span> in the first month, and <span class=\"htiop-p1-s2\">cancel anytime<\/span>!<\/p><\/div><div class=\"htiop-d1\"><\/div><div class=\"htiop-cta\"><div class=\"htiop-action\"><a class=\"htiop-action-btn\" target=\"_blank\" href=\"https:\/\/supportgenix.com\/pricing\/?utm_source=onedollar&utm_medium=admin-offer-bar\">Yes, I want to explore<\/a><\/div><\/div><\/div><\/div>"},"bart":{"style":".htiop-bar-notice,.htiop-bar-notice *,.htiop-bar-notice ::after,.htiop-bar-notice ::before,.htiop-bar-notice::after,.htiop-bar-notice::before{box-sizing:border-box!important}.htiop-bar-notice{overflow:hidden!important;background:#fff!important;border:1px solid rgba(0,0,0,.125)!important;border-radius:9px!important;box-shadow:0 0 5px -3px #bababa!important;padding:0!important;margin:15px 15px -15px -5px!important}.htiop-bar-main{display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;padding:12px!important}.htiop-bar-main .htiop-d1,.htiop-bar-main .htiop-d2{display:none!important;width:100%!important}.htiop-bar-main .htiop-timer{flex:0 0 auto!important;width:auto!important;max-width:none!important;min-height:1px!important;display:inline-flex!important;flex-wrap:wrap!important;justify-content:center!important;padding:0!important;margin:0!important}.htiop-bar-main .htiop-timer-item{background:#fff!important;border:1px dashed rgba(111,139,169,.3)!important;border-radius:3px!important;box-shadow:0 5px 10px 0 rgba(111,139,169,.3)!important;max-width:100%!important;min-width:72px!important;text-align:center!important;padding:11px 5px!important;margin:0!important}.htiop-bar-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 10px!important}.htiop-bar-main .htiop-timer-time{font-size:22px!important;font-weight:500!important;line-height:26px!important;color:#ff6e30!important;margin:0!important}.htiop-bar-main .htiop-timer-unit{font-size:10px!important;font-weight:400!important;line-height:12px!important;letter-spacing:.3px!important;color:#6f8ba9!important;margin:0!important}.htiop-bar-main .htiop-message{flex:0 0 auto!important;width:auto!important;max-width:100%!important;min-height:1px!important;display:inline-block!important;padding:0 36px!important}.htiop-bar-main .htiop-p1{font-size:20px!important;font-weight:500!important;line-height:25px!important;text-align:center!important;color:#4d6074!important;margin:0!important}.htiop-bar-main .htiop-p1-s1,.htiop-bar-main .htiop-p1-s2{color:#ff6e30!important}.htiop-bar-main .htiop-cta{flex:0 0 250px!important;width:100%!important;max-width:250px!important;min-height:1px!important;padding:0!important}.htiop-bar-main .htiop-action-btn,.htiop-bar-main .htiop-action-btn:focus{display:block!important;background:#ff6e30!important;border:1px solid #ff6e30!important;border-radius:6px!important;font-size:14px!important;font-weight:600!important;line-height:18px!important;text-decoration:none!important;text-align:center!important;color:#fff!important;padding:8px 10px!important;box-shadow:none!important;outline:0!important}.htiop-bar-main .htiop-action-btn:hover{background:#ec5c1e!important;border-color:#ec5c1e!important;text-decoration:none!important;box-shadow:none!important;outline:0!important}@media only screen and (max-width:1200px){.htiop-bar-main .htiop-d1{display:block!important}.htiop-bar-main .htiop-timer{margin:0 0 12px!important}.htiop-bar-main .htiop-message{padding:0 36px 0 0!important}}@media only screen and (max-width:895px){.htiop-bar-main .htiop-d2{display:block!important}.htiop-bar-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 5px!important}.htiop-bar-main .htiop-timer-item{min-width:62px!important;padding:11px 2px!important}.htiop-bar-main .htiop-message{padding:0!important;margin:0 0 12px!important}.htiop-bar-main .htiop-p1{font-size:18px!important;line-height:23px!important}}@media only screen and (max-width:400px){.htiop-bar-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 3px!important}}","content":"<div class=\"htiop-bar-notice\"><div class=\"htiop-bar-main\"><div class=\"htiop-timer\"><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-days\">00<\/div><div class=\"htiop-timer-unit\">Days<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-hours\">00<\/div><div class=\"htiop-timer-unit\">Hours<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-minutes\">00<\/div><div class=\"htiop-timer-unit\">Minutes<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-seconds\">00<\/div><div class=\"htiop-timer-unit\">Seconds<\/div><\/div><\/div><div class=\"htiop-d1\"><\/div><div class=\"htiop-message\"><p class=\"htiop-p1\">Try the Support Genix Pro version <span class=\"htiop-p1-s1\">for just $1<\/span><br>in the first month, and <span class=\"htiop-p1-s2\">cancel anytime<\/span>!<\/div><div class=\"htiop-d2\"><\/div><div class=\"htiop-cta\"><div class=\"htiop-action\"><a class=\"htiop-action-btn\" target=\"_blank\" href=\"https:\/\/supportgenix.com\/trial-2\/?utm_source=onedollar&utm_medium=popup\">Yes, I want to explore<\/a><\/div><\/div><\/div><\/div>"},"barnt":{"style":".htiop-bar-notice,.htiop-bar-notice *,.htiop-bar-notice ::after,.htiop-bar-notice ::before,.htiop-bar-notice::after,.htiop-bar-notice::before{box-sizing:border-box!important}.htiop-bar-notice{overflow:hidden!important;background:#fff!important;border-width:0 0 1px 0!important;border-style:solid!important;border-color:rgba(0,0,0,.125)!important;border-radius:0!important;box-shadow:none!important;padding:0!important;margin:0!important}.htiop-bar-main{display:flex!important;flex-wrap:wrap!important;align-items:center!important;justify-content:center!important;padding:12px!important}.htiop-bar-main .htiop-d1{display:none!important;width:100%!important}.htiop-bar-main .htiop-message{flex:0 0 auto!important;width:auto!important;max-width:100%!important;min-height:1px!important;display:inline-block!important;padding:0 18px 0 0!important}.htiop-bar-main .htiop-p1{font-size:20px!important;font-weight:400!important;line-height:30px!important;text-align:center!important;color:#4d6074!important;margin:0!important}.htiop-bar-main .htiop-p1-s1,.htiop-bar-main .htiop-p1-s2{color:#ff6e30!important}.htiop-bar-main .htiop-cta{flex:0 0 250px!important;width:100%!important;max-width:250px!important;min-height:1px!important;padding:0 0 0 18px!important}.htiop-bar-main .htiop-action-btn,.htiop-bar-main .htiop-action-btn:focus{display:block!important;background:#ff6e30!important;border:1px solid #ff6e30!important;border-radius:6px!important;font-size:14px!important;font-weight:600!important;line-height:18px!important;text-decoration:none!important;text-align:center!important;color:#fff!important;padding:8px 10px!important;box-shadow:none!important;outline:0!important}.htiop-bar-main .htiop-action-btn:hover{background:#ec5c1e!important;border-color:#ec5c1e!important;text-decoration:none!important;box-shadow:none!important;outline:0!important}@media only screen and (max-width:1295px){.htiop-bar-main .htiop-d1{display:block!important}.htiop-bar-main .htiop-message{padding:0!important;margin:0 0 12px!important}.htiop-bar-main .htiop-cta{padding:0!important}}@media only screen and (max-width:895px){.htiop-bar-main .htiop-p1{font-size:18px!important;line-height:23px!important}}","content":"<div class=\"htiop-bar-notice\"><div class=\"htiop-bar-main\"><div class=\"htiop-message\"><p class=\"htiop-p1\">Try the Support Genix Pro version <span class=\"htiop-p1-s1\">for just $1<\/span> in the first month, and <span class=\"htiop-p1-s2\">cancel anytime<\/span>!<\/p><\/div><div class=\"htiop-d1\"><\/div><div class=\"htiop-cta\"><div class=\"htiop-action\"><a class=\"htiop-action-btn\" target=\"_blank\" href=\"https:\/\/supportgenix.com\/pricing\/?utm_source=onedollar&utm_medium=admin-offer-bar\">Yes, I want to explore<\/a><\/div><\/div><\/div><\/div>"},"popup":{"style":".htiop-popup-main,.htiop-popup-main *,.htiop-popup-main ::after,.htiop-popup-main ::before,.htiop-popup-main::after,.htiop-popup-main::before{box-sizing:border-box!important}.htiop-popup-main{background:#fff!important;border-radius:9px!important;width:600px!important;max-width:100%!important;padding:44px!important}.htiop-popup-main .htiop-p1{font-size:20px!important;font-weight:500!important;line-height:24px!important;text-align:center!important;color:#4d6074!important;margin:0 0 5px!important}.htiop-popup-main .htiop-p2{font-size:32px!important;font-weight:600!important;line-height:40px!important;text-align:center!important;color:#4d6074!important;margin:0 0 25px!important}.htiop-popup-main .htiop-timer{display:flex!important;justify-content:center!important;margin:0 0 30px!important}.htiop-popup-main .htiop-timer-item{background:#fff!important;border:1px dashed rgba(111,139,169,.3)!important;border-radius:3px!important;box-shadow:0 5px 10px 0 rgba(111,139,169,.3)!important;max-width:100%!important;min-width:80px!important;text-align:center!important;padding:22px 13px!important;margin:0!important}.htiop-popup-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 10px!important}.htiop-popup-main .htiop-timer-time{font-size:28px!important;font-weight:500!important;line-height:32px!important;color:#ff6e30!important;margin:0!important}.htiop-popup-main .htiop-timer-unit{font-size:10px!important;font-weight:400!important;line-height:12px!important;letter-spacing:.3px!important;color:#6f8ba9!important;margin:0!important}.htiop-popup-main .htiop-p3{font-size:20px!important;font-weight:500!important;line-height:26px!important;text-align:center!important;color:#4d6074!important;margin:0 0 25px!important}.htiop-popup-main .htiop-action-btn,.htiop-popup-main .htiop-action-btn:focus{display:block!important;background:#ff6e30!important;border:1px solid #ff6e30!important;border-radius:6px!important;font-size:18px!important;font-weight:600!important;line-height:26px!important;text-decoration:none!important;text-align:center!important;color:#fff!important;padding:15px 10px!important;box-shadow:none!important;outline:0!important}.htiop-popup-main .htiop-action-btn:hover{background:#ec5c1e!important;border-color:#ec5c1e!important;text-decoration:none!important;box-shadow:none!important;outline:0!important}@media only screen and (max-width:600px){.htiop-popup-main .htiop-p1{font-size:18px!important;line-height:22px!important}.htiop-popup-main .htiop-p2{font-size:20px!important;line-height:28px!important}.htiop-popup-main .htiop-p3{font-size:18px!important;line-height:23px!important}.htiop-popup-main .htiop-timer-item{min-width:62px!important;padding:11px 2px!important}.htiop-popup-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 5px!important}.htiop-popup-main .htiop-timer-time{font-size:22px!important;line-height:26px!important}.htiop-popup-main .htiop-action-btn,.htiop-popup-main .htiop-action-btn:focus{font-size:14px!important;line-height:20px!important;padding:8px 10px!important}}@media only screen and (max-width:400px){.htiop-popup-main{padding:44px 12px!important}.htiop-popup-main .htiop-timer-item+.htiop-timer-item{margin:0 0 0 3px!important}}","content":"<div class=\"htiop-popup-main\"><p class=\"htiop-p1\">A special gift for a valued user like YOU \ud83c\udf81<p class=\"htiop-p2\">Try Support Genix PRO version<br>for a month at only $1<div class=\"htiop-timer\"><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-days\">00<\/div><div class=\"htiop-timer-unit\">Days<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-hours\">00<\/div><div class=\"htiop-timer-unit\">Hours<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-minutes\">00<\/div><div class=\"htiop-timer-unit\">Minutes<\/div><\/div><div class=\"htiop-timer-item\"><div class=\"htiop-timer-time htiop-timer-seconds\">00<\/div><div class=\"htiop-timer-unit\">Seconds<\/div><\/div><\/div><p class=\"htiop-p3\">Unleash the full power of Support Genix for $1<br>and cancel anytime!<div class=\"htiop-action\"><a class=\"htiop-action-btn\" target=\"_blank\" href=\"https:\/\/supportgenix.com\/trial-2\/?utm_source=onedollar&utm_medium=popup\">Yes, I want to explore<\/a><\/div><\/div>"}},"timer":21600,"expiry":0}';
            $body = $this->is_valid_json($body) ? json_decode($body, true) : $body;

            if (! is_array($body) || empty($body)) {
                return;
            }

            $data = ((isset($body['data']) && is_array($body['data'])) ? $body['data'] : []);

            if (empty($data)) {
                return;
            }

            $timer = isset($body['timer']) ? absint($body['timer']) : 0;
            $expiry = isset($body['expiry']) ? absint($body['expiry']) : 0;

            $setted = set_transient($this->prefix . '_htiop_data', wp_json_encode($data), $expiry);

            if ($setted) {
                update_option($this->prefix . '_htiop_timer', $timer + current_time('U', true));
                update_option($this->prefix . '_htiop_data', 'yes');
            }
        }

        /**
         * Get offer data.
         */
        public function get_offer_data($type = '')
        {
            $data = get_transient($this->prefix . '_htiop_data');
            $data = $this->is_valid_json($data) ? json_decode($data, true) : $data;

            if (! is_array($data) || empty($data)) {
                return;
            }

            $bar = ((isset($data['bar']) && is_array($data['bar'])) ? $data['bar'] : []);
            $barnt = ((isset($data['barnt']) && is_array($data['barnt'])) ? $data['barnt'] : []);
            $popup = ((isset($data['popup']) && is_array($data['popup'])) ? $data['popup'] : []);

            if (empty($bar) && empty($popup)) {
                return;
            }

            if (! empty($bar) && 'bar' === $type) {
                return $bar;
            }

            if (! empty($barnt) && 'barnt' === $type) {
                return $barnt;
            }

            if (! empty($popup) && 'popup' === $type) {
                return $popup;
            }

            return $data;
        }

        /**
         * Get offer expiry.
         */
        public function get_offer_expiry()
        {
            $expiry = get_option('_transient_timeout_' . $this->prefix . '_htiop_data');
            $expiry = ((false !== $expiry) ? absint($expiry) : -1);

            if (-1 < $expiry) {
                $expiry = ($expiry - current_time('U', true));
                $expiry = (0 < $expiry ? $expiry * 1000 : 0);

                if (! $expiry) {
                    update_option($this->prefix . '_htiop_bar', 'no');
                    update_option($this->prefix . '_htiop_popup', 'no');
                }
            }

            return $expiry;
        }

        /**
         * Get timer expiry.
         */
        public function get_timer_expiry()
        {
            $offer = $this->get_offer_expiry();
            $expiry = 0;

            if ($offer) {
                $timer = get_option($this->prefix . '_htiop_timer', 0);
                $timer = (isset($timer) ? absint($timer) : 0);

                $current = current_time('U', true);

                if ($timer > $current) {
                    $expiry = ($timer - $current);
                    $expiry = (0 < $expiry ? $expiry * 1000 : 0);
                }

                if ((0 < $offer) && ($offer < $expiry)) {
                    $expiry = $offer;
                }
            }

            return $expiry;
        }

        /**
         * Set offer.
         */
        public function set_offer()
        {
            if ($this->is_pro_installed()) {
                return;
            }

            $active = get_option($this->prefix . '_htiop', 'yes');
            $active = 'yes' === $active ? true : false;

            if ($active) {
                update_option($this->prefix . '_htiop', 'no');
                update_option($this->prefix . '_htiop_bar', 'yes');
                update_option($this->prefix . '_htiop_popup', 'yes');
                update_option($this->prefix . '_htiop_redirect', 'yes');

                $this->start_redirect();
            }
        }

        /**
         * Show offer.
         */
        public function show_offer()
        {
            if ($this->is_pro_installed()) {
                return;
            }

            $bar = $this->is_show_offer_bar();
            $popup = $this->is_show_offer_popup();

            if ($bar || $popup) {
                $this->set_offer_data();

                add_action('admin_print_scripts', [$this, 'header_script'], 999999);
                add_action('admin_print_footer_scripts', [$this, 'footer_script'], 999999);
            }

            if ($bar) {
                add_action('admin_notices', function () {
                    remove_all_actions('admin_notices');
                    remove_all_actions('all_admin_notices');
                }, ~PHP_INT_MAX);

                add_action('admin_notices', [$this, 'show_offer_bar'], ~PHP_INT_MAX);
            }

            if ($popup) {
                add_action('admin_footer', [$this, 'show_offer_popup'], 999999);
                add_action('admin_footer', [$this, 'dismiss_redirect'], 999999);
            }
        }

        /**
         * Show offer bar.
         */
        public function show_offer_bar()
        {
            if (! $this->is_plugin_screen()) {
                return;
            }

            $timer = $this->get_timer_expiry();
            $data = $timer ? $this->get_offer_data('bar') : $this->get_offer_data('barnt');

            if (! is_array($data) || empty($data)) {
                return;
            }

            $style = isset($data['style']) ? $data['style'] : '';
            $content = isset($data['content']) ? $data['content'] : '';

            if (empty($content)) {
                return;
            }

            if (! empty($style)) { ?><style type="text/css">
                    <?php echo esc_html($style); ?>
                </style><?php }
                    echo wp_kses_post($content);
                }

                /**
                 * Show offer popup.
                 */
                public function show_offer_popup()
                {
                    if (! $this->is_plugin_screen()) {
                        return;
                    }

                    $data = $this->get_offer_data('popup');

                    if (! is_array($data) || empty($data)) {
                        return;
                    }

                    $style = isset($data['style']) ? $data['style'] : '';
                    $content = isset($data['content']) ? $data['content'] : '';

                    if (empty($content)) {
                        return;
                    }

                    update_option($this->prefix . '_htiop_popup', 'no');
                        ?>
            <div id="htiop-popup-inner" class="htiop-popup-inner">
                <div class="htiop-popup-wrap">
                    <div class="htiop-popup-base">
                        <div class="htiop-popup-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </div>
                        <?php
                        if (! empty($style)) { ?><style type="text/css">
                                <?php echo esc_html($style); ?>
                            </style><?php }
                                echo wp_kses_post($content);
                                    ?>
                    </div>
                </div>
            </div>
        <?php
                }

                /**
                 * Header script.
                 */
                public function header_script()
                {
                    if (! $this->is_plugin_screen()) {
                        return;
                    }
        ?>
            <style>
                body.htiop-popup-open {
                    overflow: hidden !important
                }

                #TB_window.htiop-popup-window #TB_title,
                #htiop-popup-inner {
                    display: none !important;
                    width: 0 !important;
                    height: 0 !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                    overflow: hidden !important
                }

                #TB_overlay {
                    -webkit-transition: .5s ease-out !important;
                    -moz-transition: .5s ease-out !important;
                    transition: .5s ease-out !important;
                    opacity: 0 !important
                }

                #TB_overlay.htiop-popup-overlay {
                    background: #0b0b0b !important;
                    opacity: .9 !important
                }

                #TB_window.htiop-popup-window,
                #TB_window.htiop-popup-window #TB_ajaxContent {
                    background-color: transparent !important;
                    padding: 0 !important;
                    margin: 0 !important
                }

                #TB_window #TB_ajaxContent {
                    -webkit-transition: opacity .5s !important;
                    -moz-transition: opacity .5s !important;
                    transition: opacity .5s !important;
                    opacity: 0 !important
                }

                #TB_window.htiop-popup-window {
                    width: 100% !important;
                    height: 100% !important;
                    top: 0 !important;
                    left: 0 !important;
                    overflow: hidden auto !important;
                    -webkit-box-shadow: none !important;
                    -moz-box-shadow: none !important;
                    box-shadow: none !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent {
                    border: none !important;
                    border-radius: 0 !important;
                    width: auto !important;
                    height: auto !important;
                    text-align: unset !important;
                    line-height: unset !important;
                    overflow: hidden !important;
                    opacity: 1 !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent,
                #TB_window.htiop-popup-window #TB_ajaxContent *,
                #TB_window.htiop-popup-window #TB_ajaxContent ::after,
                #TB_window.htiop-popup-window #TB_ajaxContent ::before {
                    -webkit-box-sizing: border-box !important;
                    -moz-box-sizing: border-box !important;
                    box-sizing: border-box !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent p {
                    padding: unset !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent .htiop-popup-base {
                    position: relative !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent .htiop-popup-close {
                    position: absolute !important;
                    width: 44px !important;
                    height: 44px !important;
                    top: 0 !important;
                    right: 0 !important;
                    cursor: pointer !important;
                    text-align: center !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent .htiop-popup-close .dashicons {
                    display: inline-block !important;
                    width: 44px !important;
                    height: 44px !important;
                    font-size: 24px !important;
                    line-height: 44px !important;
                    color: #333 !important;
                    opacity: .65 !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent .htiop-popup-close:hover .dashicons {
                    opacity: 1 !important
                }

                #TB_window.htiop-popup-window #TB_ajaxContent .htiop-popup-wrap {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-height: 100vh !important;
                    padding: 15px !important;
                    margin: 0 !important
                }
            </style>
        <?php
                }

                /**
                 * Footer script.
                 */
                public function footer_script()
                {
                    if (! $this->is_plugin_screen()) {
                        return;
                    }

                    $timerExpiry = $this->get_timer_expiry();
        ?>
            <script type="text/javascript">
                const htiopTimerExpiry = <?php echo esc_js($timerExpiry); ?>;
            </script>
            <script type="text/javascript">
                ! function(t) {
                    "use strict";
                    t(document).ready(function(t) {
                        let e;
                        ! function e() {
                            let o = t("#htiop-popup-inner"),
                                i = o?.find(".htiop-popup-close");
                            if (!o?.length) return;
                            let n = setTimeout(function() {
                                tb_show("", "#TB_inline?&inlineId=htiop-popup-inner"), t("body").addClass("htiop-popup-open"), t("#TB_overlay").addClass("htiop-popup-overlay"), t("#TB_window").addClass("htiop-popup-window"), t("#TB_title").remove(), clearTimeout(n)
                            }, 1e3);
                            i.on("click", function(e) {
                                e.preventDefault(), tb_remove(), t("body").removeClass("htiop-popup-open")
                            })
                        }(),
                        function e() {
                            let o = t(".htiop-timer"),
                                i = parseFloat(htiopTimerExpiry || 0);
                            if (!o?.length || 1 > i) return;
                            let n = o?.find(".htiop-timer-days"),
                                $ = o?.find(".htiop-timer-hours"),
                                p = o?.find(".htiop-timer-minutes"),
                                l = o?.find(".htiop-timer-seconds"),
                                c = setInterval(function() {
                                    let t = Math?.floor(i / 864e5),
                                        e = Math?.floor(i % 864e5 / 36e5),
                                        o = Math?.floor(i % 36e5 / 6e4),
                                        r = Math?.floor(i % 6e4 / 1e3);
                                    n?.text(("0" + t)?.slice(-2)), $?.text(("0" + e)?.slice(-2)), p?.text(("0" + o)?.slice(-2)), l?.text(("0" + r)?.slice(-2)), (i -= 1e3) < 0 && clearInterval(c)
                                }, 1e3)
                        }(), e = t(".htiop-copy"), e?.length && e.on("click", function(e) {
                            e.preventDefault();
                            let o = t(this),
                                i = o.data("content"),
                                n = o.data("copied-text"),
                                $ = o.text(),
                                p = t('<input style="position: absolute; left: -5000px;">');
                            try {
                                o.append(p), p.val(i).select(), document.execCommand("copy"), p.remove(), o.text(n);
                                let l = setTimeout(function() {
                                    o.text($), clearTimeout(l)
                                }, 1e3)
                            } catch (c) {}
                        })
                    })
                }(jQuery);
            </script>
<?php
                }

                /**
                 * Start redirect.
                 */
                public function start_redirect()
                {
                    $redirect = get_option($this->prefix . '_htiop_redirect');
                    $redirect = 'yes' === $redirect ? true : false;

                    if ($redirect) {
                        wp_safe_redirect($this->initial_page);
                        exit();
                    }
                }

                /**
                 * Dismiss redirect.
                 */
                public function dismiss_redirect()
                {
                    update_option($this->prefix . '_htiop_redirect', 'no');
                }

                /**
                 * Check and handle transient deletion.
                 */
                public function check_transient_deletion()
                {

                    $current_version = (defined('SUPPORT_GENIX_LITE_VERSION') ? SUPPORT_GENIX_LITE_VERSION : '1.0.0');
                    $last_deleted_version = get_option($this->prefix . '_offer_last_deleted_version', '');

                    if (1 === version_compare('1.4.21', $last_deleted_version)) {
                        delete_transient($this->prefix . '_htiop_data');
                        update_option($this->prefix . '_htiop_data', 'no');
                        update_option($this->prefix . '_offer_last_deleted_version', $current_version);
                    }
                }
            }

            // Returns the instance.
            ApbdWps_OfferLite::get_instance();
        }
