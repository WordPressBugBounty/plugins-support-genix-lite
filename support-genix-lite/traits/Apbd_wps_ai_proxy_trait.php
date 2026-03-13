<?php

/**
 * AI Proxy Service Trait
 * Provides methods for authenticating and making requests to the AI Proxy Server.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_ai_proxy_trait
{
    /**
     * AI Proxy Server Configuration
     * Change these values to point to your AI Proxy Server
     */
    protected static $ai_proxy_server_url = 'https://ai.supportgenix.com';
    protected static $ai_proxy_product_slug = 'support-genix';

    /**
     * Authenticate with AI Proxy Server
     * Returns JWT token for subsequent requests
     * Auto-registers free license if not found in AI Proxy database
     *
     * @param array $config Configuration array with server_url, license_key, domain, product_slug
     * @return array ['token' => $jwt] on success, ['error' => $message] on failure
     */
    protected function ai_proxy_authenticate($config)
    {
        if (empty($config['server_url'])) {
            return ['error' => 'Support Genix AI configuration is incomplete.'];
        }

        // For free version: if no license key, register for free first
        if (empty($config['license_key'])) {
            $register_result = $this->ai_proxy_register_free($config);

            if (isset($register_result['error'])) {
                return $register_result;
            }

            // Update config with the new license key
            $config['license_key'] = $register_result['license_key'];
        }

        // First, try to verify the license
        $verify_result = $this->ai_proxy_verify_license($config);

        // If license not found, try to register free again
        if (isset($verify_result['error']) && isset($verify_result['error_code']) && 'INVALID_LICENSE' === $verify_result['error_code']) {
            $register_result = $this->ai_proxy_register_free($config);

            if (isset($register_result['error'])) {
                return $register_result;
            }

            // Update config with the new license key
            $config['license_key'] = $register_result['license_key'];

            // Retry verification after registration
            $verify_result = $this->ai_proxy_verify_license($config);
        }

        return $verify_result;
    }

    /**
     * Verify license with AI Proxy Server
     *
     * @param array $config Configuration array
     * @return array ['token' => $jwt] on success, ['error' => $message, 'error_code' => $code] on failure
     */
    protected function ai_proxy_verify_license($config)
    {
        $url = rtrim($config['server_url'], '/') . '/v1/license/verify';

        $body = [
            'license_key' => $config['license_key'],
            'site_url' => $config['domain'],
            'site_name' => get_bloginfo('name'),
            'plugin_version' => defined('SUPPORT_GENIX_LITE_VERSION') ? SUPPORT_GENIX_LITE_VERSION : '1.0.0',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
        ];

        $args = [
            'method' => 'POST',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (200 !== $status_code && 201 !== $status_code) {
            $error_message = 'Authentication failed.';
            $error_code = isset($data['error']['code']) ? $data['error']['code'] : '';

            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            } elseif (isset($data['message'])) {
                $error_message = $data['message'];
            }

            return ['error' => $error_message, 'error_code' => $error_code];
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Authentication failed.';
            $error_code = isset($data['error']['code']) ? $data['error']['code'] : '';
            return ['error' => $error_message, 'error_code' => $error_code];
        }

        if (!isset($data['data']['token'])) {
            return ['error' => 'No token received from Support Genix AI.'];
        }

        return ['token' => $data['data']['token']];
    }

    /**
     * Register free license with AI Proxy Server
     * For free plugins that don't have a pre-existing license key
     *
     * @param array $config Configuration array
     * @return array ['license_key' => $key, 'token' => $jwt] on success, ['error' => $message] on failure
     */
    protected function ai_proxy_register_free($config)
    {
        $url = rtrim($config['server_url'], '/') . '/v1/license/register-free';

        $body = [
            'product_slug' => $config['product_slug'],
            'site_url' => $config['domain'],
            'site_name' => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'plugin_version' => defined('SUPPORT_GENIX_LITE_VERSION') ? SUPPORT_GENIX_LITE_VERSION : '1.0.0',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
        ];

        $args = [
            'method' => 'POST',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (200 !== $status_code && 201 !== $status_code) {
            $error_message = 'Free registration failed.';

            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            } elseif (isset($data['message'])) {
                $error_message = $data['message'];
            }

            return ['error' => $error_message];
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Free registration failed.';
            return ['error' => $error_message];
        }

        if (!isset($data['data']['license_key'])) {
            return ['error' => 'No license key received from Support Genix AI.'];
        }

        // Store the generated license key for future use
        $license_key = sanitize_text_field($data['data']['license_key']);
        update_option('apbd_wps_ai_proxy_license_key', $license_key);

        return [
            'license_key' => $license_key,
            'token' => isset($data['data']['token']) ? $data['data']['token'] : '',
        ];
    }

    /**
     * Make AI completion request via proxy
     *
     * @param array  $config   Configuration array with server_url, license_key, domain
     * @param string $token    JWT token from authentication
     * @param array  $messages Messages array with role and content
     * @param array  $options  Optional parameters: max_tokens, temperature, feature
     * @return array ['content' => $text, 'credits_used' => $n, 'credits_remaining' => $n] on success,
     *               ['error' => $message] on failure
     */
    protected function ai_proxy_completion($config, $token, $messages, $options = [])
    {
        if (empty($config['server_url']) || empty($token)) {
            return ['error' => 'Invalid configuration or token.'];
        }

        $url = rtrim($config['server_url'], '/') . '/v1/ai/completion';

        $body = [
            'messages' => $messages,
            'max_tokens' => isset($options['max_tokens']) ? absint($options['max_tokens']) : 2048,
            'temperature' => isset($options['temperature']) ? floatval($options['temperature']) : 0.7,
            'feature' => isset($options['feature']) ? sanitize_text_field($options['feature']) : 'general',
        ];

        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-License-Key' => $config['license_key'],
                'X-Site-URL' => $config['domain'],
            ],
            'body' => wp_json_encode($body),
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (200 !== $status_code) {
            $error_message = 'AI completion request failed.';

            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            } elseif (isset($data['error']['code'])) {
                // Handle specific error codes
                switch ($data['error']['code']) {
                    case 'INSUFFICIENT_CREDITS':
                        $error_message = 'Insufficient AI credits. Please check your subscription.';
                        break;
                    case 'LICENSE_EXPIRED':
                        $error_message = 'Your license has expired. Please renew to continue using AI features.';
                        break;
                    case 'SITE_LIMIT_REACHED':
                        $error_message = 'Site activation limit reached. Please upgrade your license.';
                        break;
                    default:
                        $error_message = isset($data['error']['message']) ? $data['error']['message'] : $error_message;
                }
            }

            return ['error' => $error_message];
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'AI completion request failed.';
            return ['error' => $error_message];
        }

        if (!isset($data['data']['content'])) {
            return ['error' => 'No content received from Support Genix AI.'];
        }

        return [
            'content' => $data['data']['content'],
            'model' => isset($data['data']['model']) ? $data['data']['model'] : '',
            'credits_used' => isset($data['data']['credits_used']) ? absint($data['data']['credits_used']) : 0,
            'credits_remaining' => isset($data['data']['credits_remaining']) ? absint($data['data']['credits_remaining']) : 0,
        ];
    }

    /**
     * Get credits balance from AI Proxy Server
     *
     * @param array  $config Configuration array with server_url, license_key, domain
     * @param string $token  JWT token from authentication
     * @return array Credits info on success, ['error' => $message] on failure
     */
    protected function ai_proxy_get_credits($config, $token)
    {
        if (empty($config['server_url']) || empty($token)) {
            return ['error' => 'Invalid configuration or token.'];
        }

        $url = rtrim($config['server_url'], '/') . '/v1/credits/balance';

        $args = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-License-Key' => $config['license_key'],
                'X-Site-URL' => $config['domain'],
            ],
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (200 !== $status_code) {
            $error_message = 'Failed to fetch credits balance.';

            if (isset($data['error']['message'])) {
                $error_message = $data['error']['message'];
            }

            return ['error' => $error_message];
        }

        if (!isset($data['success']) || !$data['success']) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Failed to fetch credits balance.';
            return ['error' => $error_message];
        }

        // Map API response fields to expected format
        // API returns: balance, used, allocated, purchased, bonus
        // Frontend expects: credits_balance, credits_used, max_credits
        // max_credits = allocated + purchased + bonus (total credits ever received)
        $allocated = isset($data['data']['allocated']) ? absint($data['data']['allocated']) : 0;
        $purchased = isset($data['data']['purchased']) ? absint($data['data']['purchased']) : 0;
        $bonus = isset($data['data']['bonus']) ? absint($data['data']['bonus']) : 0;

        return [
            'credits_balance' => isset($data['data']['balance']) ? absint($data['data']['balance']) : 0,
            'credits_used' => isset($data['data']['used']) ? absint($data['data']['used']) : 0,
            'max_credits' => $allocated + $purchased + $bonus,
            'unlimited' => isset($data['data']['unlimited']) ? (bool) $data['data']['unlimited'] : false,
            'renewal_date' => isset($data['data']['renewal_date']) ? $data['data']['renewal_date'] : null,
            'tier' => isset($data['data']['tier']) ? $data['data']['tier'] : '',
        ];
    }

    /**
     * Generate checkout URL for purchasing AI credits
     *
     * Uses a masked license key (first 8 + **** + last 8) for security.
     * This prevents full license key exposure if the checkout URL is shared.
     *
     * @param array $config Configuration array with server_url, license_key, product_slug
     * @return string The checkout URL with Base64 encoded token
     */
    protected function ai_proxy_get_checkout_url($config)
    {
        $license_key = $config['license_key'];

        // Create masked license key: first 8 chars + **** + last 8 chars
        // This prevents full license key exposure in the URL
        $masked_key = substr($license_key, 0, 8) . '****' . substr($license_key, -8);

        $token_data = [
            'license_key_masked' => $masked_key,
            'product_slug' => $config['product_slug'],
        ];

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        $token = base64_encode(wp_json_encode($token_data));

        return rtrim($config['server_url'], '/') . '/checkout?token=' . $token;
    }

    /**
     * Helper method to make AI Proxy request with authentication
     * Combines authentication and completion in one call
     *
     * @param array $messages Messages array for the AI
     * @param array $options  Optional parameters: max_tokens, temperature
     * @return array ['content' => $text, ...] on success, ['error' => $message] on failure
     */
    protected function ai_proxy_request($messages, $options = [])
    {
        // Get AI Proxy configuration
        $config = Apbd_wps_settings::GetAIProxyConfig();

        if (null === $config) {
            return ['error' => 'Support Genix AI is not configured. Please enable it in Settings > API Keys.'];
        }

        // Authenticate (may register free and store new license key)
        $auth_result = $this->ai_proxy_authenticate($config);

        if (isset($auth_result['error'])) {
            return $auth_result;
        }

        // Refresh license key from database in case it was just registered
        // This is needed because $config is passed by value, not reference
        $config['license_key'] = get_option('apbd_wps_ai_proxy_license_key', '');

        // Make completion request
        return $this->ai_proxy_completion($config, $auth_result['token'], $messages, $options);
    }
}
