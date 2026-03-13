<?php

/**
 * Users.
 */

defined('ABSPATH') || exit;

class Apbd_wps_users extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("data_search", [$this, "data_search"]);

        $this->AddPortalAjaxAction("add", [$this, "add"]);
        $this->AddPortalAjaxAction("data_search", [$this, "data_search"]);
        $this->AddPortalAjaxAction("logout", [$this, "logout"]);
        $this->AddPortalAjaxAction("update", [$this, "update"]);
        $this->AddPortalAjaxAction("change_password", [$this, "change_password"]);

        $this->AddPortalAjaxNoPrivAction("add_guest", [$this, "add_guest"]);
        $this->AddPortalAjaxNoPrivAction("login", [$this, "login"]);
        $this->AddPortalAjaxNoPrivAction("register", [$this, "register"]);
        $this->AddPortalAjaxNoPrivAction("reset_password", [$this, "reset_password"]);
    }

    public function OnInit()
    {
        parent::OnInit();

        /* Multiple user roles */
        $multi_role_enabled = $this->is_multi_role_enbled();

        if ($multi_role_enabled) {
            add_action('user_new_form', [$this, 'new_profile_fields']);
            add_action('show_user_profile', [$this, 'edit_profile_fields']);
            add_action('edit_user_profile', [$this, 'edit_profile_fields']);
            add_action('user_register', [$this, 'user_role_register'], 5);
            add_action('profile_update',  [$this, 'profile_role_update'], 10, 2);
        }
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $data = [];
        $hasError = false;

        if (ApbdWps_IsPostBack && current_user_can('create-ticket-user')) {
            $email = sanitize_email(ApbdWps_PostValue('email', ''));
            $first_name = sanitize_text_field(ApbdWps_PostValue('first_name', ''));
            $last_name = sanitize_text_field(ApbdWps_PostValue('last_name', ''));
            $notify = sanitize_text_field(ApbdWps_PostValue('notify', ''));

            $notify = 'Y' === $notify ? 'Y' : 'N';

            if (
                (1 > strlen($email)) ||
                (1 > strlen($first_name))
            ) {
                $hasError = true;
            }

            $userObj = $this->CreateUser($email, $first_name, $last_name, $notify);

            if ($userObj) {
                $data = [
                    'id' => strval(absint($userObj->ID)),
                    'name' => $userObj->display_name,
                    'email' => $userObj->user_email,
                    'avatar' => get_avatar_url($userObj->ID),
                ];

                $userId = absint($userObj->ID);
                $userObj = get_user_by("ID", $userId);

                if ($userObj) {
                    $custom_fields_json = ApbdWps_PostValue('custom_fields', '');
                    $custom_fields = !empty($custom_fields_json) ? json_decode(stripslashes($custom_fields_json), true) : [];

                    if (!empty($custom_fields) && is_array($custom_fields)) {
                        $custom_fields = array_map(function ($value) {
                            return !is_bool($value) ? sanitize_text_field($value) : $value;
                        }, $custom_fields);
                    }

                    $userData = Apbd_Wps_User::get_user_data($userObj);
                    do_action('apbd-wps/action/user-created', $userData, $custom_fields);
                }
            }

            if (!$hasError) {
                if (!empty($data)) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'), $data);
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function add_guest()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $is_disabled = Apbd_wps_settings::GetModuleOption('disable_guest_ticket_creation', 'N');

        if ('Y' === $is_disabled) {
            wp_send_json_error([
                'message' => $this->__('Invalid request.'),
                'status' => 403
            ], 403);
        }

        if (!Apbd_wps_settings::RegistrationAllowed()) {
            wp_send_json_error([
                'message' => $this->__('Sorry, you are not allowed to do that.'),
                'status' => 403
            ], 403);
        }

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $grcToken = ApbdWps_PostValue('grcToken', '');

            $user = ApbdWps_PostValue('user', '');
            $user = !empty($user) ? json_decode(stripslashes($user), true) : [];
            $user = wp_parse_args($user, [
                'email' => '',
                'first_name' => '',
                'last_name' => '',
                'custom_fields' => [],
            ]);

            $ticket = ApbdWps_PostValue('ticket', '');
            $ticket = !empty($ticket) ? json_decode(stripslashes($ticket), true) : [];
            $ticket = wp_parse_args($ticket, [
                'cat_id' => '',
                'title' => '',
                'ticket_body' => '',
                'is_public' => 'N',
                'custom_fields' => [],
            ]);

            $email = sanitize_email($user['email']);
            $first_name = sanitize_text_field($user['first_name']);
            $last_name = sanitize_text_field($user['last_name']);
            $user_custom_fields = $user['custom_fields'];

            if (is_array($user_custom_fields)) {
                $user_custom_fields = array_map(function ($value) {
                    return !is_bool($value) ? sanitize_text_field($value) : $value;
                }, $user_custom_fields);
            } else {
                $user_custom_fields = [];
            }

            $password = wp_generate_password();

            $username = ApbdWps_GenerateBaseUsername($first_name, $last_name, '', $email);
            $username = ApbdWps_GenerateUniqueUsername($username);

            $cat_id = sanitize_text_field($ticket['cat_id']);
            $title = sanitize_text_field($ticket['title']);
            $ticket_body = sanitize_text_field($ticket['ticket_body']);
            $is_public = sanitize_text_field($ticket['is_public']);
            $ticket_custom_fields = $ticket['custom_fields'];

            if (is_array($ticket_custom_fields)) {
                $ticket_custom_fields = array_map(function ($value) {
                    return !is_bool($value) ? sanitize_text_field($value) : $value;
                }, $ticket_custom_fields);
            } else {
                $ticket_custom_fields = [];
            }

            $cat_id = strval($cat_id);
            $ticket_body = stripslashes($ticket_body);
            $check__ticket_body = sanitize_text_field($ticket_body);
            $is_public = 'Y' === $is_public ? 'Y' : 'N';

            if (
                (1 > strlen($email)) ||
                (1 > strlen($first_name)) ||
                (1 > strlen($password)) ||
                (1 > strlen($username)) ||
                (1 > strlen($title)) ||
                (1 > strlen($check__ticket_body))
            ) {
                $hasError = true;
            }

            if (!$hasError) {
                $userObj = get_user_by('email', $email);

                if (!$userObj) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_User($namespace, false);

                    $apiObj->SetPayload('grcToken', $grcToken);

                    $apiObj->SetPayload('user', [
                        'email' => $email,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'username' => $username,
                        'password' => $password,
                        'custom_fields' => $user_custom_fields,
                    ]);

                    $apiObj->SetPayload('ticket', [
                        'cat_id' => $cat_id,
                        'title' => $title,
                        'ticket_body' => $ticket_body,
                        'is_public' => $is_public,
                        'custom_fields' => $ticket_custom_fields,
                    ]);

                    $resObj = $apiObj->create_user();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Successfully created.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $allCustomFields = $ticket_custom_fields;

                    if (! empty($user_custom_fields) && is_array($user_custom_fields)) {
                        $allCustomFields = array_merge($user_custom_fields, $allCustomFields);
                    }

                    if (! empty($allCustomFields)) {
                        $isValidCustomField = apply_filters('apbd-wps/filter/ticket-custom-field-valid', true, $allCustomFields, $email);

                        if (! $isValidCustomField) {
                            $msg = ApbdWps_GetMsgAPI();

                            if (empty($msg)) {
                                $msg = $this->__('Ticket creation failed.');
                            }

                            $apiResponse->SetResponse(false, $msg);
                            echo wp_json_encode($apiResponse);
                            return;
                        }
                    }

                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                    $ticket_user = isset($userObj->ID) ? absint($userObj->ID) : 0;

                    $apiObj->SetPayload('cat_id', $cat_id);
                    $apiObj->SetPayload('ticket_user', $ticket_user);
                    $apiObj->SetPayload('title', $title);
                    $apiObj->SetPayload('ticket_body', $ticket_body);
                    $apiObj->SetPayload('is_public', $is_public);
                    $apiObj->SetPayload('custom_fields', $ticket_custom_fields);

                    $resObj = $apiObj->create_ticket();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Successfully created.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function update()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = get_current_user_id();

        $hasError = false;

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $first_name = sanitize_text_field(ApbdWps_PostValue('first_name', ''));
            $last_name = sanitize_text_field(ApbdWps_PostValue('last_name', ''));
            $custom_fields = ApbdWps_PostValue('custom_fields', '');

            if (!empty($custom_fields)) {
                $custom_fields = json_decode(stripslashes($custom_fields), true);

                if (is_array($custom_fields)) {
                    $custom_fields = array_map(function ($value) {
                        return !is_bool($value) ? sanitize_text_field($value) : $value;
                    }, $custom_fields);
                }
            }

            $custom_fields = is_array($custom_fields) ? $custom_fields : [];

            if (1 > strlen($first_name)) {
                $hasError = true;
            }

            if (!$hasError) {
                $userObj = get_user_by('id', $param_id);

                if ($userObj) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_User($namespace, false);

                    $apiObj->SetPayload('id', $param_id);
                    $apiObj->SetPayload('first_name', $first_name);
                    $apiObj->SetPayload('last_name', $last_name);
                    $apiObj->SetPayload('custom_fields', $custom_fields);
                    $apiObj->SetPayload('username', $userObj->user_login);
                    $apiObj->SetPayload('email', $userObj->user_email);

                    $resObj = $apiObj->update_client();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Invalid user.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function login()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $grcToken = ApbdWps_PostValue('grcToken', '');
            $username = sanitize_text_field(ApbdWps_PostValue('username', ''));
            $password = strval(ApbdWps_PostValue('password', ''));
            $remember = ApbdWps_PostValue('remember', '');

            if (
                (1 > strlen($username)) ||
                (1 > strlen($password))
            ) {
                $hasError = true;
            }

            if (!$hasError) {
                $user = wp_authenticate($username, $password);

                if (!is_wp_error($user)) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_User($namespace, false);

                    $apiObj->SetPayload('grcToken', $grcToken);
                    $apiObj->SetPayload('username', $username);
                    $apiObj->SetPayload('password', $password);
                    $apiObj->SetPayload('remember', $remember);

                    $resObj = $apiObj->user_login();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Login successful.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Invalid username or password.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function logout()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_User($namespace, false);

            $resObj = $apiObj->user_logout();
            $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

            if ($resStatus) {
                $apiResponse->SetResponse(true, $this->__('Logout successful.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function register()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $is_disabled = Apbd_wps_settings::GetModuleOption('disable_registration_form', 'N');

        if ('Y' === $is_disabled) {
            wp_send_json_error([
                'message' => $this->__('Invalid request.'),
                'status' => 403
            ], 403);
        }

        if (!Apbd_wps_settings::RegistrationAllowed()) {
            wp_send_json_error([
                'message' => $this->__('Sorry, you are not allowed to do that.'),
                'status' => 403
            ], 403);
        }

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $grcToken = ApbdWps_PostValue('grcToken', '');
            $email = sanitize_email(ApbdWps_PostValue('email', ''));
            $first_name = sanitize_text_field(ApbdWps_PostValue('first_name', ''));
            $last_name = sanitize_text_field(ApbdWps_PostValue('last_name', ''));
            $password = strval(ApbdWps_PostValue('password', ''));
            $custom_fields = ApbdWps_PostValue('custom_fields', '');

            if (!empty($custom_fields)) {
                $custom_fields = json_decode(stripslashes($custom_fields), true);

                if (is_array($custom_fields)) {
                    $custom_fields = array_map(function ($value) {
                        return !is_bool($value) ? sanitize_text_field($value) : $value;
                    }, $custom_fields);
                }
            }

            $custom_fields = is_array($custom_fields) ? $custom_fields : [];

            $username = ApbdWps_GenerateBaseUsername($first_name, $last_name, '', $email);
            $username = ApbdWps_GenerateUniqueUsername($username);

            if (
                (1 > strlen($email)) ||
                (1 > strlen($first_name)) ||
                (1 > strlen($last_name)) ||
                (1 > strlen($password)) ||
                (1 > strlen($username))
            ) {
                $hasError = true;
            }

            if (!$hasError) {
                $userObj = get_user_by('email', $email);

                if (!$userObj) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_User($namespace, false);

                    $apiObj->SetPayload('grcToken', $grcToken);
                    $apiObj->SetPayload('id', null);
                    $apiObj->SetPayload('email', $email);
                    $apiObj->SetPayload('first_name', $first_name);
                    $apiObj->SetPayload('last_name', $last_name);
                    $apiObj->SetPayload('username', $username);
                    $apiObj->SetPayload('password', $password);
                    $apiObj->SetPayload('custom_fields', $custom_fields);
                    $apiObj->SetPayload('image', '');
                    $apiObj->SetPayload('role', '');

                    $resObj = $apiObj->create_client();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Registration successful.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('User already exists.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function reset_password()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $grcToken = ApbdWps_PostValue('grcToken', '');
            $username = sanitize_text_field(ApbdWps_PostValue('username', ''));

            if (1 > strlen($username)) {
                $hasError = true;
            }

            if (!$hasError) {
                $userObj = get_user_by('email', $username);

                if (!$userObj) {
                    $userObj = get_user_by('login', $username);
                }

                if ($userObj) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_User($namespace, false);

                    $apiObj->SetPayload('grcToken', $grcToken);
                    $apiObj->SetPayload('username', $username);

                    $resObj = $apiObj->reset_password();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Check your email for the confirmation link, then visit the login page.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Invalid username or email address.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function change_password()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $old_password = strval(ApbdWps_PostValue('old_password', ''));
            $new_password = strval(ApbdWps_PostValue('new_password', ''));

            if (
                (1 > strlen($old_password)) ||
                (1 > strlen($new_password))
            ) {
                $hasError = true;
            }

            if (!$hasError) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_User($namespace, false);

                $apiObj->SetPayload('oldPass', $old_password);
                $apiObj->SetPayload('newPass', $new_password);

                $resObj = $apiObj->change_pass();
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function data_search($term = '')
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, "", []);

        $term = ApbdWps_GetValue("term", '');
        $term = sanitize_text_field($term);

        $sort = ApbdWps_GetValue("sort");
        $page = ApbdWps_GetValue("page");
        $limit = ApbdWps_GetValue("limit");

        $orderBy = 'id';
        $order = 'ASC';

        if ($sort) {
            $sort = explode('-', $sort);

            if (isset($sort[0]) && !empty($sort[0])) {
                $orderBy = sanitize_key($sort[0]);
            }

            if (isset($sort[1]) && !empty($sort[1])) {
                $order = 'asc' === sanitize_key($sort[1]) ? 'ASC' : 'DESC';
            }
        }

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 10);
        $offset = ($limit * ($page - 1));

        $queryArgs = [
            'search' => '*' . esc_attr($term) . '*',
            'number' => $limit,
            'offset' => $offset,
            'orderby' => $orderBy,
            'order' => $order
        ];

        $sortArgs = apply_filters('support_genix_sort_args_for_ticket_user_query', ['orderby' => $orderBy, 'order' => $order]);
        $queryArgs = apply_filters('support_genix_query_args_for_ticket_user_fetch', array_merge($queryArgs, $sortArgs));

        $result = get_users($queryArgs);

        $data = [];

        if (is_array($result) && !empty($result)) {
            foreach ($result as $userObj) {
                $data[] = [
                    'id' => strval(absint($userObj->ID)),
                    'name' => $userObj->display_name,
                    'email' => $userObj->user_email,
                    'avatar' => get_avatar_url($userObj->ID),
                ];
            }
        }

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function CreateUser($email, $firstName, $lastName, $notify = 'N')
    {
        $userObj = get_user_by("email", $email);

        if (!$userObj) {
            $username = ApbdWps_GenerateBaseUsername($firstName, $lastName, '', $email);
            $username = ApbdWps_GenerateUniqueUsername($username);

            $password = wp_generate_password();

            $userId = wp_create_user($username, $password, $email);
            $userId = !is_wp_error($userId) ? $userId : 0;

            if (!empty($userId)) {
                $display_name = trim($firstName . " " . $lastName);

                wp_update_user([
                    "ID" => $userId,
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                    "display_name" => $display_name,
                ]);

                if ('Y' === $notify) {
                    wp_send_new_user_notifications($userId, 'user');
                }

                $userObj = get_user_by("id", $userId);
            }
        }

        return $userObj;
    }

    /* Multiple user roles */

    public function new_profile_fields()
    {
        if (!current_user_can('promote_users')) {
            return;
        }

        $creating = isset($_POST['createuser']);
        $user_roles = $creating && isset($_POST['role']) ? wp_unslash($_POST['role']) : '';

        if (!$user_roles) {
            $user_roles = get_option('default_role');
        }

        $roles = get_editable_roles();
        $user_roles = is_array($user_roles) ? $user_roles : array($user_roles);
        $opts_roles = array_map(function ($role) {
            return translate_user_role($role['name']);
        }, $roles);

        wp_nonce_field('sgenix_wp_user_roles', 'sgenix_wp_user_roles_nonce');
?>
        <table class="form-table">
            <tr>
                <th><?php $this->_e('User Roles'); ?></th>
                <td>
                    <div class="wp-tab-panel sgenix-wp-user-roles-tab-panel">
                        <ul>
                            <?php foreach ($opts_roles as $role_key => $role_name): ?>
                                <li>
                                    <label title="<?php echo esc_attr($role_key); ?>">
                                        <input type="checkbox" name="sgenix_wp_user_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $user_roles)); ?> />
                                        <?php echo esc_html($role_name); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
        <style type="text/css">
            .sgenix-wp-user-roles-tab-panel {
                min-height: unset !important;
                max-height: unset !important;
            }

            @media only screen and (min-width: 783px) {
                .sgenix-wp-user-roles-tab-panel {
                    max-width: calc(500px - 1.8em) !important;
                }
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('select#role').closest('tr').remove();
            });
        </script>
    <?php
    }

    public function edit_profile_fields($user)
    {
        if (
            !current_user_can('promote_users') ||
            !current_user_can('edit_user', $user->ID)
        ) {
            return;
        }

        $roles = get_editable_roles();
        $user_roles = array_intersect(array_values($user->roles), array_keys($roles));
        $opts_roles = array_map(function ($role) {
            return translate_user_role($role['name']);
        }, $roles);

        wp_nonce_field('sgenix_wp_user_roles', 'sgenix_wp_user_roles_nonce');
    ?>
        <h2><?php $this->_e('Roles'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php $this->_e('User Roles'); ?></th>
                <td>
                    <div class="wp-tab-panel sgenix-wp-user-roles-tab-panel">
                        <ul>
                            <?php foreach ($opts_roles as $role_key => $role_name): ?>
                                <li>
                                    <label title="<?php echo esc_attr($role_key); ?>">
                                        <input type="checkbox" name="sgenix_wp_user_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $user_roles)); ?> />
                                        <?php echo esc_html($role_name); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
        <style type="text/css">
            .user-role-wrap {
                display: none !important;
            }

            .sgenix-wp-user-roles-tab-panel {
                min-height: unset !important;
                max-height: unset !important;
            }

            @media only screen and (min-width: 783px) {
                .sgenix-wp-user-roles-tab-panel {
                    max-width: calc(500px - 1.8em) !important;
                }
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.user-role-wrap').remove();
            });
        </script>
<?php
    }

    public function user_role_register($user_id)
    {
        if (
            !current_user_can('promote_users') ||
            !isset($_POST['sgenix_wp_user_roles_nonce']) ||
            !wp_verify_nonce($_POST['sgenix_wp_user_roles_nonce'], 'sgenix_wp_user_roles')
        ) {
            return;
        }

        $user = new \WP_User($user_id);

        $roles = get_editable_roles();
        $key_roles = array_keys($roles);
        $old_roles = array_intersect(array_values($user->roles), $key_roles);

        if (!empty($_POST['sgenix_wp_user_roles'])) {
            $new_roles = array_map([$this, 'sanitize_user_role_key'], $_POST['sgenix_wp_user_roles']);

            foreach ($new_roles as $new_role) {
                if (
                    in_array($new_role, $key_roles) &&
                    !in_array($new_role, $old_roles)
                ) {
                    $user->add_role($new_role);
                }
            }

            foreach ($old_roles as $old_role) {
                if (
                    in_array($old_role, $key_roles) &&
                    !in_array($old_role, $new_roles)
                ) {
                    $user->remove_role($old_role);
                }
            }
        } else {
            foreach ((array) $user->roles as $old_role) {
                if (in_array($old_role, $key_roles)) {
                    $user->remove_role($old_role);
                }
            }
        }
    }

    public function profile_role_update($user_id, $old_user)
    {
        if (
            !current_user_can('promote_users') ||
            !current_user_can('edit_user', $user_id) ||
            !isset($_POST['sgenix_wp_user_roles_nonce']) ||
            !wp_verify_nonce($_POST['sgenix_wp_user_roles_nonce'], 'sgenix_wp_user_roles')
        ) {
            return;
        }

        $roles = get_editable_roles();
        $key_roles = array_keys($roles);
        $old_roles = array_intersect(array_values($old_user->roles), $key_roles);

        if (!empty($_POST['sgenix_wp_user_roles'])) {
            $new_roles = array_map([$this, 'sanitize_user_role_key'], $_POST['sgenix_wp_user_roles']);

            foreach ($new_roles as $new_role) {
                if (
                    in_array($new_role, $key_roles) &&
                    !in_array($new_role, $old_roles)
                ) {
                    $old_user->add_role($new_role);
                }
            }

            foreach ($old_roles as $old_role) {
                if (
                    in_array($old_role, $key_roles) &&
                    !in_array($old_role, $new_roles)
                ) {
                    $old_user->remove_role($old_role);
                }
            }
        } else {
            foreach ($old_roles as $old_role) {
                if (in_array($old_role, $key_roles)) {
                    $old_user->remove_role($old_role);
                }
            }
        }
    }

    public function sanitize_user_role_key($role)
    {
        $_role = strtolower($role);
        $_role = preg_replace('/[^a-z0-9_\-\s]/', '', $_role);
        $_role = str_replace(' ', '_', $_role);

        return $_role;
    }

    public function is_multi_role_enbled()
    {
        $is_enabled = Apbd_wps_settings::GetModuleOption('user_multi_role_field');
        $is_enabled = sanitize_text_field($is_enabled);
        $is_enabled = (('Y' === $is_enabled) ? true : false);

        return $is_enabled;
    }
}
