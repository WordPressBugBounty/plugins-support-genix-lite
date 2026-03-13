<?php

/**
 * User API.
 */

defined('ABSPATH') || exit;

class ApbdWpsAPI_User extends Apbd_Wps_APIBase
{
    public function __construct($namespace, $register = true)
    {
        parent::__construct($namespace, $register);
    }

    function setAPIBase()
    {
        return 'user';
    }

    function routes() {}

    function SetRoutePermission($route)
    {
        if (in_array($route, ["login", "logout", "create-client", "reset-password"])) {
            return true;
        }
        return parent::SetRoutePermission($route);
    }

    function agent_list()
    {
        $response_agent = [];
        $agent_list = Mapbd_wps_role::getAgentRoles();

        $page = 1;
        $limit = 200;
        $args = array(
            'role__in' => $agent_list,
        );
        $agents = get_users($args);
        foreach ($agents as $user) {
            $usersObj = new stdClass();
            $usersObj->id = $user->ID;
            $usersObj->name = $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->user_nicename;
            $usersObj->username = $user->user_nicename;
            $usersObj->title = $usersObj->name . " ({$usersObj->username})";
            $usersObj->email = $user->user_email;
            $usersObj->img = get_user_meta($user->ID, 'supportgenix_avatar', true) ? get_user_meta($user->ID, 'supportgenix_avatar', true) : get_avatar_url($user->ID);
            $response_agent[] = $usersObj;
        }
        $response_agent = apply_filters('apbd-wps/filter/before-get-agent-list', $response_agent);
        $this->response->SetResponse(true, "Agent List", $response_agent);
        return $this->response;
    }
    function get_client()
    {
        if (! empty($this->payload['email']) && filter_var($this->payload['email'], FILTER_VALIDATE_EMAIL) !== false) {
            $clients = get_user_by('email', $this->payload['email']);
            if ($clients) {
                $client             = new stdClass();
                $client->id = $clients->ID;
                $client->first_name = $clients->first_name;
                $client->last_name  = $clients->last_name;
                $client->username   = $clients->user_nicename;
                $this->response->SetResponse(true, "client found", $client);
            } else {
                $this->response->SetResponse(false, "No client found with this email, enter user details below", null);
            }
        } else {
            $this->response->SetResponse(false, "Invalid email address", null);
        }
        return $this->response;
    }

