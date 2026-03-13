<?php

/**
 * Chat Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_chatbot_trait
{
    public function initialize__chatbot()
    {
        $this->AddAjaxAction("chatbot_data", [$this, "chatbot_data"]);
        $this->AddAjaxAction("chatbot_data_text", [$this, "chatbot_data_text"]);
        $this->AddAjaxAction("chatbot_data_style", [$this, "chatbot_data_style"]);
        $this->AddAjaxAction("chatbot", [$this, "AjaxRequestCallbackChatbot"]);
        $this->AddAjaxAction("chatbot_text", [$this, "AjaxRequestCallbackChatbotText"]);
        $this->AddAjaxAction("chatbot_style", [$this, "AjaxRequestCallbackChatbotStyle"]);

        $this->AddAjaxAction("chatbot_top_keywords_data", [$this, "chatbot_top_keywords_data"]);
        $this->AddAjaxAction("chatbot_no_result_keywords_data", [$this, "chatbot_no_result_keywords_data"]);

        add_action('wp_footer', [$this, 'chatbot_markup']);
    }

    public function OnInit__chatbot()
    {
        $this->chatbot_iframe();
    }

    public function chatbot_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $chatbot_status = $this->GetOption('chatbot_status', 'I');
        $chatbot_ai_tool = $this->GetOption('chatbot_ai_tool', 'ai_proxy');
        $chatbot_enable_docs_resources = $this->GetOption('chatbot_enable_docs_resources', 'Y');
        $chatbot_enable_clear_history = $this->GetOption('chatbot_enable_clear_history', 'Y');
        $chatbot_show_in_whole_site = $this->GetOption('chatbot_show_in_whole_site', 'Y');
        $chatbot_show_in_ticket_page = $this->GetOption('chatbot_show_in_ticket_page', 'Y');
        $chatbot_smart_search = $this->GetOption('chatbot_smart_search', 'Y');
        $chatbot_no_match_hello = $this->GetOption('chatbot_no_match_hello', 'Y');

        $chatbot_status = ('A' === $chatbot_status) ? true : false;
        $chatbot_smart_search = ('Y' === $chatbot_smart_search) ? true : false;
        $chatbot_no_match_hello = ('Y' === $chatbot_no_match_hello) ? true : false;

        // Display options.
        $chatbot_display_opts = [];

        if ('Y' === $chatbot_show_in_whole_site) {
            $chatbot_display_opts[] = 'whole_site';
        }

        if ('Y' === $chatbot_show_in_ticket_page) {
            $chatbot_display_opts[] = 'ticket_page';
        }

        // Feature options.
        $chatbot_feature_opts = [];

        if ('Y' === $chatbot_enable_docs_resources) {
            $chatbot_feature_opts[] = 'docs_resources';
        }

        if ('Y' === $chatbot_enable_clear_history) {
            $chatbot_feature_opts[] = 'clear_history';
        }

        $data = [
            'chatbot_status' => $chatbot_status,
            'chatbot_ai_tool' => $chatbot_ai_tool,
            'chatbot_display_opts' => $chatbot_display_opts,
            'chatbot_feature_opts' => $chatbot_feature_opts,
            'chatbot_smart_search' => $chatbot_smart_search,
            'chatbot_no_match_hello' => $chatbot_no_match_hello,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function chatbot_data_text()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $chatbot_text_title = $this->GetOption('chatbot_text_title', $this->__('AI Assistant'));
        $chatbot_text_docs_title = $this->GetOption('chatbot_text_docs_title', $this->__('Documentation'));
        $chatbot_text_welcome_message = $this->GetOption('chatbot_text_welcome_message', $this->__('Hello! How can I help you today?'));
        $chatbot_text_feedback_message = $this->GetOption('chatbot_text_feedback_message', $this->__('Was this answer helpful?'));
        $chatbot_text_helpful_response_message = $this->GetOption('chatbot_text_helpful_response_message', $this->__('Thank you for your feedback.'));
        $chatbot_text_not_helpful_response_message = $this->GetOption('chatbot_text_not_helpful_response_message', $this->__('Thank you for your feedback!'));
        $chatbot_text_related_docs_title = $this->GetOption('chatbot_text_related_docs_title', $this->__('Related documents:'));
        $chatbot_text_create_ticket_link_text = $this->__('Contact Support for Help');
        $chatbot_text_input_placeholder = $this->GetOption('chatbot_text_input_placeholder', $this->__('Ask a question...'));
        $chatbot_text_nothing_found_message = $this->GetOption('chatbot_text_nothing_found_message', $this->__('Nothing matched your query!'));
        $chatbot_text_error_message = $this->GetOption('chatbot_text_error_message', $this->__('Sorry, I encountered an error!'));

        $data = [
            'chatbot_text_title' => $chatbot_text_title,
            'chatbot_text_docs_title' => $chatbot_text_docs_title,
            'chatbot_text_welcome_message' => $chatbot_text_welcome_message,
            'chatbot_text_feedback_message' => $chatbot_text_feedback_message,
            'chatbot_text_helpful_response_message' => $chatbot_text_helpful_response_message,
            'chatbot_text_not_helpful_response_message' => $chatbot_text_not_helpful_response_message,
            'chatbot_text_related_docs_title' => $chatbot_text_related_docs_title,
            'chatbot_text_create_ticket_link_text' => $chatbot_text_create_ticket_link_text,
            'chatbot_text_input_placeholder' => $chatbot_text_input_placeholder,
            'chatbot_text_nothing_found_message' => $chatbot_text_nothing_found_message,
            'chatbot_text_error_message' => $chatbot_text_error_message,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function chatbot_data_style()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $chatbot_primary_color = $this->GetOption('chatbot_primary_color', '#7229dd');
        $chatbot_primary_color = strtolower(trim($chatbot_primary_color));

        if (empty($chatbot_primary_color)) {
            $chatbot_primary_color = '#7229dd';
        }

        $data = [
            'chatbot_primary_color' => $chatbot_primary_color,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallbackChatbot()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;
        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $chatbot_status = sanitize_text_field(ApbdWps_PostValue('chatbot_status', ''));

            if ('A' === $chatbot_status) {
                $chatbot_ai_tool = sanitize_text_field(ApbdWps_PostValue('chatbot_ai_tool', ''));
                $chatbot_display_opts = sanitize_text_field(ApbdWps_PostValue('chatbot_display_opts', ''));
                $chatbot_feature_opts = sanitize_text_field(ApbdWps_PostValue('chatbot_feature_opts', ''));
                $chatbot_smart_search = sanitize_text_field(ApbdWps_PostValue('chatbot_smart_search', ''));
                $chatbot_no_match_hello = sanitize_text_field(ApbdWps_PostValue('chatbot_no_match_hello', ''));

                $chatbot_smart_search = 'Y' === $chatbot_smart_search ? 'Y' : 'N';
                $chatbot_no_match_hello = 'Y' === $chatbot_no_match_hello ? 'Y' : 'N';

                // Display options.
                $chatbot_display_opts = explode(',', $chatbot_display_opts);
                $all__chatbot_display_opts = ['whole_site', 'ticket_page'];

                foreach ($all__chatbot_display_opts as $opt) {
                    if (in_array($opt, $chatbot_display_opts, true)) {
                        $this->AddIntoOption('chatbot_show_in_' . $opt, 'Y');
                    } else {
                        $this->AddIntoOption('chatbot_show_in_' . $opt, 'N');
                    }
                }

                // Feature options.
                $chatbot_feature_opts = explode(',', $chatbot_feature_opts);
                $all__chatbot_feature_opts = ['docs_resources', 'clear_history'];

                foreach ($all__chatbot_feature_opts as $opt) {
                    if (in_array($opt, $chatbot_feature_opts, true)) {
                        $this->AddIntoOption('chatbot_enable_' . $opt, 'Y');
                    } else {
                        $this->AddIntoOption('chatbot_enable_' . $opt, 'N');
                    }
                }

                if (!in_array($chatbot_ai_tool, ['ai_proxy', 'openai', 'claude'], true)) {
                    $hasError = true;
                }

                $this->AddIntoOption('chatbot_status', 'A');
                $this->AddIntoOption('chatbot_ai_tool', $chatbot_ai_tool);
                $this->AddIntoOption('chatbot_smart_search', $chatbot_smart_search);
                $this->AddIntoOption('chatbot_no_match_hello', $chatbot_no_match_hello);
            } else {
                $this->AddIntoOption('chatbot_status', 'I');
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

    public function AjaxRequestCallbackChatbotText()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $chatbot_text_title = sanitize_text_field(ApbdWps_PostValue('chatbot_text_title', ''));
            $chatbot_text_docs_title = sanitize_text_field(ApbdWps_PostValue('chatbot_text_docs_title', ''));
            $chatbot_text_welcome_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_welcome_message', ''));
            $chatbot_text_feedback_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_feedback_message', ''));
            $chatbot_text_helpful_response_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_helpful_response_message', ''));
            $chatbot_text_not_helpful_response_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_not_helpful_response_message', ''));
            $chatbot_text_related_docs_title = sanitize_text_field(ApbdWps_PostValue('chatbot_text_related_docs_title', ''));
            $chatbot_text_input_placeholder = sanitize_text_field(ApbdWps_PostValue('chatbot_text_input_placeholder', ''));
            $chatbot_text_nothing_found_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_nothing_found_message', ''));
            $chatbot_text_error_message = sanitize_text_field(ApbdWps_PostValue('chatbot_text_error_message', ''));

            if (
                (1 > strlen($chatbot_text_title)) ||
                (1 > strlen($chatbot_text_docs_title)) ||
                (1 > strlen($chatbot_text_welcome_message)) ||
                (1 > strlen($chatbot_text_feedback_message)) ||
                (1 > strlen($chatbot_text_helpful_response_message)) ||
                (1 > strlen($chatbot_text_not_helpful_response_message)) ||
                (1 > strlen($chatbot_text_related_docs_title)) ||
                (1 > strlen($chatbot_text_input_placeholder)) ||
                (1 > strlen($chatbot_text_nothing_found_message)) ||
                (1 > strlen($chatbot_text_error_message))
            ) {
                $hasError = true;
            }

            $this->AddIntoOption('chatbot_text_title', $chatbot_text_title);
            $this->AddIntoOption('chatbot_text_docs_title', $chatbot_text_docs_title);
            $this->AddIntoOption('chatbot_text_welcome_message', $chatbot_text_welcome_message);
            $this->AddIntoOption('chatbot_text_feedback_message', $chatbot_text_feedback_message);
            $this->AddIntoOption('chatbot_text_helpful_response_message', $chatbot_text_helpful_response_message);
            $this->AddIntoOption('chatbot_text_not_helpful_response_message', $chatbot_text_not_helpful_response_message);
            $this->AddIntoOption('chatbot_text_related_docs_title', $chatbot_text_related_docs_title);
            $this->AddIntoOption('chatbot_text_input_placeholder', $chatbot_text_input_placeholder);
            $this->AddIntoOption('chatbot_text_nothing_found_message', $chatbot_text_nothing_found_message);
            $this->AddIntoOption('chatbot_text_error_message', $chatbot_text_error_message);

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

    public function AjaxRequestCallbackChatbotStyle()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $chatbot_primary_color = sanitize_text_field(ApbdWps_PostValue('chatbot_primary_color', ''));
            $chatbot_primary_color = strtolower(trim($chatbot_primary_color));

            if (empty($chatbot_primary_color)) {
                $chatbot_primary_color = '#7229dd';
            }

            if (
                (1 > strlen($chatbot_primary_color))
            ) {
                $hasError = true;
            }

            $this->AddIntoOption('chatbot_primary_color', $chatbot_primary_color);

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

    /* Footer */

    public function chatbot_markup()
    {
        if (!$this->is_global_chatbot_active()) {
            return;
        }

        // Cookie is now set early in OnInit__chatbot() to avoid "headers already sent" errors

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($coreObject->pluginFile));

        include_once $pluginPath . '/views/knowledge_base/chatbot/main.php';
    }

    /* Iframe */

    function chatbot_asset_url($link, $withVersion = true)
    {
        if (!$withVersion) {
            $url = plugins_url("chatbot/" . $link, $this->pluginFile);
        } else {
            $version = $this->kernelObject->pluginVersion;

            $base_path = plugin_dir_path($this->kernelObject->pluginFile);
            $file_path = realpath($base_path . "chatbot/" . $link);

            if (file_exists($file_path)) {
                $version .= '-';
                $version .= filemtime($file_path);

                if (defined('WP_DEBUG') && !!WP_DEBUG) {
                    $version .= '-';
                    $version .= time();
                }
            }

            $url = plugins_url("chatbot/" . $link . "?v=" . $version, $this->pluginFile);
        }

        // Adjust URL to match current request's host (fixes www/non-www CORS issues)
        return ApbdWps_AdjustUrlToCurrentHost($url);
    }

    function chatbot_iframe()
    {
        $init = (isset($_GET['chatbot_iframe']) ? rest_sanitize_boolean($_GET['chatbot_iframe']) : false);

        if (!$init) {
            return;
        }

        /**
         * Start output buffering EARLY to capture any PHP errors/warnings
         * from other plugins or themes that might break the iframe.
         *
         * This is a WordPress.org compliant approach that:
         * - Doesn't suppress errors (they still get logged if WP_DEBUG is on)
         * - Doesn't change global error settings
         * - Only ensures clean HTML output for the chatbot iframe
         *
         * @since 1.4.30
         */
        ob_start();

        // Set proper headers before output
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Enhanced cache control for legacy browsers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache'); // HTTP/1.0
        header('Expires: 0'); // Proxies

        // Content Security Policy for iframe content
        $csp_directives = array(
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://static.cloudflareinsights.com",
            'style-src' => "'self' 'unsafe-inline'",
            'font-src' => "'self' data:",
            'img-src' => "'self' data: https:",
            'connect-src' => "'self' https:",
            'frame-src' => "https://www.google.com https://www.gstatic.com https://recaptcha.google.com",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'"
        );
        $csp_directives = apply_filters('apbd-wps/filter/chatbot-iframe-csp', $csp_directives);
        $csp_parts = array();
        foreach ($csp_directives as $directive => $value) {
            $csp_parts[] = $directive . ' ' . $value;
        }
        header("Content-Security-Policy: " . implode('; ', $csp_parts));

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $base_path = plugin_dir_path($coreObject->pluginFile);
        $dist_path = untrailingslashit($base_path) . "/chatbot/dist";
        $dist_css_files = ApbdWps_GetFilesInDirectory($dist_path, 'css');
        $dist_js_files = ApbdWps_GetFilesInDirectory($dist_path, 'js');

        $logged_in = is_user_logged_in();
        $logged_user = null;

        $create_ticket = self::is_chatbot_create_ticket_enabled();
        $docs_resources = self::is_chatbot_docs_resources_enabled();

        if ($logged_in) {
            $userObj = wp_get_current_user();

            $user_id = absint($userObj->ID);
            $user_email = sanitize_email($userObj->user_email);
            $first_name = sanitize_text_field($userObj->first_name);
            $last_name = sanitize_text_field($userObj->last_name);
            $display_name = sanitize_text_field($userObj->display_name);

            $full_name = trim($first_name . ' ' . $last_name);
            $full_name = !empty($full_name) ? $full_name : $display_name;

            $logged_user = new stdClass();
            $logged_user->name = $full_name;
            $logged_user->email = $user_email;
            $logged_user->img = get_user_meta($user_id, 'supportgenix_avatar', true) ? get_user_meta($user_id, 'supportgenix_avatar', true) : get_avatar_url($user_id);
        }

        // Config JS.
        $chatbot_title = $this->GetOption('chatbot_text_title', $this->__('AI Assistant'));
        $chatbot_label = sprintf('%s | %s', $chatbot_title, get_the_title());

        $hide_cp_text = Apbd_wps_settings::GetModuleOption('is_hide_cp_text', 'N');
        $footer_cp_text = 'Y' !== $hide_cp_text ? sprintf($this->__('Powered by %s'), '<a target="_blank" href="https://supportgenix.com">Support Genix</a>') : '';

        $support_genix_chatbot_config = [
            'captcha' => Apbd_wps_settings::GetCaptchaSetting(),
            'rest_url' => ApbdWps_AdjustUrlToCurrentHost(untrailingslashit(rest_url('apbd-wps/v1/chatbot'))),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'lang' => $this->multiLangActive ? $this->multiLangCode : '',
            'logged_in' => $logged_in,
            'logged_user' => $logged_user,
            'create_ticket' => $create_ticket,
            'docs_resources' => $docs_resources,
            'enable_clear_history' => 'Y' === $this->GetOption('chatbot_enable_clear_history', 'Y'),
            'multiple_kb' => false,
            'footer_cp_text' => $footer_cp_text,
            'labels' => [
                'chatbot_title' => $chatbot_title,
                'chatbot_welcome_message' => $this->GetOption('chatbot_text_welcome_message', $this->__('Hello! How can I help you today?')),
                'chatbot_feedback_message' => $this->GetOption('chatbot_text_feedback_message', $this->__('Was this answer helpful?')),
                'chatbot_helpful_response_message' => $this->GetOption('chatbot_text_helpful_response_message', $this->__('Thank you for your feedback.')),
                'chatbot_not_helpful_response_message' => $this->GetOption('chatbot_text_not_helpful_response_message', $this->__('Thank you for your feedback!')),
                'chatbot_related_docs_title' => $this->GetOption('chatbot_text_related_docs_title', $this->__('Related documents:')),
                'chatbot_create_ticket_link_text' => $this->__('Contact Support for Help'),
                'chatbot_nothing_found_message' => $this->GetOption('chatbot_text_nothing_found_message', $this->__('Nothing matched your query!')),
                'chatbot_error_message' => $this->GetOption('chatbot_text_error_message', $this->__('Sorry, I encountered an error!')),
                'chatbot_clear_history' => $this->__('Clear History'),
                'chatbot_unknown' => $this->__('Unknown'),
                'resources_title' => $this->GetOption('chatbot_text_docs_title', $this->__('Documentation')),
            ],
            'texts' => [
                'Ask a question...' => $this->__('Ask a question...'),
                'Chat' => $this->__('Chat'),
                'Tickets' => $this->__('Tickets'),
                'Docs' => $this->__('Docs'),
                'Clear History' => $this->__('Clear History'),
                'Liked' => $this->__('Liked'),
                'Disliked' => $this->__('Disliked'),
                'Create Ticket' => $this->__('Create Ticket'),
                'Name' => $this->__('Name'),
                '%s is required.' => $this->__('%s is required.'),
                'Email' => $this->__('Email'),
                'Category' => $this->__('Category'),
                'Subject' => $this->__('Subject'),
                'Description' => $this->__('Description'),
                'Create' => $this->__('Create'),
                'Success! Your ticket has been created.' => $this->__('Success! Your ticket has been created.'),
                'Our support team will review it soon.' => $this->__('Our support team will review it soon.'),
                'Thank you for reaching out!' => $this->__('Thank you for reaching out!'),
                'Search docs...' => $this->__('Search docs...'),
                'No results found!' => $this->__('No results found!'),
                'Categories' => $this->__('Categories'),
                'Popular Docs' => $this->__('Popular Docs'),
                'Back Home' => $this->__('Back Home'),
                'Back to %s' => $this->__('Back to %s'),
                'Knowledge Bases' => $this->__('Knowledge Bases'),
                'Documentation' => $this->__('Documentation'),
                'Voice Chat' => $this->__('Voice Chat'),
                'Connecting...' => $this->__('Connecting...'),
                'Mute microphone' => $this->__('Mute microphone'),
                'End call' => $this->__('End call'),
                'Mute speaker' => $this->__('Mute speaker'),
                'Unmute microphone' => $this->__('Unmute microphone'),
                'Unmute speaker' => $this->__('Unmute speaker'),
                'Field is required.' => $this->__('Field is required.'),
                'Minimize' => $this->__('Minimize'),
                'article' => $this->__('article'),
                'articles' => $this->__('articles'),
                'category' => $this->__('category'),
                'categories' => $this->__('categories'),
                'reCAPTCHA verification failed' => $this->__('reCAPTCHA verification failed'),
                // VoiceButton
                'Voice input not supported in this browser' => $this->__('Voice input not supported in this browser'),
                'Processing...' => $this->__('Processing...'),
                'Click to stop recording' => $this->__('Click to stop recording'),
                'Click to start recording' => $this->__('Click to start recording'),
                // SpeakerButton
                'Loading...' => $this->__('Loading...'),
                'Stop playback' => $this->__('Stop playback'),
                'Read aloud' => $this->__('Read aloud'),
                // VoiceAgentButton
                'Microphone access denied. Please allow microphone access and try again.' => $this->__('Microphone access denied. Please allow microphone access and try again.'),
                'No microphone found. Please connect a microphone and try again.' => $this->__('No microphone found. Please connect a microphone and try again.'),
                'Failed to access microphone. Please check your browser settings.' => $this->__('Failed to access microphone. Please check your browser settings.'),
                'Voice Agent not supported in this browser' => $this->__('Voice Agent not supported in this browser'),
                'Start voice conversation' => $this->__('Start voice conversation'),
                // voice-support.js - requestMicrophonePermission
                'Microphone access failed' => $this->__('Microphone access failed'),
                'Microphone permission denied. Please allow access in your browser settings.' => $this->__('Microphone permission denied. Please allow access in your browser settings.'),
                'Microphone is in use by another application.' => $this->__('Microphone is in use by another application.'),
                'Microphone constraints could not be satisfied.' => $this->__('Microphone constraints could not be satisfied.'),
                // voice-support.js - getVoiceErrorMessage
                'Microphone access denied. Please enable it in your browser settings.' => $this->__('Microphone access denied. Please enable it in your browser settings.'),
                'No microphone found. Please connect one and try again.' => $this->__('No microphone found. Please connect one and try again.'),
                'Your microphone is being used by another app.' => $this->__('Your microphone is being used by another app.'),
                'Failed to start recording. Please try again.' => $this->__('Failed to start recording. Please try again.'),
                'No audio detected. Please speak louder or check your microphone.' => $this->__('No audio detected. Please speak louder or check your microphone.'),
                'Recording too short. Please speak longer.' => $this->__('Recording too short. Please speak longer.'),
                'Recording too long. Please keep it under 60 seconds.' => $this->__('Recording too long. Please keep it under 60 seconds.'),
                'Network error. Please check your connection and try again.' => $this->__('Network error. Please check your connection and try again.'),
                'Server error. Please try again later.' => $this->__('Server error. Please try again later.'),
                'Request timed out. Please try again.' => $this->__('Request timed out. Please try again.'),
                'Voice service error. Please try again.' => $this->__('Voice service error. Please try again.'),
                'Too many requests. Please wait a moment and try again.' => $this->__('Too many requests. Please wait a moment and try again.'),
                'Invalid audio format. Please try recording again.' => $this->__('Invalid audio format. Please try recording again.'),
                'Failed to transcribe audio. Please try again.' => $this->__('Failed to transcribe audio. Please try again.'),
                'Failed to generate voice response.' => $this->__('Failed to generate voice response.'),
                'Voice Agent not configured. Please contact the administrator.' => $this->__('Voice Agent not configured. Please contact the administrator.'),
                'Failed to connect to Voice Agent. Please try again.' => $this->__('Failed to connect to Voice Agent. Please try again.'),
                'Voice session ended unexpectedly.' => $this->__('Voice session ended unexpectedly.'),
                'WebRTC connection error. Your browser may not fully support this feature.' => $this->__('WebRTC connection error. Your browser may not fully support this feature.'),
                'Voice chat is not supported in your browser.' => $this->__('Voice chat is not supported in your browser.'),
                'Voice Agent is not supported in your browser.' => $this->__('Voice Agent is not supported in your browser.'),
                'Voice chat requires a secure connection (HTTPS).' => $this->__('Voice chat requires a secure connection (HTTPS).'),
                'Voice Agent requires a secure connection (HTTPS).' => $this->__('Voice Agent requires a secure connection (HTTPS).'),
                'An error occurred with voice chat.' => $this->__('An error occurred with voice chat.'),
                // useVoiceAgent
                'Voice agent error' => $this->__('Voice agent error'),
                'No agent configuration provided' => $this->__('No agent configuration provided'),
                'Failed to start voice session' => $this->__('Failed to start voice session'),
                // ticketsStore
                'Network error: %s' => $this->__('Network error: %s'),
                'HTTP error! status: %s' => $this->__('HTTP error! status: %s'),
                'JSON parsing error: %s' => $this->__('JSON parsing error: %s'),
            ],
            'debug' => defined('WP_DEBUG') ? !!WP_DEBUG : false,
        ];

        // Config CSS.
        $primary_color = $this->GetOption('chatbot_primary_color', '#7229dd');
        $primary_color = strtolower(trim($primary_color));

        if (empty($primary_color)) {
            $primary_color = '#7229dd';
        }

        $custom_css = ":root {--sgenixcbprimarycolor: {$primary_color};}";

        /**
         * Clean the output buffer to discard any errors/warnings
         * that were captured during initialization.
         *
         * This ensures only the chatbot HTML is sent to the iframe,
         * even if other plugins/themes output PHP errors.
         *
         * @since 1.4.30
         */
        ob_clean();
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <link rel="icon" href="<?php echo esc_url(Apbd_wps_settings::GetModuleOption("app_favicon", $this->chatbot_asset_url("dist/img/favicon32x32.png"))); ?>">
            <link rel="icon" type="image/png" href="<?php echo esc_url(Apbd_wps_settings::GetModuleOption("app_favicon", $this->chatbot_asset_url("dist/img/favicon180x180.png"))); ?>">
            <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(Apbd_wps_settings::GetModuleOption("app_favicon", $this->chatbot_asset_url("dist/img/favicon180x180.png"))); ?>">
            <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url(Apbd_wps_settings::GetModuleOption("app_favicon", $this->chatbot_asset_url("dist/img/favicon32x32.png"))); ?>">
            <link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url(Apbd_wps_settings::GetModuleOption("app_favicon", $this->chatbot_asset_url("dist/img/favicon16x16.png"))); ?>">
            <title><?php echo esc_html($chatbot_label); ?></title>
            <?php
            // Main CSS.
            if (is_array($dist_css_files) && !empty($dist_css_files)) {
                foreach ($dist_css_files as $file_name) {
                    if (0 === strpos($file_name, 'main.')) {
            ?>
                        <style id="support-genix-chatbot-main-inline-css">
                            <?php echo ApbdWps_KsesCss($custom_css); ?>
                        </style>
                        <link rel="stylesheet" id="support-genix-chatbot-main-css" href="<?php echo esc_url($this->chatbot_asset_url("dist/{$file_name}")); ?>" media="" />
                <?php
                    }
                }
            } else {
                ?>
                <style id="support-genix-chatbot-main-inline-css">
                    <?php echo ApbdWps_KsesCss($custom_css); ?>
                </style>
                <link rel="stylesheet" id="support-genix-chatbot-main-css" href="<?php echo esc_url($this->chatbot_asset_url("dist/main.CD_9i_vz.1773217267786.css")); ?>" media="" />
                <?php
            }

            // Main JS.
            if (is_array($dist_js_files) && !empty($dist_js_files)) {
                foreach ($dist_js_files as $file_name) {
                    if (0 === strpos($file_name, 'main.')) {
                ?>
                        <script id="support-genix-chatbot-main-js-extra">
                            var support_genix_chatbot_config = <?php echo json_encode($support_genix_chatbot_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                        </script>
                        <script type="module" src="<?php echo esc_url($this->chatbot_asset_url("dist/{$file_name}")); ?>" id="support-genix-chatbot-main-js"></script>
                <?php
                    }
                }
            } else {
                ?>
                <script id="support-genix-chatbot-main-js-extra">
                    var support_genix_chatbot_config = <?php echo json_encode($support_genix_chatbot_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                </script>
                <script type="module" src="<?php echo esc_url($this->chatbot_asset_url("dist/main.Dw5FQUrR.1773217267786.js")); ?>" id="support-genix-chatbot-main-js"></script>
            <?php
            }
            ?>
        </head>

        <body>
            <div id="support-genix-chatbot"></div>
        </body>

        </html>
<?php
        /**
         * Flush the clean output buffer and terminate.
         *
         * At this point, the buffer contains only the chatbot HTML
         * without any error messages or warnings from other plugins.
         *
         * @since 1.4.30
         */
        ob_end_flush();
        die();
    }

    /* Statistics */

    public function chatbot_top_keywords_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 5);
        $limitStart = ($limit * ($page - 1));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];

        $keywordsobj = new Mapbd_wps_chatbot_keywords();
        $keywords_table = $keywordsobj->GetTableName();

        $eventosbj = new Mapbd_wps_chatbot_events();
        $events_table = $eventosbj->GetTableName();

        global $wpdb;

        $sql = "
            SELECT k.id, k.keyword, SUM(e.count) as count
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'Y' AND e.created_date BETWEEN %s AND %s
            GROUP BY k.id, k.keyword
            ORDER BY count DESC, k.id DESC
            LIMIT %d, %d
        ";
        $result = $wpdb->get_results($wpdb->prepare($sql, $date_start, $date_ended, $limitStart, $limit));

        $sql_total = "
            SELECT COUNT(DISTINCT k.id) as total
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'Y' AND e.created_date BETWEEN %s AND %s
        ";
        $total = $wpdb->get_var($wpdb->prepare($sql_total, $date_start, $date_ended));

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => absint($total),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function chatbot_no_result_keywords_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $page = absint(ApbdWps_GetValue("page"));
        $limit = absint(ApbdWps_GetValue("limit"));
        $date_range_str = sanitize_text_field(ApbdWps_GetValue("dateRange"));

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 5);
        $limitStart = ($limit * ($page - 1));

        $date_range = $this->PrepareAnalyticsDateRange($date_range_str);
        $date_start = $date_range['date_start'];
        $date_ended = $date_range['date_ended'];

        $keywordsobj = new Mapbd_wps_chatbot_keywords();
        $keywords_table = $keywordsobj->GetTableName();

        $eventosbj = new Mapbd_wps_chatbot_events();
        $events_table = $eventosbj->GetTableName();

        global $wpdb;

        $sql = "
            SELECT k.id, k.keyword, SUM(e.count) as count
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'N' AND e.created_date BETWEEN %s AND %s
            GROUP BY k.id, k.keyword
            ORDER BY count DESC, k.id DESC
            LIMIT %d, %d
        ";
        $result = $wpdb->get_results($wpdb->prepare($sql, $date_start, $date_ended, $limitStart, $limit));

        $sql_total = "
            SELECT COUNT(DISTINCT k.id) as total
            FROM $keywords_table k
            JOIN $events_table e ON k.id = e.keyword_id
            WHERE e.founded = 'N' AND e.created_date BETWEEN %s AND %s
        ";
        $total = $wpdb->get_var($wpdb->prepare($sql_total, $date_start, $date_ended));

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => absint($total),
        ]);

        echo wp_json_encode($apiResponse);
    }

    /* Extra */

    public static function is_chatbot_active()
    {
        if (is_admin()) {
            return false;
        }

        $status = Apbd_wps_knowledge_base::GetModuleOption('chatbot_status', 'I');

        if ('A' !== $status) {
            return false;
        }

        $whole_site = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_whole_site', 'Y');
        $ticket_page = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_ticket_page', 'Y');

        if (
            ('Y' !== $whole_site) &&
            ('Y' !== $ticket_page)
        ) {
            return false;
        }

        $ai_tool = Apbd_wps_knowledge_base::GetModuleOption('chatbot_ai_tool', 'ai_proxy');
        $available_tools = Apbd_wps_settings::GetAvailableAITools();

        if (
            !in_array($ai_tool, ['ai_proxy', 'openai', 'claude']) ||
            !$available_tools[$ai_tool]
        ) {
            return false;
        }

        return true;
    }

    public static function is_global_chatbot_active()
    {
        $is_active = self::is_chatbot_active();

        if (!$is_active) {
            return false;
        }

        $ticketPageId = Apbd_wps_settings::GetModuleOption('ticket_page', '');
        $ticketPageId = absint($ticketPageId);

        $chatbot_show_in_whole_site = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_whole_site', 'Y');
        $chatbot_show_in_ticket_page = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_ticket_page', 'Y');

        if ($ticketPageId && is_page($ticketPageId)) {
            if ('Y' !== $chatbot_show_in_ticket_page) {
                return false;
            }
        } elseif ('Y' !== $chatbot_show_in_whole_site) {
            return false;
        }

        return true;
    }

    public static function is_portal_chatbot_active($shortcode = false)
    {
        $is_active = self::is_chatbot_active();

        if (!$is_active) {
            return false;
        }

        $chatbot_show_in_whole_site = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_whole_site', 'Y');
        $chatbot_show_in_ticket_page = Apbd_wps_knowledge_base::GetModuleOption('chatbot_show_in_ticket_page', 'Y');

        if (
            (
                $shortcode &&
                ('Y' === $chatbot_show_in_whole_site)
            ) ||
            ('Y' !== $chatbot_show_in_ticket_page)
        ) {
            return false;
        }

        return true;
    }

    public static function is_chatbot_create_ticket_enabled()
    {
        return false;
    }

    public static function is_chatbot_docs_resources_enabled()
    {
        $is_active = self::is_chatbot_active();

        if (!$is_active) {
            return false;
        }

        $docs_resources = Apbd_wps_knowledge_base::GetModuleOption('chatbot_enable_docs_resources', 'Y');

        if ('Y' !== $docs_resources) {
            return false;
        }

        $logged_in = is_user_logged_in();
        $manage_options = current_user_can('manage_options');

        if (!$logged_in || !$manage_options) {
            $disable_ofcb_single = Apbd_wps_knowledge_base::GetModuleOption('disable_ofcb_single', 'N');

            $docs_args = [
                'post_type' => 'sgkb-docs',
                'post_status' => 'publish',
                'posts_per_page' => 1,
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

            $docs_query = new WP_Query($docs_args);
            $docs_query->get_posts();

            $found_docs = isset($docs_query->found_posts) ? absint($docs_query->found_posts) : 0;

            if (empty($found_docs)) {
                return false;
            }
        }

        return true;
    }
}
