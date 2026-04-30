<?php

/**
 * Base helper.
 */

defined('ABSPATH') || exit;

if (!defined("ApbdWps_IsPostBack")) {
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : '';
    define("ApbdWps_IsPostBack", strtoupper($request_method) == 'POST');
}

if (! function_exists("ApbdWps_IsValidEmail")) {
    function ApbdWps_IsValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (! function_exists('ApbdWps_GetTextByKey')) {
    function ApbdWps_GetTextByKey($key, $data = array())
    {
        return ! empty($data[$key]) ? $data[$key] : $key;
    }
}

if (! function_exists("ApbdWps_DownloadFile")) {
    function ApbdWps_DownloadFile($url, $downloadpath)
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        $dir = dirname($downloadpath);
        if (! $wp_filesystem->is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        if ($wp_filesystem->is_file($downloadpath) && $wp_filesystem->exists($downloadpath)) {
            $dir          = dirname($downloadpath);
            $filename     = basename($downloadpath);
            $downloadpath = $dir . "/" . time() . $filename;
        }

        // Use WordPress function to download file
        $response = wp_remote_get($url, array(
            'timeout'     => 300,
            'sslverify'   => false,
            'stream'      => true,
            'filename'    => $downloadpath
        ));

        // Check for errors
        if (is_wp_error($response)) {
            // If WordPress HTTP API fails, fall back to alternative method using WP_Filesystem
            $temp_file = download_url($url, 300);

            if (!is_wp_error($temp_file)) {
                // Move the temp file to the final destination
                $result = $wp_filesystem->copy($temp_file, $downloadpath, true);
                $wp_filesystem->delete($temp_file);
            }
        }

        return $downloadpath;
    }
}

if (! function_exists("ApbdWps_PostValue")) {
    function ApbdWps_PostValue($index, $default = NULL)
    {
        $data = wp_parse_args($_POST);

        if (! isset($data[$index])) {
            return $default;
        } else {
            return $data[$index];
        }
    }
}

if (! function_exists("ApbdWps_RequestValue")) {
    function ApbdWps_RequestValue($index, $default = NULL)
    {
        $data = wp_parse_args($_REQUEST);

        if (! isset($data[$index])) {
            return $default;
        } else {
            return $data[$index];
        }
    }
}

if (! function_exists("ApbdWps_GetValue")) {
    function ApbdWps_GetValue($index, $default = NULL)
    {
        $data = wp_parse_args($_GET);

        if (! isset($data[$index])) {
            return $default;
        } else {
            return $data[$index];
        }
    }
}

if (! function_exists("ApbdWps_CleanDomainName")) {
    function ApbdWps_CleanDomainName($domain)
    {
        $domain = trim($domain);
        $domain = strtolower($domain);
        $url = str_replace(['https://', 'http://'], "", $domain);
        $iswww = substr($url, 0, 4);
        if (strtolower($iswww) == "www.") {
            $url = substr($url, 4);
        }
        $url = untrailingslashit($url);
        return $url;
    }
}

if (! function_exists("ApbdWps_GetUrlToHost")) {
    function ApbdWps_GetUrlToHost($url)
    {
        $result = wp_parse_url($url);
        $url    = ! empty($result['host']) ? $result['host'] : $url;
        $url    = ApbdWps_CleanDomainName($url);

        return $url;
    }
}

if (! function_exists("ApbdWps_EndWith")) {
    function ApbdWps_EndWith($haystack, $needle)
    {
        $len  = strlen($haystack);
        $nlen = strlen($needle);
        $sub  = substr($haystack, -$nlen);
        if ($sub == $needle) {
            return true;
        }

        return false;
    }
}

if (! function_exists("ApbdWps_StartWith")) {
    function ApbdWps_StartWith($haystack, $needle)
    {
        $len  = strlen($haystack);
        $nlen = strlen($needle);
        $sub  = substr($haystack, 0, $nlen);
        if ($sub == $needle) {
            return true;
        }

        return false;
    }
}

if (! function_exists('ApbdWps_StatusTxt')) {
    function ApbdWps_StatusTxt($status_code)
    {
        $status = array(
            "A" => "<span class='text-success'>" . esc_html__("Active", "support-genix-lite") . "</span>",
            "I" => "<span class='text-danger'> " . esc_html__("Inactive", "support-genix-lite") . "</span>",
            "Y" => "<span class='text-success'>" . esc_html__("Yes", "support-genix-lite") . "</span>",
            "N" => "<span class='text-danger'>" . esc_html__("No", "support-genix-lite") . "</span>"
        );

        return ! empty($status[$status_code]) ? $status[$status_code] : $status_code;
    }
}

if (! function_exists("ApbdWps_GetTimeSpan")) {
    function ApbdWps_GetTimeSpan($fisettime)
    {
        if (version_compare(PHP_VERSION, '5.3') >= 0) {
            $d1 = new DateTime();
            $d1->setTimestamp($fisettime);
            $d2 = new DateTime();
            if ($d1->diff($d2)->days > 0) {
                if ($d1->diff($d2)->i == 1) {
                    return "Yesterday";
                }
                $isS = $d1->diff($d2)->days ? "s" : "";
                return $d1->diff($d2)->days . " day$isS ago";
            } elseif ($d1->diff($d2)->h > 0) {
                $isS = $d1->diff($d2)->h ? "s" : "";
                return $d1->diff($d2)->h . " hour$isS ago";
            } elseif ($d1->diff($d2)->i > 0) {
                $isS = $d1->diff($d2)->i ? "s" : "";
                return $d1->diff($d2)->i . " minute$isS ago";
            } elseif ($d1->diff($d2)->s > 0) {
                return $d1->diff($d2)->i . " seconds ago";
            } else {
                return " a moment ago";
            }
        } else {
            return gmdate('Y-m-d H:i:s', $fisettime);
        }
    }
}

if (! function_exists("ApbdWps_GetValidDate")) {
    function ApbdWps_GetValidDate($str, $format = 'Y-m-d')
    {
        if (! empty($str)) {
            $t = strtotime($str);
            if ($t) {
                return gmdate($format, $t);
            }
        }
        return '';
    }
}

if (! function_exists("ApbdWps_FilePutContents")) {
    function ApbdWps_FilePutContents($filename, $data, $flags = 0, $context = NULL)
    {
        if (file_put_contents($filename, $data, $flags, $context)) {
            return true;
        } else {
            global $wp_filesystem;

            if (empty($wp_filesystem)) {
                require_once(ABSPATH . '/wp-admin/includes/file.php');
                WP_Filesystem();
            }

            return $wp_filesystem->put_contents(
                $filename,
                $data,
                FS_CHMOD_FILE // predefined mode settings for WP files
            );
        }
    }
}

if (! function_exists("ApbdWps_GetRemoteIP")) {
    function ApbdWps_GetRemoteIP()
    {
        if (! empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else {
            return ! empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "-";
        }
    }
}

if (! function_exists("ApbdWps_IsSafeRemoteUrl")) {
    /**
     * SSRF guard for remote URLs that the plugin downloads (webhooks, email-to-ticket attachments).
     *
     * Rejects:
     *  - Non-http(s) schemes (file://, php://, gopher://, ftp://, data://, etc.)
     *  - URLs with userinfo (user:pass@host) — DNS rebinding/credential leak vector
     *  - Hosts that resolve to private/loopback/link-local/reserved ranges
     *  - Hostnames that look like internal-only metadata services
     *
     * @param string $url
     * @return bool true if URL is safe to fetch
     */
    function ApbdWps_IsSafeRemoteUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $parts = wp_parse_url($url);
        if (empty($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, array('http', 'https'), true)) {
            return false;
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        // Strip brackets from IPv6 hosts.
        if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
            $host = substr($host, 1, -1);
        }

        // Block obvious internal-only hostnames.
        $blockedHosts = array('localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback', 'metadata.google.internal');
        if (in_array($host, $blockedHosts, true)) {
            return false;
        }

        // Resolve host to IP(s). If host is already an IP, gethostbynamel returns it.
        $ips = array();
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = array($host);
        } else {
            $resolved = @gethostbynamel($host);
            if (is_array($resolved) && !empty($resolved)) {
                $ips = $resolved;
            }
        }

        if (empty($ips)) {
            // Could not resolve — refuse rather than risk SSRF.
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists("ApbdWps_IsBlockedFileExtension")) {
    /**
     * Hardcoded denylist of executable/script extensions that must NEVER land in the
     * plugin upload directory regardless of admin-configured allowed file types.
     *
     * @param string $filename
     * @return bool true if extension is blocked
     */
    function ApbdWps_IsBlockedFileExtension($filename)
    {
        $base = strtolower(basename((string) $filename));

        // Dot-files like ".htaccess" / ".htpasswd" have no PATHINFO_EXTENSION,
        // so match them by basename before falling through to extension check.
        $blockedNames = array('.htaccess', '.htpasswd');
        if (in_array($base, $blockedNames, true)) {
            return true;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === '') {
            return false;
        }

        $blocked = array(
            'php', 'phtml', 'phar', 'phps', 'pht', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
            'js', 'mjs', 'html', 'htm', 'shtml',
            'sh', 'bash', 'zsh', 'csh', 'ksh',
            'cgi', 'pl', 'py', 'rb',
            'asp', 'aspx', 'jsp', 'jspx',
            'htaccess', 'htpasswd',
            'svg',
        );

        return in_array($ext, $blocked, true);
    }
}

if (!function_exists('ApbdWps_GetFileSystem')) {
    /**
     * @return WP_Filesystem_Direct
     */
    function &ApbdWps_GetFileSystem()
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        return $wp_filesystem;
    }
}

if (! function_exists("ApbdWps_FileGetContents")) {
    function ApbdWps_FileGetContents($filename)
    {
        $wp_filesystem = ApbdWps_GetFileSystem();
        return $wp_filesystem->get_contents($filename);
    }
}

if (! function_exists("ApbdWps_ReadPHPInputStream")) {
    function ApbdWps_ReadPHPInputStream()
    {
        $wp_filesystem = ApbdWps_GetFileSystem();
        return $wp_filesystem->get_contents('php://input');
    }
}

if (! function_exists("ApbdWps_AddLogFile")) {
    function ApbdWps_AddLogFile($data, $isAppend = true, $filename = "apbdwps.log")
    {
        $filenamePath = WP_CONTENT_DIR . "/" . $filename;
        if (!is_string($data)) {
            $data = print_r($data, true);
        }
        if ($isAppend) {
            return ApbdWps_FilePutContents($filenamePath, $data, FILE_APPEND);
        } else {
            return ApbdWps_FilePutContents($filenamePath, $data);
        }
        // in production mode
        return false;
    }
}

/* Support Genix */

if (!function_exists("SUPPORT_GENIX_init")) {
    function SUPPORT_GENIX_init()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        do_action($coreObject->_set_action_prefix . "/register_module", $coreObject);
        load_plugin_textdomain("support-genix-lite", false, basename(dirname($coreObject->pluginFile)) . '/languages/');
        if ($coreObject->isModuleLoaded()) {
            foreach ($coreObject->moduleList as $moduleObject) {
                if ($moduleObject->OnInit()) {
                    return true;
                }
            }
            $coreObject->OnInit();
        } else {
            //need to change later

        }
    }
}

if (!function_exists("SUPPORT_GENIX_SetAdminStyle")) {
    function SUPPORT_GENIX_SetAdminStyle()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        if (!$coreObject->isModuleLoaded()) {
            $coreObject->AddAdminStyle($coreObject->support_genix_assets_slug . "-global", "main.css");
            $coreObject->AddAdminScript($coreObject->support_genix_assets_slug . "-global", "main.js", false, ["jquery"]);
            return;
        }
        if (ApbdWps_SupportLite::IsMainOptionPage()) {
            $coreObject->OnAdminMainOptionStyles();
        }
        $coreObject->OnAdminGlobalStyles();

        if (! $coreObject->CheckAdminPage()) {
            return;
        }
        $coreObject->OnAdminAppStyles();

        global $wp_styles;

        $globalCss = ApbdWps_SupportLite::$apbd_wps_globalCss;

        if ($globalCss) {
            foreach ($wp_styles->queue as $style) {
                if (! in_array($style, $globalCss)) {
                    if (! $coreObject->WPAdminCheckDefaultCssScript($wp_styles->registered[$style]->src)) {
                        // wp_dequeue_style($style);
                    }
                }
            }
        }
    }
}
