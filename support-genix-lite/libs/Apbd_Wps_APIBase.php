<?php

/**
 * API base.
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_APIBase")) {
    abstract class Apbd_Wps_APIBase
    {
        /**
         * @var Apbd_Wps_APIResponse
         */
        public $response;
        public $namespace;
        public $version;
        protected $api_base = '';
        public $logged_user;
        public $register;
        /**
         * @var false|string
         */
        public $payload;
        public static $payload_obj;
        public static $isLoadedPayload = false;

        public function __construct($namespace, $register = true)
        {
            $this->LoadPayload();
            $this->response    = new Apbd_Wps_APIResponse();
            $this->namespace   = $namespace;
            $this->logged_user = wp_get_current_user();
            $this->register = $register;
            ob_start();
            $this->api_base = $this->setAPIBase();
            $this->routes();
            add_action('set_logged_in_cookie', function ($logged_in_cookie) {
                $_COOKIE[LOGGED_IN_COOKIE] = $logged_in_cookie;
            });
        }
        function LoadPayload()
        {
            if (!self::$isLoadedPayload) {
                if (!empty($_POST['payload']) && is_string($_POST['payload'])) {
                    self::$payload_obj = json_decode(stripslashes($_POST['payload']), true);
                } else {
                    self::$payload_obj = ApbdWps_ReadPHPInputStream();
                    if (!empty(self::$payload_obj)) {
                        self::$payload_obj = json_decode(self::$payload_obj, true);
                        if (empty(self::$payload_obj)) {
                            self::$payload_obj = wp_parse_args($_POST);
                        }
                    }
                }
                self::$isLoadedPayload = true;
            }
            $this->payload = &self::$payload_obj;
        }
        function get_current_user_id()
        {
            return $this->logged_user->ID;
        }
        function AddError($message, $parameter = NULL, $_ = NULL)
        {
            ApbdWps_AddError($message);
        }
        function filter_for_api(&$item)
        {
            if ($item instanceof ApbdWpsModel) {
                //unse
            }
        }
        function SetPayload($key, $value)
        {
            if (!is_array($this->payload)) {
                $this->payload = [];
            }
            $this->payload[$key] = $value;
        }
        function GetPayload($key, $default = null)
        {
            return ! empty($this->payload[$key]) ? $this->payload[$key] : $default;
        }
        function AddInfo($message, $parameter = NULL, $_ = NULL)
        {
            ApbdWps_AddInfo($message);
        }
        function AddDebug($obj) {}
        function AddWarning($message, $parameter = NULL, $_ = NULL)
        {
            ApbdWps_AddWarning($message);
        }

        abstract function routes();
        abstract function setAPIBase();


        /**
         * @param $methods
         * @param $route
         * @param callable $callback
         * @param string $permission_callback
         */
        public function RegisterRestRoute($methods, $route, $callback, $permission_callback = '')
        {
            if (! $this->register) {
                return;
            }

            $thisobj = &$this;
            if (empty($permission_callback)) {
                $permission_callback = function (WP_REST_Request $request) use ($route, $thisobj) {
                    $permission = false;
                    $mainroutes = explode("/", $route);
                    $mainroute = (isset($mainroutes[0]) ? strval($mainroutes[0]) : '');

                    if (! empty($mainroute)) {
                        $permission = $this->SetRoutePermission($mainroute);
                        $permission = apply_filters('apbd-wps/filter/api-permission', $permission, $mainroute, $request);
                        $permission = apply_filters('apbd-wps/filter/api-permission/' . $this->api_base . "/" . $mainroute, $permission, $request);
                    }

                    return $permission;
                };
            }
            if (! empty($this->api_base)) {
                $wrapped_callback = function (WP_REST_Request $request) use ($callback) {
                    nocache_headers();
                    return call_user_func($callback, $request);
                };

                register_rest_route($this->namespace, '/' . $this->api_base . '/' . $route, array(
                    'methods' => $methods,
                    'callback' => $wrapped_callback,
                    'permission_callback' => $permission_callback
                ));
            }
        }
        public function __destruct()
        {
            $debuglog = ob_get_clean();
        }
        function SetResponse($status, $message = '', $data = NULL)
        {
            $this->response->status = $status;
            $this->response->data = $data;
            $this->response->msg = $message;
        }
        function SetRoutePermission($route)
        {
            return is_user_logged_in();
        }
    }
}
