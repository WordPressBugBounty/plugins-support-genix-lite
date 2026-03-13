<?php

/**
 * @since: 21/11/2022
 * @author: Nazmul Huda
 * @version 1.0.7
 */
if (! class_exists("ApbdWps_LoaderLite")) {
    class ApbdWps_LoaderLite
    {

        public $baseFile = '';
        public $pluginFile = '';
        public $pluginVersion = '';
        public $proPluginFile = '';
        public $requestParams = array();

        public $result = true;

        /**
         * Constructor.
         */
        private function __construct($baseFile, $pluginVersion = '1.0.0')
        {
            $pluginFile = 'support-genix-lite/support-genix-lite.php';
            $proPluginFile = 'support-genix/support-genix.php';
            $requestParams = $this->getRequestParams();

            $this->baseFile = $baseFile;
            $this->pluginFile = $pluginFile;
            $this->pluginVersion = $pluginVersion;
            $this->proPluginFile = $proPluginFile;
            $this->requestParams = $requestParams;

            $this->processLoaderCheck();
        }

        /**
         * Get request parameters.
         */
        public function getRequestParams()
        {
            $requestUri = (isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '');
            $requestParams = array();

            if (! empty($requestUri)) {
                $requestUriStr = wp_parse_url($requestUri, PHP_URL_QUERY);

                if ('string' !== gettype($requestUriStr)) {
                    $requestUriStr = '';
                }

                parse_str($requestUriStr, $requestParams);
            }

            return (is_array($requestParams) ? $requestParams : array());
        }

        /**
         * Process loader check.
         */
        public function processLoaderCheck()
        {
            $result = true;

            if ($this->isProEditionBeingDeactivated() || $this->isTroubleshooting()) {
                $result = true;
            } elseif ($this->isProEditionActivated() || $this->isProEditionBeingRolledBack() || $this->isProEditionBeingActivated()) {
                $result = false;
            }

            $this->result = $result;
        }

        /**
         * Is troubleshooting.
         */
        public function isTroubleshooting()
        {
            return ((bool) get_option('health-check-allowed-plugins') && ! $this->isProEditionActivated());
        }

        /**
         * Is pro edition being rolled back.
         */
        public function isProEditionBeingRolledBack()
        {
            $plugin = (isset($_GET['plugin']) ? sanitize_text_field($_GET['plugin']) : '');
            $plugin = ((empty($plugin) && isset($this->requestParams['plugin'])) ? $this->requestParams['plugin'] : $plugin);

            return ($this->proPluginFile === $plugin);
        }

        /**
         * Is pro edition activated.
         */
        public function isProEditionActivated()
        {
            $active_plugins = get_option('active_plugins', []);
            return (in_array($this->proPluginFile, $active_plugins, true) || is_plugin_active($this->proPluginFile));
        }

        /**
         * Is pro edition being activated.
         */
        public function isProEditionBeingActivated()
        {
            if (! is_admin()) {
                return false;
            }

            $action = ((isset($_REQUEST['action']) && (-1 !== intval($_REQUEST['action']))) ? sanitize_text_field($_REQUEST['action']) : '');
            $action = ((empty($action) && isset($_REQUEST['action2']) && (-1 !== intval($_REQUEST['action2']))) ? sanitize_text_field($_REQUEST['action2']) : $action);

            $plugin  = (isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '');
            $checked = ((isset($_POST['checked']) && is_array($_POST['checked'])) ? array_map('sanitize_text_field', $_POST['checked']) : []);

            $activate         = 'activate';
            $activateSelected = 'activate-selected';

            $actions = [$activate, $activateSelected];

            if (! in_array($action, $actions, true)) {
                return false;
            }

            if (($activate === $action) && ($this->proPluginFile !== $plugin)) {
                return false;
            }

            if (($activateSelected === $action) && ! in_array($this->proPluginFile, $checked, true)) {
                return false;
            }

            return true;
        }

        /**
         * Is pro edition being deactivated.
         */
        public function isProEditionBeingDeactivated()
        {
            if (! is_admin()) {
                return false;
            }

            $action = ((isset($_REQUEST['action']) && (-1 !== intval($_REQUEST['action']))) ? sanitize_text_field($_REQUEST['action']) : '');
            $action = ((empty($action) && isset($_REQUEST['action2']) && (-1 !== intval($_REQUEST['action2']))) ? sanitize_text_field($_REQUEST['action2']) : $action);

            $plugin = (isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '');
            $checked = ((isset($_POST['checked']) && is_array($_POST['checked'])) ? array_map('sanitize_text_field', $_POST['checked']) : []);

            $deactivate          = 'deactivate';
            $deactivateSelected  = 'deactivate-selected';
            $actions             = [$deactivate, $deactivateSelected];

            if (! in_array($action, $actions, true)) {
                return false;
            }

            if (($deactivate === $action) && ($this->proPluginFile !== $plugin)) {
                return false;
            }

            if (($deactivateSelected === $action) && ! in_array($this->proPluginFile, $checked, true)) {
                return false;
            }

            return true;
        }

        /**
         * Is ready to load.
         */
        public static function isReadyToLoad($baseFile, $pluginVersion = '1.0.0')
        {
            $instance = new self($baseFile, $pluginVersion);
            return $instance->result;
        }
    }
}
