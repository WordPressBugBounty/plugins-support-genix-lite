<?php

/**
 * Chat Query Trait.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 1) . '/libs/Apbd_Wps_Parsedown.php';
require_once dirname(__DIR__, 1) . '/libs/Apbd_Wps_HtmlToMarkdown.php';

trait Apbd_wps_knowledge_base_chatquery_trait
{
    public function initialize__chatquery() {}

    /**
     * Set no-cache headers to prevent browser caching of dynamic API responses.
     */
    private function set_no_cache_headers()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }

    /* Query */

    public function chatbot_query()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!ApbdWps_IsPostBack) {
            return $apiResponse;
        }

        $captcha = $this->chatbot_validate_captcha();

        if (false === $captcha) {
            $apiResponse->SetResponse(false, $this->__('Invalid captcha, try again.'));
            return $apiResponse;
        }

        $query = ApbdWps_PostValue('query', '');
        $query = sanitize_text_field($query);

        if (empty($query)) {
            return $apiResponse;
        }

        // Get chatbot settings
        $ai_tool = $this->GetOption('chatbot_ai_tool', 'ai_proxy');
        $no_match_hello = $this->GetOption('chatbot_no_match_hello', 'Y');
        $disable_ofcb_single = $this->GetOption('disable_ofcb_single', 'N');

        // Validate AI tool
        if (!in_array($ai_tool, ['ai_proxy', 'openai', 'claude'], true)) {
            return $apiResponse;
        }

        // Get API configuration from central settings (not needed for ai_proxy)
        $api_config = null;
        $api_key = '';
        $model = '';
        $max_tokens = 4096;

        if ('ai_proxy' === $ai_tool) {
            $api_config = Apbd_wps_settings::GetAIProxyConfig();
            if (null === $api_config) {
                return $apiResponse;
            }
        } elseif ('openai' === $ai_tool) {
            $api_config = Apbd_wps_settings::GetOpenAIConfig();
            if (null === $api_config) {
                return $apiResponse;
            }
            $api_key = $api_config['api_key'];
            $model = $api_config['model'];
            $max_tokens = $api_config['max_tokens'];
        } elseif ('claude' === $ai_tool) {
            $api_config = Apbd_wps_settings::GetClaudeConfig();
            if (null === $api_config) {
                return $apiResponse;
            }
            $api_key = $api_config['api_key'];
            $model = $api_config['model'];
            $max_tokens = $api_config['max_tokens'];
        }

        $docs = $this->search_chatbot_docs($query);
        $docs_ids = array_column($docs, 'id');
        $docs_count = count($docs);

        $this->create_analytics_data($query, $docs_count);

        $context = !empty($docs) ? $this->build_chatbot_context_from_docs($docs) : '';
        $content = null;

        // Get recent conversation history for context
        $history = $this->get_recent_conversation_history(3);

        if (!empty($context) || ('Y' === $no_match_hello)) {
            if ('ai_proxy' === $ai_tool) {
                $content = $this->generate_chatbot_ai_proxy_response($query, $context, $max_tokens, $history);
            } elseif ('openai' === $ai_tool) {
                $content = $this->generate_chatbot_openai_response($query, $context, $api_key, $model, $max_tokens, $history);
            } elseif ('claude' === $ai_tool) {
                $content = $this->generate_chatbot_claude_response($query, $context, $api_key, $model, $max_tokens, $history);
            }
        }

        // Handle AI Proxy error array response
        if (is_array($content) && isset($content['error'])) {
            $resMessage = $content['error'];
            $resHistory = Mapbd_wps_chatbot_history::create_error_history($query, $resMessage, []);
            $apiResponse->SetResponse(false, $resMessage, $resHistory);
            return $apiResponse;
        }

        if (is_wp_error($content)) {
            $resMessage = $this->__('Sorry, I encountered an error!');
            $resMessage = $this->GetOption('chatbot_text_error_message', $resMessage);
            $resHistory = Mapbd_wps_chatbot_history::create_error_history($query, $resMessage, []);
            $apiResponse->SetResponse(false, $resMessage, $resHistory);
            return $apiResponse;
        }

        if (!$content) {
            $resMessage = $this->__('Nothing matched your query!');
            $resMessage = $this->GetOption('chatbot_text_nothing_found_message', $resMessage);
            $resHistory = Mapbd_wps_chatbot_history::create_error_history($query, $resMessage, []);
            $apiResponse->SetResponse(false, $resMessage, $resHistory);
            return $apiResponse;
        }

        if (!$docs_count) {
            // Store "no match" queries too for complete history
            $history = $this->create_history_data($query, $content, []);
            if (!$history) {
                $resHistory = Mapbd_wps_chatbot_history::create_error_history($query, $content, []);
                $apiResponse->SetResponse(false, '', $resHistory);
                return $apiResponse;
            }
            $apiResponse->SetResponse(true, '', $history);
            return $apiResponse;
        }

        $history = $this->create_history_data($query, $content, $docs_ids);

        if (!$history) {
            $resMessage = $this->__('Sorry, I encountered an error!');
            $resMessage = $this->GetOption('chatbot_text_error_message', $resMessage);
            $resHistory = Mapbd_wps_chatbot_history::create_error_history($query, $resMessage, []);
            $apiResponse->SetResponse(false, '', $resHistory);
            return $apiResponse;
        }

        if ('Y' === $disable_ofcb_single) {
            $docs = array_values(array_filter($docs, function ($doc) {
                if (!$doc['only_for_chatbot']) {
                    return true;
                }
            }));
        }

        $docs = array_map(function ($doc) {
            return [
                'title' => $doc['title'],
                'url' => $doc['url'],
            ];
        }, $docs);

        $history->docs_list = $docs;

        $apiResponse->SetResponse(true, $this->__('Success'), $history);

        return $apiResponse;
    }

    private function search_chatbot_docs($query)
    {
        $docs = [];
        $result = [];

        $smart_search = $this->GetOption('chatbot_smart_search', 'Y');

        if ('Y' === $smart_search) {
            $result = $this->search_smart_docs($query, 5);
        }

        if (empty($result)) {
            $args = [
                'post_type' => 'sgkb-docs',
                'post_status' => 'publish',
                'posts_per_page' => 5,
                's' => $query,
                'orderby' => 'relevance',
                'sgkb_search' => true,
                'suppress_filters' => false, // Allow WPML/Polylang to filter by language
            ];

            $result = get_posts($args);
        }

        if (!empty($result)) {
            foreach ($result as $post) {
                if (!is_object($post) || !isset($post->ID)) {
                    continue;
                }

                $id = absint($post->ID);
                $title = sanitize_text_field($post->post_title);

                $content = $post->post_content;
                // Convert HTML to markdown to preserve links, structure, and formatting
                // so the AI context retains URLs, headings, lists, etc.
                $converter = new Apbd_Wps_HtmlToMarkdown();
                $content = $converter->convert($content);
                $content = wp_check_invalid_utf8($content, true);
                $content = mb_substr($content, 0, 10000);

                $permalink = get_permalink($id);

                $only_for_chatbot = get_post_meta($id, 'only_for_chatbot', true);
                $only_for_chatbot = rest_sanitize_boolean($only_for_chatbot);

                $docs[] = [
                    'id' => $id,
                    'title' => $title,
                    'content' => $content,
                    'url' => $permalink,
                    'only_for_chatbot' => $only_for_chatbot,
                ];
            }
        }

        return $docs;
    }

    /**
     * English stop words to filter out from search queries.
     * These common words add noise and don't help find relevant results.
     *
     * @var array
     */
    private $search_stop_words = [
        'the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'should', 'may', 'might', 'must', 'can', 'to', 'of', 'in', 'for',
        'on', 'with', 'at', 'by', 'from', 'as', 'into', 'through', 'during',
        'before', 'after', 'above', 'below', 'between', 'under', 'again',
        'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how',
        'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such',
        'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too',
        'very', 'just', 'and', 'but', 'if', 'or', 'because', 'until',
        'while', 'this', 'that', 'these', 'those', 'what', 'which', 'who',
        'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'him', 'his',
        'she', 'her', 'it', 'its', 'they', 'them', 'their',
    ];

    /**
     * Filter out English stop words from search terms.
     *
     * @param array $terms Search terms to filter.
     * @return array Filtered search terms.
     */
    private function filter_search_stop_words($terms)
    {
        return array_filter($terms, function ($term) {
            return !in_array(strtolower($term), $this->search_stop_words, true);
        });
    }

    private function search_smart_docs($query, $limit = 5)
    {
        global $wpdb;

        $sanitized_query = sanitize_text_field($query);
        $sanitized_query = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $sanitized_query);

        $is_english = (strtolower(substr(get_locale(), 0, 2)) === 'en');
        $min_strlen = $is_english ? 2 : 0;
        $min_direct_strlen = $is_english ? 5 : 2;

        $search_terms = preg_split('/[\s\p{P}]+/u', $sanitized_query, -1, PREG_SPLIT_NO_EMPTY);
        $search_terms = array_filter(array_map(function ($term) use ($min_strlen) {
            $cleaned = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $term);
            return mb_strlen($cleaned) > $min_strlen ? $cleaned : null;
        }, $search_terms));

        // Filter out English stop words (only for English locale).
        if ($is_english && !empty($search_terms)) {
            $filtered_terms = $this->filter_search_stop_words($search_terms);
            // Only use filtered terms if we still have meaningful terms left.
            if (!empty($filtered_terms)) {
                $search_terms = array_values($filtered_terms);
            }
        }

        if (empty($search_terms)) {
            return [];
        }

        $direct_query_sql = '';
        $direct_query_params = [];

        if (
            (1 < count($search_terms)) &&
            ($min_direct_strlen < mb_strlen($sanitized_query))
        ) {
            $direct_query_sql = "(CASE WHEN p.post_title LIKE %s THEN 20 ELSE 0 END) +
                (CASE WHEN p.post_content LIKE %s THEN 10 ELSE 0 END) +";

            $direct_query_esc = '%' . $wpdb->esc_like($sanitized_query) . '%';
            $direct_query_params = [$direct_query_esc, $direct_query_esc];
        }

        $title_cases = [];
        $content_cases = [];
        $match_count_cases = [];
        $title_params = [];
        $content_params = [];
        $match_count_params = [];

        foreach ($search_terms as $term) {
            $escaped_term = '%' . $wpdb->esc_like($term) . '%';

            $title_cases[] = "(CASE WHEN p.post_title LIKE %s THEN 10 ELSE 0 END)";
            $content_cases[] = "(CASE WHEN p.post_content LIKE %s THEN 5 ELSE 0 END)";
            // Count if term matches in either title or content.
            $match_count_cases[] = "(CASE WHEN p.post_title LIKE %s OR p.post_content LIKE %s THEN 1 ELSE 0 END)";

            $title_params[] = $escaped_term;
            $content_params[] = $escaped_term;
            $match_count_params[] = $escaped_term;
            $match_count_params[] = $escaped_term;
        }

        $title_search = implode(' + ', $title_cases);
        $content_search = implode(' + ', $content_cases);
        $match_count_sql = implode(' + ', $match_count_cases);

        // Calculate minimum term matches required (50% threshold, minimum 1).
        $term_count = count($search_terms);
        $min_matches = max(1, (int) floor($term_count * 0.5));

        // Build taxonomy filter SQL.
        $tax_join_sql = '';
        $tax_where_sql = '';
        $tax_params = [];

        // Add WPML/Polylang language filtering for multilingual support.
        if ($this->multiLangActive && !empty($this->multiLangCode)) {
            $lang_filter = static::buildLanguageFilterSQL($this->multiLangCode);
            $tax_join_sql .= $lang_filter['join_sql'];
            $tax_where_sql .= $lang_filter['where_sql'];
            $tax_params = array_merge($tax_params, $lang_filter['params']);
        }

        // Build base SQL with match_count for threshold filtering.
        $sql_base = "SELECT DISTINCT p.ID, p.post_title, p.post_content,
                ({$direct_query_sql}
                {$title_search} +
                {$content_search}) as relevance_score,
                ({$match_count_sql}) as match_count
            FROM {$wpdb->posts} p
            {$tax_join_sql}
            WHERE p.post_type = 'sgkb-docs'
            AND p.post_status = 'publish'
            {$tax_where_sql}
            HAVING relevance_score > 0";

        $params_base = array_merge(
            $direct_query_params,
            $title_params,
            $content_params,
            $match_count_params,
            $tax_params
        );

        // First attempt: Search with minimum term threshold (50%).
        $use_threshold = ($term_count > 1 && $min_matches > 1);

        if ($use_threshold) {
            $sql_with_threshold = $sql_base . " AND match_count >= %d
                ORDER BY relevance_score DESC, p.post_date DESC
                LIMIT %d";

            $params_with_threshold = array_merge($params_base, [$min_matches, $limit]);

            $prepared_sql = call_user_func_array(
                [$wpdb, 'prepare'],
                array_merge([$sql_with_threshold], $params_with_threshold)
            );
            $docs = $wpdb->get_results($prepared_sql);

            // Fallback: If no results with threshold, retry without threshold.
            if (empty($docs)) {
                $sql_no_threshold = $sql_base . "
                    ORDER BY relevance_score DESC, p.post_date DESC
                    LIMIT %d";

                $params_no_threshold = array_merge($params_base, [$limit]);

                $prepared_sql = call_user_func_array(
                    [$wpdb, 'prepare'],
                    array_merge([$sql_no_threshold], $params_no_threshold)
                );
                $docs = $wpdb->get_results($prepared_sql);
            }
        } else {
            // Single term or threshold is 1 - no need for threshold logic.
            $sql_no_threshold = $sql_base . "
                ORDER BY relevance_score DESC, p.post_date DESC
                LIMIT %d";

            $params_no_threshold = array_merge($params_base, [$limit]);

            $prepared_sql = call_user_func_array(
                [$wpdb, 'prepare'],
                array_merge([$sql_no_threshold], $params_no_threshold)
            );
            $docs = $wpdb->get_results($prepared_sql);
        }

        if (!is_array($docs)) {
            $docs = [];
        }

        return $docs;
    }

    private function build_chatbot_context_from_docs($docs)
    {
        $context = "Based on the following documentation:\n\n";

        foreach ($docs as $index => $doc) {
            $context .= "Document " . ($index + 1) . ": " . $doc['title'] . "\n";
            $context .= $doc['content'] . "\n\n";
        }

        return $context;
    }

    /**
     * Build the system prompt for chatbot responses.
     *
     * @return string System prompt
     */
    private function build_chatbot_system_prompt()
    {
        $prompt = "You are a helpful knowledge base assistant.\n\n";

        // Language detection
        $prompt .= "## Language\n";
        $prompt .= "- If the input is too short or ambiguous to determine language (e.g., \"Hi\", \"Ok\", \"Hlw\"), default to English\n";
        $prompt .= "- Detect the language of EACH user message independently\n";
        $prompt .= "- Respond in the SAME language as the user's CURRENT message\n";
        $prompt .= "- If in the middle of a conversation, the input is too short or ambiguous to determine language, default to the previous conversation language\n";
        $prompt .= "- If the user switches language mid-conversation, switch with them immediately\n\n";

        // Safety boundaries
        $prompt .= "## Important Rules\n";
        $prompt .= "- Only provide information based on the documentation provided\n";
        $prompt .= "- If you're unsure or the documentation doesn't cover the topic, say so honestly\n";
        $prompt .= "- Never make up information or guess answers\n";
        $prompt .= "- Don't provide personal, legal, or medical advice\n\n";

        // Conditional formatting
        $prompt .= "## Response Format\n";
        $prompt .= "- Keep responses under 250 words unless detailed steps are needed\n";
        $prompt .= "- For simple questions: respond in plain text without formatting\n";
        $prompt .= "- For complex answers: use markdown headings (##), bullet points, and code blocks\n";
        $prompt .= "- Only use formatting when it genuinely helps clarity\n";
        $prompt .= "- When a link from the documentation is directly relevant to your answer, include it as a markdown link: [text](url)\n";
        $prompt .= "- Only include links that directly support your answer. Do not list all links from the docs. Never fabricate URLs\n\n";

        // Escalation path
        $prompt .= "## When You Cannot Help\n";
        $prompt .= "If the documentation doesn't have the answer:\n";
        $prompt .= "1. Acknowledge you don't have specific information on this topic\n";
        $prompt .= "2. Suggest alternative search terms if applicable\n";
        $prompt .= "3. Mention: \"For personalized help, you can create a support ticket.\"\n\n";

        // Conversation history usage
        $prompt .= "## Conversation Context\n";
        $prompt .= "Use the previous conversation (if provided) for context when answering follow-up questions.\n";

        return $prompt;
    }

    /**
     * Build the user prompt with optional conversation history.
     *
     * @param string $query   Current user query
     * @param string $context Documentation context (empty if no match)
     * @param array  $history Recent conversation history (last 3)
     * @return string User prompt
     */
    private function build_chatbot_user_prompt($query, $context, $history = [])
    {
        $prompt = '';

        // Add conversation history (last 3 exchanges)
        if (!empty($history) && is_array($history)) {
            $history_text = '';
            foreach ($history as $item) {
                if (!empty($item['query']) && !empty($item['content'])) {
                    $history_text .= "User: " . $item['query'] . "\n";
                    $history_text .= "Assistant: " . wp_strip_all_tags($item['content']) . "\n";
                    $history_text .= "---\n";
                }
            }
            if (!empty($history_text)) {
                $prompt .= "Previous conversation:\n";
                $prompt .= "---\n";
                $prompt .= $history_text;
                $prompt .= "\n";
            }
        }

        // Add documentation context if available
        if (!empty($context)) {
            $prompt .= "Context:\n";
            $prompt .= $context . "\n\n";
            $prompt .= "Current Question: " . $query . "\n\n";
            $prompt .= "If this is just a greeting (hi, hello, hey, thanks, etc.), respond warmly and invite them to ask questions (1-2 sentences) — do NOT use the context above.\n";
            $prompt .= "Otherwise, provide a helpful answer based on the provided context. Remember to respond in the user's language.";
        } else {
            // No match scenario
            $prompt .= "Current Question: " . $query . "\n\n";
            $prompt .= "SITUATION: No matching documentation found.\n\n";
            $prompt .= "If this is a greeting (hi, hello, thanks, etc.):\n";
            $prompt .= "- Respond warmly and invite them to ask questions (1-2 sentences)\n\n";
            $prompt .= "If this is a question:\n";
            $prompt .= "- Acknowledge you don't have specific information on this topic\n";
            $prompt .= "- Suggest they try different keywords or rephrase\n";
            $prompt .= "- Mention: \"For personalized help, you can create a support ticket.\"\n\n";
            $prompt .= "Keep response to 2-3 sentences. Respond in the user's language.";
        }

        return $prompt;
    }

    /**
     * Get recent conversation history for context.
     *
     * @param int $limit Number of recent exchanges to retrieve (default 3)
     * @return array Array of recent conversation items
     */
    private function get_recent_conversation_history($limit = 3)
    {
        global $wpdb;

        try {
            $session_id = sanitize_text_field(ApbdWps_PostValue('session_id', ''));
            if (empty($session_id)) {
                return [];
            }

            $tableName = $wpdb->prefix . 'apbd_wps_chatbot_history';

            $sql = "SELECT query, content FROM {$tableName}
                    WHERE session_id = %s
                    ORDER BY id DESC
                    LIMIT %d";
            $result = $wpdb->get_results($wpdb->prepare($sql, $session_id, $limit), ARRAY_A);

            // Reverse to get chronological order (oldest first)
            return is_array($result) ? array_reverse($result) : [];
        } catch (\Exception $e) {
            // If anything fails, return empty history and continue
            return [];
        }
    }

    private function generate_chatbot_openai_response($query, $context, $api_key, $model, $max_tokens, $history = [])
    {
        $api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $max_tokens = max(1, intval($max_tokens));

        // Use centralized prompt builders
        $system_prompt = $this->build_chatbot_system_prompt();
        $user_prompt = $this->build_chatbot_user_prompt($query, $context, $history);

        // Check if model requires max_completion_tokens (GPT-5 series, o-series)
        $uses_completion_tokens = preg_match('/^(gpt-5|o[0-9])/', $model);

        $request_body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => $user_prompt
                ]
            ],
        ];

        if ($uses_completion_tokens) {
            $request_body['max_completion_tokens'] = intval($max_tokens);
        } else {
            $request_body['max_tokens'] = intval($max_tokens);
            $request_body['temperature'] = 0.5;
            $request_body['response_format'] = ['type' => 'text'];
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
                        $choices_content = $choices_message['content'];
                        $choices_html = $this->convert_markdown_to_html($choices_content);

                        return $choices_html;
                    }
                }
            }
        }

        return new WP_Error('openai_error', 'Unexpected response format.');
    }

    private function generate_chatbot_claude_response($query, $context, $api_key, $model, $max_tokens, $history = [])
    {
        $api_endpoint = 'https://api.anthropic.com/v1/messages';
        $max_tokens = max(1, intval($max_tokens));

        // Use centralized prompt builders
        $system_prompt = $this->build_chatbot_system_prompt();
        $user_prompt = $this->build_chatbot_user_prompt($query, $context, $history);

        $request_body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $user_prompt
                ]
            ],
            'system' => $system_prompt
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
                    $content_text = $content_item['text'];
                    $content_html = $this->convert_markdown_to_html($content_text);

                    return $content_html;
                }
            }
        }

        return new WP_Error('claude_error', 'Unexpected response format.');
    }

    /**
     * Generate response using AI Proxy Server
     *
     * @param string $query      The user query
     * @param string $context    KB context from matched docs
     * @param int    $max_tokens Maximum tokens for response
     * @param array  $history    Recent conversation history
     * @return string|array Response content (HTML) on success, ['error' => $message] on failure
     */
    private function generate_chatbot_ai_proxy_response($query, $context, $max_tokens, $history = [])
    {
        $max_tokens = max(1, intval($max_tokens));

        // Use centralized prompt builders
        $system_prompt = $this->build_chatbot_system_prompt();
        $user_prompt = $this->build_chatbot_user_prompt($query, $context, $history);

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt],
        ];

        // Use the ai_proxy_request helper from the trait
        $result = $this->ai_proxy_request($messages, [
            'max_tokens' => $max_tokens,
            'temperature' => 0.5,
            'feature' => 'sg-chatbot',
        ]);

        // Check for errors
        if (isset($result['error'])) {
            return $result; // Return error array
        }

        // Return the content converted to HTML
        if (isset($result['content'])) {
            $content_html = $this->convert_markdown_to_html(trim($result['content']));
            return $content_html;
        }

        return ['error' => 'No content received from Support Genix AI.'];
    }

    private function convert_markdown_to_html($markdown)
    {
        if (empty($markdown)) {
            return '';
        }

        $parsedown = new \Apbd_Wps_Parsedown();
        $parsedown->setSafeMode(true);

        $html = $parsedown->text($markdown);

        // Links in chatbot responses should open in a new tab.
        $html = ApbdWps_AddLinkTargetBlank($html);

        return $html;
    }

    private function create_history_data($query, $content, $docs_ids)
    {
        $history = null;
        $logged_in = is_user_logged_in();

        // Get session ID from request
        $session_id = $this->getOrCreateSessionId();

        // Get source context (Main Site or Embed)
        $source_data = apply_filters('apbd-wps/filter/chatbot-source', ['source' => 'M', 'embed_token_id' => 0]);

        // Get page URL where the chat session started (strip query params server-side as extra safety)
        $source_data['page_url'] = sanitize_url(strtok(ApbdWps_PostValue('page_url', ''), '?'));

        // Get guest identifier from frontend (localStorage) or fallback to server-generated
        $guest_identifier = null;
        if (!$logged_in) {
            $guest_identifier = sanitize_text_field(ApbdWps_PostValue('guest_identifier', ''));
            // Fallback to server-generated if not provided
            if (empty($guest_identifier)) {
                $guest_identifier = $this->generateGuestIdentifier();
            }
        }

        if ($logged_in) {
            $history = $this->create_user_history_with_session($query, $content, $docs_ids, $session_id, $source_data);
        } else {
            $history = $this->create_guest_history_with_session($query, $content, $docs_ids, $session_id, $guest_identifier, $source_data);
        }

        return $history;
    }

    /**
     * Get or create session ID from request.
     *
     * @return string
     */
    private function getOrCreateSessionId()
    {
        $session_id = sanitize_text_field(ApbdWps_PostValue('session_id', ''));

        // Validate format or generate new
        if (empty($session_id) || strlen($session_id) > 64) {
            $session_id = 'sess_' . bin2hex(random_bytes(16));
        }

        return $session_id;
    }

    /**
     * Generate guest identifier (hashed for privacy).
     *
     * @return string
     */
    private function generateGuestIdentifier()
    {
        $ip = ApbdWps_GetRemoteIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

        // Hash for privacy - cannot be reversed
        return hash('sha256', $ip . $user_agent . wp_salt('auth'));
    }

    /**
     * Create user history with session tracking.
     *
     * @param string $query
     * @param string $content
     * @param array $docs_ids
     * @param string $session_id
     * @param array $source_data Source context with 'source' and 'embed_token_id'
     * @return Mapbd_wps_chatbot_history|null
     */
    private function create_user_history_with_session($query, $content, $docs_ids, $session_id, $source_data = [])
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return null;
        }

        $docs_ids_str = (is_array($docs_ids) ? implode(',', $docs_ids) : (is_string($docs_ids) ? $docs_ids : ''));
        $conv_hash = md5(uniqid(mt_rand(), true));
        $current_time = gmdate("Y-m-d H:i:s");

        $history = new Mapbd_wps_chatbot_history();
        $history->user_id($user_id);
        $history->session_id($session_id);
        $history->guest_identifier(null);
        $history->query($query);
        $history->content($content);
        $history->is_stored_content('Y');
        $history->docs_ids($docs_ids_str);
        $history->feedback('N');
        $history->conv_hash($conv_hash);
        $history->created_at($current_time);
        $history->updated_at($current_time);

        if ($history->save()) {
            global $wpdb;
            $table_name = $history->getTableName();

            // Configurable max messages per user (0 = unlimited)
            $max_records = absint($this->GetModuleOption('chatbot_max_messages', 100));

            if ($max_records > 0) {
                $current_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
                    $user_id
                ));

                if ($max_records < $current_count) {
                    $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$table_name}
                        WHERE user_id = %d
                        AND id NOT IN (
                            SELECT id FROM (
                                SELECT id FROM {$table_name}
                                WHERE user_id = %d
                                ORDER BY id DESC
                                LIMIT %d
                            ) AS keep_items
                        )",
                        $user_id,
                        $user_id,
                        $max_records
                    ));
                }
            }

            // Update session metadata
            Mapbd_wps_chatbot_session::findOrCreate($session_id, array(
                'user_id' => $user_id,
                'guest_identifier' => null,
                'first_query' => $query,
                'feedback' => 'N',
                'source' => isset($source_data['source']) ? $source_data['source'] : 'M',
                'embed_token_id' => isset($source_data['embed_token_id']) ? $source_data['embed_token_id'] : 0,
                'page_url' => isset($source_data['page_url']) ? $source_data['page_url'] : '',
            ));

            // Prepare return object
            unset($history->id);
            unset($history->user_id);
            unset($history->guest_identifier);
            unset($history->docs_ids);
            unset($history->is_stored_content);
            unset($history->updated_at);
            unset($history->settedPropertyforLog);

            return $history;
        }

        return null;
    }

    /**
     * Create guest history with session tracking.
     *
     * @param string $query
     * @param string $content
     * @param array $docs_ids
     * @param string $session_id
     * @param string $guest_identifier
     * @param array $source_data Source context with 'source' and 'embed_token_id'
     * @return Mapbd_wps_chatbot_history|null
     */
    private function create_guest_history_with_session($query, $content, $docs_ids, $session_id, $guest_identifier, $source_data = [])
    {
        $docs_ids_str = (is_array($docs_ids) ? implode(',', $docs_ids) : (is_string($docs_ids) ? $docs_ids : ''));
        $conv_hash = md5(uniqid(mt_rand(), true));
        $current_time = gmdate("Y-m-d H:i:s");

        $history = new Mapbd_wps_chatbot_history();
        $history->user_id(0);
        $history->session_id($session_id);
        $history->guest_identifier($guest_identifier);
        $history->query($query);
        $history->content($content);
        $history->is_stored_content('Y');
        $history->docs_ids($docs_ids_str);
        $history->feedback('N');
        $history->conv_hash($conv_hash);
        $history->created_at($current_time);
        $history->updated_at($current_time);

        if ($history->save()) {
            // Update session metadata
            Mapbd_wps_chatbot_session::findOrCreate($session_id, array(
                'user_id' => 0,
                'guest_identifier' => $guest_identifier,
                'first_query' => $query,
                'feedback' => 'N',
                'source' => isset($source_data['source']) ? $source_data['source'] : 'M',
                'embed_token_id' => isset($source_data['embed_token_id']) ? $source_data['embed_token_id'] : 0,
                'page_url' => isset($source_data['page_url']) ? $source_data['page_url'] : '',
            ));

            // Prepare return object
            unset($history->id);
            unset($history->user_id);
            unset($history->guest_identifier);
            unset($history->docs_ids);
            unset($history->is_stored_content);
            unset($history->updated_at);
            unset($history->settedPropertyforLog);

            return $history;
        }

        return null;
    }

    private function create_analytics_data($keyword, $found_count)
    {
        if (!$this->ShouldTrackAnalytics()) {
            return;
        }

        $keyword = sanitize_text_field($keyword);
        $keyword = strtolower($keyword);
        $founded = $found_count ? 'Y' : 'N';

        $current_time = current_time('mysql');
        $current_date = date('Y-m-d', strtotime($current_time));

        $keyword_id = 0;

        $keywordobj = new Mapbd_wps_chatbot_keywords();
        $keywordobj->keyword($keyword);

        if ($keywordobj->Select()) {
            $keyword_id = $keywordobj->id;
        } else {
            $keywordobj = new Mapbd_wps_chatbot_keywords();
            $keywordobj->keyword($keyword);
            $keywordobj->created_at($current_time);

            if ($keywordobj->Save()) {
                $keyword_id = $keywordobj->id;
            }
        }

        if (empty($keyword_id)) {
            return;
        }

        $existsobj = new Mapbd_wps_chatbot_events();
        $existsobj->keyword_id($keyword_id);
        $existsobj->founded($founded);
        $existsobj->created_date($current_date);

        if ($existsobj->Select()) {
            $updateobj = new Mapbd_wps_chatbot_events();
            $updateobj->count($existsobj->count + 1);

            $updateobj->SetWhereUpdate('id', $existsobj->id);
            $updateobj->Update();
        } else {
            $createobj = new Mapbd_wps_chatbot_events();
            $createobj->keyword_id($keyword_id);
            $createobj->founded($founded);
            $createobj->count(1);
            $createobj->created_at($current_time);
            $createobj->created_date($current_date);

            $createobj->Save();
        }
    }

    /* Feedback */

    public function chatbot_feedback()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!ApbdWps_IsPostBack) {
            return $apiResponse;
        }

        $feedback = ApbdWps_PostValue('feedback', '');
        $feedback = sanitize_text_field($feedback);

        $conv_hash = ApbdWps_PostValue('conv_hash', '');
        $conv_hash = sanitize_text_field($conv_hash);

        if (
            !in_array($feedback, ['H', 'U'], true) ||
            empty($conv_hash)
        ) {
            return $apiResponse;
        }

        // Find the history record to get session_id
        $history_record = Mapbd_wps_chatbot_history::FindBy('conv_hash', $conv_hash);

        $mainobj = new Mapbd_wps_chatbot_history();
        $mainobj->SetWhereUpdate('conv_hash', $conv_hash);
        $mainobj->feedback($feedback);

        if (!$mainobj->Update()) {
            $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            return $apiResponse;
        }

        // Update session feedback if session_id exists
        if ($history_record && !empty($history_record->session_id)) {
            Mapbd_wps_chatbot_session::updateFeedback($history_record->session_id, $feedback);
        }

        $apiResponse->SetResponse(true, $this->__('Success'));

        return $apiResponse;
    }

    /* History */

    public function chatbot_history()
    {
        $this->set_no_cache_headers();

        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        global $wpdb;

        $mainobj = new Mapbd_wps_chatbot_history();
        $tableName = $mainobj->GetTableName();
        $sessionTable = $wpdb->prefix . 'apbd_wps_chatbot_session';

        // Build source filter: Main site sees M + legacy, Embed sees matching E + legacy
        $source_data = apply_filters('apbd-wps/filter/chatbot-source', ['source' => 'M', 'embed_token_id' => 0]);
        $source = sanitize_text_field($source_data['source']);
        $embed_token_id = absint($source_data['embed_token_id']);

        if ('E' === $source && $embed_token_id > 0) {
            $source_filter = $wpdb->prepare(
                "AND (
                    (s.source = 'E' AND s.embed_token_id = %d)
                    OR s.source NOT IN ('M', 'E')
                    OR s.source IS NULL
                )",
                $embed_token_id
            );
        } else {
            $source_filter = "AND (
                s.source = 'M'
                OR s.source NOT IN ('M', 'E')
                OR s.source IS NULL
            )";
        }

        $logged_in = is_user_logged_in();
        $user_id = get_current_user_id();

        if ($logged_in && !empty($user_id)) {
            // Logged-in user: query by user_id with source filter
            $sql = $wpdb->prepare(
                "SELECT * FROM (
                    SELECT h.* FROM {$tableName} h
                    LEFT JOIN {$sessionTable} s ON h.session_id = s.session_id
                    WHERE h.user_id = %d
                    {$source_filter}
                    ORDER BY h.id DESC
                    LIMIT 20
                ) AS recent_convs
                ORDER BY id ASC;",
                $user_id
            );

            $result = $wpdb->get_results($sql);
        } else {
            // Guest user: query by guest_identifier (cross-session) or session_id (fallback)
            $guest_identifier = sanitize_text_field(ApbdWps_GetValue('guest_identifier', ''));
            $session_id = sanitize_text_field(ApbdWps_GetValue('session_id', ''));

            if (!empty($guest_identifier)) {
                // Load ALL history for this guest across all sessions
                $sql = $wpdb->prepare(
                    "SELECT * FROM (
                        SELECT h.* FROM {$tableName} h
                        LEFT JOIN {$sessionTable} s ON h.session_id = s.session_id
                        WHERE h.guest_identifier = %s AND h.user_id = 0
                        {$source_filter}
                        ORDER BY h.id DESC
                        LIMIT 100
                    ) AS recent_convs
                    ORDER BY id ASC;",
                    $guest_identifier
                );

                $result = $wpdb->get_results($sql);
            } elseif (!empty($session_id)) {
                // Fallback: load by session_id only
                $sql = $wpdb->prepare(
                    "SELECT * FROM (
                        SELECT h.* FROM {$tableName} h
                        LEFT JOIN {$sessionTable} s ON h.session_id = s.session_id
                        WHERE h.session_id = %s AND h.user_id = 0
                        {$source_filter}
                        ORDER BY h.id DESC
                        LIMIT 20
                    ) AS recent_convs
                    ORDER BY id ASC;",
                    $session_id
                );

                $result = $wpdb->get_results($sql);
            } else {
                return $apiResponse;
            }
        }

        if (!is_array($result)) {
            $result = [];
        }

        $disable_ofcb_single = $this->GetOption('disable_ofcb_single', 'N');

        foreach ($result as &$item) {
            // Unslash query and content to reverse wp_slash() applied during insert
            $item->query = wp_unslash($item->query);
            $item->content = wp_unslash($item->content);

            $docs_ids = array_filter(array_map('absint', explode(',', $item->docs_ids)));
            $docs_list = [];

            if (!empty($docs_ids)) {
                $docs_args = [
                    'post_type' => 'sgkb-docs',
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    'post__in' => $docs_ids,
                    'orderby' => 'post__in',
                    'suppress_filters' => false, // Allow WPML/Polylang to filter by language
                ];

                if ('Y' === $disable_ofcb_single) {
                    $docs_args['meta_query'] = [
                        [
                            'key' => 'only_for_chatbot',
                            'value' => '1',
                            'compare' => '!=',
                        ]
                    ];
                }

                $docs_list = get_posts($docs_args);
                $docs_list = is_array($docs_list) ? $docs_list : [];
                $docs_list = array_map(function ($post) {
                    $id = absint($post->ID);
                    $title = sanitize_text_field($post->post_title);
                    $permalink = get_permalink($id);

                    return [
                        'title' => $title,
                        'url' => $permalink,
                    ];
                }, $docs_list);
            }

            $item->docs_list = $docs_list;

            unset($item->id);
            unset($item->user_id);
            unset($item->docs_ids);
            unset($item->updated_at);
        }

        $apiResponse->SetResponse(true, $this->__('Success'), $result);

        return $apiResponse;
    }

    /* History Clear */

    public function chatbot_history_clear()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!ApbdWps_IsPostBack) {
            return $apiResponse;
        }

        global $wpdb;

        $historyObj = new Mapbd_wps_chatbot_history();
        $historyTable = $historyObj->GetTableName();

        $sessionObj = new Mapbd_wps_chatbot_session();
        $sessionTable = $sessionObj->GetTableName();

        // Build source filter for clearing only relevant history
        $source_data = apply_filters('apbd-wps/filter/chatbot-source', ['source' => 'M', 'embed_token_id' => 0]);
        $source = sanitize_text_field($source_data['source']);
        $embed_token_id = absint($source_data['embed_token_id']);

        if ('E' === $source && $embed_token_id > 0) {
            $session_source_filter = $wpdb->prepare(
                "AND (
                    (source = 'E' AND embed_token_id = %d)
                    OR source NOT IN ('M', 'E')
                    OR source IS NULL
                )",
                $embed_token_id
            );
        } else {
            $session_source_filter = "AND (
                source = 'M'
                OR source NOT IN ('M', 'E')
                OR source IS NULL
            )";
        }

        $logged_in = is_user_logged_in();
        $user_id = get_current_user_id();

        if ($logged_in && !empty($user_id)) {
            // Logged-in user: delete history for matching sessions only
            $sql = "DELETE h FROM {$historyTable} h
                    INNER JOIN {$sessionTable} s ON h.session_id = s.session_id
                    WHERE h.user_id = %d {$session_source_filter};";
            $result = $wpdb->query($wpdb->prepare($sql, $user_id));

            // Also delete history without a session record (legacy)
            $sql = "DELETE FROM {$historyTable}
                    WHERE user_id = %d
                    AND session_id NOT IN (SELECT session_id FROM {$sessionTable});";
            $wpdb->query($wpdb->prepare($sql, $user_id));

            // Delete matching sessions
            $sql = "DELETE FROM {$sessionTable} WHERE user_id = %d {$session_source_filter};";
            $wpdb->query($wpdb->prepare($sql, $user_id));
        } else {
            // Guest user: delete by guest_identifier (all sessions) or session_id (fallback)
            $guest_identifier = sanitize_text_field(ApbdWps_PostValue('guest_identifier', ''));
            $session_id = sanitize_text_field(ApbdWps_PostValue('session_id', ''));

            if (!empty($guest_identifier)) {
                // Delete history for matching sessions only
                $sql = "DELETE h FROM {$historyTable} h
                        INNER JOIN {$sessionTable} s ON h.session_id = s.session_id
                        WHERE h.guest_identifier = %s AND h.user_id = 0 {$session_source_filter};";
                $result = $wpdb->query($wpdb->prepare($sql, $guest_identifier));

                // Also delete history without a session record (legacy)
                $sql = "DELETE FROM {$historyTable}
                        WHERE guest_identifier = %s AND user_id = 0
                        AND session_id NOT IN (SELECT session_id FROM {$sessionTable});";
                $wpdb->query($wpdb->prepare($sql, $guest_identifier));

                // Delete matching sessions
                $sql = "DELETE FROM {$sessionTable} WHERE guest_identifier = %s AND user_id = 0 {$session_source_filter};";
                $wpdb->query($wpdb->prepare($sql, $guest_identifier));
            } elseif (!empty($session_id)) {
                // Fallback: delete by session_id only
                $sql = "DELETE FROM {$historyTable} WHERE session_id = %s AND user_id = 0;";
                $result = $wpdb->query($wpdb->prepare($sql, $session_id));

                // Also delete the session record
                $sql = "DELETE FROM {$sessionTable} WHERE session_id = %s AND user_id = 0;";
                $wpdb->query($wpdb->prepare($sql, $session_id));
            } else {
                return $apiResponse;
            }
        }

        if (false === $result) {
            $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            return $apiResponse;
        }

        $apiResponse->SetResponse(true, $this->__('Success'));

        return $apiResponse;
    }

    /* Ticket */

    public function chatbot_ticket()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!ApbdWps_IsPostBack) {
            return $apiResponse;
        }

        $captcha = $this->chatbot_validate_captcha();

        if (false === $captcha) {
            $apiResponse->SetResponse(false, $this->__('Invalid captcha, try again.'));
            return $apiResponse;
        }

        $email = sanitize_email(ApbdWps_PostValue('email', ''));
        $first_name = sanitize_text_field(ApbdWps_PostValue('first_name', ''));
        $last_name = sanitize_text_field(ApbdWps_PostValue('last_name', ''));
        $category_id = absint(ApbdWps_PostValue('category', ''));
        $subject = sanitize_text_field(ApbdWps_PostValue('title', ''));
        $description = ApbdWps_KsesHtml(ApbdWps_PostValue('ticket_body', ''));

        if (is_user_logged_in()) {
            $userObj = wp_get_current_user();

            if (!is_object($userObj)) {
                return $apiResponse;
            }

            $email = sanitize_email($userObj->user_email);
            $first_name = sanitize_text_field($userObj->first_name);
            $last_name = sanitize_text_field($userObj->last_name);
        }

        $response = $this->create_ticket_from_data([
            'user_email' => $email,
            'user_first_name' => $first_name,
            'user_last_name' => $last_name,
            'ticket_category_id' => $category_id,
            'ticket_subject' => $subject,
            'ticket_description' => $description,
        ], false);

        $res_success = false;
        $res_message = $this->__('Ticket creation failed.');

        if (is_array($response)) {
            $res_success = isset($response['success']) ? $response['success'] : $res_success;
            $res_message = isset($response['message']) ? $response['message'] : $res_message;
        }

        $apiResponse->SetResponse($res_success, $res_message);

        return $apiResponse;
    }

    /* Ticket Basic */

    public function chatbot_ticket_basic()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $catInstance = Apbd_wps_ticket_category::GetModuleInstance();
        $catResponse = $catInstance->list_for_select(0, true);
        $categories = isset($catResponse['result']) ? $catResponse['result'] : [];

        $data = [
            'categories' => $categories,
        ];

        $apiResponse->SetResponse(true, $this->__('Success.'), $data);

        return $apiResponse;
    }

    /* Resources */

    public function chatbot_resources()
    {
        $this->set_no_cache_headers();

        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $multiple_kb = false; // Multiple KB is a pro-only feature
        $space_id = absint(ApbdWps_GetValue('space', 0));

        $data = [
            'spaces' => [],
            'categories' => [],
            'top_docs' => [],
            'total_docs' => 0,
            'current_space' => null,
        ];

        // If multiple KB is enabled and no space is selected, return spaces list
        if ($multiple_kb && empty($space_id)) {
            $space_args = array(
                'taxonomy' => 'sgkb-docs-space',
                'hide_empty' => true,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
            );

            $spaces = get_terms($space_args);

            if (!is_wp_error($spaces) && !empty($spaces)) {
                $data['spaces'] = array_map(function ($space) {
                    $id = absint($space->term_id);
                    $title = sanitize_text_field($space->name);
                    $description = sanitize_text_field($space->description);
                    $icon_image = get_term_meta($id, '_sg_icon_image', true);

                    // Count categories in this space
                    $categories_in_space = get_terms(array(
                        'taxonomy' => 'sgkb-docs-category',
                        'hide_empty' => true,
                        'meta_query' => array(
                            array(
                                'key' => '_sg_spaces',
                                'value' => '"' . $id . '"',
                                'compare' => 'LIKE'
                            )
                        )
                    ));
                    $category_count = (!is_wp_error($categories_in_space) && !empty($categories_in_space)) ? count($categories_in_space) : 0;

                    // Count articles in this space (excluding chatbot-only)
                    $docs_query = new WP_Query(array(
                        'post_type' => 'sgkb-docs',
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'sgkb-docs-space',
                                'field' => 'term_id',
                                'terms' => $id,
                            )
                        ),
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => 'only_for_chatbot',
                                'compare' => 'NOT EXISTS'
                            ),
                            array(
                                'key' => 'only_for_chatbot',
                                'value' => '1',
                                'compare' => '!='
                            )
                        )
                    ));
                    $docs_count = $docs_query->found_posts;
                    wp_reset_postdata();

                    return [
                        'id' => $id,
                        'title' => $title,
                        'description' => $description,
                        'category_count' => $category_count,
                        'docs_count' => $docs_count,
                        'icon_image' => $icon_image ? esc_url($icon_image) : '',
                    ];
                }, $spaces);
            }

            $apiResponse->SetResponse(true, $this->__('Success.'), $data);
            return $apiResponse;
        }

        // Get categories (filtered by space if multiple KB is enabled)
        if ($multiple_kb && $space_id) {
            // Get space info for current_space
            $space_term = get_term($space_id, 'sgkb-docs-space');
            if ($space_term && !is_wp_error($space_term)) {
                $data['current_space'] = [
                    'id' => $space_id,
                    'title' => sanitize_text_field($space_term->name),
                ];
            }

            // Get categories that belong to this space
            $categories = ApbdWps_GetSpaceCategories($space_id);
        } else {
            $cat_args = array(
                'taxonomy' => 'sgkb-docs-category',
                'hide_empty' => true,
                'hierarchical' => false,
                'meta_key' => '_sg_order',
                'orderby' => 'meta_value_num',
                'order' => 'ASC',
                'suppress_filters' => false,
            );

            $categories = get_terms($cat_args);
        }

        if (
            !is_wp_error($categories) &&
            !empty($categories)
        ) {
            $data['categories'] = array_map(function ($category) {
                $id = absint($category->term_id);
                $title = sanitize_text_field($category->name);
                $description = sanitize_text_field($category->description);
                $count = absint($category->count);

                return [
                    'id' => $id,
                    'title' => $title,
                    'description' => $description,
                    'count' => $count,
                ];
            }, $categories);
        }

        $resources_docs = $this->chatbot_resources_docs(['space' => $space_id]);

        $data['top_docs'] = $resources_docs['docs'];
        $data['total_docs'] = $resources_docs['total'];

        $apiResponse->SetResponse(true, $this->__('Success.'), $data);

        return $apiResponse;
    }

    /* Top docs */

    public function chatbot_top_docs()
    {
        $this->set_no_cache_headers();

        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $category = absint(ApbdWps_GetValue('category'));
        $space = absint(ApbdWps_GetValue('space', 0));
        $resources_docs = $this->chatbot_resources_docs(['category' => $category, 'space' => $space]);

        $data['top_docs'] = $resources_docs['docs'];
        $data['total_docs'] = $resources_docs['total'];

        $apiResponse->SetResponse(true, $this->__('Success.'), $data);

        return $apiResponse;
    }

    /* Search docs */

    public function chatbot_search_docs()
    {
        $this->set_no_cache_headers();

        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $search = sanitize_text_field(ApbdWps_GetValue('search'));
        $space = absint(ApbdWps_GetValue('space', 0));
        $resources_docs = $this->chatbot_resources_docs(['search' => $search, 'space' => $space]);

        $data['top_docs'] = $resources_docs['docs'];
        $data['total_docs'] = $resources_docs['total'];

        $apiResponse->SetResponse(true, $this->__('Success.'), $data);

        return $apiResponse;
    }

    /* Fetch docs */

    public function chatbot_resources_docs($args = [])
    {
        $docs = [];

        $page = 1;
        $limit = 100;
        $limitStart = ($limit * ($page - 1));

        $current_date = current_time('Y-m-d');
        $date_start = date_format(date_create($current_date)->sub(new DateInterval('P1M'))->add(new DateInterval('P1D')), 'Y-m-d');
        $date_ended = $current_date;

        $search = isset($args['search']) ? sanitize_text_field($args['search']) : '';
        $category = isset($args['category']) ? absint($args['category']) : 0;
        $space = isset($args['space']) ? absint($args['space']) : 0;

        $docs_args = array(
            'post_type' => 'sgkb-docs',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $limitStart,
            'orderby' => 'analytics_views',
            'order' => 'DESC',
            'analytics_join' => true,
            'analytics_date_range' => [
                'start' => $date_start,
                'ended' => $date_ended,
            ],
            'suppress_filters' => false,
        );

        $taxq_args = [];

        if (!empty($category)) {
            if (999999 == $category) {
                $taxq_args[] = [
                    'taxonomy' => 'sgkb-docs-category',
                    'operator' => 'NOT EXISTS',
                ];
            } else {
                $taxq_args[] = [
                    'taxonomy' => 'sgkb-docs-category',
                    'field' => 'term_id',
                    'terms' => [$category],
                    'operator' => 'IN',
                ];
            }
        }

        // Filter by space if provided
        if (!empty($space)) {
            $taxq_args[] = [
                'taxonomy' => 'sgkb-docs-space',
                'field' => 'term_id',
                'terms' => [$space],
                'operator' => 'IN',
            ];
        }

        if (!empty($taxq_args)) {
            $taxq_args['relation'] = 'AND';
            $docs_args['tax_query'] = $taxq_args;
        }

        if (0 < strlen($search)) {
            $docs_args['s'] = $search;
        }

        $disable_ofcb_single = $this->GetOption('disable_ofcb_single', 'N');

        if ('Y' === $disable_ofcb_single) {
            $docs_args['meta_query'] = array(
                array(
                    'key' => 'only_for_chatbot',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        add_filter('posts_join', array($this, 'analytics_join_query'), 10, 2);
        add_filter('posts_fields', array($this, 'analytics_fields_query'), 10, 2);
        add_filter('posts_orderby', array($this, 'analytics_orderby_query'), 10, 2);
        add_filter('posts_groupby', array($this, 'analytics_groupby_query'), 10, 2);

        $count_args = $docs_args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';
        $count_args['analytics_join'] = false;

        unset($count_args['offset']);
        unset($count_args['orderby']);
        unset($count_args['order']);

        $docsQuery = new WP_Query($docs_args);
        $countQuery = new WP_Query($count_args);

        $result = $docsQuery->posts;
        $total = $countQuery->found_posts;

        remove_filter('posts_join', array($this, 'analytics_join_query'), 10);
        remove_filter('posts_fields', array($this, 'analytics_fields_query'), 10);
        remove_filter('posts_orderby', array($this, 'analytics_orderby_query'), 10);
        remove_filter('posts_groupby', array($this, 'analytics_groupby_query'), 10);

        if (!is_wp_error($result) && !empty($result)) {
            foreach ($result as &$post) {
                $id = absint($post->ID);
                $title = sanitize_text_field($post->post_title);
                $permalink = get_the_permalink($post);

                $docs[] = [
                    'id' => $id,
                    'title' => $title,
                    'url' => $permalink,
                ];
            }
        }

        if (0 < strlen($search)) {
            $this->update_searches_data($search, $total);
        }

        return [
            'docs' => $docs,
            'total' => $total,
        ];
    }

    /* Validate captcha */

    public function chatbot_validate_captcha()
    {
        $logged_in = is_user_logged_in();
        $grc_status = Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I");
        $create_tckt = Apbd_wps_settings::GetModuleOption("captcha_on_create_tckt", "Y");

        if (
            !$logged_in &&
            ('A' === $grc_status) &&
            ('Y' === $create_tckt)
        ) {
            $grc_token = sanitize_text_field(ApbdWps_PostValue('grc_token', ''));

            if ($grc_token) {
                return Apbd_wps_settings::CheckCaptcha($grc_token);
            }

            return false;
        }

        return true;
    }
}
