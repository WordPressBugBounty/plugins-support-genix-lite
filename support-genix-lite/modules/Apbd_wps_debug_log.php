<?php

/**
 * Debug log.
 */

defined('ABSPATH') || exit;

class Apbd_wps_debug_log extends ApbdWpsBaseModuleLite
{

    function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);
        $this->AddAjaxAction("clean_data", [$this, "clean_data"]);
        $this->AddAjaxAction("view_dtls", [$this, "view_dtls"]);
    }

    function view_dtls($param_id = "")
    {
        $this->SetPOPUPColClass("col-sm-8");

        $param_id = ApbdWps_GetValue("id");
        if (empty($param_id)) {
            $this->AddError("Invalid request");
            $this->DisplayPOPUPMsg();
            return;
        }
        $this->SetTitle("Details Debug Log");

        $mainobj = new Mapbd_wps_debug_log();
        $mainobj->id($param_id);
        if (!$mainobj->Select()) {
            $this->AddError("Invalid request");
            $this->DisplayPOPUPMsg();
            return;
        }
        $this->AddViewData("mainobj", $mainobj);
        $this->AddViewData("isUpdateMode", true);
        $this->DisplayPOPUP("view_dtls");
    }


    function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $mainobj = new Mapbd_wps_debug_log();

        $mainResponse = new ApbdWpsAjaxDataResponse();
        $mainResponse->setDateRange($mainobj);

        $total = $mainobj->CountALL($mainResponse->srcItem, $mainResponse->srcText, $mainResponse->multiparam, "after");

        if ($total > 0) {
            $result = $mainobj->SelectAllGridData("", $mainResponse->orderBy, $mainResponse->order, $mainResponse->rows, $mainResponse->limitStart, $mainResponse->srcItem, $mainResponse->srcText, $mainResponse->multiparam, "after");

            if ($result) {
                $entry_type_options = $mainobj->GetPropertyOptionsTag("entry_type");
                $log_type_options = $mainobj->GetPropertyOptionsTag("log_type");
                $status_options = $mainobj->GetPropertyOptionsTag("status");

                foreach ($result as &$data) {
                    $data->entry_type = ApbdWps_GetTextByKey($data->entry_type, $entry_type_options);
                    $data->log_type = ApbdWps_GetTextByKey($data->log_type, $log_type_options);
                    $data->status = ApbdWps_GetTextByKey($data->status, $status_options);
                }

                $apiResponse->SetResponse(true, "", [
                    'result' => $result,
                    'total' => $total,
                ]);
            }
        }

        echo wp_json_encode($apiResponse);
    }


    function delete_item($param = "")
    {
        $mainResponse = new ApbdWpsAjaxConfirmResponse();
        //temporary
        $mainResponse->DisplayWithResponse(false, $this->__("Delete is temporary disabled"));
        return;
        if (empty($param)) {
            $mainResponse->DisplayWithResponse(false, $this->__("Invalid Request"));
            return;
        }
        $mr = new Mapbd_wps_debug_log();
        $mr->id($param);
        if ($mr->Select()) {
            if (Mapbd_wps_debug_log::DeleteByKeyValue("id", $param)) {
                ApbdWps_AddLog("D", "id={$param}", "l003", "Wp_apbd_wps_debug_log_confirm");
                $mainResponse->DisplayWithResponse(true, $this->__("Successfully deleted"));
            } else {
                $mainResponse->DisplayWithResponse(false, $this->__("Delete failed try again"));
            }
        }
    }
    function clean_data()
    {
        $mainResponse = new ApbdWpsAjaxConfirmResponse();
        if (Mapbd_wps_debug_log::ClearAll()) {
            ApbdWps_AddLog("D", "clear all", "l003", "Debug_log_confirm");
            $mainResponse->DisplayWithResponse(true, $this->__("Clear failed try again"));
        } else {
            $mainResponse->DisplayWithResponse(true, $this->__("Successfully Cleared"));
        }
    }
}
