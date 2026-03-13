<?php

/**
 * API config.
 */

defined('ABSPATH') || exit;

class ApbdWpsAPI_Portal extends Apbd_Wps_APIBase
{
    public function setAPIBase()
    {
        return 'portal';
    }

    public function routes()
    {
        $this->RegisterRestRoute('GET', '', [$this, "callback"], [$this, "permission"]);
        $this->RegisterRestRoute('POST', '', [$this, "callback"], [$this, "permission"]);
    }

    public function all_routes()
    {
        return [
            'role_data_agent_access' => [
                'module' => 'role',
                'action' => 'data_agent_access',
                'method' => 'data_agent_access',
                'access' => 'both',
                'master' => false,
            ],
            'role_agent_for_select' => [
                'module' => 'role',
                'action' => 'agent_for_select',
                'method' => 'agent_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'settings_data_file' => [
                'module' => 'settings',
                'action' => 'data_file',
                'method' => 'dataFile',
                'access' => 'login',
                'master' => false,
            ],
            'settings_data_basic' => [
                'module' => 'settings',
                'action' => 'data_basic',
                'method' => 'dataBasic',
                'access' => 'both',
                'master' => false,
            ],
            'ticket_category_data_for_select' => [
                'module' => 'ticket_category',
                'action' => 'data_for_select',
                'method' => 'data_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_reply_add' => [
                'module' => 'ticket_reply',
                'action' => 'add',
                'method' => 'add',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_tag_data_for_select' => [
                'module' => 'ticket_tag',
                'action' => 'data_for_select',
                'method' => 'data_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_add' => [
                'module' => 'ticket',
                'action' => 'add',
                'method' => 'add_portal',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_note_add' => [
                'module' => 'ticket',
                'action' => 'note_add',
                'method' => 'note_add',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_edit' => [
                'module' => 'ticket',
                'action' => 'edit',
                'method' => 'edit_portal',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_field_edit' => [
                'module' => 'ticket',
                'action' => 'field_edit',
                'method' => 'field_edit',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_bulk_edit' => [
                'module' => 'ticket',
                'action' => 'bulk_edit',
                'method' => 'bulk_edit',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_privacy_edit' => [
                'module' => 'ticket',
                'action' => 'privacy_edit',
                'method' => 'privacy_edit',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_data' => [
                'module' => 'ticket',
                'action' => 'data',
                'method' => 'data_portal',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_data_single' => [
                'module' => 'ticket',
                'action' => 'data_single',
                'method' => 'data_single_portal',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_change_ticket_user' => [
                'module' => 'ticket',
                'action' => 'change_ticket_user',
                'method' => 'change_ticket_user',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_edit_ticket_user_info' => [
                'module' => 'ticket',
                'action' => 'edit_ticket_user_info',
                'method' => 'edit_ticket_user_info',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_trash_item' => [
                'module' => 'ticket',
                'action' => 'trash_item',
                'method' => 'trash_item',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_trash_items' => [
                'module' => 'ticket',
                'action' => 'trash_items',
                'method' => 'trash_items',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_restore_item' => [
                'module' => 'ticket',
                'action' => 'restore_item',
                'method' => 'restore_item',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_restore_items' => [
                'module' => 'ticket',
                'action' => 'restore_items',
                'method' => 'restore_items',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_delete_item' => [
                'module' => 'ticket',
                'action' => 'delete_item',
                'method' => 'delete_item',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_delete_items' => [
                'module' => 'ticket',
                'action' => 'delete_items',
                'method' => 'delete_items',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_status_for_select' => [
                'module' => 'ticket',
                'action' => 'status_for_select',
                'method' => 'status_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_priority_for_select' => [
                'module' => 'ticket',
                'action' => 'priority_for_select',
                'method' => 'priority_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'ticket_download' => [
                'module' => 'ticket',
                'action' => 'download',
                'method' => 'download',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_current_viewers' => [
                'module' => 'ticket',
                'action' => 'current_viewers',
                'method' => 'current_viewers',
                'access' => 'login',
                'master' => true,
            ],
            'ticket_remove_current_viewer' => [
                'module' => 'ticket',
                'action' => 'remove_current_viewer',
                'method' => 'remove_current_viewer',
                'access' => 'login',
                'master' => true,
            ],
            'users_add' => [
                'module' => 'users',
                'action' => 'add',
                'method' => 'add',
                'access' => 'login',
                'master' => true,
            ],
            'users_data_search' => [
                'module' => 'users',
                'action' => 'data_search',
                'method' => 'data_search',
                'access' => 'login',
                'master' => true,
            ],
            'users_logout' => [
                'module' => 'users',
                'action' => 'logout',
                'method' => 'logout',
                'access' => 'login',
                'master' => false,
            ],
            'users_update' => [
                'module' => 'users',
                'action' => 'update',
                'method' => 'update',
                'access' => 'login',
                'master' => false,
            ],
            'users_change_password' => [
                'module' => 'users',
                'action' => 'change_password',
                'method' => 'change_password',
                'access' => 'login',
                'master' => false,
            ],
            'users_add_guest' => [
                'module' => 'users',
                'action' => 'add_guest',
                'method' => 'add_guest',
                'access' => 'logout',
                'master' => false,
            ],
            'users_login' => [
                'module' => 'users',
                'action' => 'login',
                'method' => 'login',
                'access' => 'logout',
                'master' => false,
            ],
            'users_register' => [
                'module' => 'users',
                'action' => 'register',
                'method' => 'register',
                'access' => 'logout',
                'master' => false,
            ],
            'users_reset_password' => [
                'module' => 'users',
                'action' => 'reset_password',
                'method' => 'reset_password',
                'access' => 'logout',
                'master' => false,
            ],
            'email_to_ticket_both_for_select' => [
                'module' => 'email_to_ticket',
                'action' => 'both_for_select',
                'method' => 'both_for_select',
                'access' => 'login',
                'master' => false,
            ],
            'help_me_write_reply_generate' => [
                'module' => 'help_me_write',
                'action' => 'reply_generate',
                'method' => 'reply_generate',
                'access' => 'login',
                'master' => true,
            ],
            'help_me_write_reply_refine' => [
                'module' => 'help_me_write',
                'action' => 'reply_refine',
                'method' => 'reply_refine',
                'access' => 'login',
                'master' => true,
            ],
        ];
    }

    public function permission(\WP_REST_Request $request)
    {
        $nonce = $request->get_header('X-WP-Nonce');

        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return false;
        }

        $routes = $this->all_routes();
        $action = $request->get_param('action');

        if (isset($routes[$action])) {
            $access = $routes[$action]['access'];
            $master = $routes[$action]['master'];

            if ('login' === $access) {
                if (is_user_logged_in()) {
                    if ($master) {
                        return Apbd_wps_settings::isAgentLoggedIn();
                    }

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

    public function callback(\WP_REST_Request $request)
    {
        $response = $this->response;
        $coreObject = ApbdWps_SupportLite::GetInstance();

        $response->SetResponse(false, $coreObject->__('Invalid request.'));

        $routes = $this->all_routes();
        $action = $request->get_param('action');

        if (!empty($action) && isset($routes[$action])) {
            $route = $routes[$action];
            $module = $route['module'];
            $method = $route['method'];

            $class = 'Apbd_wps_' . $module;
            $instance = new $class($coreObject->pluginBaseName, $coreObject);

            if (method_exists($instance, $method)) {
                $instance->$method();
                die();
            }
        }

        return $response;
    }
}
