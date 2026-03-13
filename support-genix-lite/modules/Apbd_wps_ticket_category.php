<?php

/**
 * Ticket category.
 */

defined('ABSPATH') || exit;

class Apbd_wps_ticket_category extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("add_many", [$this, "add_many"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddAjaxAction("delete_items", [$this, "delete_items"]);
        $this->AddAjaxAction("data_for_select", [$this, "data_for_select"]);
        $this->AddAjaxAction("activate_items", [$this, "activate_items"]);
        $this->AddAjaxAction("deactivate_items", [$this, "deactivate_items"]);
        $this->AddAjaxAction("order_change", [$this, "order_change"]);
        $this->AddAjaxAction("reset_order", [$this, "reset_order"]);

        $this->AddPortalAjaxAction("data_for_select", [$this, "data_for_select"]);
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $nobject = new Mapbd_wps_ticket_category();

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

    public function add_many()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $items = stripslashes(ApbdWps_PostValue('items', ''));
            $items = !empty($items) ? json_decode($items, true) : [];

            if (is_array($items) && !empty($items)) {
                $added = 0;
                $updated = 0;
                $saved = 0;

                foreach ($items as $item) {
                    $id = isset($item['id']) ? absint($item['id']) : 0;
                    $title = isset($item['title']) ? sanitize_text_field($item['title']) : '';

                    if (empty($title)) {
                        continue;
                    }

                    if (!$id) {
                        $nobject = new Mapbd_wps_ticket_category();
                        $nobject->title($title);
                        $nobject->status('A');

                        if ($nobject->Save()) {
                            $added++;
                        }
                    } else {
                        $uobject = new Mapbd_wps_ticket_category();
                        $uobject->SetWhereUpdate("id", $id);
                        $uobject->title($title);
                        $uobject->status('A');

                        if ($uobject->Update()) {
                            $updated++;
                        } else {
                            $saved++;
                        }
                    }
                }

                if ($added && $updated) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added and updated.'));
                } elseif ($added) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));
                } elseif ($updated) {
                    $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                } elseif ($saved) {
                    $apiResponse->SetResponse(true, '');
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
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
            $mainobj = new Mapbd_wps_ticket_category();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $uobject = new Mapbd_wps_ticket_category();

                if ($uobject->SetFromPostData(false)) {
                    if (absint($param_id) !== absint($uobject->parent_category)) {
                        $uobject->SetWhereUpdate("id", $param_id);

                        if ($uobject->Update()) {
                            $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
                        } else {
                            $apiResponse->SetResponse(false, $this->__('Nothing to update.'));
                        }
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Invalid data.'));
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
        $mainobj = new Mapbd_wps_ticket_category();
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

            $ctgs = $mainobj->SelectAllWithKeyValue("id", "title");
            $result = $mainobj->SelectAll("", $orderBy, $order, $limit, $limitStart);

            if ($result) {
                foreach ($result as &$data) {
                    $parent_category = absint($data->parent_category);

                    if ($parent_category) {
                        $data->parent_category_title = ApbdWps_GetTextByKey($data->parent_category, $ctgs);
                    } else {
                        $data->parent_category_title = '';
                    }
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
            $mainobj = new Mapbd_wps_ticket_category();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                $dobject = new Mapbd_wps_ticket_category();
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
                    $mainobj = new Mapbd_wps_ticket_category();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $dobject = new Mapbd_wps_ticket_category();
                        $dobject->SetWhereUpdate("id", $param_id);
                        $dobject->Delete();
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

        $list_data = $this->list_for_select($except_id, $select, $select_all, $with_id, $no_value);
        $apiResponse->SetResponse(true, "", $list_data);

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
                    $mainobj = new Mapbd_wps_ticket_category();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_ticket_category();
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
                    $mainobj = new Mapbd_wps_ticket_category();
                    $mainobj->id($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_ticket_category();
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
            $mainobj = new Mapbd_wps_ticket_category();
            $mainobj->id($param_id);

            if ($mainobj->Select()) {
                if (Mapbd_wps_ticket_category::changeOrder($param_id, $param_type)) {
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

        Mapbd_wps_ticket_category::ResetOrder();

        echo wp_json_encode($apiResponse);
    }

    public function FilterChildList($parents, $id)
    {
        $child = [];

        foreach ($parents as $child_id => $parent) {
            if ($parent === strval($id)) {
                $child[] = $child_id;
                $child = array_merge($child, $this->FilterChildList($parents, $child_id));
            }
        }

        return $child;
    }

    // Extra.

    public function list_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $except_id = absint($except_id);
        $select = rest_sanitize_boolean($select);
        $select_all = rest_sanitize_boolean($select_all);
        $with_id = rest_sanitize_boolean($with_id);
        $no_value = rest_sanitize_boolean($no_value);

        $mainobj = new Mapbd_wps_ticket_category();
        $prntobj = new Mapbd_wps_ticket_category();
        $total = absint($mainobj->CountALL());

        $result = [];
        $valkey = $no_value ? 'key' : 'value';

        if ($select) {
            $result[] = [
                $valkey => "",
                'label' => '-- ' . $this->__('Select Category') . ' --',
            ];
        }

        if ($select_all) {
            $result[] = [
                $valkey => "0",
                'label' => $this->__('All Categories'),
            ];
        }

        if (0 < $total) {
            $records = $mainobj->SelectAllWithKeyValue("id", "title", 'fld_order', 'ASC', '', '', '', '', ['status' => 'A']);
            $parents = $prntobj->SelectAllWithKeyValue("id", "parent_category", 'fld_order', 'ASC');

            if ($records) {
                foreach ($records as $id => $title) {
                    $id = absint($id);

                    if ($id !== $except_id) {
                        $title .= $with_id ? ' ' . $this->___('(ID: %d)', $id) : '';
                        $child = $this->FilterChildList($parents, $id);

                        $result[] = [
                            $valkey => strval($id),
                            'label' => $title,
                            'child' => $child,
                        ];
                    }
                }
            }
        }

        return [
            'result' => $result,
            'total' => $total,
        ];
    }
}
