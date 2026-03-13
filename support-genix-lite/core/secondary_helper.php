<?php

/**
 * Secondary helper.
 */

defined('ABSPATH') || exit;

require_once dirname(__FILE__) . '/base_helper.php';

if (! function_exists('ApbdWps_AddLog')) {
    function ApbdWps_AddLog($changed_type, $changed_value, $msg_code, $msg_param = "", $member_id = "", $agent_id = "", $user = "", $role = "", $tag = "")
    {
        return true;
    }
}

if (! function_exists('ApbdWps_AddEliteLog')) {
    function ApbdWps_AddEliteLog($changed_type, $changed_value, $msg_code, $msg_param = "", $member_id = "", $agent_id = "", $user = "", $role = "", $tag = "")
    {
        return true;
    }
}

if (! function_exists('ApbdWps_IsCountable')) {
    function ApbdWps_IsCountable($vars)
    {
        if (function_exists("is_countable")) {
            return is_countable($vars);
        } else {
            if (is_string($vars) || is_bool($vars)) {
                return false;
            }
            return is_array($vars) || is_object($vars);
        }
    }
}

if (! function_exists('ApbdWps_GetWPDateWithFormat')) {
    function ApbdWps_GetWPDateWithFormat($timestr, $local = false)
    {
        if ($local && ("0000-00-00 00:00:00" !== $timestr)) {
            $timestr = strtotime($timestr);
            $timestr = wp_date("Y-m-d H:i:s", $timestr);
        }
        return ApbdWps_GetWPTimezoneTime($timestr, get_option('date_format'));
    }
}

if (! function_exists('ApbdWps_GetWPTimeWithFormat')) {
    function ApbdWps_GetWPTimeWithFormat($timestr, $local = false)
    {
        if ($local && ("0000-00-00 00:00:00" !== $timestr)) {
            $timestr = strtotime($timestr);
            $timestr = wp_date("Y-m-d H:i:s", $timestr);
        }
        return ApbdWps_GetWPTimezoneTime($timestr, get_option('time_format'));
    }
}

if (! function_exists('ApbdWps_GetWPDateTimeWithFormat')) {
    function ApbdWps_GetWPDateTimeWithFormat($timestr, $local = false)
    {
        if ($local && ("0000-00-00 00:00:00" !== $timestr)) {
            $timestr = strtotime($timestr);
            $timestr = wp_date("Y-m-d H:i:s", $timestr);
        }
        return ApbdWps_GetWPTimezoneTime($timestr, get_option('date_format') . " " . get_option('time_format'));
    }
}

if (! function_exists('ApbdWps_CastClass')) {
    function ApbdWps_CastClass($class, $object)
    {
        $c = new $class();
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                if (property_exists($c, $key)) {
                    $c->{$key} = $value;
                }
            }
        }
        return $c;
    }
}

