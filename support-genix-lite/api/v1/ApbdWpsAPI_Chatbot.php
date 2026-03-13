<?php

/**
 * Chatbot API.
 */

defined('ABSPATH') || exit;

class ApbdWpsAPI_Chatbot extends Apbd_Wps_APIBase
{
    /**
     * Set API base.
     */
    public function setAPIBase()
    {
        return "chatbot";
    }

    /**
     * Routes.
     */
    public function routes()
    {
        $this->RegisterRestRoute("POST", "query", [$this, "chatbot_query"], [$this, "permission"]);
        $this->RegisterRestRoute("POST", "feedback", [$this, "chatbot_feedback"], [$this, "permission"]);
        $this->RegisterRestRoute("POST", "history_clear", [$this, "chatbot_history_clear"], [$this, "permission"]);
        $this->RegisterRestRoute("POST", "ticket", [$this, "chatbot_ticket"], [$this, "permission"]);

        $this->RegisterRestRoute("GET", "history", [$this, "chatbot_history"], [$this, "permission"]);
        $this->RegisterRestRoute("GET", "ticket_basic", [$this, "chatbot_ticket_basic"], [$this, "permission"]);
        $this->RegisterRestRoute("GET", "resources", [$this, "chatbot_resources"], [$this, "permission"]);
        $this->RegisterRestRoute("GET", "top_docs", [$this, "chatbot_top_docs"], [$this, "permission"]);
        $this->RegisterRestRoute("GET", "search_docs", [$this, "chatbot_search_docs"], [$this, "permission"]);
    }

    public function all_routes()
    {
        $chatbot_active = Apbd_wps_knowledge_base::is_chatbot_active();
        $ticket_enabled = Apbd_wps_knowledge_base::is_chatbot_create_ticket_enabled();
        $resources_enabled = Apbd_wps_knowledge_base::is_chatbot_docs_resources_enabled();

        return [
            'query' => [
                'access' => 'both',
                'active' => $chatbot_active,
            ],
            'feedback' => [
                'access' => 'both',
                'active' => $chatbot_active,
            ],
            'history' => [
                'access' => 'both',
                'active' => $chatbot_active,
            ],
            'history_clear' => [
                'access' => 'both',
                'active' => $chatbot_active,
            ],
            'ticket' => [
                'access' => 'both',
                'active' => $ticket_enabled,
            ],
            'ticket_basic' => [
                'access' => 'both',
                'active' => $ticket_enabled,
            ],
            'resources' => [
                'access' => 'both',
                'active' => $resources_enabled,
            ],
            'top_docs' => [
                'access' => 'both',
                'active' => $resources_enabled,
            ],
            'search_docs' => [
                'access' => 'both',
                'active' => $resources_enabled,
            ],
        ];
    }

    /**
     * Switch language context for WPML/Polylang.
     * Call this before instantiating modules that query posts.
     */
    private function switch_language_context(\WP_REST_Request $request)
    {
        $lang = sanitize_text_field($request->get_param('lang') ?? '');

        if (empty($lang)) {
            return;
        }

        // WPML language switching
        if (class_exists('SitePress')) {
            do_action('wpml_switch_language', $lang);
        }
        // Polylang language switching
        elseif (function_exists('pll_set_language')) {
            pll_set_language($lang);
        }
    }

    /**
     * Chatbot query.
     */
    public function chatbot_query(\WP_REST_Request $request)
    {
        $this->switch_language_context($request);

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_query')) {
            $apiResponse = $moduleObj->chatbot_query();
        }

        return $apiResponse;
    }

    /**
     * Chatbot feedback.
     */
    public function chatbot_feedback(\WP_REST_Request $request)
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        // Allow guests to give feedback - tracked via conv_hash
        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_feedback')) {
            $apiResponse = $moduleObj->chatbot_feedback();
        }

        return $apiResponse;
    }

    /**
     * Chatbot history.
     */
    public function chatbot_history(\WP_REST_Request $request)
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_history')) {
            $apiResponse = $moduleObj->chatbot_history();
            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Chatbot history clear.
     */
    public function chatbot_history_clear(\WP_REST_Request $request)
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_history_clear')) {
            $apiResponse = $moduleObj->chatbot_history_clear();
        }

        return $apiResponse;
    }

    /**
     * Chatbot ticket.
     */
    public function chatbot_ticket(\WP_REST_Request $request)
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_ticket')) {
            $apiResponse = $moduleObj->chatbot_ticket();
        }

        return $apiResponse;
    }

    /**
     * Chatbot ticket basic.
     */
    public function chatbot_ticket_basic(\WP_REST_Request $request)
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_ticket_basic')) {
            $apiResponse = $moduleObj->chatbot_ticket_basic();
            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Chatbot resources.
     */
    public function chatbot_resources(\WP_REST_Request $request)
    {
        $this->switch_language_context($request);

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_resources')) {
            $apiResponse = $moduleObj->chatbot_resources();
            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Chatbot top docs.
     */
    public function chatbot_top_docs(\WP_REST_Request $request)
    {
        $this->switch_language_context($request);

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_top_docs')) {
            $apiResponse = $moduleObj->chatbot_top_docs();
            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Chatbot search docs.
     */
    public function chatbot_search_docs(\WP_REST_Request $request)
    {
        $this->switch_language_context($request);

        $coreObject = ApbdWps_SupportLite::GetInstance();
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $coreObject->__('Invalid request.'));

        $moduleObj = new Apbd_wps_knowledge_base($coreObject->pluginBaseName, $coreObject);

        if (method_exists($moduleObj, 'chatbot_search_docs')) {
            $apiResponse = $moduleObj->chatbot_search_docs();
            return $apiResponse;
        }

        return $apiResponse;
    }

    /**
     * Permission.
     */
    public function permission(\WP_REST_Request $request)
    {
        $nonce = $request->get_header('X-WP-Nonce');

        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return false;
        }

        $routes = $this->all_routes();

        $action = $request->get_route();
        $action = str_replace('/apbd-wps/v1/chatbot/', '', $action);

        if (isset($routes[$action])) {
            $active = $routes[$action]['active'];

            if (!$active) {
                return false;
            }

            $access = $routes[$action]['access'];

            if ('login' === $access) {
                if (is_user_logged_in()) {
                    return true;
                }

                return false;
            } elseif ('logout' === $access) {
                return !is_user_logged_in();
            }

            return true;
        }

        return false;
    }
}