    public function user_login()
    {
        $credentials = [];
        $credentials['user_login'] = $this->payload['username'];
        $credentials['user_password'] = $this->payload['password'];

        if (Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A" && Apbd_wps_settings::GetModuleOption("captcha_on_login_form", "Y") == "Y") {
            $grcToken = $this->GetPayload('grcToken');
            if (!Apbd_wps_settings::CheckCaptcha($grcToken)) {
                $this->response->SetResponse(false, "Invalid captcha, try again");
                return $this->response;
            }
        }

        $user = wp_signon($credentials);
        if (is_wp_error($user)) {
            $this->response->SetResponse(false, "Incorrect username or password", $credentials);
            return $this->response;
        } else {
            $remember = isset($this->payload['remember']) ? rest_sanitize_boolean($this->payload['remember']) : false;
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, $remember);
            $responseData = new stdClass();
            $responseData->id = $user->ID;
            $responseData->username = $user->user_login;
            $responseData->email = $user->user_email;
            $responseData->name = $user->first_name . ' ' . $user->last_name;
            $responseData->loggedIn = is_user_logged_in();
            $responseData->isAgent = Apbd_wps_settings::isAgentLoggedIn();
            if (empty(trim($responseData->name))) {
                $responseData->name = $user->display_name;
            }
            $responseData->caps = Mapbd_wps_role::SetCapabilitiesByRole($user->caps, $user);
            $responseData->img = get_user_meta($user->ID, 'supportgenix_avatar', true) ? get_user_meta($user->ID, 'supportgenix_avatar', true) : get_avatar_url($user->ID);
            $responseData->wpsnonce =  wp_create_nonce('wp_rest');
            $responseData = apply_filters('apbd-wps/filter/logged-user', $responseData, $user);
            $this->response->SetResponse(true, "Logged in successfully", $responseData);
            return $this->response;
        }
    }

    function check_unique()
    {
        if (! empty($this->payload) && ! empty($this->payload['fld_name'])) {
            $this->payload['fld_name'] = strtolower($this->payload['fld_name']);
            if ($this->payload['fld_name'] == "email") {
                if (email_exists($this->payload['fld_value'])) {
                    $this->response->SetResponse(false, "Email exist / not valid");
                    return $this->response;
                } else {
                    $this->response->SetResponse(true, "Valid email");
                    return $this->response;
                }
            } elseif ($this->payload['fld_name'] == "username") {
                if (username_exists($this->payload['fld_value'])) {
                    $this->response->SetResponse(false, "Username exist / not valid");
                    return $this->response;
                } else {
                    $this->response->SetResponse(true, "Valid username");
                    return $this->response;
                }
            } else {
                $this->response->SetResponse(false, "No valid field checking");
                return $this->response;
            }
        } else {
            $this->response->SetResponse(false, "Empty field");
            return $this->response;
        }
    }

    function user_logout()
    {
        wp_logout();
        if (is_user_logged_in()) {
            $this->response->SetResponse(false, "Logout failed");
            return $this->response;
        } else {
            $this->response->SetResponse(true, "Logout successful");
            return $this->response;
        }
    }

    function change_pass()
    {
        if (! empty($this->payload['newPass']) && ! empty($this->payload['oldPass'])) {
            $userData = wp_get_current_user();
            if (! empty($userData->ID)) {
                if (wp_check_password($this->payload['oldPass'], $userData->user_pass, $userData->ID)) {
                    if ($userData->user_pass == $this->payload['newPass']) {
                        $this->response->SetResponse(true, "Password changed successfully");
                        return $this->response;
                    } else {
                        wp_set_password($this->payload['newPass'], $userData->ID);
                        $credentials = [];
                        $credentials['user_login'] = $userData->user_login;
                        $credentials['user_password'] = $this->payload['newPass'];
                        $user = wp_signon($credentials, false);
                        $responseData = new stdClass();
                        if (! is_wp_error($user)) {
                            $responseData->wpsnonce = wp_create_nonce('wp_rest');
                        } else {
                            $responseData->logout = true;
                        }
                        $this->response->SetResponse(true, "Password changed successfully", $responseData);
                        do_action('apbd-wps/action/change-password');
                        return $this->response;
                    }
                } else {
                    $this->response->SetResponse(false, "Old password not matched");
                    return $this->response;
                }
            } else {
                $this->response->SetResponse(false, "Invalid request");
                return $this->response;
            }
        }
    }



    function roles()
    {
        $responseRoles = Mapbd_wps_role::GetRoleList();
        $this->response->SetResponse(true, "", $responseRoles);
        $responseRoles = apply_filters('apbd-wps/filter/before-get-user-role-list', $responseRoles);
        return $this->response;
    }

    private function getUserObjectById($id)
    {
        $user = get_user_by('ID', $id);
        if (! empty($user)) {
            $userObj = new stdClass();
            $userObj->id = $user->ID;
            $userObj->first_name = $user->first_name;
            $userObj->last_name = $user->last_name;
            $userObj->username = $user->user_nicename;
            $userObj->email = $user->user_email;
            if (Apbd_wps_settings::isAgentLoggedIn($user)) {
                $userObj->role = Apbd_wps_settings::getSupportGenixRole($user);
            } else {
                $userObj->role = Apbd_wps_settings::GetModuleInstance()->__("User");
            }
            $userObj->image = get_user_meta($user->ID, 'supportgenix_avatar', true) ? get_user_meta($user->ID, 'supportgenix_avatar', true) : get_avatar_url($user->user_email);
            $userObj->custom_fields = apply_filters('apbd-wps/filter/user-custom-properties', [], $userObj->id);
            return $userObj;
        }
        return null;
    }

    function user_details($data)
    {
        if (! empty($data['id'])) {
            $id = intval($data['id']);
            $userObj = $this->getUserObjectById($id);
            $userObj = apply_filters('apbd-wps/filter/before-get-user-details', $userObj);
            $this->SetResponse(true, "data found", $userObj);
            return $this->response;
        }
        $this->SetResponse(false, "data not found or invalid param");
        return $this->response;
    }

    function create_user()
    {
        if (!is_user_logged_in() && Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A" && Apbd_wps_settings::GetModuleOption("captcha_on_create_tckt", "Y") == "Y") {
            $grcToken = $this->GetPayload('grcToken');
            if (!Apbd_wps_settings::CheckCaptcha($grcToken)) {
                $this->response->SetResponse(false, "Invalid captcha, try again");
                return $this->response;
            }
        }
        $userPayload = ! empty($this->payload['user']) ? $this->payload['user'] : null;
        $ticketPayload = ! empty($this->payload['ticket']) ? $this->payload['ticket'] : null;
        if ($userPayload || $ticketPayload) {
            $userId = null;
            $userEmail = '';
            $customFields = [];
            if (! empty($userPayload['email'])) {
                $userEmail = $userPayload['email'];
            }
            if (! empty($userPayload['custom_fields'])) {
                $customFields = $userPayload['custom_fields'];
            }
            if (! empty($ticketPayload['custom_fields'])) {
                if (! empty($customFields)) {
                    $customFields = array_merge($customFields, $ticketPayload['custom_fields']);
                } else {
                    $customFields = $ticketPayload['custom_fields'];
                }
            }
            if (! empty($customFields)) {
                $isValidCustomField = apply_filters('apbd-wps/filter/ticket-custom-field-valid', true, $customFields, $userEmail);
                if (!$isValidCustomField) {
                    $this->response->SetResponse(false, "User email is invalid");
                    $msg = ApbdWps_GetMsgAPI();
                    if (empty($msg)) {
                        $msg = Apbd_wps_settings::GetModuleInstance()->__("Ticket creation failed");
                    }
                    $this->response->SetResponse(false, $msg);
                    return $this->response;
                }
            }
            if (is_user_logged_in()) {
                if (Apbd_wps_settings::isClientLoggedIn()) {
                    $userId = $this->get_current_user_id();
                } else {
                    if (empty($userEmail) || filter_var($userEmail, FILTER_VALIDATE_EMAIL) === false) {
                        $this->response->SetResponse(false, "User email is invalid");
                        return $this->response;
                    } else {
                        if (!email_exists($userEmail)) {
                            $userId = Apbd_Wps_User::create_ticket_user($userPayload, false);
                            if (empty($userId)) {
                                add_user_meta($userId, "is_guest", "Y");
                                $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                                return $this->response;
                            }
                        } else {
                            $exists_user = get_user_by("email", $userEmail);
                            if ($exists_user instanceof WP_User) {
                                $userId = $exists_user->ID;
                            } else {
                                $this->response->SetResponse(false, "Invalid existing user");
                                return $this->response;
                            }
                        }
                    }
                }
            } else {
                $userId = Apbd_Wps_User::create_ticket_user($userPayload, false);
                if (! empty($userId)) {
                    Apbd_Wps_User::auto_login($userId);
                } else {
                    $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                    return $this->response;
                }
            }
            if (! empty($userId)) {
                if (Mapbd_wps_ticket::create_ticket_by_payload($ticketPayload, $userId, $ticketObj, true)) {
                    $this->response->SetResponse(true, "Ticket created successfully", $ticketObj);
                } else {
                    $msg = ApbdWps_GetMsgAPI();
                    if (empty($msg)) {
                        $msg = Apbd_wps_settings::GetModuleInstance()->__("Ticket creation failed");
                    }
                    $this->response->SetResponse(false, $msg);
                }
            } else {
                $this->response->SetResponse(false, "User creation failed");
            }
            return $this->response;
        } else {
            $this->response->SetResponse(false, "User creation and ticket creation failed");
            return $this->response;
        }
    }
    function create_client()
    {
        if (Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A" && Apbd_wps_settings::GetModuleOption("captcha_on_reg_form", "Y") == "Y") {
            $grcToken = $this->GetPayload('grcToken');
            if (!Apbd_wps_settings::CheckCaptcha($grcToken)) {
                $this->response->SetResponse(false, "Invalid captcha, try again");
                return $this->response;
            }
        }
        if (! empty($this->payload['custom_fields'])) {
            $userEmail = isset($this->payload['email']) ? sanitize_email($this->payload['email']) : '';
            $isValidCustomField = apply_filters('apbd-wps/filter/ticket-custom-field-valid', true, $this->payload['custom_fields'], $userEmail);
            if (!$isValidCustomField) {
                $msg = ApbdWps_GetMsgAPI();
                if (empty($msg)) {
                    $msg = Apbd_wps_settings::GetModuleInstance()->__("User creation failed");
                }
                $this->response->SetResponse(false, $msg);
                return $this->response;
            }
        }
        if (! empty($this->payload['email'])) {
            if (!email_exists($this->payload['email'])) {
                $userPayload = $this->payload;
                if ($userPayload) {
                    $userId = null;
                    if (is_user_logged_in()) {
                        wp_logout();
                    }
                    $userId = Apbd_Wps_User::create_ticket_user($userPayload, false);
                    if (! empty($userId)) {
                        $this->response->SetResponse(true, "User created successfully");
                        return $this->response;
                    } else {
                        $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                        return $this->response;
                    }
                } else {
                    $this->response->SetResponse(false, "Empty form");
                    return $this->response;
                }
            } else {
                $this->response->SetResponse(false, "Email address is already exists");
            }
        } else {
            $this->response->SetResponse(false, "Empty email address");
        }
        return $this->response;
    }
    function update_client()
    {
        if (! empty($this->payload['id'])) {

            if (isset($this->payload['imgSrc'])) {
                if (! function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $uploadedfile = $_FILES['file'];
                $allowed_image_extension = array("png", "jpg", "jpeg");

                $file_extension = pathinfo($uploadedfile["name"], PATHINFO_EXTENSION);

                if (! in_array($file_extension, $allowed_image_extension)) {
                    $this->response->SetResponse(false, "Invaliid images file. Only PNG and JPEG are allowed");
                    return $this->response;
                }

                $upload_overrides = array(
                    'test_form' => false
                );

                $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

                if ($movefile && isset($movefile['error'])) {
                    $this->response->SetResponse(false, "Invalid image file");
                    return $this->response;
                }

                update_user_meta($this->payload['id'], 'supportgenix_avatar', $movefile['url']);
            }

            $oldUser = get_user_by('ID', $this->payload['id']);
            if (! empty($oldUser)) {
                $userObj = new Apbd_Wps_User();
                $userObj->SetFromArray($this->payload, false);
                $userObj->UnsetAllExcepts('first_name,last_name');
                if ($userObj->IsValidForm(false)) {
                    $userEmail = $this->payload['email'];
                    $customFields = ! empty($this->payload['custom_fields']) ? $this->payload['custom_fields'] : null;
                    $customFieldsValid = apply_filters('apbd-wps/filter/update-client-custom-field-valid', array(), $customFields, $userEmail);

                    if ($customFieldsValid) {
                        $this->response->SetResponse($customFieldsValid['status'], $customFieldsValid['msg'], $userObj);
                    } else {
                        if ($userObj->Update()) {
                            $ruserObj = $this->getUserObjectById($this->payload['id']);
                            do_action('apbd-wps/action/user-updated', $ruserObj, $customFields);
                            $this->response->SetResponse(true, "Successfully updated", $ruserObj);
                        } else {
                            $this->response->SetResponse(false, ApbdWps_GetMsgAPI(), $userObj);
                        }
                    }
                } else {
                    $this->response->SetResponse(false, "Validation failed", $userObj);
                }
                return $this->response;
            }
        } else {
            $this->response->SetResponse(false, "No id found to update user");
            return $this->response;
        }
    }
    function reset_password()
    {
        $credentials = [];
        $user_login = $this->payload['username'];
        $credentials['user_login'] = $user_login;

        if (Apbd_wps_settings::GetModuleOption("recaptcha_v3_status", "I") == "A" && Apbd_wps_settings::GetModuleOption("captcha_on_login_form", "Y") == "Y") {
            $grcToken = $this->GetPayload('grcToken');
            if (!Apbd_wps_settings::CheckCaptcha($grcToken)) {
                $this->response->SetResponse(false, "Invalid captcha, try again");
                return $this->response;
            }
        }

        $retrieve = retrieve_password($user_login);
        if (is_wp_error($retrieve)) {
            $this->response->SetResponse(false, wp_strip_all_tags($retrieve->get_error_message()), $credentials);
            return $this->response;
        } else {
            $responseData = new stdClass();
            $this->response->SetResponse(true, "Password reset email has been sent", $responseData);
            return $this->response;
        }
    }
}
