<?php

/**
 * Helper.
 */

defined('ABSPATH') || exit;

include_once "secondary_helper.php";

if (!function_exists('ApbdWps_OldDataArrayMerge')) {
    function ApbdWps_OldDataArrayMerge($arr1, &$newArray)
    {
        if (!is_array($arr1)) {
            if (!method_exists($arr1, "getPropertiesArray")) {
                return;
            }
            $arr1 = $arr1->getPropertiesArray();
        }
        $except = ['id'];
        foreach ($arr1 as $key => $val) {
            if (in_array($key, $except)) {
                continue;
            }
            if (!isset($newArray['old_' . $key])) {
                $newArray['old_' . $key] = $val;
            }
        }
    }
}

if (!function_exists("ApbdWps_KsesHtml")) {
    function ApbdWps_KsesHtml($html)
    {
        $allowedposttags = wp_kses_allowed_html('post');
        $allowed_atts = array('align' => true, 'class' => true, 'type' => true, 'id' => true, 'dir' => true, 'lang' => true, 'style' => true, 'xml:lang' => true, 'src' => true, 'alt' => true, 'href' => true, 'rel' => true, 'rev' => true, 'target' => true, 'novalidate' => true, 'value' => true, 'name' => true, 'tabindex' => true, 'action' => true, 'method' => true, 'for' => true, 'width' => true, 'height' => true, 'data-*' => true, 'selected' => true, "checked" => true, 'title' => true,);
        $allowedTags = ['address', 'a', 'abbr', 'acronym', 'area', 'article', 'aside', 'audio', 'b', 'bdo', 'big', 'blockquote', 'br', 'button', 'caption', 'cite', 'code', 'col', 'colgroup', 'del', 'dd', 'dfn', 'details', 'div', 'dl', 'dt', 'em', 'fieldset', 'section', 'figure', 'figcaption', 'font', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'menu', 'nav', 'p', 'pre', 'q', 's', 'samp', 'span', 'section', 'small', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'tt', 'u', 'ul', 'ol', 'var', 'video', 'form', 'input', 'iframe', 'script', 'style', 'option', 'select'];
        foreach ($allowedTags as $tag) {
            $allowedposttags[$tag] = $allowed_atts;
        }
        return wp_kses($html, $allowedposttags);
    }
}

if (!function_exists("ApbdWps_KsesCss")) {
    function ApbdWps_KsesCss($css)
    {
        $allowedposttags = wp_kses_allowed_html('post');
        $allowed_atts = array('align' => true, 'class' => true, 'type' => true, 'id' => true, 'dir' => true, 'lang' => true, 'style' => true, 'xml:lang' => true, 'src' => true, 'alt' => true, 'href' => true, 'rel' => true, 'rev' => true, 'target' => true, 'novalidate' => true, 'value' => true, 'name' => true, 'tabindex' => true, 'action' => true, 'method' => true, 'for' => true, 'width' => true, 'height' => true, 'data-*' => true, 'selected' => true, "checked" => true, 'title' => true,);
        $allowedTags = ['address', 'a', 'abbr', 'acronym', 'area', 'article', 'aside', 'audio', 'b', 'bdo', 'big', 'blockquote', 'br', 'button', 'caption', 'cite', 'code', 'col', 'colgroup', 'del', 'dd', 'dfn', 'details', 'div', 'dl', 'dt', 'em', 'fieldset', 'section', 'figure', 'figcaption', 'font', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'menu', 'nav', 'p', 'pre', 'q', 's', 'samp', 'span', 'section', 'small', 'strike', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'tt', 'u', 'ul', 'ol', 'var', 'video', 'form', 'input', 'iframe', 'script', 'style', 'option', 'select'];
        foreach ($allowedTags as $tag) {
            $allowedposttags[$tag] = $allowed_atts;
        }
        $css = wp_kses($css, $allowedposttags);
        $css = str_replace(htmlentities('>'), '>', $css);
        return $css;
    }
}

if (!function_exists('ApbdWps_SanitizeObject')) {
    function ApbdWps_SanitizeObject($var)
    {
        if (is_array($var)) {
            return array_map('ApbdWps_SanitizeObject', $var);
        } else {
            return is_scalar($var) ? sanitize_text_field($var) : $var;
        }
    }
}