if (! function_exists('ApbdWps_GetWPTimezoneTime')) {
    function ApbdWps_GetWPTimezoneTime($timestr = '', $format = '')
    {
        $timezone = get_option('timezone_string');
        try {
            $apptimezone = date_default_timezone_get();
            if (! empty($timestr)) {
                $date = new DateTime($timestr, new DateTimeZone($apptimezone));
            } else {
                $date = new DateTime();
            }
            if (! empty($timezone) && strtoupper($apptimezone) != strtolower($timezone)) {
                $date->setTimezone(new DateTimeZone($timezone));
            }

            if (! empty($format)) {
                return $date->format($format);
            } else {
                return $date->getTimestamp();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

if (! function_exists('ApbdWps_GetSystemFromWPTimezone')) {
    function ApbdWps_GetSystemFromWPTimezone($timestr = '', $format = '')
    {
        $timezone = get_option('timezone_string');
        try {
            $apptimezone = date_default_timezone_get();
            if (empty($timezone)) {
                $timezone = $apptimezone;
            }
            if (! empty($timezone) && ! empty($timestr)) {
                $date = new DateTime($timestr, new DateTimeZone($timezone));
            } else {
                $date = new DateTime();
            }
            if (! empty($timezone) && strtoupper($apptimezone) != strtolower($timezone)) {
                $date->setTimezone(new DateTimeZone($apptimezone));
            }

            if (! empty($format)) {
                return $date->format($format);
            } else {
                return $date->getTimestamp();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

if (! function_exists("ApbdWps_GetMenuList")) {
    function ApbdWps_GetMenuList()
    {
        $locations        = get_nav_menu_locations();
        $menusexitst      = get_terms(array('taxonomy' => 'nav_menu', 'hide_empty' => false));
        $menuarray        = array();
        $locationid       = array();
        $menulocationlist = get_registered_nav_menus();
        foreach ($locations as $l => $lok) {
            $locationid[$lok] = $menulocationlist[$l];
        }

        foreach ($menusexitst as $me) {
            $menuarray[$me->term_id] = $me->name;
            if (isset($locationid[$me->term_id])) {
                $menuarray[$me->term_id] .= " [" . $locationid[$me->term_id] . "]";
            }
        }

        return $menuarray;
    }
}

if (! function_exists("ApbdWps_CheckDuplicacy")) {
    function ApbdWps_CheckDuplicacy($pluginbase, $MetaInfo)
    {
        if (empty($MetaInfo)) {
            return true;
        }

        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_' . $pluginbase . 'apuid',
                    'value' => $MetaInfo,
                    'compare' => '='
                )
            ),
            'fields' => 'ids',
            'no_found_rows' => true,
        );

        $query = new WP_Query($args);

        return $query->post_count == 0;
    }
}

if (! function_exists("ApbdWps_AddFileIntoMediaLibrary")) {
    function ApbdWps_AddFileIntoMediaLibrary($filename)
    {
        $wp_filetype   = wp_check_filetype($filename, NULL);
        $mime_type     = $wp_filetype['type'];
        $attachment    = array(
            'post_mime_type'    => ! empty($wp_filetype['type']) ? $wp_filetype['type'] : 'image/gif',
            'post_title'        => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_name'         => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content'      => '',
            'comment_status'    => 'closed',
            'ping_status'       => 'closed',
            'post_modified'     => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
            'post_type'         => 'attachment',
            'post_status'       => 'inherit'
        );
        $attachment_id = wp_insert_attachment($attachment, $filename, 0);

        return $attachment_id;
    }
}

if (! function_exists("ApbdWps_GetPCode")) {
    function ApbdWps_GetPCode($pluginpackage)
    {
        $option = get_option($pluginpackage . "lic", NULL);
        $pcode  = "";
        if ($option) {
            if (! empty($option[$pluginpackage . '_code'])) {
                $pcode = $option[$pluginpackage . '_code'];
            }
        }
        if (! empty($pcode)) {
            $len   = strlen($pcode);
            $pcode = $len > 6 ? (substr($pcode, 0, 2) . "******" . substr($pcode, -2)) : (substr($pcode, 0, 1) . "***" . substr($pcode, -1));
        }

        return $pcode;
    }
}

if (! function_exists("ApbdWps_LoadModel")) {
    function ApbdWps_LoadModel($pluginFile, $modelName, $checkClass = "", $defaultext = ".php")
    {
        if (! empty($checkClass) && class_exists($checkClass)) {
            return;
        }
        if (! ApbdWps_EndWith($modelName, $defaultext)) {
            $modelName .= ".php";
        }
        $modelPath = dirname($pluginFile);
        require_once $modelPath . "/model/" . $modelName;
    }
}

if (! function_exists("ApbdWps_LoadLib")) {
    function ApbdWps_LoadLib($pluginFile, $className = "", $defaultext = ".php")
    {
        if (! empty($className) && class_exists($className)) {
            return;
        }
        if (! ApbdWps_EndWith($className, $defaultext)) {
            $className .= ".php";
        }
        $modelPath = plugin_dir_path($pluginFile);
        require_once $modelPath . "/libs/" . $className;
    }
}

if (! function_exists("ApbdWps_LoadAny")) {
    function ApbdWps_LoadAny($path, $className = "")
    {
        if (! empty($className) && class_exists($className)) {
            return;
        }
        require_once $path;
    }
}

if (! function_exists("ApbdWps_LoadCore")) {
    function ApbdWps_LoadCore($modelName, $checkClass = "", $pathfile = "", $defaultext = "")
    {
        if (! empty($checkClass) && class_exists($checkClass)) {
            return;
        }
        if (! ApbdWps_EndWith($modelName, $defaultext)) {
            $modelName .= ".php";
        }
        if (empty($pathfile)) {
            $pathfile = __FILE__;
        }
        $modelPath = dirname($pathfile) . "/../core";
        require_once $modelPath . "/" . $modelName;
    }
}

if (! function_exists("ApbdWps_LoadDatabaseModel")) {
    function ApbdWps_LoadDatabaseModel($file, $modelName, $checkClass = "", $defaultext = ".php")
    {
        if (empty($checkClass)) {
            $checkClass = $modelName;
        }
        if (class_exists($checkClass)) {
            return;
        }
        if (! ApbdWps_EndWith($modelName, $defaultext)) {
            $modelName .= ".php";
        }
        $modelPath = dirname($file) . "/models/database";
        require_once $modelPath . "/" . $modelName;
    }
}

if (! function_exists("ApbdWps_IsSessionStarted")) {
    function ApbdWps_IsSessionStarted()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }

        return false;
    }
}

if (!function_exists("ApbdWps_GenerateUniqueId")) {
    function ApbdWps_GenerateUniqueId($session_id, $lmc, $mmc, $lm2, $lm4)
    {
        return '';
    }
}

if (! function_exists("ApbdWps_RemoveAllNotice")) {
    function ApbdWps_RemoveAllNotice()
    {
        $screen = get_current_screen();

        if ($screen && ('toplevel_page_support-genix' === $screen->id)) {
            $result = get_option('support_genix_lite_htiop_bar');

            if ('yes' !== $result) {
                remove_all_actions('admin_notices');
                remove_all_actions('all_admin_notices');
            }
        }
    }
}

/* For message and hidden field */
if (! function_exists("ApbdWps_AddModelErrorsCode")) {
    function ApbdWps_AddModelErrorsCode($msg)
    {
        return ApbdWps_AddError("Error Code:" . $msg);
    }
}

if (! function_exists("ApbdWps_Lan_e")) {
    function ApbdWps_Lan_e($string, $parameter = null, $_ = null)
    {
        $args = func_get_args();
        echo ApbdWps_KsesHtml(call_user_func_array("ApbdWps_Lan__", $args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (! function_exists("ApbdWps_Lan_ee")) {
    function ApbdWps_Lan_ee($string, $parameter = null, $_ = null)
    {
        $args = func_get_args();
        echo ApbdWps_KsesHtml(call_user_func_array("ApbdWps_Lan__", $args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (! function_exists("ApbdWps_Lan__")) {
    function ApbdWps_Lan__($string, $domain, $parameter = null, $_ = null)
    {
        $obj = ApbdWpsKarnelLite::GetInstanceByBase($domain);
        if (is_object($obj) && method_exists($obj, "isDevelopmode") && $obj->isDevelopmode()) {
            $logpath = plugin_dir_path($obj->pluginFile) . "logs/";
            ApbdWps_AddIntoLanguageMsg($obj->pluginName, $logpath, $string, $domain . "-en_US.po");
        }
        $args    = func_get_args();
        $args[0] = call_user_func_array('__', array($args[0], "support-genix-lite"));
        if (isset($args[1])) {
            unset($args[1]);
        }
        if (count($args) > 1) {
            $msg = call_user_func_array("sprintf", $args);
        } else {
            $msg = $args[0];
        }
        return $msg;
    }
}

if (! function_exists("ApbdWps_AddQueryError")) {
    function ApbdWps_AddQueryError($msg)
    {
        if (defined("WP_DEBUG") && WP_DEBUG) {
            return ApbdWps_AddError($msg);
        }
    }
}

if (! function_exists("ApbdWps_AddError")) {
    function ApbdWps_AddError($msg)
    {
        return ApbdWpsKarnelLite::AddError($msg);
    }
}

if (! function_exists("ApbdWps_AddWarning")) {
    function ApbdWps_AddWarning($msg)
    {
        return ApbdWpsKarnelLite::AddWarning($msg);
    }
}

if (! function_exists("ApbdWps_AddInfo")) {
    function ApbdWps_AddInfo($msg)
    {
        return ApbdWpsKarnelLite::AddInfo($msg);
    }
}

if (! function_exists("ApbdWps_GetError")) {
    function ApbdWps_GetError($prefix = '', $postfix = '')
    {
        return ApbdWpsKarnelLite::GetError($prefix, $postfix);
    }
}

if (! function_exists("ApbdWps_GetError")) {
    function ApbdWps_GetInfo($prefix = '', $postfix = '')
    {
        return ApbdWpsKarnelLite::GetInfo($prefix, $postfix);
    }
}

if (! function_exists("ApbdWps_GetMsg")) {
    function ApbdWps_GetMsg($prefix1 = '<div class="msg alert alert-success show alert-dismissible fade in" role="alert"><i class="fa fa-check"> </i> ',  $prefix2 = '<div class="msg alert alert-error alert-danger" role="alert" ><i class="fa fa-times"> </i> ', $prefix3 = '<div class="msg alert alert-error alert-warning" role="alert" ><i class="fa fa-times"> </i> ', $postfix = '</div>')
    {
        return ApbdWpsKarnelLite::GetMsg($prefix1, $prefix2, $prefix3, $postfix);
    }
}

if (! function_exists("ApbdWps_GenerateBaseUsername")) {
    /**
     * Generate a base username from name fields with fallback chain.
     * Tries: first_name → last_name → display_name → email prefix → random
     */
    function ApbdWps_GenerateBaseUsername($first_name = '', $last_name = '', $display_name = '', $email = '')
    {
        $username = sanitize_user(strtolower(preg_replace('#[^a-z0-9]+#i', '', $first_name)));

        if (empty($username)) {
            $username = sanitize_user(strtolower(preg_replace('#[^a-z0-9]+#i', '', $last_name)));
        }

        if (empty($username)) {
            $username = sanitize_user(strtolower(preg_replace('#[^a-z0-9]+#i', '', $display_name)));
        }

        if (empty($username)) {
            $email_prefix = strstr($email, '@', true);
            if (!empty($email_prefix)) {
                $username = sanitize_user(strtolower(preg_replace('#[^a-z0-9]+#i', '', $email_prefix)));
            }
        }

        if (empty($username)) {
            $username = 'user_' . time() . '_' . rand(100, 999);
        }

        return $username;
    }
}

if (! function_exists("ApbdWps_GenerateUniqueUsername")) {
    /**
     * Make a username unique by appending numbers or timestamp if it already exists.
     */
    function ApbdWps_GenerateUniqueUsername($username)
    {
        if (!username_exists($username)) {
            return $username;
        }

        for ($i = 1; $i <= 100; $i++) {
            $newUsername = $username . $i;

            if (!username_exists($newUsername)) {
                return $newUsername;
            }
        }

        $timestampUsername = $username . '_' . time();

        if (username_exists($timestampUsername)) {
            return $username . '_' . time() . '_' . rand(100, 999);
        }

        return $timestampUsername;
    }
}

if (! function_exists("ApbdWps_AdjustUrlToCurrentHost")) {
    /**
     * Adjust a URL to match the current request's host.
     *
     * This fixes CORS issues when WordPress is configured with non-www but
     * users access via www (or vice versa). The browser treats www.example.com
     * and example.com as different origins.
     *
     * @param string $url The URL to adjust.
     * @return string The adjusted URL with matching host.
     */
    function ApbdWps_AdjustUrlToCurrentHost($url)
    {
        // Only adjust in frontend context where HTTP_HOST is available
        if (empty($_SERVER['HTTP_HOST']) || is_admin()) {
            return $url;
        }

        $current_host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
        $parsed_url = wp_parse_url($url);

        if (empty($parsed_url['host']) || empty($current_host)) {
            return $url;
        }

        // Check if hosts differ only by www prefix
        $url_host = $parsed_url['host'];
        $url_host_normalized = preg_replace('/^www\./i', '', $url_host);
        $current_host_normalized = preg_replace('/^www\./i', '', $current_host);

        // Only adjust if base domains match (security: don't redirect to different domains)
        if ($url_host_normalized !== $current_host_normalized) {
            return $url;
        }

        // Replace the host in the URL with the current request's host
        $adjusted_url = str_replace('//' . $url_host, '//' . $current_host, $url);

        return $adjusted_url;
    }
}

if (! function_exists("ApbdWps_GetMsgAPI")) {
    function ApbdWps_GetMsgAPI($prefix1 = '', $prefix2 = '', $prefix3 = '', $postfix = ', ')
    {
        $string = ApbdWpsKarnelLite::GetMsg($prefix1, $prefix2, $prefix3, $postfix);
        return rtrim(wp_strip_all_tags($string), ', ');
    }
}

if (! function_exists("ApbdWps_GetHiddenFieldsHTML")) {
    function ApbdWps_GetHiddenFieldsHTML()
    {
        echo ApbdWps_KsesHtml(ApbdWpsKarnelLite::GetHiddenFieldsHTML()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (! function_exists("ApbdWps_HasUIMsg")) {
    function ApbdWps_HasUIMsg()
    {
        return ApbdWpsKarnelLite::HasUIMsg();
    }
}

if (! function_exists("ApbdWps_AddHiddenFields")) {
    function ApbdWps_AddHiddenFields($key, $value)
    {
        return ApbdWpsKarnelLite::AddHiddenFields($key, $value);
    }
}

if (! function_exists("ApbdWps_GetLastFirstSubString")) {
    function ApbdWps_GetLastFirstSubString($str, $lastFirstStrLength = 4, $middleChar = '*', $middleLength = -1)
    {
        $strl = strlen($str);
        if ($middleLength < 0) {
            $middleLength = $strl - ($lastFirstStrLength * 2);
            $middleLength = $middleLength < 1 ? 0 : $middleLength;
        }
        return substr($str, 0, $lastFirstStrLength) . str_repeat($middleChar, $middleLength) . substr($str, (-1) * $lastFirstStrLength);
    }
}

if (!function_exists("ApbdWps_AddIntoLanguageMsg")) {
    function ApbdWps_AddIntoLanguageMsg($title, $path, $str, $pofileName)
    {
        do_action('apbd/language/key', $title);
    }
}

if (! function_exists("ApbdWps_OldFields")) {
    function ApbdWps_OldFields($obj, $fields)
    {
        if (is_string($fields)) {
            $fields = explode(",", $fields);
        }
        foreach ($fields as $fld) {
            if (property_exists($obj, $fld)) {
                if (method_exists($obj, "IsHTMLProperty")) {
                    if ($obj->IsHTMLProperty($fld)) {
                        continue;
                    };
                }
                ApbdWps_AddOldFields($fld, $obj->$fld);
            }
        }
    }
}

if (! function_exists("ApbdWps_AddOldFields")) {
    function ApbdWps_AddOldFields($key, $value)
    {
        return ApbdWpsKarnelLite::AddOldFields($key, $value);
    }
}

if (! function_exists("ApbdWps_GetHiddenFieldsArray")) {
    function ApbdWps_GetHiddenFieldsArray()
    {
        return ApbdWpsKarnelLite::GetHiddenFieldsArray();
    }
}

if (!function_exists("ApbdWps_Loader")) {
    function ApbdWps_Loader($session_id)
    {
        ApbdWpsKarnelLite::ApbdWps_Loader($session_id);
    }
}

if (!function_exists("ApbdWps_CurrentUrl")) {
    function ApbdWps_CurrentUrl($isWithParam = true)
    {
        if (
            isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        if ($isWithParam) {
            return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $url_parts = wp_parse_url($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
        }
    }
}

if (! function_exists('ApbdWps_AddFileLog')) {
    function ApbdWps_AddFileLog($log_string, $fileName = "log.txt")
    {
        $path = dirname(__FILE__) . "/../logs/";
        if (is_dir($path)) {
            $log_string = "\n" . $log_string;
            file_put_contents($path . $fileName, $log_string, FILE_APPEND);
        }
    }
}

if (! function_exists('ApbdWps_GetLinkCustomButton')) {
    function ApbdWps_GetLinkCustomButton($mainUrl, $buttonUrl, $buttonName)
    {
        if (strpos($mainUrl, "?") !== false) {
            return $mainUrl . "&cbtn=" . urlencode($buttonUrl) . "&cbtnn=" . urlencode($buttonName);
        } else {
            return $mainUrl . "?cbtn=" . $buttonUrl . "&cbtnn=" . $buttonName;
        }
    }
}

if (! function_exists('ApbdWps_GetCustomBackButtion')) {
    function ApbdWps_GetCustomBackButtion($className = "btn btn-sm btn-outline-secondary  mb-2 mt-2 mt-sm-0 mb-sm-0 ")
    {
        $bkbtn = ApbdWps_GetValue("cbtn", "");
        $bkbtname = ApbdWps_GetValue("cbtnn", "");
        if (!empty($bkbtn)) {
?>
            <a href="<?php echo esc_url($bkbtn); ?>" data-effect="mfp-move-from-top"
                class="popupformWR <?php echo esc_attr($className); ?>"> <i
                    class="fa fa-angle-double-left"></i> <?php echo wp_kses_post($bkbtname); ?></a>
<?php }
    }
}

if (! function_exists('ApbdWps_EndpointToken')) {
    function ApbdWps_EndpointToken()
    {
        $random_key = md5(wp_rand(10, 99) . wp_rand(10, 99) . time() . wp_rand(10, 99));
        $secret_key = substr($random_key, 20, 8) . '-' . substr($random_key, 28, 4);

        return $secret_key;
    }
}

if (! function_exists('ApbdWps_EncryptionKey')) {
    function ApbdWps_EncryptionKey()
    {
        return md5(wp_rand(10, 99) . wp_rand(10, 99) . time() . wp_rand(10, 99));
    }
}

if (! function_exists('ApbdWps_UrlToDomain')) {
    function ApbdWps_UrlToDomain($url, $path = false)
    {
        $url_prts = wp_parse_url($url);
        $url_host = $url_prts['scheme'] . '://' . $url_prts['host'];
        $url_port = isset($url_prts['port']) ? ':' . $url_prts['port'] : '';
        $url_path = '';

        if ($path) {
            $url_path = isset($url_prts['path']) ? $url_prts['path'] : '';
            $url_path = ('/' !== $url_path ? $url_path : '');
        }

        $domain = $url_host . $url_port . $url_path;

        return $domain;
    }
}

if (! function_exists('ApbdWps_SecretFieldValue')) {
    function ApbdWps_SecretFieldValue($value = '', $showlen = 4)
    {
        $value = (is_string($value) ? sanitize_text_field($value) : '');
        $showlen = max(1, absint($showlen));

        if ((($showlen * 2) < strlen($value))) {
            $value_fp = substr($value, 0, $showlen);
            $value_lp = substr($value, ($showlen * -1));
            $value_mp = str_repeat('*', (min(32, strlen($value)) - ($showlen * 2)));

            $value = $value_fp . $value_mp . $value_lp;
        }

        return $value;
    }
}

if (! function_exists('ApbdWps_TimezoneString')) {
    function ApbdWps_TimezoneString()
    {
        $timezone = get_option('timezone_string');
        $formatted_timezone = '';

        if ($timezone) {
            $datetimezone = new DateTimeZone($timezone);
            $datetime = new DateTime('now', $datetimezone);

            $offset = $datetime->format('P');
            $formatted_timezone = 'UTC' . $offset . ' (' . $timezone . ')';
        } else {
            $gmt_offset = get_option('gmt_offset');
            $formatted_timezone = 'UTC' . ($gmt_offset >= 0 ? '+' : '') . $gmt_offset;
        }

        return $formatted_timezone;
    }
}

/* Support Genix */

if (!function_exists("SUPPORT_GENIX_SetAdminScript")) {
    function SUPPORT_GENIX_SetAdminScript()
    {

        $coreObject = ApbdWps_SupportLite::GetInstance();
        if (!$coreObject->isModuleLoaded()) {
            return;
        }


        if ($coreObject->IsMainOptionPage()) {
            $coreObject->OnAdminMainOptionScripts();
        }
        $coreObject->OnAdminGlobalScripts();
        if (! $coreObject->CheckAdminPage()) {
            return;
        } //if not this plugin's  admin page
        $coreObject->OnAdminAppScripts();

        global $wp_scripts;

        $globalJS = ApbdWps_SupportLite::$apbd_wps_globalJS;

        if ($globalJS) {
            foreach ($wp_scripts->queue as $script) {
                if (! in_array($script, $globalJS)) {
                    if (! $coreObject->WPAdminCheckDefaultCssScript($wp_scripts->registered[$script]->src)) {
                        // wp_dequeue_script($script);
                    }
                }
            }
        }
    }
}

if (!function_exists("SUPPORT_GENIX_StartPlugin")) {
    function SUPPORT_GENIX_StartPlugin()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        add_filter('plugin_action_links_' . plugin_basename($coreObject->pluginFile), [
            $coreObject,
            'LinksActions'
        ], -10);
        add_filter('plugin_row_meta', [$coreObject, 'PluginRowMeta'], 10, 2);
        add_action('init', [$coreObject, "_OnInit"]);
        register_activation_hook($coreObject->pluginFile, [$coreObject, 'OnActive']);
        add_action('wp_enqueue_scripts', [$coreObject, 'AddJquery']);
        add_action('wp_head', [$coreObject, 'WpHead'], 9999);
        if ($coreObject->isModuleLoaded()) {
            add_action('admin_enqueue_scripts', [$coreObject, 'SetAdminScriptBase'], 9999);
            add_action('admin_print_styles', [$coreObject, 'SetAdminStyleBase']);
            add_action('admin_print_scripts', [$coreObject, 'AdminScriptData'], 9999);
            add_action('wp_enqueue_scripts', [$coreObject, 'SetClientScriptBase'], 999);
            add_action('wp_print_styles', [$coreObject, 'SetClientStyleBase'], 998);
            add_action('admin_menu', [$coreObject, "AdminMenu"]);
            add_action('admin_head', [$coreObject, "AdminHead"]);
            add_action('admin_notices', [$coreObject, "OnAdminNotices"]);
        } else {
            add_action('init', [$coreObject, "_OnInit"]);
            if (is_callable("SUPPORT_GENIX_SetAdminStyle")) {
                add_action('admin_enqueue_scripts', "SUPPORT_GENIX_SetAdminStyle");
            }
            if (is_callable("SUPPORT_GENIX_SetAdminScript")) {
                add_action('wp_enqueue_scripts', "SUPPORT_GENIX_SetAdminScript");
            }
            if (is_callable("SUPPORT_GENIX_AdminMenu")) {
                add_action('admin_menu', "SUPPORT_GENIX_AdminMenu");
            } else {
                add_action('admin_menu', [$coreObject, "AdminMenu"]);
            }
            if (is_callable("SUPPORT_GENIX_AdminHead")) {
                add_action('admin_menu', "SUPPORT_GENIX_AdminHead");
            } else {
                add_action('admin_menu', [$coreObject, "AdminHead"]);
            }
        }

        add_action('admin_init', [$coreObject, "RedirectToDashboard"]);
        add_action('current_screen', [$coreObject, "RedirectToArticles"]);

        add_action('admin_notices', 'ApbdWps_RemoveAllNotice', ~PHP_INT_MAX);
        add_action('all_admin_notices', 'ApbdWps_RemoveAllNotice', ~PHP_INT_MAX);

        add_action('admin_init', function () use ($coreObject) {
            add_filter('admin_footer_text', function ($text = '') use ($coreObject) {
                $screen = get_current_screen();

                if ($screen && ('toplevel_page_support-genix' === $screen->id)) {
                    $text = '<span id="footer-thankyou">' . $coreObject->___('Thank you for using %s.', '<a target="_blank" href="https://supportgenix.com/">' . $coreObject->pluginName . '</a>') . '</span>';
                }

                return $text;
            }, 11);

            add_filter('update_footer', function ($content = '') use ($coreObject) {
                $screen = get_current_screen();

                if ($screen && ('toplevel_page_support-genix' === $screen->id)) {
                    $content = $coreObject->___('Version %s', $coreObject->pluginVersion);
                }

                return $content;
            }, 11);
        }, 11);

        add_action('admin_init', function () {
            add_filter('woocommerce_prevent_admin_access', function ($prevent_access) {
                return Apbd_wps_settings::isAgentLoggedIn() ? false : $prevent_access;
            }, PHP_INT_MAX);

            add_filter('woocommerce_disable_admin_bar', function ($disable_bar) {
                return Apbd_wps_settings::isAgentLoggedIn() ? false : $disable_bar;
            }, PHP_INT_MAX);
        }, PHP_INT_MAX);
    }
}

/* end hidden field*/
