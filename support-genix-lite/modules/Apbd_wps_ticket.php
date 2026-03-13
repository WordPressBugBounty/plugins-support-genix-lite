<?php

/**
 * Ticket.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_ticket_migration_trait.php';
require_once dirname(__DIR__, 1) . '/traits/Apbd_wps_ticket_migration_create_trait.php';

class Apbd_wps_ticket extends ApbdWpsBaseModuleLite
{
    use Apbd_wps_ticket_migration_trait;
    use Apbd_wps_ticket_migration_create_trait;

    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("note_add", [$this, "note_add"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("field_edit", [$this, "field_edit"]);
        $this->AddAjaxAction("bulk_edit", [$this, "bulk_edit"]);
        $this->AddAjaxAction("privacy_edit", [$this, "privacy_edit"]);
        $this->AddAjaxAction("data_single", [$this, "data_single"]);
        $this->AddAjaxAction("trash_item", [$this, "trash_item"]);
        $this->AddAjaxAction("trash_items", [$this, "trash_items"]);
        $this->AddAjaxAction("restore_item", [$this, "restore_item"]);
        $this->AddAjaxAction("restore_items", [$this, "restore_items"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddAjaxAction("delete_items", [$this, "delete_items"]);
        $this->AddAjaxAction("status_for_select", [$this, "status_for_select"]);
        $this->AddAjaxAction("priority_for_select", [$this, "priority_for_select"]);
        $this->AddAjaxAction("download", [$this, "download"]);
        $this->AddAjaxAction("current_viewers", [$this, "current_viewers"]);
        $this->AddAjaxAction("remove_current_viewer", [$this, "remove_current_viewer"]);
        $this->AddAjaxAction("change_ticket_user", [$this, "change_ticket_user"]);
        $this->AddAjaxAction("edit_ticket_user_info", [$this, "edit_ticket_user_info"]);

        $this->AddPortalAjaxAction("add", [$this, "add_portal"]);
        $this->AddPortalAjaxAction("note_add", [$this, "note_add"]);
        $this->AddPortalAjaxAction("edit", [$this, "edit_portal"]);
        $this->AddPortalAjaxAction("field_edit", [$this, "field_edit"]);
        $this->AddPortalAjaxAction("bulk_edit", [$this, "bulk_edit"]);
        $this->AddPortalAjaxAction("privacy_edit", [$this, "privacy_edit"]);
        $this->AddPortalAjaxAction("data", [$this, "data_portal"]);
        $this->AddPortalAjaxAction("data_single", [$this, "data_single_portal"]);
        $this->AddPortalAjaxAction("trash_item", [$this, "trash_item"]);
        $this->AddPortalAjaxAction("trash_items", [$this, "trash_items"]);
        $this->AddPortalAjaxAction("restore_item", [$this, "restore_item"]);
        $this->AddPortalAjaxAction("restore_items", [$this, "restore_items"]);
        $this->AddPortalAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddPortalAjaxAction("delete_items", [$this, "delete_items"]);
        $this->AddPortalAjaxAction("status_for_select", [$this, "status_for_select"]);
        $this->AddPortalAjaxAction("priority_for_select", [$this, "priority_for_select"]);
        $this->AddPortalAjaxAction("download", [$this, "download"]);

        $this->initialize__migration();
        $this->initialize__migration_create();
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack && current_user_can('create-ticket')) {
            $cat_id = absint(ApbdWps_PostValue('cat_id', ''));
            $ticket_user = absint(ApbdWps_PostValue('ticket_user', ''));
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $ticket_body = ApbdWps_KsesHtml(ApbdWps_PostValue('ticket_body', ''));
            $is_public = sanitize_text_field(ApbdWps_PostValue('is_public', ''));
            $custom_fields = ApbdWps_PostValue('custom_fields', '');

            if (!empty($custom_fields)) {
                $custom_fields = json_decode(stripslashes($custom_fields), true);

                if (is_array($custom_fields)) {
                    $custom_fields = array_map(function ($value) {
                        return !is_bool($value) ? sanitize_text_field($value) : $value;
                    }, $custom_fields);
                }
            }

            $ticket_body = stripslashes($ticket_body);
            $check__ticket_body = sanitize_text_field($ticket_body);
            $is_public = 'Y' === $is_public ? 'Y' : 'N';

            $cat_id = strval($cat_id);
            $ticket_user = strval($ticket_user);
            $custom_fields = is_array($custom_fields) ? $custom_fields : [];

            if (
                (1 > strlen($title)) ||
                (1 > strlen($check__ticket_body))
            ) {
                $hasError = true;
            }

            $userObj = get_user_by("id", $ticket_user);

            if (empty($userObj)) {
                $hasError = true;
            }

            if (!$hasError && ! empty($custom_fields)) {
                $userEmail = (is_object($userObj) && ! empty($userObj->user_email)) ? $userObj->user_email : '';
                $isValidCustomField = apply_filters('apbd-wps/filter/ticket-custom-field-valid', true, $custom_fields, $userEmail);

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

            if (!$hasError) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $apiObj->SetPayload('cat_id', $cat_id);
                $apiObj->SetPayload('ticket_user', $ticket_user);
                $apiObj->SetPayload('title', $title);
                $apiObj->SetPayload('ticket_body', $ticket_body);
                $apiObj->SetPayload('is_public', $is_public);
                $apiObj->SetPayload('custom_fields', $custom_fields);

                $resObj = $apiObj->create_ticket();
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function add_portal()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $cat_id = absint(ApbdWps_PostValue('cat_id', ''));
            $ticket_user = absint(ApbdWps_PostValue('ticket_user', ''));
            $title = sanitize_text_field(ApbdWps_PostValue('title', ''));
            $ticket_body = ApbdWps_KsesHtml(ApbdWps_PostValue('ticket_body', ''));
            $is_public = sanitize_text_field(ApbdWps_PostValue('is_public', ''));
            $custom_fields = ApbdWps_PostValue('custom_fields', '');

            if (Apbd_wps_settings::isClientLoggedIn()) {
                $userObj = wp_get_current_user();
                $ticket_user = is_object($userObj) && isset($userObj->ID) ? absint($userObj->ID) : 0;
            } elseif (!current_user_can('create-ticket')) {
                $hasError = true;
            }

            if (!empty($custom_fields)) {
                $custom_fields = json_decode(stripslashes($custom_fields), true);

                if (is_array($custom_fields)) {
                    $custom_fields = array_map(function ($value) {
                        return !is_bool($value) ? sanitize_text_field($value) : $value;
                    }, $custom_fields);
                }
            }

            $ticket_body = stripslashes($ticket_body);
            $check__ticket_body = sanitize_text_field($ticket_body);
            $is_public = 'Y' === $is_public ? 'Y' : 'N';

            $cat_id = strval($cat_id);
            $ticket_user = strval($ticket_user);
            $custom_fields = is_array($custom_fields) ? $custom_fields : [];

            if (
                (1 > strlen($title)) ||
                (1 > strlen($check__ticket_body))
            ) {
                $hasError = true;
            }

            $userObj = get_user_by("id", $ticket_user);

            if (empty($userObj)) {
                $hasError = true;
            }

            if (!$hasError && ! empty($custom_fields)) {
                $userEmail = (is_object($userObj) && ! empty($userObj->user_email)) ? $userObj->user_email : '';
                $isValidCustomField = apply_filters('apbd-wps/filter/ticket-custom-field-valid', true, $custom_fields, $userEmail);

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

            if (!$hasError) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $apiObj->SetPayload('cat_id', $cat_id);
                $apiObj->SetPayload('ticket_user', $ticket_user);
                $apiObj->SetPayload('title', $title);
                $apiObj->SetPayload('ticket_body', $ticket_body);
                $apiObj->SetPayload('is_public', $is_public);
                $apiObj->SetPayload('custom_fields', $custom_fields);

                $resObj = $apiObj->create_ticket();
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function note_add($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        $hasError = false;

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $note_text = ApbdWps_KsesHtml(ApbdWps_PostValue('note_text', ''));

            $note_text = stripslashes($note_text);
            $check__note_text = sanitize_text_field($note_text);

            if (1 > strlen($check__note_text)) {
                $hasError = true;
            }

            if (!$hasError) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $mainobj = new Mapbd_wps_ticket();
                $mainobj->id($param_id);

                if ($mainobj->Select()) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                    $apiObj->SetPayload('ticket_id', $param_id);
                    $apiObj->SetPayload('note_text', $note_text);

                    $resObj = $apiObj->create_note();
                    $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                    if ($resStatus) {
                        $apiResponse->SetResponse(true, $this->__('Successfully added.'));
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

    public function edit($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $assigned_on = absint(ApbdWps_PostValue('assigned_on', ''));
            $cat_id = absint(ApbdWps_PostValue('cat_id', ''));
            $priority = sanitize_text_field(ApbdWps_PostValue('priority', ''));
            $email_notification = sanitize_text_field(ApbdWps_PostValue('email_notification', ''));
            $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

            if (
                !empty($assigned_on) ||
                !empty($cat_id) ||
                !empty($priority) ||
                !empty($email_notification) ||
                !empty($status)
            ) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $mainobj = new Mapbd_wps_ticket();
                $mainobj->id($param_id);

                if ($mainobj->Select()) {
                    if (!empty($assigned_on)) {
                        $apiObj->SetPayload('propName', 'assigned_on');
                        $apiObj->SetPayload('value', $assigned_on);
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }

                    if (!empty($cat_id)) {
                        $apiObj->SetPayload('propName', 'cat_id');
                        $apiObj->SetPayload('value', $cat_id);
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }

                    if (!empty($priority)) {
                        $priority = in_array($priority, array('N', 'M', 'H')) ? $priority : 'N';

                        $apiObj->SetPayload('propName', 'priority');
                        $apiObj->SetPayload('value', $priority);
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }

                    if (!empty($email_notification)) {
                        $apiObj->SetPayload('propName', 'email_notification');
                        $apiObj->SetPayload('value', 'Y' === $email_notification ? 'Y' : 'N');
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }

                    if (!empty($status)) {
                        $apiObj->SetPayload('propName', 'status');
                        $apiObj->SetPayload('value', $status);
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function edit_portal($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $assigned_on = absint(ApbdWps_PostValue('assigned_on', ''));
            $cat_id = absint(ApbdWps_PostValue('cat_id', ''));
            $priority = sanitize_text_field(ApbdWps_PostValue('priority', ''));
            $email_notification = sanitize_text_field(ApbdWps_PostValue('email_notification', ''));
            $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

            $isAgent = Apbd_wps_settings::isAgentLoggedIn();

            if (
                (
                    $isAgent &&
                    (
                        !empty($assigned_on) ||
                        !empty($cat_id) ||
                        !empty($priority) ||
                        !empty($email_notification) ||
                        !empty($status)
                    )
                ) ||
                (!empty($status))
            ) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $mainobj = new Mapbd_wps_ticket();
                $mainobj->id($param_id);

                if (!$isAgent) {
                    $user_id = get_current_user_id();
                    $mainobj->ticket_user($user_id);
                }

                if ($mainobj->Select()) {
                    if ($isAgent) {
                        if (!empty($assigned_on)) {
                            $apiObj->SetPayload('propName', 'assigned_on');
                            $apiObj->SetPayload('value', $assigned_on);
                            $apiObj->SetPayload('ticketId', $param_id);

                            $apiObj->update_ticket();
                        }

                        if (!empty($cat_id)) {
                            $apiObj->SetPayload('propName', 'cat_id');
                            $apiObj->SetPayload('value', $cat_id);
                            $apiObj->SetPayload('ticketId', $param_id);

                            $apiObj->update_ticket();
                        }

                        if (!empty($priority)) {
                            $priority = in_array($priority, array('N', 'M', 'H')) ? $priority : 'N';

                            $apiObj->SetPayload('propName', 'priority');
                            $apiObj->SetPayload('value', $priority);
                            $apiObj->SetPayload('ticketId', $param_id);

                            $apiObj->update_ticket();
                        }

                        if (!empty($email_notification)) {
                            $apiObj->SetPayload('propName', 'email_notification');
                            $apiObj->SetPayload('value', 'Y' === $email_notification ? 'Y' : 'N');
                            $apiObj->SetPayload('ticketId', $param_id);

                            $apiObj->update_ticket();
                        }
                    }

                    if (!empty($status)) {
                        $apiObj->SetPayload('propName', 'status');
                        $apiObj->SetPayload('value', $status);
                        $apiObj->SetPayload('ticketId', $param_id);

                        $apiObj->update_ticket();
                    }

                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function field_edit($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $fields = array_map(function ($value) {
                return !is_bool($value) ? sanitize_text_field($value) : $value;
            }, $_POST);

            if (!empty($fields)) {
                $isAgent = Apbd_wps_settings::isAgentLoggedIn();

                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $mainobj = new Mapbd_wps_ticket();
                $mainobj->id($param_id);

                if (!$isAgent) {
                    $user_id = get_current_user_id();
                    $mainobj->ticket_user($user_id);
                }

                if ($mainobj->Select()) {
                    if (isset($fields['wc_order_id'])) {
                        $wc_store_id = isset($fields['wc_store_id']) ? absint($fields['wc_store_id']) : 0;
                        $wc_order_id = absint($fields['wc_order_id']);

                        if (empty($wc_store_id) && !empty($wc_order_id)) {
                            $stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
                            $store = ((is_array($stores) && isset($stores[0])) ? $stores[0] : null);
                            $wc_store_id = ((is_object($store) && isset($store->id)) ? absint($store->id) : 0);
                        }

                        if (!empty($wc_store_id) && !empty($wc_order_id)) {
                            $apiObj->SetPayload('propName', 'wc_field_data');
                            $apiObj->SetPayload('value', $wc_store_id . ',' . $wc_order_id);
                            $apiObj->SetPayload('ticket_id', $param_id);

                            $apiObj->update_custom_field();
                        }

                        unset($fields['wc_store_id']);
                        unset($fields['wc_order_id']);
                    }

                    foreach ($fields as $name => $value) {
                        $apiObj->SetPayload('propName', $name);
                        $apiObj->SetPayload('value', $value);
                        $apiObj->SetPayload('ticket_id', $param_id);

                        $apiObj->update_custom_field();
                    }

                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function bulk_edit($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                $assigned_on = absint(ApbdWps_PostValue('assigned_on', ''));
                $cat_id = absint(ApbdWps_PostValue('cat_id', ''));
                $priority = sanitize_text_field(ApbdWps_PostValue('priority', ''));
                $email_notification = sanitize_text_field(ApbdWps_PostValue('email_notification', ''));
                $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

                if (
                    !empty($assigned_on) ||
                    !empty($cat_id) ||
                    !empty($priority) ||
                    !empty($email_notification) ||
                    !empty($status)
                ) {
                    $namespace = ApbdWps_SupportLite::getNamespaceStr();
                    $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                    foreach ($param_ids as $param_id) {
                        $mainobj = new Mapbd_wps_ticket();
                        $mainobj->id($param_id);

                        if ($mainobj->Select()) {
                            if (!empty($assigned_on)) {
                                $apiObj->SetPayload('propName', 'assigned_on');
                                $apiObj->SetPayload('value', $assigned_on);
                                $apiObj->SetPayload('ticketId', $param_id);

                                $apiObj->update_ticket();
                            }

                            if (!empty($cat_id)) {
                                $apiObj->SetPayload('propName', 'cat_id');
                                $apiObj->SetPayload('value', $cat_id);
                                $apiObj->SetPayload('ticketId', $param_id);

                                $apiObj->update_ticket();
                            }

                            if (!empty($priority)) {
                                $priority = in_array($priority, array('N', 'M', 'H')) ? $priority : 'N';

                                $apiObj->SetPayload('propName', 'priority');
                                $apiObj->SetPayload('value', $priority);
                                $apiObj->SetPayload('ticketId', $param_id);

                                $apiObj->update_ticket();
                            }

                            if (!empty($email_notification)) {
                                $apiObj->SetPayload('propName', 'email_notification');
                                $apiObj->SetPayload('value', 'Y' === $email_notification ? 'Y' : 'N');
                                $apiObj->SetPayload('ticketId', $param_id);

                                $apiObj->update_ticket();
                            }

                            if (!empty($status)) {
                                $apiObj->SetPayload('propName', 'status');
                                $apiObj->SetPayload('value', $status);
                                $apiObj->SetPayload('ticketId', $param_id);

                                $apiObj->update_ticket();
                            }
                        }
                    }

                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function privacy_edit($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $public = sanitize_text_field(ApbdWps_PostValue('public', ''));
            $public = 'Y' === $public ? 'Y' : 'N';

            $isAgent = Apbd_wps_settings::isAgentLoggedIn();

            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($param_id);

            if (!$isAgent) {
                $user_id = get_current_user_id();
                $mainobj->ticket_user($user_id);
            }

            if ($mainobj->Select()) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $apiObj->SetPayload('ticketId', $param_id);
                $apiObj->SetPayload('privacy', $public);

                $resObj = $apiObj->update_privacy();
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function data()
    {
        $tkt_type = ApbdWps_GetValue("tkt_type");
        $sub_type = ApbdWps_GetValue("sub_type");
        $category = ApbdWps_GetValue("category");
        $tag = ApbdWps_GetValue("tag");
        $agent = ApbdWps_GetValue("agent");
        $priority = ApbdWps_GetValue("priority");
        $search = ApbdWps_GetValue("search");
        $need_reply = ApbdWps_GetValue("need_reply");
        $sort = ApbdWps_GetValue("sort");
        $page = ApbdWps_GetValue("page");
        $limit = ApbdWps_GetValue("limit");

        $tkt_type = in_array($tkt_type, ['T', 'MY', 'UA', 'D'], true) ? $tkt_type : 'T';
        $sub_type = in_array($sub_type, ['A', 'I', 'C', 'ST'], true) ? $sub_type : ('D' !== $tkt_type ? 'A' : 'ST');

        $orderBy = 'last_reply_time';
        $order = 'desc';

        if ($sort) {
            $sort = explode('-', $sort);

            if (isset($sort[0]) && !empty($sort[0])) {
                $orderBy = sanitize_key($sort[0]);
            }

            if (isset($sort[1]) && !empty($sort[1])) {
                $order = 'asc' === sanitize_key($sort[1]) ? 'asc' : 'desc';
            }
        }

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 10);
        $filter_prop = '';
        $sort_by = [];
        $src_by = [];
        $group_by = [];

        if ('Y' === $need_reply) {
            $filter_prop = 'nr';
        }

        $sort_by[] = ['prop' => $orderBy, 'ord' => $order];

        if ($search) {
            $src_by[] = ['prop' => '*', 'val' => esc_attr($search), 'opr' => 'like'];
        }

        $namespace = ApbdWps_SupportLite::getNamespaceStr();
        $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

        $apiObj->SetPayload('data', $tkt_type);
        $apiObj->SetPayload('sub_type', $sub_type);
        $apiObj->SetPayload('category', $category);
        $apiObj->SetPayload('tag', $tag);
        $apiObj->SetPayload('agent', $agent);
        $apiObj->SetPayload('priority', $priority);
        $apiObj->SetPayload('limit', $limit);
        $apiObj->SetPayload('page', $page);
        $apiObj->SetPayload('filter_prop', $filter_prop);
        $apiObj->SetPayload('sort_by', $sort_by);
        $apiObj->SetPayload('src_by', $src_by);
        $apiObj->SetPayload('group_by', $group_by);
        $apiObj->SetPayload('force', false);

        $apiResponse = $apiObj->ticket_list();

        echo wp_json_encode($apiResponse);
    }

    public function data_portal()
    {
        $tkt_type = ApbdWps_GetValue("tkt_type");
        $sub_type = ApbdWps_GetValue("sub_type");
        $category = ApbdWps_GetValue("category");
        $tag = ApbdWps_GetValue("tag");
        $agent = ApbdWps_GetValue("agent");
        $priority = ApbdWps_GetValue("priority");
        $search = ApbdWps_GetValue("search");
        $need_reply = ApbdWps_GetValue("need_reply");
        $sort = ApbdWps_GetValue("sort");
        $page = ApbdWps_GetValue("page");
        $limit = ApbdWps_GetValue("limit");

        $isAgent = Apbd_wps_settings::isAgentLoggedIn();

        $tkt_type = in_array($tkt_type, ['T', 'MY', 'UA', 'D'], true) ? $tkt_type : 'T';
        $sub_type = in_array($sub_type, ['A', 'I', 'C', 'ST'], true) ? $sub_type : ('D' !== $tkt_type ? 'A' : 'ST');

        if (!$isAgent) {
            $tkt_type = 'T';
            $sub_type = in_array($sub_type, ['A', 'I', 'C', 'ST'], true) ? $sub_type : 'A';
            $tag = '';
            $agent = '';
            $priority = '';
            $need_reply = 'N';
        }

        $orderBy = 'last_reply_time';
        $order = 'desc';

        if ($sort) {
            $sort = explode('-', $sort);

            if (isset($sort[0]) && !empty($sort[0])) {
                $orderBy = sanitize_key($sort[0]);
            }

            if (isset($sort[1]) && !empty($sort[1])) {
                $order = 'asc' === sanitize_key($sort[1]) ? 'asc' : 'desc';
            }
        }

        $page = max(absint($page), 1);
        $limit = max(absint($limit), 10);
        $filter_prop = '';
        $sort_by = [];
        $src_by = [];
        $group_by = [];

        if ('Y' === $need_reply) {
            $filter_prop = 'nr';
        }

        $sort_by[] = ['prop' => $orderBy, 'ord' => $order];

        if ($search) {
            $src_by[] = ['prop' => '*', 'val' => esc_attr($search), 'opr' => 'like'];
        }

        $namespace = ApbdWps_SupportLite::getNamespaceStr();
        $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

        $apiObj->SetPayload('data', $tkt_type);
        $apiObj->SetPayload('sub_type', $sub_type);
        $apiObj->SetPayload('category', $category);
        $apiObj->SetPayload('tag', $tag);
        $apiObj->SetPayload('agent', $agent);
        $apiObj->SetPayload('priority', $priority);
        $apiObj->SetPayload('limit', $limit);
        $apiObj->SetPayload('page', $page);
        $apiObj->SetPayload('filter_prop', $filter_prop);
        $apiObj->SetPayload('sort_by', $sort_by);
        $apiObj->SetPayload('src_by', $src_by);
        $apiObj->SetPayload('group_by', $group_by);
        $apiObj->SetPayload('force', false);

        $apiResponse = $apiObj->ticket_list();

        echo wp_json_encode($apiResponse);
    }

    public function data_single($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (!empty($param_id)) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $apiResponse = $apiObj->ticket_details__dashboard(['ticketId' => $param_id]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function data_single_portal($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (!empty($param_id)) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $apiResponse = $apiObj->ticket_details__portal(['ticketId' => $param_id]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function trash_item($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $resObj = $apiObj->move_to_trash(['ticketId' => $param_id]);
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function trash_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_ticket();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $apiResponse = $apiObj->move_to_trash(['ticketId' => $param_id]);
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully moved to trash.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function restore_item($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $resObj = $apiObj->restore_ticket(['ticketId' => $param_id]);
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully restored.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function restore_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_ticket();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $apiResponse = $apiObj->restore_ticket(['ticketId' => $param_id]);
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully restored.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function delete_item($param_id = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $namespace = ApbdWps_SupportLite::getNamespaceStr();
            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $resObj = $apiObj->delete_ticket(['ticketId' => $param_id]);
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
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
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_ticket();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $apiResponse = $apiObj->delete_ticket(['ticketId' => $param_id]);
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function status_for_select($except_key = '', $select = false, $select_all = false, $with_key = false, $no_value = false)
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

        $settingsObj = Apbd_wps_settings::GetModuleInstance();

        $statuses = array(
            'N' => $this->__('New'),
            'C' => $this->__('Closed'),
            'P' => $this->__('In-progress'),
            'R' => $this->__('Re-open'),
            'A' => $this->__('Active'),
            'I' => $this->__('Inactive'),
            'D' => $this->__('Trashed'),
        );

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Status') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Statuses'),
            ];
        }

        foreach ($statuses as $key => $title) {
            $key = strval($key);

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

    public function priority_for_select($except_key = '', $select = false, $select_all = false, $with_key = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $isAgentUser = Apbd_wps_settings::isAgentLoggedIn();

        if ($isAgentUser) {
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

            $priorities = array(
                'N' => $this->__('Normal'),
                'M' => $this->__('Medium'),
                'H' => $this->__('High'),
            );

            $result = [];
            $valkey = $no_value ? 'key' : 'value';

            if ($select) {
                $result[] = [
                    $valkey => "",
                    'label' => '-- ' . $this->__('Select Priority') . ' --',
                ];
            }

            if ($select_all) {
                $result[] = [
                    $valkey => "0",
                    'label' => $this->__('All Priorities'),
                ];
            }

            foreach ($priorities as $key => $title) {
                $key = strval($key);

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
        } else {
            $apiResponse->SetResponse(true, "", [
                'result' => [],
                'total' => 0,
            ]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function download($param_id = 0)
    {
        $obj = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($obj->pluginFile));

        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $detailsObj = Mapbd_wps_ticket::getTicketDetails($param_id);

            if (! empty($detailsObj)) {
                $userObj = $detailsObj->user;
                $ticketObj = $detailsObj->ticket;
                $replies = $detailsObj->replies;

                $firstReplyObj = new stdClass();
                $firstReplyObj->replied_by = $ticketObj->ticket_user;
                $firstReplyObj->replied_by_type = 'U';
                $firstReplyObj->reply_text = $ticketObj->ticket_body;
                $firstReplyObj->reply_time = $ticketObj->opened_time;
                $firstReplyObj->is_private = 'N';
                $firstReplyObj->reply_user = $userObj;
                $firstReplyObj->attached_files = array();

                array_unshift($replies, $firstReplyObj);

                if (! empty($userObj) && ! empty($ticketObj)) {
                    $cssPath = $pluginPath . "/views/download_ticket/style.min.php";
                    $htmlPath = $pluginPath . "/views/download_ticket/main.php";

                    ob_start();
                    include $cssPath;
                    include $htmlPath;
                    $fileHtml = ob_get_clean();

                    $fileContent = base64_encode($fileHtml);

                    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
                    $domainr = str_replace('.', '-dot-', $domain);

                    $fileName = sprintf('%1$s-sg-ticket-%2$s-%3$s.pdf', current_time('U'), $param_id, $domainr);
                    $fileName = sanitize_file_name($fileName);

                    $data = array(
                        'fileName' => $fileName,
                        'fileContent' => $fileContent,
                    );

                    $apiResponse->SetResponse(true, $this->__('Ticket downloaded.'), $data);
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function current_viewers()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!Apbd_wps_settings::isAgentLoggedIn()) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $ticket_id = absint(ApbdWps_GetValue("id", 0));

        if (empty($ticket_id)) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $current_user_id = get_current_user_id();
        $transient_key = 'apbd_wps_ticket_viewers_' . $ticket_id;

        $entries = get_transient($transient_key);

        if (!is_array($entries)) {
            $entries = array();
        }

        // Clean stale entries older than 30 seconds
        $now = time();
        foreach ($entries as $agent_id => $timestamp) {
            if (($now - $timestamp) > 30) {
                unset($entries[$agent_id]);
            }
        }

        // Add current agent
        $entries[$current_user_id] = $now;

        // Save transient with 60-second TTL
        set_transient($transient_key, $entries, 60);

        // Build viewers list excluding current agent
        $viewers = array();
        foreach ($entries as $agent_id => $timestamp) {
            if ((int) $agent_id === $current_user_id) {
                continue;
            }

            $user = get_user_by('id', $agent_id);

            if (empty($user)) {
                continue;
            }

            $avatar = get_user_meta($agent_id, 'supportgenix_avatar', true);

            if (empty($avatar)) {
                $avatar = get_avatar_url($agent_id);
            }

            $viewers[] = array(
                'id'     => $agent_id,
                'name'   => $user->display_name,
                'avatar' => $avatar,
            );
        }

        $apiResponse->SetResponse(true, '', $viewers);
        echo wp_json_encode($apiResponse);
    }

    public function remove_current_viewer()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (!Apbd_wps_settings::isAgentLoggedIn()) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $ticket_id = absint(ApbdWps_GetValue("id", 0));

        if (empty($ticket_id)) {
            echo wp_json_encode($apiResponse);
            return;
        }

        $current_user_id = get_current_user_id();
        $transient_key = 'apbd_wps_ticket_viewers_' . $ticket_id;

        $entries = get_transient($transient_key);

        if (!is_array($entries)) {
            $apiResponse->SetResponse(true, '');
            echo wp_json_encode($apiResponse);
            return;
        }

        unset($entries[$current_user_id]);

        // Clean stale entries
        $now = time();
        foreach ($entries as $agent_id => $timestamp) {
            if (($now - $timestamp) > 30) {
                unset($entries[$agent_id]);
            }
        }

        if (empty($entries)) {
            delete_transient($transient_key);
        } else {
            set_transient($transient_key, $entries, 60);
        }

        $apiResponse->SetResponse(true, '');
        echo wp_json_encode($apiResponse);
    }

    public function change_ticket_user()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $ticket_id = absint(ApbdWps_GetValue('id', ''));

        if (ApbdWps_IsPostBack && !empty($ticket_id) && current_user_can('change-ticket-user')) {
            $new_user_id = absint(ApbdWps_PostValue('ticket_user', ''));

            if (empty($new_user_id)) {
                $apiResponse->SetResponse(false, $this->__('Please select a user.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($ticket_id);

            if (!$mainobj->Select()) {
                $apiResponse->SetResponse(false, $this->__('Ticket not found.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $old_user_id = absint($mainobj->ticket_user);

            if ($old_user_id === $new_user_id) {
                $apiResponse->SetResponse(false, $this->__('This ticket already belongs to the selected user.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $new_user = get_user_by('id', $new_user_id);

            if (empty($new_user)) {
                $apiResponse->SetResponse(false, $this->__('User not found.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            if (Mapbd_wps_ticket::ChangeTicketUser($ticket_id, $new_user_id, $old_user_id)) {
                do_action('apbd-wps/action/ticket-user-changed', $mainobj, $old_user_id);
                do_action('apbd-wps/action/data-change');
                $apiResponse->SetResponse(true, $this->__('Ticket user changed successfully.'));
            } else {
                $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function edit_ticket_user_info()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $ticket_id = absint(ApbdWps_GetValue('id', ''));

        if (ApbdWps_IsPostBack && !empty($ticket_id) && current_user_can('create-ticket-user')) {
            $first_name = sanitize_text_field(ApbdWps_PostValue('first_name', ''));
            $last_name = sanitize_text_field(ApbdWps_PostValue('last_name', ''));

            if (empty($first_name)) {
                $apiResponse->SetResponse(false, $this->__('First name is required.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            if (empty($last_name)) {
                $apiResponse->SetResponse(false, $this->__('Last name is required.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $mainobj = new Mapbd_wps_ticket();
            $mainobj->id($ticket_id);

            if (!$mainobj->Select()) {
                $apiResponse->SetResponse(false, $this->__('Ticket not found.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $ticket_user_id = absint($mainobj->ticket_user);
            $user = get_user_by('id', $ticket_user_id);

            if (empty($user)) {
                $apiResponse->SetResponse(false, $this->__('User not found.'));
                echo wp_json_encode($apiResponse);
                return;
            }

            $display_name = trim($first_name . ' ' . $last_name);

            $update_data = array(
                'ID' => $ticket_user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $display_name,
            );

            $result = wp_update_user($update_data);

            if (is_wp_error($result)) {
                $apiResponse->SetResponse(false, $result->get_error_message());
            } else {
                $custom_fields_json = ApbdWps_PostValue('custom_fields', '');
                $custom_fields = !empty($custom_fields_json) ? json_decode(stripslashes($custom_fields_json), true) : [];

                if (!empty($custom_fields) && is_array($custom_fields)) {
                    $custom_fields = array_map(function ($value) {
                        return !is_bool($value) ? sanitize_text_field($value) : $value;
                    }, $custom_fields);

                    $userObj = new stdClass();
                    $userObj->id = $ticket_user_id;
                    do_action('apbd-wps/action/user-updated', $userObj, $custom_fields);
                }

                do_action('apbd-wps/action/data-change');
                $apiResponse->SetResponse(true, $this->__('User info updated successfully.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }
}