if (!function_exists("ApbdWps_KsesEmailHtml")) {
    /**
     * Sanitize email HTML content using wp_kses_post with 'display' CSS
     * property added. Used by both the webhook and IMAP email-to-ticket paths.
     */
    function ApbdWps_KsesEmailHtml($html)
    {
        $add_display = function ($styles) {
            if (!in_array('display', $styles, true)) {
                $styles[] = 'display';
            }
            return $styles;
        };

        add_filter('safe_style_css', $add_display, 99);
        $result = wp_kses_post($html);
        remove_filter('safe_style_css', $add_display, 99);

        return $result;
    }
}

if (!function_exists('ApbdWps_AddLinkTargetBlank')) {
    function ApbdWps_AddLinkTargetBlank($html)
    {
        return preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function ($m) {
                $attrs = $m[1];

                // Skip if target already set.
                if (preg_match('/\btarget\s*=/i', $attrs)) {
                    // Ensure rel exists.
                    if (!preg_match('/\brel\s*=/i', $attrs)) {
                        $attrs .= ' rel="noopener noreferrer"';
                    }
                    return '<a' . $attrs . '>';
                }

                // Remove any existing rel to rebuild it.
                $attrs = preg_replace('/\brel\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
                $attrs .= ' target="_blank" rel="noopener noreferrer"';

                return '<a' . $attrs . '>';
            },
            $html
        );
    }
}

/* Support Genix */

if (!function_exists("SUPPORT_GENIX_initialize")) {
    function SUPPORT_GENIX_initialize()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $coreObject->setIsModuleLoaded(true);

        $coreObject->_set_action_prefix = $coreObject->pluginBaseName;

        add_action('wp_ajax_apbd_wps_license_info', function () {
            check_ajax_referer('apbd-el-license-r');

            $apiResponse = new Apbd_Wps_APIResponse();
            $apiResponse->SetResponse(true, "", [
                'data' => [
                    "is_valid" => true,
                    "expire_date" => "No expiry",
                    "support_end" => "Unlimited",
                    "license_title" => "Support Genix Lite",
                    "license_key" => "XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX",
                    "msg" => "License successfully verified!",
                    "renew_link" => "",
                    "expire_renew_link" => "",
                    "support_renew_link" => "",
                ],
                'active' => true,
            ]);

            wp_send_json($apiResponse);
        });

        if (!$coreObject->isModuleLoaded()) {
        }

        $coreObject->initialize();
    }
}

