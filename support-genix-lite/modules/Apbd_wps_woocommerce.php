<?php

/**
 * WooCommerce.
 */

defined('ABSPATH') || exit;

class Apbd_wps_woocommerce extends ApbdWpsBaseModuleLite
{
    function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddAjaxAction("delete_items", [$this, "delete_items"]);
        $this->AddAjaxAction("activate_items", [$this, "activate_items"]);
        $this->AddAjaxAction("deactivate_items", [$this, "deactivate_items"]);
        $this->AddAjaxAction("settings_data", [$this, "settings_data"]);
        $this->AddAjaxAction('order_change', [$this, 'order_change']);
        $this->AddAjaxAction('reset_order', [$this, 'reset_order']);
    }

    public function OnInit()
    {
        parent::OnInit();
        add_filter('apbd-wps/filter/before-custom-get', [$this, 'set_custom_field']);
        add_filter('apbd-wps/filter/custom-field-metadata', [$this, 'custom_field_metadata']);
        add_filter('apbd-wps/filter/ticket-details-custom-properties', [$this, 'set_custom_field_properties']);

        add_filter('apbd-wps/filter/ticket-custom-field-valid', [$this, 'validate_post_data'], 10, 3);
        add_filter('apbd-wps/filter/incoming-webhook-custom-field-valid', [$this, 'validate_incoming_webhook_custom_field'], 10, 4);
        add_filter('apbd-wps/filter/ht-contact-form-custom-field-valid', [$this, 'valid_ht_contact_form_custom_field'], 10, 4);

        add_filter('apbd-wps/filter/ticket-order-info', [$this, 'ticket_order_info'], 10, 2);
        add_filter('apbd-wps/filter/ticket-order-statuses', [$this, 'ticket_order_statuses']);

        add_action('apbd-wps/action/ticket-created', [$this, 'save_ticket_meta'], 10, 2);
        add_action('apbd-wps/action/ticket-custom-field-update', [$this, 'update_ticket_meta'], 10, 3);

        // Lite: Auto-create ticket hooks removed (woocommerce_checkout_order_created, woocommerce_store_api_checkout_order_processed).

        add_action('woocommerce_account_menu_items', array($this, 'wc_account_menu_items'));
        add_action('woocommerce_get_endpoint_url', array($this, 'wc_get_endpoint_url'), 10, 2);
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $nobject = new Mapbd_wps_woocommerce();

            if ($nobject->SetFromPostData(true)) {
                if ($nobject->Save()) {
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
            $mainobj = new Mapbd_wps_woocommerce();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $uobject = new Mapbd_wps_woocommerce();

                if ($uobject->SetFromPostData(false)) {
                    $uobject->SetWhereUpdate("id", $param_id);

                    if ($uobject->Update()) {
                        $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Nothing to update.'));
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
        $mainobj = new Mapbd_wps_woocommerce();
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

            $result = $mainobj->SelectAll("", $orderBy, $order, $limit, $limitStart);

            if ($result) {
                foreach ($result as &$item) {
                    if ('Y' === $item->same_site) {
                        $item->store_url = home_url();
                    } else {
                        $item->store_url = $item->store_url ? ApbdWps_UrlToDomain($item->store_url) : '';
                    }

                    $item->api_consumer_key = ApbdWps_SecretFieldValue($item->api_consumer_key);
                    $item->api_consumer_secret = ApbdWps_SecretFieldValue($item->api_consumer_secret);

                    $disallow_opts = [];

                    if ('Y' === $item->prevent_for_c) {
                        $disallow_opts[] = 'prevent_for_c';
                    }

                    if ('Y' === $item->prevent_for_r) {
                        $disallow_opts[] = 'prevent_for_r';
                    }

                    $verify_opts = [];

                    if ('Y' === $item->verify_email) {
                        $verify_opts[] = 'verify_email';
                    }

                    if ('Y' === $item->verify_ssl) {
                        $verify_opts[] = 'verify_ssl';
                    }

                    $item->disallow_opts = $disallow_opts;
                    $item->verify_opts = $verify_opts;
                }
            }

            $apiResponse->SetResponse(true, "", [
                'result' => $result,
                'total' => $total,
            ]);
        }

        echo wp_json_encode($apiResponse);
    }

    public function delete_item($param_id = 0)
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");

        if (!empty($param_id)) {
            $mainobj = new Mapbd_wps_woocommerce();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $dobject = new Mapbd_wps_woocommerce();
                $dobject->SetWhereUpdate("id", $param_id);

                if ($dobject->Delete()) {
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
                    $mainobj = new Mapbd_wps_woocommerce();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $dobject = new Mapbd_wps_woocommerce();
                        $dobject->SetWhereUpdate("id", $param_id);
                        $dobject->Delete();
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully deleted.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function activate_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_woocommerce();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_woocommerce();
                        $uobject->status('A');
                        $uobject->SetWhereUpdate("id", $param_id);
                        $uobject->Update();
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function deactivate_items($param_ids = "")
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_ids = ApbdWps_GetValue("ids");

        if (!empty($param_ids)) {
            $param_ids = explode(',', $param_ids);

            if (!empty($param_ids)) {
                foreach ($param_ids as $param_id) {
                    $mainobj = new Mapbd_wps_woocommerce();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_woocommerce();
                        $uobject->status('I');
                        $uobject->SetWhereUpdate("id", $param_id);
                        $uobject->Update();
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function order_change($param_id = 0, $param_type = '')
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = ApbdWps_GetValue("id");
        $param_type = ApbdWps_GetValue('typ');

        if (!empty($param_id) && !empty($param_type) && in_array($param_type, ['u', 'd'], true)) {
            $mainobj = new Mapbd_wps_woocommerce();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                if (Mapbd_wps_woocommerce::changeOrder($param_id, $param_type)) {
                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function reset_order()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, $this->__('Successfully reset.'));

        Mapbd_wps_woocommerce::ResetOrder();

        echo wp_json_encode($apiResponse);
    }

    public function settings_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $collect_order_info = $this->GetOption('collect_order_info', 'N');
        $is_required = $this->GetOption('is_required', 'Y');
        $menu_in_my_account = $this->GetOption('menu_in_wc_my_account_page', 'Y');
        $menu_title_in_my_account = $this->GetOption('menu_label_in_wc_my_account_page', $this->__('Get Support'));

        // Collect order info.
        $collect_order_info = ('Y' === $collect_order_info) ? true : false;

        // Form options.
        $form_opts = [];

        if ('Y' === $is_required) {
            $form_opts[] = 'is_required';
        }

        // My account menu.
        $menu_in_my_account = ('Y' === $menu_in_my_account) ? true : false;

        $data = [
            'collect_order_info' => $collect_order_info,
            'form_opts' => $form_opts,
            'menu_in_wc_my_account_page' => $menu_in_my_account,
            'menu_label_in_wc_my_account_page' => $menu_title_in_my_account,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallback()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $collect_order_info = sanitize_text_field(ApbdWps_PostValue('collect_order_info', ''));
            $form_opts = sanitize_text_field(ApbdWps_PostValue('form_opts', ''));
            $menu_in_wc_my_account_page = sanitize_text_field(ApbdWps_PostValue('menu_in_wc_my_account_page', ''));
            $menu_label_in_wc_my_account_page = sanitize_text_field(ApbdWps_PostValue('menu_label_in_wc_my_account_page', ''));

            $collect_order_info = 'Y' === $collect_order_info ? 'Y' : 'N';
            $menu_in_wc_my_account_page = 'Y' === $menu_in_wc_my_account_page ? 'Y' : 'N';

            // Collect order info.
            $this->AddIntoOption('collect_order_info', $collect_order_info);

            if ('Y' === $collect_order_info) {
                // Form options.
                $form_opts = explode(',', $form_opts);
                $all__form_opts = ['is_required'];

                foreach ($all__form_opts as $opt) {
                    if (in_array($opt, $form_opts)) {
                        $this->AddIntoOption($opt, 'Y');
                    } else {
                        $this->AddIntoOption($opt, 'N');
                    }
                }

            }

            // Others.
            $this->AddIntoOption('menu_in_wc_my_account_page', $menu_in_wc_my_account_page);

            if ('Y' === $menu_in_wc_my_account_page) {
                $this->AddIntoOption('menu_label_in_wc_my_account_page', $menu_label_in_wc_my_account_page);
            } else {
                $this->AddIntoOption('menu_label_in_wc_my_account_page', $this->GetOption('menu_label_in_wc_my_account_page', $this->__('Get Support')));
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

    public function is_woocommerce_active()
    {
        return (is_plugin_active('woocommerce/woocommerce.php') ? true : false);
    }

    public function set_custom_field($custom_fields)
    {
        if ('Y' !== $this->GetOption('collect_order_info', 'N')) {
            return $custom_fields;
        }

        $integrations = Mapbd_wps_woocommerce::FindAllBy('status', 'A');

        if (! is_array($integrations) || empty($integrations)) {
            return $custom_fields;
        }

        $stores = array();

        foreach ($integrations as $integration) {
            if (! is_object($integration) || empty($integration)) {
                continue;
            }

            $id = (isset($integration->id) ? absint($integration->id) : 0);
            $same_site = (isset($integration->same_site) ? sanitize_text_field($integration->same_site) : '');
            $store_title = (isset($integration->store_title) ? sanitize_text_field($integration->store_title) : '');
            $store_url = (isset($integration->store_url) ? esc_url($integration->store_url) : '');

            // Lite edition does not support external WooCommerce sites.
            if ('Y' !== $same_site) {
                continue;
            }

            if ('Y' === $same_site) {
                if (! $this->is_woocommerce_active()) {
                    continue;
                }

                $store_title = (! empty($store_title) ? $store_title : wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));
                $store_url = (! empty($store_url) ? $store_url : home_url());
            }

            $store_url = $this->CleanURL($store_url);
            $store_url = untrailingslashit($store_url);

            $store_title = (! empty($store_title) ? $store_title : $store_url);

            $stores[] = array(
                'id' =>  $id,
                'title' => $store_title,
            );
        }

        $is_required = $this->GetOption('is_required', 'Y');

        if (! empty($stores)) {
            $wc_store_id_fld = new stdClass();
            $wc_store_id_fld->id = 'wc_store_id';
            $wc_store_id_fld->is_required = $is_required;
            $wc_store_id_fld->field_label = $this->__('Store');
            $wc_store_id_fld->input_name = 'wc_store_id';
            $wc_store_id_fld->v_rules = ltrim((('Y' === $is_required) ? 'required' : '') . '|isValid:custom,wc_store_id,1', '|');
            $wc_store_id_fld->help_text = $this->__('Store');
            $wc_store_id_fld->choose_category = ['0'];
            $wc_store_id_fld->fld_option = $stores;
            $wc_store_id_fld->fld_order = '101';
            $wc_store_id_fld->where_to_create = 'T';
            $wc_store_id_fld->field_type = 'W';
            $wc_store_id_fld->status = 'A';
            $wc_store_id_fld->is_half_field = 'N';
            $wc_store_id_fld->categories = [];

            $wc_order_id_fld = new stdClass();
            $wc_order_id_fld->id = 'wc_order_id';
            $wc_order_id_fld->is_required = $is_required;
            $wc_order_id_fld->field_label = $this->__('Order ID');
            $wc_order_id_fld->input_name = 'wc_order_id';
            $wc_order_id_fld->v_rules = ltrim((('Y' === $is_required) ? 'required' : '') . '|isValid:custom,wc_order_id,1', '|');
            $wc_order_id_fld->help_text = $this->__('Order ID');
            $wc_order_id_fld->choose_category = ['0'];
            $wc_order_id_fld->fld_option = '';
            $wc_order_id_fld->fld_order = '102';
            $wc_order_id_fld->where_to_create = 'T';
            $wc_order_id_fld->field_type = 'T';
            $wc_order_id_fld->status = 'A';
            $wc_order_id_fld->is_half_field = 'N';
            $wc_order_id_fld->categories = [];

            $is_single_store = (count($stores) === 1);

            if (! $is_single_store) {
                $custom_fields->ticket_form[] = $wc_store_id_fld;
            }
            $custom_fields->ticket_form[] = $wc_order_id_fld;
        }

        return $custom_fields;
    }

    public function custom_field_metadata($metadata)
    {
        if (is_array($metadata) && ! empty($metadata) && isset($metadata['W1'])) {
            $wc_data = sanitize_text_field($metadata['W1']);
            $wc_data = (is_serialized($wc_data) ? unserialize($wc_data) : array());

            if (is_array($wc_data) && ! empty($wc_data)) {
                $metadata['wc_store_id'] = (isset($wc_data['store_id']) ? sanitize_text_field($wc_data['store_id']) : '');
                $metadata['wc_order_id'] = (isset($wc_data['order_id']) ? sanitize_text_field($wc_data['order_id']) : '');
            }
        }

        return $metadata;
    }

    public function set_custom_field_properties($custom_fields)
    {
        $isClient = Apbd_wps_settings::isClientLoggedIn();
        if ($isClient) {
            foreach ($custom_fields as &$custom_field) {
                if (is_object($custom_field) && ! empty($custom_field)) {
                    $field_id = (isset($custom_field->id) ? $custom_field->id : '');
                    $field_value = (isset($custom_field->field_value) ? $custom_field->field_value : '');

                    if ((('wc_store_id' === $field_id) || ('wc_order_id' === $field_id)) && ! empty($field_value)) {
                        $custom_field->is_editable = false;
                    }
                }
            }
        } elseif (! current_user_can('edit-wc-order-source')) {
            foreach ($custom_fields as &$custom_field) {
                if (is_object($custom_field) && ! empty($custom_field)) {
                    $field_id = (isset($custom_field->id) ? $custom_field->id : '');

                    if ((('wc_store_id' === $field_id) || ('wc_order_id' === $field_id))) {
                        $custom_field->is_editable = false;
                    }
                }
            }
        }

        return $custom_fields;
    }

    public function validate_post_data($validate, $custom_fields, $user_email = '')
    {
        if ('Y' !== $this->GetOption('collect_order_info', 'N')) {
            return $validate;
        }

        $custom_fields = (is_array($custom_fields) ? $custom_fields : array());

        $store_id = (isset($custom_fields['wc_store_id']) ? sanitize_text_field($custom_fields['wc_store_id']) : '');
        $order_id = (isset($custom_fields['wc_order_id']) ? sanitize_text_field($custom_fields['wc_order_id']) : '');

        if (empty($store_id) && ! empty($order_id)) {
            $stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
            $store = ((is_array($stores) && isset($stores[0])) ? $stores[0] : null);
            $store_id = ((is_object($store) && isset($store->id)) ? sanitize_text_field($store->id) : '');
        }

        if (empty($store_id) || empty($order_id)) {
            if ($this->is_required_in_tckt_form()) {
                $this->AddError($this->__('Store & Order ID is required'));
                $validate = false;
            }
        } else {
            $response = $this->validate_order_id($store_id, $order_id, $user_email);

            if (is_array($response) && ! empty($response)) {
                $status = (isset($response['status']) ? rest_sanitize_boolean($response['status']) : false);
                $msg = (isset($response['msg']) ? sanitize_text_field($response['msg']) : $this->__('Invalid Store & Order ID'));

                if (false === $status) {
                    $this->AddError($msg);
                    $validate = false;
                }
            }
        }

        return $validate;
    }

    public function validate_incoming_webhook_custom_field($validate_data, $custom_fields, $user_email = '', $user_exists = false)
    {
        if ('Y' !== $this->GetOption('collect_order_info', 'N')) {
            return $validate_data;
        }

        if (empty($validate_data)) {
            $custom_fields = (is_array($custom_fields) ? $custom_fields : array());

            $store_id = (isset($custom_fields['wc_store_id']) ? sanitize_text_field($custom_fields['wc_store_id']) : '');
            $order_id = (isset($custom_fields['wc_order_id']) ? sanitize_text_field($custom_fields['wc_order_id']) : '');

            if (empty($order_id)) {
                if ($this->is_required_in_tckt_form()) {
                    $validate_data = array(
                        'status' => false,
                        'msg' => $this->__('Store & Order ID is required'),
                    );
                }
            } else {
                if (empty($store_id)) {
                    $stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
                    $store = ((is_array($stores) && isset($stores[0])) ? $stores[0] : null);
                    $store_id = ((is_object($store) && isset($store->id)) ? sanitize_text_field($store->id) : '');
                }

                $response = $this->validate_order_id($store_id, $order_id, $user_email);

                if (is_array($response) && ! empty($response)) {
                    $status = (isset($response['status']) ? rest_sanitize_boolean($response['status']) : false);

                    if (false === $status) {
                        $validate_data = $response;
                    }
                }
            }
        }

        return $validate_data;
    }

    public function valid_ht_contact_form_custom_field($validate_data, $custom_fields, $user_email = '', $user_exists = false)
    {
        if ('Y' !== $this->GetOption('collect_order_info', 'N')) {
            return $validate_data;
        }

        if (empty($validate_data)) {
            $custom_fields = (is_array($custom_fields) ? $custom_fields : array());

            $store_id = (isset($custom_fields['wc_store_id']) ? sanitize_text_field($custom_fields['wc_store_id']) : '');
            $order_id = (isset($custom_fields['wc_order_id']) ? sanitize_text_field($custom_fields['wc_order_id']) : '');

            if (empty($order_id)) {
                if ($this->is_required_in_tckt_form()) {
                    $validate_data = array(
                        'status' => false,
                        'msg' => $this->__('Store & Order ID is required'),
                    );
                }
            } else {
                if (empty($store_id)) {
                    $stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
                    $store = ((is_array($stores) && isset($stores[0])) ? $stores[0] : null);
                    $store_id = ((is_object($store) && isset($store->id)) ? sanitize_text_field($store->id) : '');
                }

                $response = $this->validate_order_id($store_id, $order_id, $user_email);

                if (is_array($response) && ! empty($response)) {
                    $status = (isset($response['status']) ? rest_sanitize_boolean($response['status']) : false);

                    if (false === $status) {
                        $validate_data = $response;
                    }
                }
            }
        }

        return $validate_data;
    }

    public function ticket_order_info($order_info, $ticket_id)
    {
        $ticket_id = absint($ticket_id);

        if (! empty($ticket_id)) {
            $ticket_meta = Mapbd_wps_support_meta::getTicketMeta($ticket_id);

            if (is_array($ticket_meta) && ! empty($ticket_meta)) {
                $wc_data = (isset($ticket_meta['W1']) ? sanitize_text_field($ticket_meta['W1']) : '');
                $wc_data = (is_serialized($wc_data) ? unserialize($wc_data) : array());

                if (is_array($wc_data) && ! empty($wc_data)) {
                    $store_id = (isset($wc_data['store_id']) ? sanitize_text_field($wc_data['store_id']) : 0);
                    $order_id = (isset($wc_data['order_id']) ? sanitize_text_field($wc_data['order_id']) : '');

                    if (! empty($store_id) && ! empty($order_id)) {
                        $order_info = $this->get_order_info($store_id, $order_id);
                    }
                }
            }
        }

        return $order_info;
    }

    public function ticket_order_statuses($statuses)
    {
        $statuses = array(
            'wc-pending'    => $this->__('Pending payment'),
            'wc-processing' => $this->__('Processing'),
            'wc-on-hold'    => $this->__('On hold'),
            'wc-completed'  => $this->__('Completed'),
            'wc-cancelled'  => $this->__('Cancelled'),
            'wc-refunded'   => $this->__('Refunded'),
            'wc-failed'     => $this->__('Failed'),
        );

        return $statuses;
    }

    public function save_ticket_meta($ticket, $custom_fields)
    {
        if (! is_object($ticket) || empty($ticket) || ! is_array($custom_fields) || empty($custom_fields)) {
            return;
        }

        if ('Y' !== $this->GetOption('collect_order_info', 'N')) {
            return;
        }

        $ticket_id = (isset($ticket->id) ? absint($ticket->id) : 0);

        $store_id = (isset($custom_fields['wc_store_id']) ? sanitize_text_field($custom_fields['wc_store_id']) : '');
        $order_id = (isset($custom_fields['wc_order_id']) ? sanitize_text_field($custom_fields['wc_order_id']) : '');

        if (empty($store_id) && ! empty($order_id)) {
            $stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
            $store = ((is_array($stores) && isset($stores[0])) ? $stores[0] : null);
            $store_id = ((is_object($store) && isset($store->id)) ? sanitize_text_field($store->id) : '');
        }

        if (! empty($ticket_id) && ! empty($store_id) && ! empty($order_id)) {
            $wc_data = serialize(array('store_id' => $store_id, 'order_id' => $order_id));

            $new_obj = new Mapbd_wps_support_meta();
            $new_obj->item_id($ticket_id);
            $new_obj->item_type('T');
            $new_obj->meta_key('1');
            $new_obj->meta_type('W');
            $new_obj->meta_value($wc_data);
            $new_obj->Save();
        }
    }

    public function update_ticket_meta($ticket_id, $pro_name, $pro_value)
    {
        $ticket_id = absint($ticket_id);

        $pro_name = sanitize_text_field($pro_name);
        $pro_value = sanitize_text_field($pro_value);

        if (('wc_field_data' === $pro_name) && !empty($ticket_id)) {
            $wc_data = is_string($pro_value) ? explode(',', $pro_value) : array();

            $store_id = isset($wc_data[0]) ? absint($wc_data[0]) : 0;
            $order_id = isset($wc_data[1]) ? absint($wc_data[1]) : 0;

            if (!empty($store_id) && !empty($order_id)) {
                $wc_data = maybe_serialize(array(
                    'store_id' => sanitize_text_field($store_id),
                    'order_id' => sanitize_text_field($order_id),
                ));

                $current_obj = new Mapbd_wps_support_meta();
                $current_obj->item_id($ticket_id);
                $current_obj->item_type('T');
                $current_obj->meta_key('1');
                $current_obj->meta_type('W');

                if ($current_obj->Select()) {
                    $update_obj = new Mapbd_wps_support_meta();
                    $update_obj->SetWhereUpdate('id', $current_obj->id);
                    $update_obj->meta_value($wc_data);
                    $update_obj->Update();
                } else {
                    $new_obj = new Mapbd_wps_support_meta();
                    $new_obj->item_id($ticket_id);
                    $new_obj->item_type('T');
                    $new_obj->meta_key('1');
                    $new_obj->meta_type('W');
                    $new_obj->meta_value($wc_data);
                    $new_obj->Save();
                }
            }
        }
    }

    public function validate_order_id($store_id = '', $order_id = '', $user_email = '')
    {
        $validate = array(
            'status' => false,
            'msg' => $this->__('Invalid Store & Order ID'),
        );

        $store_id = sanitize_text_field($store_id);
        $order_id = sanitize_text_field($order_id);

        if (! empty($store_id) && ! empty($order_id)) {
            $store_data = Mapbd_wps_woocommerce::FindBy('id', $store_id, array('status' => 'A'));

            if (is_object($store_data) && ! empty($store_data)) {
                $same_site = (isset($store_data->same_site) ? sanitize_text_field($store_data->same_site) : '');

                if ('Y' === $same_site) {
                    if ($this->is_woocommerce_active()) {
                        $validate = $this->validate_same_site_order_id($store_data, $order_id, $user_email);
                    }
                }
            }
        }

        return $validate;
    }

    public function validate_same_site_order_id($store_data, $order_id, $user_email = '')
    {
        $status = true;
        $msg = '';

        $order = call_user_func('wc_get_order', $order_id);

        if (is_object($order) && ! empty($order)) {
            $verify_email = (isset($store_data->verify_email) ? sanitize_text_field($store_data->verify_email) : 'Y');
            $prevent_for_c = (isset($store_data->prevent_for_c) ? sanitize_text_field($store_data->prevent_for_c) : 'Y');
            $prevent_for_r = (isset($store_data->prevent_for_r) ? sanitize_text_field($store_data->prevent_for_r) : 'Y');

            if ((true === $status) && ('Y' === $verify_email) && ! empty($user_email)) {
                $customer_email = $order->get_billing_email();

                if ($user_email !== $customer_email) {
                    $status = false;
                    $msg = $this->__("Email doesn't match with customer email");
                }
            }

            if ((true === $status) && (('Y' === $prevent_for_c) || ('Y' === $prevent_for_r))) {
                $order_status = $order->get_status();

                if (('cancelled' === $order_status) && ('Y' === $prevent_for_c)) {
                    $status = false;
                    $msg = $this->__('Cancelled Order ID is not allowed');
                } elseif (('refunded' === $order_status) && ('Y' === $prevent_for_r)) {
                    $status = false;
                    $msg = $this->__('Refunded Order ID is not allowed');
                }
            }
        } else {
            $status = false;
            $msg = $this->__('Invalid Store & Order ID');
        }

        $validate = array(
            'status' => $status,
            'msg' => $msg,
        );

        return $validate;
    }

    public function get_order_info($store_id = '', $order_id = '')
    {
        $store_id = sanitize_text_field($store_id);
        $order_id = sanitize_text_field($order_id);

        $order_info = array();

        if (! empty($store_id) && ! empty($order_id)) {
            $store_data = Mapbd_wps_woocommerce::FindBy('id', $store_id, array('status' => 'A'));

            if (is_object($store_data) && ! empty($store_data)) {
                $same_site = (isset($store_data->same_site) ? sanitize_text_field($store_data->same_site) : '');

                if ('Y' === $same_site) {
                    if ($this->is_woocommerce_active()) {
                        $order_info = $this->get_same_site_order_info($order_id);
                    }
                }
            }
        }

        return $order_info;
    }

    public function get_same_site_order_info($order_id)
    {
        $order_info = array(
            'same_site' => true,
            'order_data' => call_user_func('wc_get_order', $order_id),
        );

        return $order_info;
    }

    public function is_required_in_tckt_form()
    {
        $required = false;

        if ($this->is_custom_field_exists('ticket')) {
            $is_required = $this->GetOption('is_required', 'Y');

            if ('Y' === $is_required) {
                $required = true;
            }
        }

        return $required;
    }

    public function is_custom_field_exists($form = 'ticket')
    {
        $exists = false;

        $custom_fields = Mapbd_wps_custom_field::getCustomFieldForAPI();
        $custom_fields = apply_filters('apbd-wps/filter/before-custom-get', $custom_fields);

        if ('ticket' === $form) {
            $fields = ((isset($custom_fields->ticket_form) && is_array($custom_fields->ticket_form)) ? $custom_fields->ticket_form : array());
        } elseif ('reg' === $form) {
            $fields = ((isset($custom_fields->reg_form) && is_array($custom_fields->reg_form)) ? $custom_fields->reg_form : array());
        } else {
            $fields = array();
        }

        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (! is_object($field)) {
                    continue;
                }

                $input_name = (isset($field->input_name) ? sanitize_text_field($field->input_name) : '');

                $data = array(
                    'input_name' => $input_name,
                    'field' => $field,
                );

                if (('wc_store_id' === $input_name) || ('wc_order_id' === $input_name)) {
                    $exists = true;
                    break;
                }
            }
        }

        return $exists;
    }

    public function wc_account_menu_items($items)
    {
        $ticket_page_id = Apbd_wps_settings::GetModuleOption("ticket_page", "");

        if (empty($ticket_page_id) || ('page' !== get_post_type($ticket_page_id))) {
            return $items;
        }

        $show_menu = Apbd_wps_woocommerce::GetModuleOption("menu_in_wc_my_account_page", 'Y');

        if ('Y' !== $show_menu) {
            return $items;
        }

        $menu_label = Apbd_wps_woocommerce::GetModuleOption("menu_label_in_wc_my_account_page", '');
        $menu_label = trim($menu_label);

        if ('' === $menu_label) {
            $menu_label = $this->__('Get Support');
        }

        $output = array();
        $items_count = count($items);
        $counter = 0;

        foreach ($items as $key => $value) {
            if ($counter === ($items_count - 1)) {
                $output['support-genix'] = $menu_label;
            }

            $output[$key] = $value;
            $counter++;
        }

        return $output;
    }

    public function wc_get_endpoint_url($url, $endpoint)
    {
        if ('support-genix' === $endpoint) {
            $ticket_page_id = Apbd_wps_settings::GetModuleOption("ticket_page", "");

            if (! empty($ticket_page_id) && ('page' === get_post_type($ticket_page_id))) {
                return get_page_link($ticket_page_id);
            }
        }

        return $url;
    }

    public function CleanURL($url)
    {
        $url = esc_url($url);

        if (! empty($url)) {
            $search = array('http://', 'https://', '//', '://', 'www.');
            $replace = array('', '', '', '', '');

            $url = str_replace($search, $replace, $url);
        }

        return $url;
    }

    public function ReplacePlaceholders($content, $placeholders = array())
    {
        $search = array_keys($placeholders);
        $replace = array_values($placeholders);

        if (! empty($search) || ! empty($replace)) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    static function GetPurchasedOrders($ticketObj)
    {
        $output = array('valid' => false);

        if ('Y' === self::GetModuleOption('collect_order_info', 'N')) {
            return $output;
        }

        $user_id = (isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0);
        $userdata = get_userdata($user_id);

        if (! $userdata) {
            return $output;
        }

        $user_email = sanitize_email($userdata->user_email);

        $mainObj = ApbdWps_SupportLite::GetInstance();

        $same_site_items = self::GetSameSiteOrders($mainObj, $user_email);

        if (! empty($same_site_items)) {
            $output['items'] = $same_site_items;
            $output['count'] = count($same_site_items);
            $output['valid'] = true;
        }

        return $output;
    }

    static function GetSameSiteOrders($mainObj, $user_email)
    {
        $items = array();

        if (! is_plugin_active('woocommerce/woocommerce.php')) {
            return $items;
        }

        $config = Mapbd_wps_woocommerce::FindBy('status', 'A', array('same_site' => 'Y'));

        if (empty($config)) {
            return $items;
        }

        $site_url = site_url();

        $orders = call_user_func('wc_get_orders', array(
            'customer' => $user_email,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending', 'wc-refunded', 'wc-cancelled'),
        ));

        if (! $orders) {
            return $items;
        }

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $order_status = call_user_func('wc_get_order_status_name', $order->get_status());
            $order_date = wp_date(get_option('date_format'), $order->get_date_created()->getTimestamp());
            $total_amount = $order->get_formatted_order_total();

            $products = array();

            foreach ($order->get_items() as $item) {
                $qty = $item->get_quantity();
                $name = $item->get_name();
                $line_total = $order->get_formatted_line_subtotal($item);
                $products[] = sprintf('%1$s x %2$d &mdash; %3$s', $name, $qty, $line_total);
            }

            $totals = array(
                sprintf('%1$s: %2$s', $mainObj->__('Total'), $total_amount),
            );

            $store_title = isset($config->store_title) ? sanitize_text_field($config->store_title) : '';
            $store_display = $store_title ? $store_title : '<span style="color:rgba(0,0,0,0.45)">N/A</span>';

            $others = array(
                sprintf('%1$s: %2$d', $mainObj->__('ID'), $order_id),
                sprintf('%1$s: %2$s', $mainObj->__('Status'), $order_status),
                sprintf('%1$s: %2$s', $mainObj->__('Date'), $order_date),
                sprintf('%1$s: %2$s', $mainObj->__('Store'), $store_display),
            );

            $more_url = admin_url('post.php?post=' . $order_id . '&action=edit');

            $items[] = array(
                'products' => $products,
                'totals' => $totals,
                'others' => $others,
                'site_url' => $site_url,
                'more_url' => $more_url,
                'same_site' => true,
            );
        }

        return $items;
    }

    static function UpdateDefaultOpts()
    {
        $instance = self::GetModuleInstance();

        if (empty($instance)) {
            return;
        }

        $existing = $instance->GetOption('collect_order_info', '');

        if (! empty($existing)) {
            return;
        }

        $instance->AddOption('collect_order_info', 'Y');
    }

    /**
     * Migration: If in_tckt_form was disabled, disable collect_order_info entirely.
     */
    static function MigrateDisplayOpts()
    {
        $instance = self::GetModuleInstance();

        if (empty($instance)) {
            return;
        }

        $in_tckt_form = $instance->GetOption('in_tckt_form', 'Y');

        if ('N' === $in_tckt_form) {
            $instance->AddOption('collect_order_info', 'N');
        }
    }
}
