<?php

/**
 * Karnel.
 */

defined('ABSPATH') || exit;

if (!class_exists("ApbdWpsKarnelLite")) {
    abstract class ApbdWpsKarnelLite
    {
        public static $apbd_wps_globalJS;
        public static $apbd_wps_globalCss;
        public static $setAppProperies;

        public $moduleList = [];
        public $pluginFile;
        public $pluginBaseName;
        private static $appGlobalVar = [];
        private static $_instence = [];
        private static $_instence_base = [];
        public $pluginName;
        public $pluginVersion;
        public $isTabMenu = false;
        protected static $warningMessage;
        protected static $errorMessage = [];
        protected static $infoMessage = [];
        protected static $hiddenFilelds = [];
        protected $isDevelopmode = false;
        protected $isDemoMode = false;
        private $isLoadJqGrid = false;
        public $pluginIconClass;
        public $mainMenuIconClass;
        public $_topmenu = [];
        public $_set_action_prefix = "";

        public $licenseMessage = "";
        public $showMessage = false;
        private $is_license_active = false;
        private $is_module_loaded = false;
        public $support_genix_slug = "";
        public $support_genix_assets_slug = "";
        public $menuTitle;
        public  $pluginSlugName;
        public $bootstrapVersion = '4.3.1';
        public static $_admin_notice = [];
        /**
         * @return bool
         */
        public function isLicenseActive()
        {
            return $this->is_license_active;
        }

        /**
         * @param bool $is_license_active
         */
        public function setIsLicenseActive($is_license_active)
        {
            $this->is_license_active = $is_license_active;
        }
        /**
         * @return bool
         */
        public function isModuleLoaded()
        {
            return $this->is_module_loaded;
        }

        /**
         * @param bool $is_module_loaded
         */
        public function setIsModuleLoaded($is_module_loaded)
        {
            $this->is_module_loaded = $is_module_loaded;
        }

        /**
         * @return array
         */
        public function GetAppGlobalVar()
        {
            return self::$appGlobalVar;
        }
        function AddAdminNotice($msg)
        {
            $id = hash("crc32b", $msg);
            static::$_admin_notice[$id] = $msg;
        }
        function is_countable($vars)
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

        function AddTopMenu($title, $icon, $func, $class = '', $isTab = true, $attr = [])
        {
            $n        = new stdClass();
            $n->title = $title;
            $n->func  = $func;
            $n->icon  = $icon;
            $n->class = $class;
            $n->istab = $isTab;
            $n->attr  = "";
            if ($this->is_countable($attr) && count($attr) > 0) {
                foreach ($attr as $ke => $v) {
                    $n->attr .= ' ' . $ke . '="' . $v . '" ';
                }
            }

            $this->_topmenu[] = $n;
        }

        /**
         * @param array $appGlobalVar
         */
        public function AddAppGlobalVar($key, $value)
        {
            self::$appGlobalVar[$key] = $this->__($value);
        }
        /**
         * @param mixed $menuTitle
         */
        public function setMenuTitle($menuTitle)
        {
            $this->menuTitle = $menuTitle;
        }
        /**
         * @return bool
         */
        public function isDevelopmode()
        {
            return $this->isDevelopmode;
        }

        /**
         * @param bool $isDevelopmode
         */
        public function setIsDevelopmode($isDevelopmode)
        {
            $this->isDevelopmode = $isDevelopmode;
        }

        /**
         * @return bool
         */
        public function isLoadJqGrid()
        {
            return $this->isLoadJqGrid;
        }
        /**
         * @param bool $isLoadJqGrid
         */
        public function SetIsLoadJqGrid($isLoadJqGrid)
        {
            $this->isLoadJqGrid = $isLoadJqGrid;
        }

        public function SetPluginIconClass($class, $mainMenuIconClass = '')
        {
            $this->pluginIconClass = $class;
            if (empty($mainMenuIconClass)) {
                $mainMenuIconClass = $class;
            }
            $this->mainMenuIconClass = $mainMenuIconClass;
        }

        /**
         * @param string $set_action_prefix
         */
        public function setSetActionPrefix($set_action_prefix)
        {
            $this->_set_action_prefix = $set_action_prefix;
        }

        /**
         * @return string
         */
        public function getHookActionStr($str)
        {
            return $this->_set_action_prefix . "/" . $str;
        }

        /**
         * @return bool
         */
        public function isDemoMode()
        {
            return $this->isDemoMode;
        }

        /**
         * @param bool $isDemoMode
         */
        public function setIsDemoMode($isDemoMode)
        {
            $this->isDemoMode = $isDemoMode;
        }


        abstract function GetHeaderHtml();

        abstract function GetFooterHtml();

        function __construct($pluginBaseFile, $version = '1.0.0')
        {
            $this->pluginFile                              = $pluginBaseFile;
            $this->menuTitle = $this->pluginName;
            self::$_instence[get_class($this)]         = &$this;
            self::$_instence_base[$this->pluginBaseName] = &self::$_instence[get_class($this)];
            spl_autoload_register(array($this, "_myautoload_method"));
            $this->pluginSlugName     = &$this->pluginBaseName;
            $this->support_genix_slug = "SUPPORT_GENIX";
            $this->support_genix_assets_slug = "support-genix";
            if (is_callable($this->support_genix_slug . "_initialize")) {
                call_user_func($this->support_genix_slug . "_initialize");
            }
        }

        function initialize() {}

        public static function __callStatic($func, $args)
        {
            if (isset(self::$setAppProperies[$func])) {
                return call_user_func_array(self::$setAppProperies[$func], $args);
            }

            return;
        }

        public static function SetProptety($name, $value)
        {
            self::$setAppProperies[$name] = $value;
        }

        function __destruct()
        {
            if ($this->isDevelopmode) {
                $qu   = ApbdWpsModel::GetTotalQueriesForLog();
                $path = plugin_dir_path($this->pluginFile) . "logs/";

                global $wp_filesystem;

                if (empty($wp_filesystem)) {
                    require_once(ABSPATH . '/wp-admin/includes/file.php');
                    WP_Filesystem();
                }

                if ($wp_filesystem->is_writable(dirname($path))) {
                    if (!$wp_filesystem->is_dir($path)) {
                        wp_mkdir_p($path);
                    }
                    $file_path = $path . "queries.sql";
                    if ($wp_filesystem->exists($file_path) && $wp_filesystem->size($file_path) > (1024 * 500)) {
                        $wp_filesystem->delete($file_path);
                    }
                    if (!empty($qu)) {
                        $count   = ApbdWpsModel::GetTotalQueriesCountStr();
                        $queries = "-- " . get_permalink() . "----" . (gmdate('Y-m-d h:i:s A')) . "--$count\n";
                        $queries .= $qu;
                        $queries .= "-- -----------------------------------------------------\n\n";
                        $wp_filesystem->put_contents($file_path, $queries, FS_CHMOD_FILE);
                    }
                }
            }
        }

        final function CheckPluginVersionUpdate()
        {
            $db_version = get_option("ApbdWps_pv_support-genix-lite", "");

            // When version is less than 1.4.32
            if (empty($db_version)) {
                $db_version = get_option("APBD_pv_support-genix-lite", "");
            }

            $db_pro_version = get_option("ApbdWps_pv_support-genix", "");

            // When pro version is less than 1.8.32
            if (empty($db_pro_version)) {
                $db_pro_version = get_option("APBD_pv_support-genix", "");
            }

            // When pro version is less than 1.8.0
            if (empty($db_pro_version)) {
                $db_pro_version = get_option("APBD_pv_apbd-wp-support", "");
            }

            $db_new_activated = rest_sanitize_boolean(get_option("apbd_support_genix_lite_new_activation", true));
            $db_new_pro_activated = rest_sanitize_boolean(get_option("apbd_support_genix_new_activation", true));

            if (true === $db_new_activated) {
                $new_activated = (empty($db_version) ? true : false);
                update_option('apbd_support_genix_lite_new_activation', $new_activated);
            }

            if (true === $db_new_pro_activated) {
                $new_pro_activated = (empty($db_pro_version) ? true : false);
                update_option('apbd_support_genix_new_activation', $new_pro_activated);
            }

            if (empty($db_version) || $db_version != $this->pluginVersion) {
                update_option("ApbdWps_pv_support-genix-lite", $this->pluginVersion);
                delete_option("APBD_pv_support-genix-lite");

                // Backfill activation time for existing users.
                if (empty(get_option('apbd_support_genix_lite_activated_at', ''))) {
                    update_option('apbd_support_genix_lite_activated_at', current_time('U', true));
                }

                if ($this->is_countable($this->moduleList)) {
                    foreach ($this->moduleList as $moduleObject) {
                        $moduleObject->OnTableCreate();
                        $moduleObject->OnPluginVersionUpdated($this->pluginVersion, $db_version, $db_pro_version);
                    }
                }
            }
        }

        public function _myautoload_method($class)
        {
            $basepath  = $path = plugin_dir_path($this->pluginFile);
            $firstchar = substr($class, 0, 1);
            if (strtoupper($firstchar) == "M") {
                $modelfilename = $basepath . "models/";
                if (file_exists($modelfilename . "database/{$class}.php")) {
                    ApbdWps_LoadDatabaseModel($this->pluginFile, $class, $class);
                    return;
                } elseif (file_exists($modelfilename . "{$class}.php")) {
                    ApbdWps_LoadAny($modelfilename . "{$class}.php");
                }
            } elseif (file_exists($basepath . "libs/{$class}.php")) {
                ApbdWps_LoadLib($this->pluginFile, $class);
            } elseif (file_exists($basepath . "core/{$class}.php")) {
                ApbdWps_LoadAny($basepath . "core/{$class}.php", $class);
            } elseif (file_exists($basepath . "appcore/{$class}.php")) {
                ApbdWps_LoadAny($basepath . "appcore/{$class}.php", $class);
            }
        }

        public static function AddError($msg)
        {
            self::$errorMessage[] = $msg;
        }

        public static function AddWarning($msg)
        {
            self::$warningMessage[] = $msg;
        }

        public static function AddInfo($msg)
        {
            self::$infoMessage[] = $msg;
        }

        public static function GetError($prefix = '', $postfix = '')
        {
            if (count(self::$errorMessage) > 0) {
                return $prefix . implode($postfix . $prefix, self::$errorMessage) . $postfix;
            }

            return '';
        }

        public static function GetInfo($prefix = '', $postfix = '')
        {
            if (count(self::$infoMessage) > 0) {
                return $prefix . implode($postfix . $prefix, self::$infoMessage) . $postfix;
            }

            return '';
        }

        public static function GetWarning($prefix = '', $postfix = '')
        {
            if (is_array(self::$warningMessage) && count(self::$warningMessage) > 0) {
                return $prefix . implode($postfix . $prefix, self::$warningMessage) . $postfix;
            }

            return '';
        }

        public static function GetMsg($prefix1 = '', $prefix2 = '', $prefix3 = '', $postfix = '')
        {
            $str = self::GetError($prefix2, $postfix);
            $str .= self::GetInfo($prefix1, $postfix);
            $str .= self::GetWarning($prefix3, $postfix);
            if (! empty($str)) {
                return '<div class="d-m-b">' . $str . '</div>';
            }

            return '';
        }

        public static function HasUIMsg()
        {
            return count(self::$infoMessage) > 0 || count(self::$errorMessage) > 0;
        }

        public static function AddHiddenFields($key, $value)
        {
            self::$hiddenFilelds[$key] = $value;
        }

        public static function AddOldFields($key, $value)
        {
            self::AddHiddenFields("old_" . $key, $value);
        }

        public static function GetHiddenFieldsArray()
        {
            return self::$hiddenFilelds;
        }

        public static function GetHiddenFieldsHTML()
        {
            ob_start();
            foreach (self::$hiddenFilelds as $name => $value) {
?>
                <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php
            }

            return ob_get_clean();
        }

        function AddCoreLib($libname)
        {
            if (! class_exists($libname)) {
                $path = dirname(__FILE__) . "/" . $libname . ".php";
                if (file_exists($path)) {
                    @include_once($path);
                }
            }
        }

        function AddLib($libname)
        {
            if (! class_exists($libname)) {
                $path = plugin_dir_path($this->pluginFile) . "lib/" . $libname . ".php";
                if (file_exists($path)) {
                    @include_once($path);
                }
            }
        }

        /**
         *
         * @return self
         */
        static function &GetInstance()
        {
            return self::$_instence[static::class];
        }

        /**
         * @param $base
         *
         * @return self
         */
        static function &GetInstanceByBase($base)
        {
            return self::$_instence_base[$base];
        }

        /**
         * @param $moduleClassName
         */
        function AddModule($moduleClassName)
        {
            if (! class_exists($moduleClassName)) {
                $path = plugin_dir_path($this->pluginFile) . "modules/" . $moduleClassName . ".php";
                if (file_exists($path)) {
                    @include_once($path);
                }
            }
            $this->moduleList[] = new $moduleClassName($this->pluginBaseName, $this);
            if (! $this->isTabMenu) {
                if ($this->is_countable($this->moduleList) && count($this->moduleList) > 1) {
                    $this->isTabMenu = true;
                }
            }
        }

        function WPAdminCheckDefaultCssScript($src)
        {

            if (empty($src) || $src == 1 || preg_match("/\/assets|\/css\/main.css|\/wp-admin\/|\/wp-includes\/|\/plugins\/woocommerce\/assets\/|\/plugins\/elementor\/assets\/css\/admin/", $src)) {
                return true;
            }

            return false;
        }

        function AddJquery()
        {
            wp_enqueue_script('jquery');
        }

        function WpHead() {}
        public function BasePath($relative_path = '')
        {
            return  plugin_dir_path($this->pluginFile) . $relative_path;
        }
        function AdminScriptData()
        {
            ?>
            <script type="text/javascript">
                <?php
                foreach ($this->moduleList as $moduleObject) {
                    //$moduleObject=new ApbdWpsBase();
                    $moduleObject->AdminScriptData();
                }
                ?>
            </script>
<?php
        }

        function AddAdminStyle($StyleId, $StyleFileName = '', $isFromRoot = false, $deps = [])
        {
            if ($isFromRoot) {
                $start = "/";
            } else {
                $start = "/assets/css/";
            }

            if (! empty($StyleFileName)) {
                self::RegisterAdminStyle($StyleId, plugins_url($start . $StyleFileName, $this->pluginFile), $deps);
            } else {
                self::RegisterAdminStyle($StyleId);
            }
        }

        function AddAdminScript($ScriptId, $ScriptFileName = '', $isFromRoot = false, $deps = [])
        {
            if ($isFromRoot) {
                $start = "/";
            } else {
                $start = "/assets/js/";
            }

            if (! empty($ScriptFileName)) {
                self::RegisterAdminScript($ScriptId, plugins_url($start . $ScriptFileName, $this->pluginFile), $deps, false, true);
            } else {
                self::RegisterAdminScript($ScriptId, '');
            }
        }

        static function RegisterAdminStyle($handle, $src = "", $deps = [], $ver = false, $in_footer = false)
        {
            self::$apbd_wps_globalCss[] = $handle;
            if (! empty($src)) {
                if (! $ver) {
                    $thisObj = self::GetInstance();
                    $pluginFile = $thisObj->pluginFile;
                    $ver = $thisObj->pluginVersion;

                    $base_url = plugin_dir_url($pluginFile);
                    $base_path = plugin_dir_path($pluginFile);
                    $file_path = realpath(str_replace($base_url, $base_path, $src));

                    if (file_exists($file_path)) {
                        $ver .= '-';
                        $ver .= filemtime($file_path);

                        if (defined('WP_DEBUG') && !!WP_DEBUG) {
                            $ver .= '-';
                            $ver .= time();
                        }
                    }
                }

                wp_register_style($handle, $src, $deps, $ver, $in_footer);
            }
            wp_enqueue_style($handle);
        }

        static function RegisterAdminScript($handle, $src = "", $deps = [], $ver = false, $in_footer = false)
        {
            self::$apbd_wps_globalJS[] = $handle;
            if (! empty($src)) {
                if (! $ver) {
                    $thisObj = self::GetInstance();
                    $pluginFile = $thisObj->pluginFile;
                    $ver = $thisObj->pluginVersion;

                    $base_url = plugin_dir_url($pluginFile);
                    $base_path = plugin_dir_path($pluginFile);
                    $file_path = realpath(str_replace($base_url, $base_path, $src));

                    if (file_exists($file_path)) {
                        $ver .= '-';
                        $ver .= filemtime($file_path);

                        if (defined('WP_DEBUG') && !!WP_DEBUG) {
                            $ver .= '-';
                            $ver .= time();
                        }
                    }
                }

                wp_deregister_script($handle);
                wp_register_script($handle, $src, $deps, $ver, $in_footer);
            }
            wp_enqueue_script($handle);
        }

        function OnAdminMainOptionStyles()
        {

            foreach ($this->moduleList as $moduleObject) {
                if ($moduleObject->OnAdminMainOptionStyles($this)) {
                }
            }
        }

        function OnAdminGlobalStyles()
        {
            $this->AddAdminStyle($this->support_genix_assets_slug . "-global", "main.css");

            foreach ($this->moduleList as $moduleObject) {
                if ($moduleObject->OnAdminGlobalStyles()) {
                }
            }
        }
        function OnAdminNotices()
        {
            echo ApbdWps_KsesHtml(implode('', static::$_admin_notice)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        function OnAdminAppStyles()
        {
            foreach ($this->moduleList as $moduleObject) {
                //$moduleObject=new ApbdWpsBase();
                $moduleObject->AdminStyles();
            }
        }

        function OnAdminAppScripts()
        {
            foreach ($this->moduleList as $moduleObject) {
                //$moduleObject=new ApbdWpsBase();
                $moduleObject->AdminScripts();
            }
        }

        function OnAdminMainOptionScripts()
        {
            foreach ($this->moduleList as $moduleObject) {
                if ($moduleObject->OnAdminMainOptionScripts()) {
                }
            }
        }

        function OnAdminGlobalScripts()
        {
            $this->AddAdminScript($this->support_genix_assets_slug . "-global", "main.js", false, ["jquery"]);

            foreach ($this->moduleList as $moduleObject) {
                if ($moduleObject->OnAdminGlobalScripts()) {
                }
            }
        }


        final function SetAdminStyle()
        {

            if (is_callable($this->support_genix_slug . "_SetAdminStyle")) {
                call_user_func($this->support_genix_slug . "_SetAdminStyle");
            }
        }

        function SetAdminScript()
        {
            if (is_callable($this->support_genix_slug . "_SetAdminScript")) {
                call_user_func($this->support_genix_slug . "_SetAdminScript");
            }
        }


        function SetClientScript()
        {
            foreach ($this->moduleList as $moduleObject) {
                //$moduleObject=new ApbdWpsBase();
                if ($moduleObject->IsActive()) {
                    $moduleObject->ClientScript();
                }
            }
        }

        function SetClientStyle()
        {
            foreach ($this->moduleList as $moduleObject) {
                //$moduleObject=new ApbdWpsBase();
                if ($moduleObject->IsActive()) {
                    $moduleObject->ClientStyle();
                }
            }
        }

        function CheckAdminPage()
        {
            $page = ! empty($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : "";
            $page = trim($page);
            if (! empty($page)) {
                if ($page == $this->pluginBaseName) {
                    return true;
                }
                foreach ($this->moduleList as $moduleObject) {
                    //$moduleObject=new ApbdWpsBase();
                    if ($moduleObject->IsPageCheck($page)) {
                        return true;
                    }
                }
            }

            return false;
        }

        static function IsMainOptionPage()
        {
            $file = basename($_SERVER['SCRIPT_FILENAME']);
            if ($file == "plugins.php") {
                if (empty($_REQUEST['page'])) {
                    return true;
                }
            }

            return false;
        }

        final public function _OnInit()
        {
            if (is_callable($this->support_genix_slug . "_init")) {
                call_user_func($this->support_genix_slug . "_init");
            }
        }

        final function AdminMenu()
        {
            if (is_callable($this->support_genix_slug . "_AdminMenu")) {
                call_user_func($this->support_genix_slug . "_AdminMenu");
            }
        }

        final function AdminHead()
        {
            if (is_callable($this->support_genix_slug . "_AdminHead")) {
                call_user_func($this->support_genix_slug . "_AdminHead");
            }
        }

        function _e($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            echo ApbdWps_KsesHtml(call_user_func_array([$this, "__"], $args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        function _ee($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            foreach ($args as &$arg) {
                if (is_string($arg)) {
                    $arg = $this->__($arg);
                }
            }
            echo ApbdWps_KsesHtml(call_user_func_array("sprintf", $args)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        function __($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            array_splice($args, 1, 0, array($this->pluginBaseName));

            return call_user_func_array("ApbdWps_Lan__", $args);
        }

        function ___($string, $parameter = NULL, $_ = NULL)
        {
            $args = func_get_args();
            foreach ($args as &$arg) {
                if (is_string($arg)) {
                    $arg = $this->__($arg);
                }
            }

            return call_user_func_array("sprintf", $args);
        }

        function OnInit()
        {
            //$this->AddAdminStyle( "admin-core-style.css", "apsbdplugincore" );
        }

        final function LinksActions($links)
        {
            $user = wp_get_current_user();
            $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();
            $canWriteDocs = Apbd_wps_knowledge_base::UserCanWriteDocs();

            if ($isAgentUser) {
                $links[] = "<a class='edit coption' href='admin.php?page=" . $this->pluginBaseName . "'>" . $this->__("Support Tickets") . "</a>";
            }

            if ($canWriteDocs) {
                $links[] = "<a class='edit coption' href='admin.php?page=" . $this->pluginBaseName . "#/docs'>" . $this->__("Knowledge Base") . "</a>";
            }

            if (current_user_can('manage_options') || is_super_admin($user->ID) || in_array('administrator', $user->roles)) {
                $links[] = "<a class='edit coption' href='admin.php?page=" . $this->pluginBaseName . "#/settings'>" . $this->__("Settings") . "</a>";
            }

            foreach ($this->moduleList as $moduleObject) {
                $moduleObject->LinksActions($links);
            }

            return $links;
        }

        final function PluginRowMeta($plugin_meta, $plugin_file)
        {
            if ($plugin_file == plugin_basename($this->pluginFile)) {
                foreach ($this->moduleList as $moduleObject) {
                    $moduleObject->PluginRowMeta($plugin_meta);
                }
            }

            return $plugin_meta;
        }

        final function SetClientScriptBase()
        {
            $this->SetClientScript();
        }

        final function SetClientStyleBase()
        {
            $this->SetClientStyle();
        }

        final function SetAdminScriptBase()
        {
            $this->SetAdminScript();
        }

        final function SetAdminStyleBase()
        {
            $this->SetAdminStyle();
        }

        final function OnActive()
        {
            $new_activation = rest_sanitize_boolean(get_option('apbd_support_genix_lite_new_activation', true));
            $new_pro_activation = rest_sanitize_boolean(get_option('apbd_support_genix_new_activation', true));

            foreach ($this->moduleList as $moduleObject) {
                $moduleObject->OnTableCreate();
                $moduleObject->OnActive($new_activation, $new_pro_activation);
            }

            update_option('apbd_support_genix_lite_activated_at', current_time('U', true));
            update_option('apbd_support_genix_redirect_flag', true);
        }

        final function OnDeactive()
        {
            foreach ($this->moduleList as $moduleObject) {
                if ($moduleObject->OnDeactive()) {
                    return true;
                }
            }

            // Clear corn schedule.
            $corn_hooks = [
                'support_genix_scheduled_five_minutes_tasks',
            ];

            foreach ($corn_hooks as $hook) {
                if (wp_next_scheduled($hook)) {
                    wp_clear_scheduled_hook($hook);
                }
            }
        }

        function getActiveModuleId()
        {
            $selected = (! empty($_COOKIE[$this->pluginBaseName . '_st_menu'])) ? $_COOKIE[$this->pluginBaseName . '_st_menu'] : "";
            if (! empty($selected)) {
                return $selected;
            }
            if ($this->is_countable($this->moduleList) && count($this->moduleList) > 0) {
                return $this->moduleList[0]->GetModuleId();
            }

            return "";
        }

        function OptionFormBase()
        {
            echo '<div id="support-genix"></div>';
        }

        final function PluginUpdate($transient)
        {
            return $transient;
        }

        final function checkUpdateInfo($false, $action, $arg)
        {
            return $false;
        }

        private function _getHeaderHtml()
        {
            $this->GetHeaderHtml();
        }

        final function RedirectToDashboard()
        {
            $redirect_flag = get_option('apbd_support_genix_redirect_flag');

            if (true === rest_sanitize_boolean($redirect_flag)) {
                update_option('apbd_support_genix_redirect_flag', false);
                if (Apbd_wps_settings::isAgentLoggedIn()) {
                    wp_safe_redirect(admin_url('admin.php?page=support-genix'));
                    exit();
                }
            }
        }



        final function RedirectToArticles()
        {
            $screen = get_current_screen();
            $screen_id = isset($screen->id) ? $screen->id : '';

            $redirect_url = '';

            if ('edit-sgkb-docs' === $screen_id) {
                $redirect_url = admin_url('admin.php?page=support-genix#/docs');
            } elseif ('edit-sgkb-docs-category' === $screen_id) {
                $redirect_url = admin_url('admin.php?page=support-genix#/docs/config/categories');
            } elseif ('edit-sgkb-docs-tag' === $screen_id) {
                $redirect_url = admin_url('admin.php?page=support-genix#/docs/config/tags');
            }

            if (empty($redirect_url)) {
                return;
            }

            // Check if classic UI mode is enabled via cookie (set by React dashboard)
            // This allows users to access WordPress native screens when they click "Classic UI" button
            if (isset($_COOKIE['sgkb_classic_ui_mode']) && $_COOKIE['sgkb_classic_ui_mode'] === 'enabled') {
                // Navigation buttons for classic UI screens (exclude current page)
                $nav_items = array(
                    'edit-sgkb-docs' => array(
                        'label' => 'Docs',
                        'url'   => admin_url('edit.php?post_type=sgkb-docs'),
                    ),
                    'edit-sgkb-docs-category' => array(
                        'label' => 'Categories',
                        'url'   => admin_url('edit-tags.php?taxonomy=sgkb-docs-category&post_type=sgkb-docs'),
                    ),
                    'edit-sgkb-docs-tag' => array(
                        'label' => 'Tags',
                        'url'   => admin_url('edit-tags.php?taxonomy=sgkb-docs-tag&post_type=sgkb-docs'),
                    ),
                );

                $safe_url = esc_url($redirect_url);

                // CSS in admin_head
                add_action('admin_head', function () {
                    ?>
                    <style>
                    .sgkb-nav-bar {
                        display: flex;
                        gap: 4px;
                        flex-wrap: wrap;
                        align-items: center;
                        margin: 20px 0 12px;
                        clear: both;
                    }
                    .sgkb-nav-bar-divider {
                        border-left: 1px solid #c3c4c7;
                        height: 20px;
                        margin: 0 4px;
                    }
                    h3.sgkb-nav-bar-label {
                        margin: 0 4px 0 0;
                        font-weight: normal;
                    }
                    .sgkb-nav-bar .sgkb-btn {
                        display: inline-block;
                        text-decoration: none;
                        font-size: 13px;
                        line-height: 2.15384615;
                        min-height: 30px;
                        margin: 0;
                        padding: 0 10px;
                        cursor: pointer;
                        border: 1px solid #2271b1;
                        border-radius: 3px;
                        white-space: nowrap;
                        box-sizing: border-box;
                        color: #2271b1;
                        background: transparent;
                    }
                    .sgkb-nav-bar .sgkb-btn:hover {
                        color: #135e96;
                        border-color: #135e96;
                    }
                    .sgkb-nav-bar .sgkb-modern-ui-btn .dashicons {
                        font-size: 16px;
                        width: 16px;
                        height: 16px;
                        vertical-align: text-bottom;
                        margin-right: 3px;
                    }
                    </style>
                    <?php
                });

                // Render nav bar HTML hidden, then position and show via JS
                add_action('all_admin_notices', function () use ($nav_items, $screen_id, $safe_url) {
                    ?>
                    <div class="sgkb-nav-bar" id="sgkb-nav-bar" style="display:none;">
                        <h3 class="sgkb-nav-bar-label">Quick Nav:</h3>
                        <a href="<?php echo $safe_url; ?>" class="sgkb-btn sgkb-modern-ui-btn" id="sgkb-modern-ui-btn"><span class="dashicons dashicons-grid-view"></span> Modern UI</a>
                        <span class="sgkb-nav-bar-divider"></span>
                        <?php foreach ($nav_items as $sid => $item) :
                            if ($sid === $screen_id) continue;
                        ?>
                        <a href="<?php echo esc_url($item['url']); ?>" class="sgkb-btn"><?php echo esc_html($item['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <script>
                    document.getElementById('sgkb-modern-ui-btn').addEventListener('click', function(e) {
                        e.preventDefault();
                        document.cookie = 'sgkb_classic_ui_mode=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
                        try { localStorage.removeItem('sgkb_classic_ui_mode'); } catch(ex) {}
                        window.location.href = this.href;
                    });
                    document.addEventListener('DOMContentLoaded', function() {
                        var navBar = document.getElementById('sgkb-nav-bar');
                        if (!navBar) return;
                        var hr = document.querySelector('.wrap hr.wp-header-end');
                        if (hr) {
                            hr.parentNode.insertBefore(navBar, hr);
                        }
                        navBar.style.display = '';
                    });
                    </script>
                    <?php
                });
                return;
            }

            wp_safe_redirect($redirect_url, 302);
            exit();
        }

        final function StartPlugin()
        {
            if (is_callable($this->support_genix_slug . "_StartPlugin")) {
                call_user_func($this->support_genix_slug . "_StartPlugin");
            }
            $this->CheckPluginVersionUpdate($this->pluginVersion);

            new Apbd_Wps_CornJobs();
        }
    }
}
