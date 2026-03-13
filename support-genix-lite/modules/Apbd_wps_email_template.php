<?php

/**
 * Email template.
 */

defined('ABSPATH') || exit;

class Apbd_wps_email_template extends ApbdWpsBaseModuleLite
{

    function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("activate_items", [$this, "activate_items"]);
        $this->AddAjaxAction("deactivate_items", [$this, "deactivate_items"]);
    }

    public function edit($param_id = '')
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $param_id = sanitize_text_field(ApbdWps_GetValue("id"));

        if (ApbdWps_IsPostBack && !empty($param_id)) {
            $mainobj = new Mapbd_wps_email_templates();
            $mainobj->k_word($param_id);

            if ($mainobj->Select()) {
                $uobject = new Mapbd_wps_email_templates();

                if ($uobject->SetFromPostData(false)) {
                    $uobject->SetWhereUpdate("k_word", $param_id);

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
        $mainobj = new Mapbd_wps_email_templates();
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

            $apiResponse->SetResponse(true, "", [
                'result' => $result,
                'total' => $total,
            ]);
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
                    $mainobj = new Mapbd_wps_email_templates();
                    $mainobj->k_word($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_email_templates();
                        $uobject->status('A');
                        $uobject->SetWhereUpdate("k_word", $param_id);
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
                    $mainobj = new Mapbd_wps_email_templates();
                    $mainobj->k_word($param_id);

                    if ($mainobj->Select()) {
                        $uobject = new Mapbd_wps_email_templates();
                        $uobject->status('I');
                        $uobject->SetWhereUpdate("k_word", $param_id);
                        $uobject->Update();
                    }
                }

                $apiResponse->SetResponse(true, $this->__('Successfully updated.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }
}
