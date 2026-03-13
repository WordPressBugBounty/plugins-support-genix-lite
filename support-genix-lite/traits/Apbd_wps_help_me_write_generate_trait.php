<?php

/**
 * Help Me Write Trait for AI-assisted ticket reply generation.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_help_me_write_generate_trait
{
    public function initialize__generate()
    {
        $this->AddAjaxAction("reply_generate", [$this, "reply_generate"]);
        $this->AddAjaxAction("reply_refine", [$this, "reply_refine"]);

        $this->AddPortalAjaxAction("reply_generate", [$this, "reply_generate"]);
        $this->AddPortalAjaxAction("reply_refine", [$this, "reply_refine"]);
    }

    /**
     * Generate reply with AI
     */
    public function reply_generate()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();

        if (!ApbdWps_IsPostBack || !$isAgentUser) {
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get input parameters
        $ticket_id = absint(ApbdWps_PostValue('ticket_id', 0));
        $prompt = sanitize_text_field(ApbdWps_PostValue('prompt', ''));
        $tone = sanitize_text_field(ApbdWps_PostValue('tone', 'professional'));
        $context = wp_kses_post(ApbdWps_PostValue('context', ''));
        $ai_tool = sanitize_text_field(ApbdWps_PostValue('ai_tool', ''));

        if (empty($prompt)) {
            $apiResponse->SetResponse(false, $this->__('Ticket ID and prompt are required.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get Help Me Write settings
        $help_me_write_module = Apbd_wps_help_me_write::GetModuleInstance();

        // Check if Help Me Write is enabled
        $status = $help_me_write_module->GetOption('status', 'I');
        if ('A' !== $status) {
            $apiResponse->SetResponse(false, $this->__('AI Ticket Reply is not enabled.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get selected AI tools
        $ai_tools = maybe_unserialize($help_me_write_module->GetOption('ai_tools', ''));
        if (empty($ai_tools) || !is_array($ai_tools)) {
            $apiResponse->SetResponse(false, $this->__('No AI tools are configured.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // If no tool specified, use first available tool
        if (empty($ai_tool)) {
            if (in_array('ai_proxy', $ai_tools, true) && Apbd_wps_settings::GetAIProxyConfig()) {
                $ai_tool = 'ai_proxy';
            } elseif (in_array('openai', $ai_tools, true) && Apbd_wps_settings::GetOpenAIConfig()) {
                $ai_tool = 'openai';
            } elseif (in_array('claude', $ai_tools, true) && Apbd_wps_settings::GetClaudeConfig()) {
                $ai_tool = 'claude';
            }
        }

        // Validate AI tool
        if (!in_array($ai_tool, ['ai_proxy', 'openai', 'claude'], true)) {
            $apiResponse->SetResponse(false, $this->__('Invalid AI tool.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Check if selected tool is enabled for this feature
        if (!in_array($ai_tool, $ai_tools, true)) {
            $apiResponse->SetResponse(false, $this->__('Selected AI tool is not enabled for AI Ticket Reply.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get API configuration from central settings (not needed for ai_proxy)
        $api_config = null;
        $api_key = '';
        $model = '';
        $max_tokens = 2000;

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
            $max_tokens = min($api_config['max_tokens'], 2000);
        } elseif ('claude' === $ai_tool) {
            $api_config = Apbd_wps_settings::GetClaudeConfig();
            if (null === $api_config) {
                $apiResponse->SetResponse(false, $this->__('Claude API key not configured in settings.'));
                echo wp_json_encode($apiResponse);
                return;
            }
            $api_key = $api_config['api_key'];
            $model = $api_config['model'];
            $max_tokens = min($api_config['max_tokens'], 2000);
        }

        // Get ticket information for context
        $ticket = new stdClass();

        if (!empty($ticket_id)) {
            $ticket = Mapbd_wps_ticket::FindBy("id", $ticket_id);

            if (empty($ticket)) {
                $apiResponse->SetResponse(false, $this->__('Ticket not found.'));
                echo wp_json_encode($apiResponse);
                return;
            }
        }

        // Build AI prompt with context
        $system_prompt = $this->build_help_me_write_system_prompt($tone);
        $user_prompt = $this->build_help_me_write_user_prompt($prompt, $ticket, $context);

        // Generate response based on AI tool
        $generated_reply = null;
        if ('ai_proxy' === $ai_tool) {
            $generated_reply = $this->generate_help_me_write_ai_proxy_response($system_prompt, $user_prompt, $max_tokens);
        } elseif ('openai' === $ai_tool) {
            $generated_reply = $this->generate_help_me_write_openai_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens);
        } elseif ('claude' === $ai_tool) {
            $generated_reply = $this->generate_help_me_write_claude_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens);
        }

        if (is_wp_error($generated_reply)) {
            $apiResponse->SetResponse(false, $this->__('Failed to generate reply. Please try again.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (isset($generated_reply['error'])) {
            $apiResponse->SetResponse(false, $generated_reply['error']);
            echo wp_json_encode($apiResponse);
            return;
        }

        if (empty($generated_reply)) {
            $apiResponse->SetResponse(false, $this->__('Could not generate a reply. Please try again with more details.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Sanitize the generated reply
        $generated_reply = $this->sanitize_help_me_write_content($generated_reply);

        $apiResponse->SetResponse(true, $this->__('Reply generated successfully.'), [
            'reply' => $generated_reply,
            'tone' => $tone
        ]);

        echo wp_json_encode($apiResponse);
    }

    /**
     * Refine an existing reply with AI
     */
    public function reply_refine()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();

        if (!ApbdWps_IsPostBack || !$isAgentUser) {
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get input parameters
        $current_reply = wp_kses_post(ApbdWps_PostValue('current_reply', ''));
        $instruction = sanitize_text_field(ApbdWps_PostValue('instruction', ''));
        $tone = sanitize_text_field(ApbdWps_PostValue('tone', 'professional'));
        $ai_tool = sanitize_text_field(ApbdWps_PostValue('ai_tool', ''));

        if (empty($current_reply) || empty($instruction)) {
            $apiResponse->SetResponse(false, $this->__('Current reply and instruction are required.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get Help Me Write settings
        $help_me_write_module = Apbd_wps_help_me_write::GetModuleInstance();

        // Check if Help Me Write is enabled
        $status = $help_me_write_module->GetOption('status', 'I');
        if ('A' !== $status) {
            $apiResponse->SetResponse(false, $this->__('AI Ticket Reply is not enabled.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get selected AI tools
        $ai_tools = maybe_unserialize($help_me_write_module->GetOption('ai_tools', ''));
        if (empty($ai_tools) || !is_array($ai_tools)) {
            $apiResponse->SetResponse(false, $this->__('No AI tools are configured.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // If no tool specified, use first available tool
        if (empty($ai_tool)) {
            if (in_array('ai_proxy', $ai_tools, true) && Apbd_wps_settings::GetAIProxyConfig()) {
                $ai_tool = 'ai_proxy';
            } elseif (in_array('openai', $ai_tools, true) && Apbd_wps_settings::GetOpenAIConfig()) {
                $ai_tool = 'openai';
            } elseif (in_array('claude', $ai_tools, true) && Apbd_wps_settings::GetClaudeConfig()) {
                $ai_tool = 'claude';
            }
        }

        // Validate AI tool
        if (!in_array($ai_tool, ['ai_proxy', 'openai', 'claude'], true)) {
            $apiResponse->SetResponse(false, $this->__('Invalid AI tool.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Check if selected tool is enabled for this feature
        if (!in_array($ai_tool, $ai_tools, true)) {
            $apiResponse->SetResponse(false, $this->__('Selected AI tool is not enabled for AI Ticket Reply.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Get API configuration from central settings (not needed for ai_proxy)
        $api_config = null;
        $api_key = '';
        $model = '';
        $max_tokens = 2000;

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
            $max_tokens = min($api_config['max_tokens'], 2000);
        } elseif ('claude' === $ai_tool) {
            $api_config = Apbd_wps_settings::GetClaudeConfig();
            if (null === $api_config) {
                $apiResponse->SetResponse(false, $this->__('Claude API key not configured in settings.'));
                echo wp_json_encode($apiResponse);
                return;
            }
            $api_key = $api_config['api_key'];
            $model = $api_config['model'];
            $max_tokens = min($api_config['max_tokens'], 2000);
        }

        // Build refinement prompt
        $system_prompt = "You are an AI assistant helping to refine support ticket replies. Maintain a {$tone} tone.

                         IMPORTANT: Format your response using ONLY these HTML tags: <p>, <strong>, <em>, <a>, <ul>, <ol>, <li>, <br>
                         - Use <p> tags for paragraphs
                         - Use <strong> for bold text and <em> for italic text
                         - Use <a> tags for links and ALWAYS include target=\"_blank\" attribute (e.g., <a href=\"https://example.com\" target=\"_blank\">Link</a>)
                         - Use <ul>, <ol>, and <li> for lists
                         - Use <br> for line breaks
                         - Do NOT use any other HTML tags or attributes
                         - Do NOT use Markdown formatting";
        $user_prompt = "Please refine the following support reply according to this instruction: {$instruction}\n\nCurrent reply:\n{$current_reply}\n\nProvide only the refined reply without any additional explanation.";

        // Generate refined response
        $refined_reply = null;
        if ('ai_proxy' === $ai_tool) {
            $refined_reply = $this->generate_help_me_write_ai_proxy_response($system_prompt, $user_prompt, $max_tokens);
        } elseif ('openai' === $ai_tool) {
            $refined_reply = $this->generate_help_me_write_openai_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens);
        } elseif ('claude' === $ai_tool) {
            $refined_reply = $this->generate_help_me_write_claude_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens);
        }

        if (is_wp_error($refined_reply)) {
            $apiResponse->SetResponse(false, $this->__('Failed to refine reply. Please try again.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        if (isset($refined_reply['error'])) {
            $apiResponse->SetResponse(false, $refined_reply['error']);
            echo wp_json_encode($apiResponse);
            return;
        }

        if (empty($refined_reply)) {
            $apiResponse->SetResponse(false, $this->__('Could not refine the reply. Please try again.'));
            echo wp_json_encode($apiResponse);
            return;
        }

        // Sanitize the refined reply
        $refined_reply = $this->sanitize_help_me_write_content($refined_reply);

        $apiResponse->SetResponse(true, $this->__('Reply refined successfully.'), [
            'reply' => $refined_reply,
            'tone' => $tone
        ]);

        echo wp_json_encode($apiResponse);
    }

    /**
     * Build system prompt for help me write
     */
    private function build_help_me_write_system_prompt($tone)
    {
        $tone_instructions = [
            'professional' => 'professional, clear, and courteous',
            'friendly' => 'friendly, warm, and approachable',
            'formal' => 'formal, respectful, and business-like',
            'casual' => 'casual, relaxed, and conversational',
            'empathetic' => 'empathetic, understanding, and supportive'
        ];

        $tone_desc = isset($tone_instructions[$tone]) ? $tone_instructions[$tone] : $tone_instructions['professional'];

        return "You are a helpful support agent assistant. Generate support ticket replies that are {$tone_desc}.
                Focus on addressing the customer's concerns directly and providing clear, actionable solutions.
                Keep responses concise but thorough. Do not include signatures or greetings unless specifically requested.

                IMPORTANT: Format your response using ONLY these HTML tags: <p>, <strong>, <em>, <a>, <ul>, <ol>, <li>, <br>
                - Use <p> tags for paragraphs
                - Use <strong> for bold text and <em> for italic text
                - Use <a> tags for links and ALWAYS include target=\"_blank\" attribute (e.g., <a href=\"https://example.com\" target=\"_blank\">Link</a>)
                - Use <ul>, <ol>, and <li> for lists
                - Use <br> for line breaks
                - Do NOT use any other HTML tags or attributes
                - Do NOT use Markdown formatting";
    }

    /**
     * Build user prompt for help me write
     */
    private function build_help_me_write_user_prompt($prompt, $ticket, $context)
    {
        $user_prompt = "Generate a support reply based on the following:\n\n";

        if (!empty($ticket->id)) {
            $ticket_user = absint($ticket->ticket_user);
            $ticket_title = sanitize_text_field($ticket->title);

            $ticket_content = $ticket->ticket_body;
            $ticket_content = wp_strip_all_tags($ticket_content, true);
            $ticket_content = wp_check_invalid_utf8($ticket_content, true);
            $ticket_content = wp_specialchars_decode($ticket_content, ENT_QUOTES);
            $ticket_content = preg_replace('/\s+/u', ' ', trim($ticket_content));

            $ticket_replies = [
                [
                    'role' => 'customer',
                    'content' => $ticket_content,
                ],
            ];

            // Get ticket replies.
            $reply_obj = new Mapbd_wps_ticket_reply();
            $reply_obj->ticket_id($ticket->id);

            $replies_array = $reply_obj->SelectAllGridData('replied_by,replied_by_type,reply_text', 'reply_time', 'ASC');

            if (!empty($replies_array)) {
                foreach ($replies_array as &$reply_obj) {
                    $replied_by = absint($reply_obj->replied_by);
                    $replied_by_type = sanitize_text_field($reply_obj->replied_by_type);

                    $reply_content = $reply_obj->reply_text;
                    $reply_content = wp_strip_all_tags($reply_content, true);
                    $reply_content = wp_check_invalid_utf8($reply_content, true);
                    $reply_content = wp_specialchars_decode($reply_content, ENT_QUOTES);
                    $reply_content = preg_replace('/\s+/u', ' ', trim($reply_content));

                    $reply_role = 'Customer';

                    if (
                        ('U' === $replied_by_type) ||
                        ($replied_by === $ticket_user)
                    ) {
                        $reply_role = 'Customer';
                    } elseif ('A' === $replied_by_type) {
                        $reply_role = 'Agent';
                    } elseif ('G' === $replied_by_type) {
                        $reply_role = 'Guest';
                    }

                    $ticket_replies[] = [
                        'role' => $reply_role,
                        'content' => $reply_content,
                    ];
                }
            }

            // Add ticket subject for context
            $user_prompt .= "Ticket Subject: " . $ticket_title . "\n\n";

            // Add conversation history if available
            if (!empty($ticket_replies)) {
                $user_prompt .= "Conversation History:\n";
                foreach ($ticket_replies as $index => $message) {
                    $user_prompt .= $message['role'] . ": " . $message['content'] . "\n";

                    // Add a separator between messages for clarity
                    if ($index < count($ticket_replies) - 1) {
                        $user_prompt .= "---\n";
                    }
                }
                $user_prompt .= "\n";
            }
        }

        // Add user's prompt/instruction
        $user_prompt .= "Instructions: " . $prompt . "\n\n";

        // Add additional context if provided
        if (!empty($context)) {
            $user_prompt .= "Additional Context:\n" . wp_strip_all_tags($context) . "\n\n";
        }

        $user_prompt .= "Please generate a helpful support reply addressing the above. Provide only the reply content without any meta-commentary.";

        return $user_prompt;
    }

    /**
     * Generate response using OpenAI
     */
    private function generate_help_me_write_openai_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens)
    {
        $url = 'https://api.openai.com/v1/chat/completions';

        // Check if model requires max_completion_tokens (GPT-5 series, o-series)
        $uses_completion_tokens = preg_match('/^(gpt-5|o[0-9])/', $model);

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt]
            ],
        ];

        if ($uses_completion_tokens) {
            $body['max_completion_tokens'] = $max_tokens;
        } else {
            $body['max_tokens'] = $max_tokens;
            $body['temperature'] = 0.5;
        }

        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($body)
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }

        return new WP_Error('api_error', 'Failed to generate response from OpenAI');
    }

    /**
     * Generate response using Claude
     */
    private function generate_help_me_write_claude_response($system_prompt, $user_prompt, $api_key, $model, $max_tokens)
    {
        $url = 'https://api.anthropic.com/v1/messages';

        $body = [
            'model' => $model,
            'system' => $system_prompt,
            'messages' => [
                ['role' => 'user', 'content' => $user_prompt]
            ],
            'max_tokens' => $max_tokens
        ];

        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($body)
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['content'][0]['text'])) {
            return trim($data['content'][0]['text']);
        }

        return new WP_Error('api_error', 'Failed to generate response from Claude');
    }

    /**
     * Generate response using AI Proxy Server
     *
     * @param string $system_prompt System prompt for the AI
     * @param string $user_prompt   User prompt/content for the AI
     * @param int    $max_tokens    Maximum tokens for response
     * @return string|array Response content on success, ['error' => $message] on failure
     */
    private function generate_help_me_write_ai_proxy_response($system_prompt, $user_prompt, $max_tokens)
    {
        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt],
        ];

        // Use the ai_proxy_request helper from the trait
        $result = $this->ai_proxy_request($messages, [
            'max_tokens' => $max_tokens,
            'temperature' => 0.5,
            'feature' => 'sg-help-me-write',
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

    /**
     * Sanitize AI-generated content to only allow specific HTML tags
     */
    private function sanitize_help_me_write_content($content)
    {
        // Define allowed tags and attributes
        $allowed_tags = [
            'p' => [],
            'strong' => [],
            'em' => [],
            'a' => [
                'href' => true,
                'target' => true,
            ],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'br' => [],
        ];

        // Use wp_kses to sanitize the content
        $sanitized = wp_kses($content, $allowed_tags);

        return $sanitized;
    }
}
