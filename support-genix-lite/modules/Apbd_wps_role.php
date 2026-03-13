<?php

/**
 * Role.
 */

defined('ABSPATH') || exit;

class Apbd_wps_role extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddAjaxAction("delete_items", [$this, "delete_items"]);
        $this->AddAjaxAction("data_agent_access", [$this, "data_agent_access"]);
        $this->AddAjaxAction("data_for_select", [$this, "data_for_select"]);
        $this->AddAjaxAction("editable_for_select", [$this, "editable_for_select"]);
        $this->AddAjaxAction("agent_for_select", [$this, "agent_for_select"]);
        $this->AddAjaxAction("access_lists", [$this, "access_lists"]);

        $this->AddPortalAjaxAction("agent_for_select", [$this, "agent_for_select"]);

        $this->AddPortalAjaxBothAction("data_agent_access", [$this, "data_agent_access"]);

        add_action('apbd-wps/action/role-added', [$this, "RoleAdded"]);
        add_action('apbd-wps/action/role-updated', [$this, "RoleUpdated"]);
        add_action('apbd-wps/action/role-deleted', [$this, "RoleRemoved"]);

        add_action('apbd-wps/action/add-role-access', [$this, 'AddRoleAccess'], 10, 2);
    }

    public function OnInit()
    {
        parent::OnInit();
        add_filter('editable_roles', [$this, 'EditableRoles']);
        add_filter('user_has_cap', [$this, 'UserHasCap'], 10, 4);
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $nobject = new Mapbd_wps_role();

            $capabilities = sanitize_text_field(ApbdWps_PostValue("capabilities"));
            $capabilities = array_unique(array_map('sanitize_key', explode(',', $capabilities)));

            if ($nobject->SetFromPostData(true)) {
                $nobject->is_editable('Y');
                if ($nobject->Save()) {
                    do_action('apbd-wps/action/add-role-access', $nobject, $capabilities);
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $dataError = ApbdWps_GetError();

                if ($dataError) {
                    $apiResponse->SetResponse(false, $dataError);
                } else {
                    $apiResponse->SetResponse(false, $this->__('Invalid data.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function edit($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $mainobj = new Mapbd_wps_role();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $uobject = new Mapbd_wps_role();

                $capabilities = sanitize_text_field(ApbdWps_PostValue("capabilities"));
                $capabilities = array_unique(array_map('sanitize_key', explode(',', $capabilities)));

                if ($uobject->SetFromPostData(false)) {
                    $uobject->SetWhereUpdate("id", $param_id);

                    if ($uobject->Update()) {
                        do_action('apbd-wps/action/role-updated', $param_id);
                        do_action('apbd-wps/action/add-role-access', $uobject, $capabilities);
                        $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                    } else {
                        $eobject = Mapbd_wps_role::FindBy('id', $param_id);

                        if ($eobject) {
                            $role_slug = $eobject->slug;
                            $ex_capabilities = Mapbd_wps_role_access::FindAllByKeyValue("role_slug", $role_slug, "resource_id", "role_access", ["role_access" => "Y"]);
                            $ex_capabilities = array_keys($ex_capabilities);

                            $caps_diff_1 = array_diff($ex_capabilities, $capabilities);
                            $caps_diff_2 = array_diff($capabilities, $ex_capabilities);

                            if (! empty($caps_diff_1) || ! empty($caps_diff_2)) {
                                do_action('apbd-wps/action/add-role-access', $eobject, $capabilities);
                                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                            } else {
                                $apiResponse->SetResponse(false, $this->__('Nothing to update.'));
                            }
                        } else {
                            $apiResponse->SetResponse(false, $this->__('Nothing to update.'));
                        }
                    }
                } else {
                    $dataError = ApbdWps_GetError();

                    if ($dataError) {
                        $apiResponse->SetResponse(false, $dataError);
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Invalid data.'));
                    }
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid item.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $mainobj = new Mapbd_wps_role();
        $accessobj = new Mapbd_wps_role_access();
        $total = absint($mainobj->CountALL());

        if (0 < $total) {
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
            $limitStart = ($limit * ($page - 1));

            $mainTable = $mainobj->GetTableName();
            $accessTable = $accessobj->GetTableName();

            $mainobj->GetSelectDB()->join($accessTable, "{$mainTable}.`slug` = {$accessTable}.`role_slug` AND {$accessTable}.`role_access` = 'Y'", 'LEFT');
            $mainobj->AddGroupBy("{$mainTable}.`slug`");

            $result = $mainobj->SelectAll("{$mainTable}.*, GROUP_CONCAT({$accessTable}.`resource_id`) AS capabilities", $orderBy, $order, $limit, $limitStart);

            $apiResponse->SetResponse(true, "", [
                'result' => $result,
                'total' => $total,
            ]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function data_agent_access()
    {
        $permissions = [];

        $apiResponse = new Apbd_Wps_APIResponse();

        if (Apbd_wps_settings::isAgentLoggedIn()) {
            $user = wp_get_current_user();

            if (current_user_can('manage_options') || is_super_admin($user->ID) || in_array('administrator', $user->roles)) {
                $permissions = ['all'];
            } else {
                $allRoles = Mapbd_wps_role::GetRoleListWithCapabilities();

                foreach ($user->roles as $role_slug) {
                    if (isset($allRoles[$role_slug])) {
                        $role = $allRoles[$role_slug];

                        if (isset($role->capabilities) && is_array($role->capabilities) && !empty($role->capabilities)) {
                            $permissions = array_keys($role->capabilities);
                        }

                        break;
                    }
                }
            }
        } else {
            $permissions = ['all'];
        }

        if (Apbd_wps_knowledge_base::UserCanWriteDocs()) {
            $permissions[] = 'write_docs';
        }

        if (Apbd_wps_knowledge_base::UserCanAccessAnalytics()) {
            $permissions[] = 'access_analytics';
        }

        if (Apbd_wps_knowledge_base::UserCanAccessConfig()) {
            $permissions[] = 'access_config';
        }

        $apiResponse->SetResponse(true, "", $permissions);

        echo wp_json_encode($apiResponse);
    }

    public function delete_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (!empty($param_id)) {
            $mainobj = new Mapbd_wps_role();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                if (Mapbd_wps_role::DeleteBySlug($mainobj->slug)) {
                    do_action('apbd-wps/action/role-deleted', $mainobj);
                    $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid item.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function delete_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_role();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        if (Mapbd_wps_role::DeleteBySlug($mainobj->slug)) {
                            do_action('apbd-wps/action/role-deleted', $mainobj);
                        }
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $mainobj = new Mapbd_wps_role();
        $total = absint($mainobj->CountALL());

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Role') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Roles'),
            ];
        }

        if (0 < $total) {
            $records = $mainobj->SelectAllWithKeyValue("id", "name", 'id', 'ASC', '', '', '', '', ['status' => 'A']);

            if ($records) {
                foreach ($records as $id => $title) {
                    $id = absint($id);

                    if ($id !== $except_id) {
                        $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                        $result[] = [
                            $valkey => strval($id),
                            'label' => $title,
                        ];
                    }
                }
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => $total,
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function editable_for_select($except_key = '', $select = false, $select_all = false, $with_key = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_key = ApbdWps_GetValue("except_key", "");
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_key = ApbdWps_GetValue("with_key", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_key = sanitize_text_field($except_key);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_key = rest_sanitize_boolean($with_key);
        $no_value = rest_sanitize_boolean($no_value);

        $roles = get_editable_roles();
        $roles = array_reverse($roles);

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Role') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Roles'),
            ];
        }

        foreach ($roles as $key => $role) {
            $key = strval($key);
            $title = $role['name'];

            if ($key !== $except_key) {
                $title .= $with_key ? ' ' . $this->___('(Key: %d)', $key) : '';

                $result[] = [
                    $valkey => $key,
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function agent_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $except_id = ApbdWps_GetValue("except_id", 0);
        $select = ApbdWps_GetValue("select", false);
        $select_all = ApbdWps_GetValue("select_all", false);
        $with_id = ApbdWps_GetValue("with_id", false);
        $no_value = ApbdWps_GetValue("no_value", false);

        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $agent_roles = Mapbd_wps_role::getAgentRoles();
        $agents = get_users(['role__in' => $agent_roles]);

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Agent') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Agents'),
            ];
        }

        foreach ($agents as $agent) {
            $id = $agent->ID;
            $title = $agent->display_name;

            $id = absint($id);

            if ($id !== $except_id) {
                $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';

                $result[] = [
                    $valkey => strval($id),
                    'label' => $title,
                ];
            }
        }

        $apiResponse->SetResponse(true, "", [
            'result' => $result,
            'total' => count($result),
        ]);

        echo wp_json_encode($apiResponse);
    }

    public function access_lists()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $result = [
            'all' => Mapbd_wps_role_access::GetAccessList(),
            'agent' => Mapbd_wps_role_access::GetAgentAccessList(),
            'manager' => Mapbd_wps_role_access::GetManagerAccessList(),
        ];

        $apiResponse->SetResponse(true, "", $result);

        echo wp_json_encode($apiResponse);
    }

    public function RoleAdded($role)
    {
        if ($role instanceof Mapbd_wps_role) {
            $existingRoles = wp_roles()->get_names();
            if ($role->is_editable == 'Y' && !isset($existingRoles[$role->slug])) {
                add_role($role->slug, $role->name, ['read' => true, 'level_0' => true]);
            }
        }
    }

    public function RoleUpdated($role_id)
    {
        $role = Mapbd_wps_role::FindBy("id", $role_id);
        if (! empty($role)) {
            $existingRoles = wp_roles()->get_names();
            if ($role->is_editable == 'Y') {
                if (isset($existingRoles[$role->slug])) {
                    remove_role($role->slug);
                }
                if ($role->status == "A") {
                    add_role($role->slug, $role->name, ['read' => true, 'level_0' => true]);
                }
            }
        }
    }

    public function RoleRemoved($role)
    {
        if ($role instanceof Mapbd_wps_role) {
            $existingRoles = wp_roles()->get_names();
            if ($role->is_editable == 'Y' && isset($existingRoles[$role->slug])) {
                remove_role($role->slug);
            }
        }
    }

    public function AddRoleAccess($roleObj, $capabilities)
    {
        $role_slug = isset($roleObj->slug) ? $roleObj->slug : '';

        if ($role_slug) {
            $access_list = Mapbd_wps_role_access::GetAccessList();
            $capabilities = is_array($capabilities) ? $capabilities : [];

            foreach ($access_list as $res_id) {
                Mapbd_wps_role_access::AddAccessIfNotExits($role_slug, $res_id);

                if (in_array($res_id, $capabilities, true)) {
                    Mapbd_wps_role_access::UpdateStatus($role_slug, $res_id, 'Y');
                } else {
                    Mapbd_wps_role_access::UpdateStatus($role_slug, $res_id, 'N');
                }
            }
        }
    }

    public function EditableRoles($all_roles)
    {
        $capabilities = [
            'level_0' => true,
            'read' => true
        ];

        $roles = Mapbd_wps_role::GetRoleListWithCapabilities();

        if (!empty($all_roles['subscriber']['capabilities'])) {
            $capabilities = $all_roles['subscriber']['capabilities'];
        }

        if (!empty($all_roles['administrator']['capabilities'])) {
            $access_list = Mapbd_wps_role_access::GetAccessList();

            foreach ($access_list as $res_id) {
                $all_roles['administrator']['capabilities'][$res_id] = true;
            }
        }

        foreach ($roles as $role) {
            if ($role->slug !== 'administrator') {
                if (empty($role->capabilities)) {
                    $role->capabilities = [];
                }

                $all_roles[$role->slug] = [
                    "name" => $role->name,
                    'capabilities' => array_merge($capabilities, $role->capabilities)
                ];
            }
        }

        return $all_roles;
    }

    public function UserHasCap($all_caps, $caps, $args, $user)
    {
        return Mapbd_wps_role::SetCapabilitiesByRole($all_caps, $user);
    }
}
