<?php

/**
 * Google reCAPTCHA siteverify call.
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_RecaptchaVerify")) {
    class Apbd_Wps_RecaptchaVerify
    {
        const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

        /**
         * @return array|WP_Error Decoded response, or WP_Error on failure.
         */
        public static function Verify($secret = '', $token = '')
        {
            if (empty($secret) || empty($token)) {
                return new WP_Error('apbd_wps_recaptcha_missing_input', 'Secret key or token missing.');
            }

            $response = wp_remote_post(self::ENDPOINT, array(
                'timeout' => 10,
                'body' => array(
                    'secret' => $secret,
                    'response' => $token,
                ),
            ));

            if (is_wp_error($response)) {
                return new WP_Error('apbd_wps_recaptcha_unreachable', $response->get_error_message());
            }

            $status = absint(wp_remote_retrieve_response_code($response));

            if (200 !== $status) {
                return new WP_Error('apbd_wps_recaptcha_http_error', sprintf('HTTP %d', $status));
            }

            $result = json_decode(wp_remote_retrieve_body($response), true);

            if (! is_array($result)) {
                return new WP_Error('apbd_wps_recaptcha_bad_response', 'Unreadable verify response.');
            }

            return $result;
        }
    }
}
