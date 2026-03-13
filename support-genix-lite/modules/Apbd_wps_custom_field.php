<?php

/**
 * Custom field.
 */

defined('ABSPATH') || exit;

class Apbd_wps_custom_field extends ApbdWpsBaseModuleLite
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
        $this->AddAjaxAction("order_change", [$this, "order_change"]);
        $this->AddAjaxAction("reset_order", [$this, "reset_order"]);
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $nobject = new Mapbd_wps_custom_field();

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
            $mainobj = new Mapbd_wps_custom_field();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $uobject = new Mapbd_wps_custom_field();

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
        $mainobj = new Mapbd_wps_custom_field();
        $total = absint($mainobj->CountALL());

        if (0 < $total) {
            $sort = ApbdWps_GetValue("sort");
            $page = ApbdWps_GetValue("page");
            $limit = ApbdWps_GetValue("limit");

            $orderBy = 'fld_order';
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
                    $form_opts = [];

                    if ('Y' === $item->is_required) {
                        $form_opts[] = 'is_required';
                    }

                    if ('Y' === $item->is_half_field) {
                        $form_opts[] = 'is_half_field';
                    }

                    $item->form_opts = $form_opts;

                    // Conditions.
                    $item->has_condition = 'N';
                    $item->conditions = [];
                    $item->condition_rel = 'A';
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
            $mainobj = new Mapbd_wps_custom_field();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                if (Mapbd_wps_custom_field::DeleteById($param_id)) {
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
                    $mainobj = new Mapbd_wps_custom_field();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        Mapbd_wps_custom_field::DeleteById($param_id);
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
                    $mainobj = new Mapbd_wps_custom_field();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_custom_field();
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
                    $mainobj = new Mapbd_wps_custom_field();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_custom_field();
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
            $mainobj = new Mapbd_wps_custom_field();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                if (Mapbd_wps_custom_field::changeOrder($param_id, $param_type)) {
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

        Mapbd_wps_custom_field::ResetOrder();

        echo wp_json_encode($apiResponse);
    }
}
