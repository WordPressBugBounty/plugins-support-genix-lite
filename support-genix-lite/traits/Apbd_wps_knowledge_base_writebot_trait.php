<?php

/**
 * Write Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_writebot_trait
{
    public function initialize__writebot()
    {
        $this->AddAjaxAction("write_with_ai_data", [$this, "write_with_ai_data"]);
        $this->AddAjaxAction("write_with_ai", [$this, "AjaxRequestCallbackWriteWithAi"]);

        $this->AddAjaxAction("writebot_generate", [$this, "writebot_generate"]);

        add_action('admin_footer', [$this, 'writebot_markup']);
    }

    public function OnAdminGlobalStyles__writebot($assetsSlug)
    {
        if (!$this->writebot_active()) {
            return;
        }

        $this->AddAdminScript($assetsSlug . "-sgkb-writebot", "sgkb-writebot.js", false, ['jquery']);
        $this->AddAdminStyle($assetsSlug . "-sgkb-writebot", "sgkb-writebot.css");

        wp_localize_script($assetsSlug . "-sgkb-writebot", 'sgkb_writebot_args', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax-nonce'),
            'text' => [
                'button_text' => $this->__('AI Docs Writer'),
                'generating_button_text' => $this->__('Generating...'),
                'generated_button_text' => $this->__('Generated!'),
                'fields_required_message' => $this->__('Please fill all required fields.'),
                'error_message' => $this->__('An error occurred while generating content.'),
            ],
        ]);
    }

    public function write_with_ai_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $status = sanitize_text_field($this->GetOption('write_with_ai_status', 'I'));
        $ai_tools = $this->GetOption('write_with_ai_tools', '');

        $status = ('A' === $status) ? true : false;
        $ai_tools = maybe_unserialize($ai_tools);

        // Default to ai_proxy if no tools configured
        if (empty($ai_tools) || !is_array($ai_tools)) {
            $ai_tools = ['ai_proxy'];
        }

        $data = [
            'status' => $status,
            'ai_tools' => $ai_tools,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackWriteWithAi()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;
        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

            if ('A' === $status) {
                $ai_tools = sanitize_text_field(ApbdWps_PostValue('ai_tools', ''));

                // AI tools.
                $ai_tools = explode(',', $ai_tools);
                $ai_tools = array_filter($ai_tools, function ($value) {
                    return ('ai_proxy' === $value || 'openai' === $value || 'claude' === $value);
                });

                if (empty($ai_tools)) {
                    $hasError = true;
                }

                $this->AddIntoOption('write_with_ai_status', 'A');
                $this->AddIntoOption('write_with_ai_tools', maybe_serialize($ai_tools));
            } else {
                $this->AddIntoOption('write_with_ai_status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    /* Generate */

    public function writebot_generate()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            // Check if Write With AI is enabled
            $status = $this->GetOption('write_with_ai_status', 'I');
            if ('A' !== $status) {
                $apiResponse->SetResponse(false, $this->__('AI Docs Writer is not enabled.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            // Get selected AI tools
            $ai_tools = maybe_unserialize($this->GetOption('write_with_ai_tools', ''));
            if (empty($ai_tools) || !is_array($ai_tools)) {
                $apiResponse->SetResponse(false, $this->__('No AI tools are configured.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $ai_tool = sanitize_text_field(ApbdWps_PostValue('tool', ''));
            $keywords = sanitize_text_field(ApbdWps_PostValue('keywords', ''));
            $prompt = sanitize_text_field(ApbdWps_PostValue('prompt', ''));

            // Validate AI tool
            if (!in_array($ai_tool, ['ai_proxy', 'openai', 'claude'], true)) {
                $apiResponse->SetResponse(false, $this->__('Invalid AI tool.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            // Check if selected tool is enabled for this feature
            if (!in_array($ai_tool, $ai_tools, true)) {
                $apiResponse->SetResponse(false, $this->__('Selected AI tool is not enabled for AI Docs Writer.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            // Get API configuration from central settings (not needed for ai_proxy)
            $api_config = null;
            $api_key = '';
            $model = '';
            $max_tokens = 4096;

            if ('ai_proxy' === $ai_tool) {
                $api_config = Apbd_wps_settings::GetAIProxyConfig();
                if (null === $api_config) {
                    $apiResponse->SetResponse(false, $this->__('Support Genix AI not configured in settings.'));
                    echo wp_json_encode($apiResponse);
                    return;
                }
            } elseif ('openai' === $ai_tool) {
                $api_config = Apbd_wps_settings::GetOpenAIConfig();
                if (null === $api_config) {
                    $apiResponse->SetResponse(false, $this->__('OpenAI API key not configured in settings.'));
                    echo wp_json_encode($apiResponse);
                    return;
                }
                $api_key = $api_config['api_key'];
                $model = $api_config['model'];
                $max_tokens = $api_config['max_tokens'];
            } elseif ('claude' === $ai_tool) {
                $api_config = Apbd_wps_settings::GetClaudeConfig();
                if (null === $api_config) {
                    $apiResponse->SetResponse(false, $this->__('Claude API key not configured in settings.'));
                    echo wp_json_encode($apiResponse);
                    return;
                }
                $api_key = $api_config['api_key'];
                $model = $api_config['model'];
                $max_tokens = $api_config['max_tokens'];
            }

            $response = null;

            if ('ai_proxy' === $ai_tool) {
                $response = $this->generate_writebot_ai_proxy_response($prompt, $keywords, $max_tokens);
            } elseif ('openai' === $ai_tool) {
                $response = $this->generate_writebot_openai_response($prompt, $keywords, $api_key, $model, $max_tokens);
            } elseif ('claude' === $ai_tool) {
                $response = $this->generate_writebot_claude_response($prompt, $keywords, $api_key, $model, $max_tokens);
            }

            // Handle AI Proxy error array response
            if (is_array($response) && isset($response['error'])) {
                $apiResponse->SetResponse(false, $response['error']);
                echo wp_json_encode($apiResponse);
                return;
            }

            if (!is_wp_error($response) && $response) {
                $apiResponse->SetResponse(true, "", [
                    'content' => $response,
                ]);
            } else {
                $error_msg = is_wp_error($response) ? $response->get_error_message() : $this->__('Failed to generate content.');
                $apiResponse->SetResponse(false, $error_msg);
            }
        }

        echo wp_json_encode($apiResponse);
    }

    private function generate_writebot_openai_response($prompt, $keywords, $api_key, $model, $max_tokens)
    {
        $api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $max_tokens = max(1, intval($max_tokens));

        // Check if model requires max_completion_tokens (GPT-5 series, o-series)
        $uses_completion_tokens = preg_match('/^(gpt-5|o[0-9])/', $model);

        $request_body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a documentation expert who writes documentation for users with proper HTML formatting. Create actionable documentation that naturally uses keywords, includes step-by-step instructions, and helps users solve real problems.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
        ];

        if ($uses_completion_tokens) {
            $request_body['max_completion_tokens'] = intval($max_tokens);
        } else {
            $request_body['max_tokens'] = intval($max_tokens);
        }

        $request_args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($request_body),
            'timeout' => 60
        ];

        $response = wp_remote_post($api_endpoint, $request_args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $body = stripslashes($body);
            $data = json_decode($body, true);
        }

        if (!is_array($data) || empty($data)) {
            return new WP_Error('openai_error', 'Unexpected response format.');
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];

            if (isset($error['message']) && is_string($error['message'])) {
                return new WP_Error('openai_error', $error['message']);
            }
        }

        if (isset($data['choices']) && is_array($data['choices'])) {
            $choices = $data['choices'];

            if (isset($choices[0]) && is_array($choices[0])) {
                $choices_item = $choices[0];

                if (isset($choices_item['message']) && is_array($choices_item['message'])) {
                    $choices_message = $choices_item['message'];

                    if (isset($choices_message['content']) && is_string($choices_message['content'])) {
                        return $choices_message['content'];
                    }
                }
            }
        }

        return new WP_Error('openai_error', 'Unexpected response format.');
    }

    private function generate_writebot_claude_response($prompt, $keywords, $api_key, $model, $max_tokens)
    {
        $api_endpoint = 'https://api.anthropic.com/v1/messages';
        $max_tokens = max(1, intval($max_tokens));

        $request_body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'system' => 'You are a documentation expert who writes documentation for users with proper HTML formatting. Create actionable documentation that naturally uses keywords, includes step-by-step instructions, and helps users solve real problems.'
        ];

        $request_args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01' // Use current Anthropic API version
            ],
            'body' => json_encode($request_body),
            'timeout' => 60
        ];

        $response = wp_remote_post($api_endpoint, $request_args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $body = stripslashes($body);
            $data = json_decode($body, true);
        }

        if (!is_array($data) || empty($data)) {
            return new WP_Error('claude_error', 'Unexpected response format.');
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];

            if (isset($error['message']) && is_string($error['message'])) {
                return new WP_Error('claude_error', $error['message']);
            }
        }

        if (isset($data['content']) && is_array($data['content'])) {
            $content = $data['content'];

            if (isset($content[0]) && is_array($content[0])) {
                $content_item = $content[0];

                if (isset($content_item['text']) && is_string($content_item['text'])) {
                    return $content_item['text'];
                }
            }
        }

        return new WP_Error('claude_error', 'Unexpected response format.');
    }

    /**
     * Generate response using AI Proxy Server
     *
     * @param string $prompt     The user prompt
     * @param string $keywords   Keywords for the content
     * @param int    $max_tokens Maximum tokens for response
     * @return string|array Response content on success, ['error' => $message] on failure
     */
    private function generate_writebot_ai_proxy_response($prompt, $keywords, $max_tokens)
    {
        $system_prompt = 'You are a documentation expert who writes documentation for users with proper HTML formatting. Create actionable documentation that naturally uses keywords, includes step-by-step instructions, and helps users solve real problems.';

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $prompt],
        ];

        // Use the ai_proxy_request helper from the trait
        $result = $this->ai_proxy_request($messages, [
            'max_tokens' => max(1, intval($max_tokens)),
            'temperature' => 0.7,
            'feature' => 'sg-write-with-ai',
        ]);

        // Check for errors
        if (isset($result['error'])) {
            return $result; // Return error array
        }

        // Return the content string
        if (isset($result['content'])) {
            return trim($result['content']);
        }

        return ['error' => 'No content received from Support Genix AI.'];
    }

    /* Markup */

    public function writebot_markup()
    {
        if (!$this->writebot_active()) {
            return;
        }

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($coreObject->pluginFile));

        include_once $pluginPath . '/views/knowledge_base/writebot/main.php';
    }

    /* Extra */

    public static function writebot_active()
    {
        if (!is_admin()) {
            return false;
        }

        $screen = get_current_screen();

        if (
            !$screen ||
            ('post' !== $screen->base) ||
            ('sgkb-docs' !== $screen->post_type)
        ) {
            return false;
        }

        // Check if Write With AI is enabled
        $status = Apbd_wps_knowledge_base::GetModuleOption('write_with_ai_status', 'I');
        if ('A' !== $status) {
            return false;
        }

        // Check if any AI tools are configured
        $ai_tools = maybe_unserialize(Apbd_wps_knowledge_base::GetModuleOption('write_with_ai_tools', ''));
        if (empty($ai_tools) || !is_array($ai_tools)) {
            return false;
        }

        // Check if at least one selected tool has API keys in central settings
        $available_tools = Apbd_wps_settings::GetAvailableAITools();

        foreach ($ai_tools as $tool) {
            if ('ai_proxy' === $tool && $available_tools['ai_proxy']) {
                return true;
            }
            if ('openai' === $tool && $available_tools['openai']) {
                return true;
            }
            if ('claude' === $tool && $available_tools['claude']) {
                return true;
            }
        }

        return false;
    }
}
