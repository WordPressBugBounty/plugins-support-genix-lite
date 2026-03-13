<?php

/**
 * Elite caller.
 */

defined('ABSPATH') || exit;

class Apbd_Wps_EliteCaller
{
    public $host = "";
    public $apikey = "";
    public $isCacheEnable = false;

    function __construct($host, $apikey, $isCache = false)
    {
        $this->host = $host;
        $this->apikey = $apikey;
        $this->isCacheEnable = $isCache;
    }

    function getHashID() {}

    function _request($relatetivePath, $data, &$error = '')
    {
        $response = new stdClass();
        $response->status = false;
        $response->msg = "Empty Response";
        $finalData = (array)($data);
        if (!isset($finalData['api_key'])) {
            $finalData['api_key'] = $this->apikey;
        }
        $fullUrl = rtrim($this->host, '/') . '/' . ltrim($relatetivePath, '/');
        if (function_exists('wp_remote_post')) {
            $serverResponse = wp_remote_post(
                $fullUrl,
                array(
                    'method' => 'POST',
                    'sslverify' => false,
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => $finalData,
                    'cookies' => array()
                )
            );


            if (is_wp_error($serverResponse)) {
                $error = $serverResponse->get_error_message();
                return null;
            } else {
                if (! empty($serverResponse['body']) && $serverResponse['body'] != "GET404") {
                    return $this->process_response($serverResponse['body'], $error);
                }
            }
        }
        return null;
    }

    private function process_response($serverResponse, &$error = "")
    {
        if (! empty($serverResponse)) {
            $obj = json_decode($serverResponse);
            if (! empty($obj->code)) {
                $error = ! empty($obj->message) ? $obj->message : $error;
                return null;
            } else {
                return $obj;
            }
        }
        return null;
    }

    public function isAPI_OK(&$error)
    {
        $response = $this->_request("hello", [], $error);
        if (! empty($response->status) && (! empty($response->data->view_license) && $response->data->view_license == 'Y')) {
            return true;
        }
        return false;
    }

    public function get_license_info($license_code, $isCheck = false)
    {
        $license_code = trim($license_code);
        if ($this->isCacheEnable) {
            $cache_id = "ApbdWps_EL_" . hash('crc32b', $license_code);
            $cacheResponse = get_transient($cache_id);
            if (! empty($cacheResponse)) {
                if (is_string($cacheResponse)) {
                    $cacheResponse = unserialize($cacheResponse);
                }
                return $cacheResponse;
            }
        }
        $response = $this->_request("license/view_with_product_client", ["license_code" => $license_code]);
        if (! empty($response)) {
            if (! empty($response->status)) {
                if ($this->isCacheEnable) {
                    set_transient($cache_id, serialize($response->data), 5 * MINUTE_IN_SECONDS);
                }
                return $response->data;
            }
        }
        return null;
    }
}