if (!function_exists("SUPPORT_GENIX_AdminMenu")) {
    /**
     * @param ApbdWpsKarnelLite s$coreObjec
     */
    function SUPPORT_GENIX_AdminMenu()
    {
        $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();
        $canAccessAnalytics = Apbd_wps_knowledge_base::UserCanAccessAnalytics();
        $canAccessConfig = Apbd_wps_knowledge_base::UserCanAccessConfig();

        $userObj = wp_get_current_user();
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();
        $isAdminUser = current_user_can('manage_options') || is_super_admin($userObj->ID) || in_array('administrator', $userObj->roles);
        $isManageDocs = $canWriteDocs || $canAccessAnalytics || $canAccessConfig;
        $pricingUrl = 'https://supportgenix.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free';

        if (!$isAgentUser && !$isManageDocs) {
            return;
        }

        $separatorStyle = '';

        if ($isAgentUser) {
            $separatorSlug = '';

            if ($canWriteDocs) {
                $separatorSlug = '#/docs';
            } else if ($canAccessAnalytics) {
                $separatorSlug = '#/docs/analytics';
            } else if ($canAccessConfig) {
                $separatorSlug = '#/docs/config';
            }

            if ($separatorSlug) {
                $separatorStyle = '<style>#adminmenu li#toplevel_page_support-genix ul.wp-submenu a[href="admin.php?page=support-genix' . $separatorSlug . '"] {border-top: 2px solid rgba(240, 246, 252, .2); margin-top: 5px; padding-top: 8px;}</style>';
            }
        }

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $menu_label = $separatorStyle . $coreObject->menuTitle;
        $capability = 'read';

        add_menu_page(
            $coreObject->pluginName,
            $menu_label,
            $capability,
            $coreObject->pluginBaseName,
            [$coreObject, 'OptionFormBase'],
            $coreObject->mainMenuIconClass,
            2.00001
        );

        $hasSubMenu = false;

        if ($isAgentUser) {
            if ($isAdminUser || $isManageDocs) {
                add_submenu_page(
                    $coreObject->pluginBaseName,
                    $coreObject->__('Support Tickets'),
                    $coreObject->__('Support Tickets'),
                    $capability,
                    $coreObject->pluginBaseName . '#/tickets',
                    [$coreObject, 'OptionFormBase']
                );

                $hasSubMenu = false;
            }

            if ($isAdminUser) {
                add_submenu_page(
                    $coreObject->pluginBaseName,
                    $coreObject->__('Reports'),
                    $coreObject->__('Reports'),
                    $capability,
                    $coreObject->pluginBaseName . '#/reports',
                    [$coreObject, 'OptionFormBase']
                );

                $hasSubMenu = false;
            }
        }

        if ($canWriteDocs) {
            add_submenu_page(
                $coreObject->pluginBaseName,
                $coreObject->__('Knowledge Base'),
                $coreObject->__('Knowledge Base'),
                $capability,
                $coreObject->pluginBaseName . '#/docs',
                [$coreObject, 'OptionFormBase']
            );

            $hasSubMenu = true;
        }

        if ($canAccessAnalytics) {
            add_submenu_page(
                $coreObject->pluginBaseName,
                $coreObject->__('Chat History'),
                $coreObject->__('Chat History'),
                $capability,
                $coreObject->pluginBaseName . '#/chat-history',
                [$coreObject, 'OptionFormBase']
            );

            add_submenu_page(
                $coreObject->pluginBaseName,
                $coreObject->__('Analytics'),
                $coreObject->__('Analytics'),
                $capability,
                $coreObject->pluginBaseName . '#/docs/analytics',
                [$coreObject, 'OptionFormBase']
            );

            $hasSubMenu = true;
        }

        if ($isAdminUser || $canAccessConfig) {
            $separatorStyle = '';

            if ($hasSubMenu) {
                $separatorStyle = '<style>#adminmenu li#toplevel_page_support-genix ul.wp-submenu a[href="admin.php?page=support-genix#/settings"] {border-top: 2px solid rgba(240, 246, 252, .2); margin-top: 5px; padding-top: 8px;}</style>';
            }

            add_submenu_page(
                $coreObject->pluginBaseName,
                $coreObject->__('Settings'),
                $separatorStyle . $coreObject->__('Settings'),
                $capability,
                $coreObject->pluginBaseName . '#/settings',
                [$coreObject, 'OptionFormBase']
            );
        }

        if ($isAdminUser) {
            add_submenu_page(
                $coreObject->pluginBaseName,
                $coreObject->__('Upgrade to Pro'),
                $coreObject->__('Upgrade to Pro'),
                $capability,
                $pricingUrl
            );
        }

        foreach ($coreObject->moduleList as $moduleObject) {
            $moduleObject->AdminSubMenu();
        }
    }
}

if (!function_exists("SUPPORT_GENIX_AdminHead")) {
    /**
     * @param ApbdWpsKarnelLite s$coreObjec
     */
    function SUPPORT_GENIX_AdminHead()
    {
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();

        $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();
        $canAccessAnalytics = Apbd_wps_knowledge_base::UserCanAccessAnalytics();
        $canAccessConfig = Apbd_wps_knowledge_base::UserCanAccessConfig();

        if (!$isAgentUser && !$canWriteDocs && !$canAccessAnalytics && !$canAccessConfig) {
            return;
        }

        global $submenu;

        if (is_array($submenu) && !empty($submenu)) {
            foreach ($submenu as $key => $items) {
                if (('support-genix' !== $key) || !is_array($items) || empty($items)) {
                    continue;
                }

                $new__items = [];

                foreach ($items as $item) {
                    $slug = isset($item[2]) ? $item[2] : '';

                    if (empty($slug) || ('support-genix' === $slug)) {
                        continue;
                    }

                    if (0 === strpos($slug, 'https://supportgenix.com/pricing/')) {
                        if (isset($item[4])) {
                            $item[4] .= ' support-genix-upgrade-pro';
                        } else {
                            $item[] = 'support-genix-upgrade-pro';
                        }
                    }

                    $new__items[] = $item;
                }

                $submenu[$key] = $new__items;
            }
        }
    }
}

/* end hidden field*/
