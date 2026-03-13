<?php

/**
 * Notification.
 */

defined('ABSPATH') || exit;

class Apbd_wps_notification extends ApbdWpsBaseModuleLite
{

    function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);
        $this->AddAjaxAction("edit", [$this, "edit"]);
        $this->AddAjaxAction("delete_item", [$this, "delete_item"]);

        $this->AddAjaxAction("is_popup_link_change", [$this, "is_popup_link_change"]);
    }

    function add()
    {
        $this->SetTitle("Add New Notification");
        $this->SetPOPUPColClass("col-sm-6");

        if (ApbdWps_IsPostBack) {
            $nobject = new Mapbd_wps_notification();
            if ($nobject->SetFromPostData(true)) {
                if ($nobject->Save()) {
                    $this->AddInfo("Successfully added");
                    ApbdWps_AddLog("A", $nobject->settedPropertyforLog(), "l001", "");
                    $this->DisplayPOPUPMsg();
                    return;
                }
            }
        }
        $mainobj = new Mapbd_wps_notification();
        $this->AddViewData("isUpdateMode", false);
        $this->AddViewData("mainobj", $mainobj);
        $this->DisplayPOPUp("add");
    }
    function edit($param_id = "")
    {
        $this->SetPOPUPColClass("col-sm-6");

        $param_id = ApbdWps_GetValue("id");
        if (empty($param_id)) {
            $this->AddError("Invalid request");
            $this->DisplayPOPUPMsg();
            return;
        }
        $this->SetTitle("Edit Notification");
        if (ApbdWps_IsPostBack) {
            $uobject = new Mapbd_wps_notification();
            if ($uobject->SetFromPostData(false)) {
                $uobject->SetWhereUpdate("id", $param_id);
                if ($uobject->Update()) {
                    ApbdWps_AddLog("U", $uobject->settedPropertyforLog(), "l002", "");
                    $this->AddInfo("Successfully updated");
                    $this->DisplayPOPUPMsg();
                    return;
                }
            }
        }
        $mainobj = new Mapbd_wps_notification();
        $mainobj->id($param_id);
        if (!$mainobj->Select()) {
            $this->AddError("Invalid request");
            $this->DisplayPOPUPMsg();
            return;
        }
        ApbdWps_OldFields($mainobj, "user_id,title,msg,entry_type,entry_link,n_counter,is_popup_link,view_time,entry_time,item_type,extra_param,msg_param,status");
        $this->AddViewData("mainobj", $mainobj);
        $this->AddViewData("isUpdateMode", true);
        $this->DisplayPOPUP("add");
    }


    function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $mainobj = new Mapbd_wps_notification();

        $mainResponse = new ApbdWpsAjaxDataResponse();
        $mainResponse->setDateRange($mainobj);

        $total = $mainobj->CountALL($mainResponse->srcItem, $mainResponse->srcText, $mainResponse->multiparam, "after");

        if ($total > 0) {
            $result = $mainobj->SelectAllGridData("", $mainResponse->orderBy, $mainResponse->order, $mainResponse->rows, $mainResponse->limitStart, $mainResponse->srcItem, $mainResponse->srcText, $mainResponse->multiparam, "after");

            if ($result) {
                $entry_type_options = $mainobj->GetPropertyOptionsTag("entry_type");
                $status_options = $mainobj->GetPropertyOptionsTag("status");

                foreach ($result as &$data) {
                    $data->entry_type = ApbdWps_GetTextByKey($data->entry_type, $entry_type_options);
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
        $mr = new Mapbd_wps_notification();
        $mr->id($param);
        if ($mr->Select()) {
            if (Mapbd_wps_notification::DeleteByKeyValue("id", $param)) {
                ApbdWps_AddLog("D", "id={$param}", "l003", "Wp_apbd_wps_notification_confirm");
                $mainResponse->DisplayWithResponse(true, $this->__("Successfully deleted"));
            } else {
                $mainResponse->DisplayWithResponse(false, $this->__("Delete failed try again"));
            }
        }
    }

    function is_popup_link_change()
    {
        $param = ApbdWps_GetValue("id");
        $mainResponse = new ApbdWpsAjaxConfirmResponse();
        if (empty($param)) {
            $mainResponse->DisplayWithResponse(false, $this->__("Invalid Request"));
            return;
        }
        $mr = new Mapbd_wps_notification();
        $is_popup_linkChange = $mr->GetPropertyOptionsTag("is_popup_link");

        $mr->id($param);
        if ($mr->Select("is_popup_link")) {
            $newStatus = $mr->is_popup_link == "Y" ? "N" : "Y";
            $uo = new Mapbd_wps_notification();
            $uo->is_popup_link($newStatus);
            $uo->SetWhereUpdate("id", $param);
            if ($uo->Update()) {
                $status_text = ApbdWps_GetTextByKey($uo->is_popup_link, $is_popup_linkChange);
                ApbdWps_AddLog("U", $uo->settedPropertyforLog(), "l002", "Wp_apbd_wps_notification");
                $mainResponse->DisplayWithResponse(true, $this->__("Successfully Updated"), $status_text);
            } else {
                $mainResponse->DisplayWithResponse(false, $this->__("Update failed try again"));
            }
        }
    }
}
