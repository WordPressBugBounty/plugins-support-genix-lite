<?php

/**
 * Envato API.
 */

defined('ABSPATH') || exit;

class Apbd_Wps_EnvatoAPI
{
    private $bearerToken = "";

    function __construct($token = "")
    {
        if (! empty($token)) {
            $this->SetToken($token);
        }
    }

    function SetToken($token)
    {
        $this->bearerToken = $token;
    }

    function getSale($license_code)
    {
        $url = 'https://api.envato.com/v3/market/author/sale?code=' . $license_code;
        $data = $this->apicall($url);
        return $data;
    }

    function checkAPI(&$error)
    {
        $url = 'https://api.envato.com/v1/market/private/user/username.json';
        $data = $this->apicall($url);
        if (! empty($data->error)) {
            $error = $data->error;
            return false;
        }
        return true;
    }

    function getAPIusername(&$error)
    {
        $url = 'https://api.envato.com/v1/market/private/user/username.json';
        $data = $this->apicall($url);
        if (! empty($data->error)) {
            $error = $data->error;
            return null;
        }
        return $data;
    }

    private function apicall($url, $postarray = array())
    {
        $isPost = false;
        if (count($postarray) > 0) {
            $isPost = true;
        }
        if (function_exists('wp_remote_post')) {
            $rq_params = [
                'method' => $isPost ? 'POST' : 'GET',
                'sslverify' => true,
                'timeout' => 120,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => [
                    'Authorization' => "Bearer {$this->bearerToken}"
                ],
                'cookies' => []
            ];
            if ($isPost) {
                $rq_params['body'] = $postarray;
            }
            $serverResponse = wp_remote_post($url, $rq_params);
            if (is_wp_error($serverResponse)) {
                $rq_params['sslverify'] = false;
                $serverResponse = wp_remote_post($url, $rq_params);
                if (!is_wp_error($serverResponse)) {
                    $error = $serverResponse->get_error_message();
                } else {
                    if (! empty($serverResponse['body']) && (is_array($serverResponse) && 200 === (int)wp_remote_retrieve_response_code($serverResponse)) && $serverResponse['body'] != "GET404") {
                        return json_decode(trim($serverResponse['body']));
                    }
                }
            } else {
                if (! empty($serverResponse['body']) && (is_array($serverResponse) && (404 !== (int)wp_remote_retrieve_response_code($serverResponse)))) {
                    return json_decode(trim($serverResponse['body']));
                }
            }
        }
        $sup = ApbdWps_SupportLite::GetInstance();
        $newError = new stdClass();
        $newError->error = esc_html($sup->__("API Key invalid"));
        return $newError;
    }
}
