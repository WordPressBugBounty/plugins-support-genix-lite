<?php

/**
 * Recommended plugins.
 */

defined('ABSPATH') || exit;

class Apbd_wps_recommended_plugins extends ApbdWpsBaseModuleLite
{
    const INFO_CACHE_PREFIX = 'apbd_wps_recommended_plugins_info_v2_';
    const WARM_CRON_HOOK = 'apbd-wps/cron/warm-recommended-plugins';

    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();

        $this->AddAjaxAction("data", [$this, "data"]);
        $this->AddAjaxAction("boost_data", [$this, "boost_data"]);
        $this->AddAjaxAction("install", [$this, "install"]);
        $this->AddAjaxAction("activate", [$this, "activate"]);

        add_action(self::WARM_CRON_HOOK, [$this, "WarmCache"]);
    }

    public function OnActive($new_activation = true, $new_lite_activation = true)
    {
        parent::OnActive($new_activation, $new_lite_activation);

        if (!wp_next_scheduled(self::WARM_CRON_HOOK)) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::WARM_CRON_HOOK);
        }
    }

    public function OnDeactive()
    {
        wp_clear_scheduled_hook(self::WARM_CRON_HOOK);

        return parent::OnDeactive();
    }

    public function WarmCache()
    {
        $this->GetPluginsInfo(array_keys($this->GetWhitelist()));
    }

    public function GetCatalog()
    {
        $catalog = [
            'ht-contactform' => [
                'slug' => 'ht-contactform',
                'location' => 'ht-contactform/contact-form-widget-elementor.php',
                'name' => 'HT Contact Form – Drag & Drop Form Builder for WordPress',
            ],
            'kelune-crm' => [
                'slug' => 'kelune-crm',
                'location' => 'kelune-crm/kelune-crm.php',
                'name' => 'Kelune CRM – Contact Management, Email Marketing, Newsletter & Marketing Automation',
            ],
            'courseglade-lms' => [
                'slug' => 'courseglade-lms',
                'location' => 'courseglade-lms/courseglade-lms.php',
                'name' => 'CourseGlade LMS – Online Course & eLearning Platform',
            ],
            'hashbar-wp-notification-bar' => [
                'slug' => 'hashbar-wp-notification-bar',
                'location' => 'hashbar-wp-notification-bar/init.php',
                'name' => 'Notification Bar for WordPress',
            ],
            'cookieray' => [
                'slug' => 'cookieray',
                'location' => 'cookieray/cookieray.php',
                'name' => 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)',
            ],
            'wp-plugin-manager' => [
                'slug' => 'wp-plugin-manager',
                'location' => 'wp-plugin-manager/plugin-main.php',
                'name' => 'WP Plugin Manager',
            ],
            'ht-easy-google-analytics' => [
                'slug' => 'ht-easy-google-analytics',
                'location' => 'ht-easy-google-analytics/ht-easy-google-analytics.php',
                'name' => 'HT Easy GA4 ( Google Analytics 4 )',
            ],
            'pixelavo' => [
                'slug' => 'pixelavo',
                'location' => 'pixelavo/pixelavo.php',
                'name' => 'Pixelavo – Server Side Tracking & Pixel + AI Ads Tools',
            ],
            'insert-headers-and-footers-script' => [
                'slug' => 'insert-headers-and-footers-script',
                'location' => 'insert-headers-and-footers-script/init.php',
                'name' => 'Insert Headers and Footers Code',
            ],
            'ht-mega-for-elementor' => [
                'slug' => 'ht-mega-for-elementor',
                'location' => 'ht-mega-for-elementor/htmega_addons_elementor.php',
                'name' => 'HT Mega Addons for Elementor – Elementor Widgets & Template Builder',
            ],
            'woolentor-addons' => [
                'slug' => 'woolentor-addons',
                'location' => 'woolentor-addons/woolentor_addons_elementor.php',
                'name' => 'ShopLentor – All-in-One WooCommerce Growth & Store Enhancement Plugin',
            ],
            'whols' => [
                'slug' => 'whols',
                'location' => 'whols/whols.php',
                'name' => 'Whols – Wholesale Prices and B2B Store Solution for WooCommerce',
            ],
            'recurio' => [
                'slug' => 'recurio',
                'location' => 'recurio/recurio.php',
                'name' => 'Recurio – Subscriptions & Recurring Payments for WooCommerce',
            ],
            'swatchly' => [
                'slug' => 'swatchly',
                'location' => 'swatchly/swatchly.php',
                'name' => 'Swatchly – Product Variation Swatches for WooCommerce',
            ],
        ];

        return apply_filters('apbd-wps/filter/recommended-plugins-catalog', $catalog);
    }

    protected function catalogItems($slugs)
    {
        $catalog = $this->GetCatalog();
        $items = [];

        foreach ((array) $slugs as $slug) {
            if (isset($catalog[$slug]) && !isset($items[$slug])) {
                $items[$slug] = $catalog[$slug];
            }
        }

        return array_values($items);
    }

    public function GetTabs()
    {
        $tabs = [
            [
                'key' => 'recommended',
                'title' => $this->__('Recommended'),
                'plugins' => $this->catalogItems([
                    'ht-contactform',
                    'kelune-crm',
                    'courseglade-lms',
                    'hashbar-wp-notification-bar',
                    'woolentor-addons',
                    'wp-plugin-manager',
                    'ht-easy-google-analytics',
                    'cookieray',
                    'pixelavo',
                ]),
            ],
            [
                'key' => 'woocommerce',
                'title' => $this->__('WooCommerce'),
                'plugins' => $this->catalogItems([
                    'woolentor-addons',
                    'whols',
                    'recurio',
                    'swatchly',
                ]),
            ],
            [
                'key' => 'other',
                'title' => $this->__('Popular'),
                'plugins' => $this->catalogItems([
                    'kelune-crm',
                    'courseglade-lms',
                    'ht-easy-google-analytics',
                    'pixelavo',
                    'cookieray',
                    'wp-plugin-manager',
                    'insert-headers-and-footers-script',
                    'ht-mega-for-elementor',
                ]),
            ],
        ];

        return apply_filters('apbd-wps/filter/recommended-plugins', $tabs);
    }

    public function GetWhitelist()
    {
        $whitelist = [];

        foreach ($this->GetTabs() as $tab) {
            if (empty($tab['plugins']) || !is_array($tab['plugins'])) {
                continue;
            }

            foreach ($tab['plugins'] as $plugin) {
                if (empty($plugin['slug']) || empty($plugin['location'])) {
                    continue;
                }

                $whitelist[$plugin['slug']] = $plugin;
            }
        }

        return $whitelist;
    }

    public function GetBoostPlugins()
    {
        $plugins = [
            ['slug' => 'ht-contactform'],
            ['slug' => 'kelune-crm'],
            ['slug' => 'hashbar-wp-notification-bar'],
            ['slug' => 'woolentor-addons', 'requires' => ['woocommerce']],
            ['slug' => 'wp-plugin-manager'],
            ['slug' => 'ht-easy-google-analytics'],
            ['slug' => 'cookieray'],
            ['slug' => 'pixelavo'],
        ];

        return apply_filters('apbd-wps/filter/recommended-plugins-boost', $plugins);
    }

    public function GetDependencies()
    {
        $dependencies = [
            'woocommerce' => ['woocommerce/woocommerce.php'],
            'elementor' => ['elementor/elementor.php'],
        ];

        return apply_filters('apbd-wps/filter/recommended-plugins-dependencies', $dependencies);
    }

    protected function dependenciesMet($requires)
    {
        if (empty($requires) || !is_array($requires)) {
            return true;
        }

        $dependencies = $this->GetDependencies();

        foreach ($requires as $key) {
            if (empty($dependencies[$key])) {
                return false;
            }

            $isActive = false;

            foreach ((array) $dependencies[$key] as $location) {
                if (is_plugin_active($location)) {
                    $isActive = true;
                    break;
                }
            }

            if (!$isActive) {
                return false;
            }
        }

        return true;
    }

    public function GetPluginsInfo($slugs)
    {
        if (empty($slugs) || !is_array($slugs)) {
            return [];
        }

        $slugs = array_values(array_unique($slugs));

        $pluginsInfo = [];
        $missing = [];

        foreach ($slugs as $slug) {
            $cached = get_transient(self::INFO_CACHE_PREFIX . $slug);

            if (false !== $cached) {
                if (!empty($cached)) {
                    $pluginsInfo[$slug] = $cached;
                }

                continue;
            }

            $missing[] = $slug;
        }

        if (empty($missing)) {
            return $pluginsInfo;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        foreach ($missing as $slug) {
            $info = plugins_api('plugin_information', [
                'slug' => $slug,
                'fields' => [
                    'short_description' => true,
                    'icons' => true,
                    'active_installs' => true,
                    'sections' => false,
                    'author' => false,
                    'versions' => false,
                    'ratings' => false,
                    'reviews' => false,
                    'banners' => false,
                    'compatibility' => false,
                    'homepage' => false,
                    'donate_link' => false,
                    'tags' => false,
                ],
            ]);

            if (is_wp_error($info)) {
                // Short cache on failure, so a wp.org outage is not retried on every request.
                set_transient(self::INFO_CACHE_PREFIX . $slug, [], HOUR_IN_SECONDS);
                continue;
            }

            $icons = isset($info->icons) ? (array) $info->icons : [];

            $pluginsInfo[$slug] = [
                'name' => html_entity_decode((string) $info->name, ENT_QUOTES, 'UTF-8'),
                'icon' => $this->pickIcon($icons),
                'description' => html_entity_decode(wp_strip_all_tags((string) $info->short_description), ENT_QUOTES, 'UTF-8'),
                'active_installs' => isset($info->active_installs) ? absint($info->active_installs) : 0,
                // Kept so install() does not repeat this lookup for every plugin.
                'download_link' => isset($info->download_link) ? esc_url_raw((string) $info->download_link) : '',
            ];

            set_transient(self::INFO_CACHE_PREFIX . $slug, $pluginsInfo[$slug], WEEK_IN_SECONDS);
        }

        return $pluginsInfo;
    }

    protected function pickIcon($icons)
    {
        foreach (['2x', '1x', 'svg', 'default'] as $size) {
            if (!empty($icons[$size])) {
                return esc_url_raw($icons[$size]);
            }
        }

        return '';
    }

    protected function pluginStatus($location, $installed)
    {
        if (!isset($installed[$location])) {
            return 'not_installed';
        }

        return is_plugin_active($location) ? 'active' : 'inactive';
    }

    protected function prepareItem($plugin, $liveInfo, $installed)
    {
        $info = isset($liveInfo[$plugin['slug']]) ? $liveInfo[$plugin['slug']] : [];

        return [
            'slug' => $plugin['slug'],
            'location' => $plugin['location'],
            'name' => !empty($info['name']) ? $info['name'] : $plugin['name'],
            'description' => isset($info['description']) ? $info['description'] : '',
            'icon' => isset($info['icon']) ? $info['icon'] : '',
            'active_installs' => array_key_exists('active_installs', $info) ? $info['active_installs'] : null,
            'url' => 'https://wordpress.org/plugins/' . $plugin['slug'] . '/',
            'status' => $this->pluginStatus($plugin['location'], $installed),
        ];
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!current_user_can('manage_options')) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $tabs = $this->GetTabs();
        $whitelist = $this->GetWhitelist();
        $liveInfo = $this->GetPluginsInfo(array_keys($whitelist));
        $installed = get_plugins();

        $responseTabs = [];

        foreach ($tabs as $tab) {
            $items = [];

            if (empty($tab['plugins']) || !is_array($tab['plugins'])) {
                continue;
            }

            foreach ($tab['plugins'] as $plugin) {
                if (empty($plugin['slug']) || empty($plugin['location'])) {
                    continue;
                }

                $items[] = $this->prepareItem($plugin, $liveInfo, $installed);
            }

            $responseTabs[] = [
                'key' => $tab['key'],
                'title' => $tab['title'],
                'plugins' => $items,
            ];
        }

        $apiResponse->SetResponse(true, '', ['tabs' => $responseTabs]);

        echo wp_json_encode($apiResponse);
    }

    public function boost_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        if (!current_user_can('manage_options')) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $whitelist = $this->GetWhitelist();
        $slugs = [];

        foreach ((array) $this->GetBoostPlugins() as $entry) {
            $slug = sanitize_key(isset($entry['slug']) ? $entry['slug'] : '');

            if (empty($slug) || !isset($whitelist[$slug]) || in_array($slug, $slugs, true)) {
                continue;
            }

            if (!$this->dependenciesMet(isset($entry['requires']) ? $entry['requires'] : [])) {
                continue;
            }

            $slugs[] = $slug;
        }

        $installed = get_plugins();
        $liveInfo = $this->GetPluginsInfo($slugs);

        $plugins = [];

        foreach ($slugs as $slug) {
            $plugins[] = $this->prepareItem($whitelist[$slug], $liveInfo, $installed);
        }

        $apiResponse->SetResponse(true, '', ['plugins' => $plugins]);

        echo wp_json_encode($apiResponse);
    }

    public function install()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!$this->canInstall()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (!ApbdWps_IsPostBack) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $slug = sanitize_key(ApbdWps_PostValue('slug', ''));
        $whitelist = $this->GetWhitelist();

        if (empty($slug) || !isset($whitelist[$slug])) {
            $apiResponse->SetResponse(false, $this->__('This plugin is not in the recommended list.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/template.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

        $cached = get_transient(self::INFO_CACHE_PREFIX . $slug);
        $downloadLink = !empty($cached['download_link']) ? $cached['download_link'] : '';
        $isCachedLink = !empty($downloadLink);

        if (empty($downloadLink)) {
            $downloadLink = $this->GetDownloadLink($slug);
        }

        if (empty($downloadLink)) {
            $apiResponse->SetResponse(false, $this->__('Plugin not found on WordPress.org.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result = $upgrader->install($downloadLink);
        $hasFailed = is_wp_error($result) || is_wp_error($skin->result) || $skin->get_errors()->has_errors();

        if ($hasFailed && $isCachedLink) {
            delete_transient(self::INFO_CACHE_PREFIX . $slug);
            $downloadLink = $this->GetDownloadLink($slug);

            if (!empty($downloadLink)) {
                $skin = new WP_Ajax_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $result = $upgrader->install($downloadLink);
                $hasFailed = is_wp_error($result) || is_wp_error($skin->result) || $skin->get_errors()->has_errors();
            }
        }

        if ($hasFailed) {
            if (is_wp_error($result)) {
                $apiResponse->SetResponse(false, $result->get_error_message());
            } elseif (is_wp_error($skin->result)) {
                $apiResponse->SetResponse(false, $skin->result->get_error_message());
            } else {
                $apiResponse->SetResponse(false, $skin->get_error_messages());
            }

            echo wp_json_encode($apiResponse);
            return;
        }

        $location = $upgrader->plugin_info();

        if (empty($location)) {
            $apiResponse->SetResponse(false, $this->__('Could not determine the installed plugin file.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $apiResponse->SetResponse(true, $this->__('Installed successfully.'), [
            'slug' => $slug,
            'location' => $location,
            'status' => 'inactive',
        ]);

        echo wp_json_encode($apiResponse);
    }

    protected function GetDownloadLink($slug)
    {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $info = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => ['sections' => false],
        ]);

        if (is_wp_error($info) || empty($info->download_link)) {
            return '';
        }

        return esc_url_raw((string) $info->download_link);
    }

    public function activate()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!$this->canInstall()) {
            $apiResponse->SetResponse(false, $this->__('Permission denied.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (!ApbdWps_IsPostBack) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $location = sanitize_text_field(ApbdWps_PostValue('location', ''));
        $whitelist = $this->GetWhitelist();

        $slug = '';

        foreach ($whitelist as $plugin) {
            if ($plugin['location'] === $location) {
                $slug = $plugin['slug'];
                break;
            }
        }

        if (empty($location) || empty($slug)) {
            $apiResponse->SetResponse(false, $this->__('This plugin is not in the recommended list.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (!file_exists(WP_PLUGIN_DIR . '/' . $location)) {
            $apiResponse->SetResponse(false, $this->__('Plugin is not installed.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        $activated = activate_plugin($location);

        if (is_wp_error($activated)) {
            $apiResponse->SetResponse(false, $activated->get_error_message());
            echo wp_json_encode($apiResponse);
            return;
        }

        $apiResponse->SetResponse(true, $this->__('Activated successfully.'), [
            'slug' => $slug,
            'location' => $location,
            'status' => 'active',
        ]);

        echo wp_json_encode($apiResponse);
    }

    protected function canInstall()
    {
        return current_user_can('manage_options')
            && current_user_can('install_plugins')
            && current_user_can('activate_plugins');
    }
}
