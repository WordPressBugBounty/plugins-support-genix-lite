<?php

/**
 * Ticket tag.
 */

defined('ABSPATH') || exit;

class Apbd_wps_ticket_tag extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        $this->AddAjaxAction("data_for_select", [$this, "data_for_select"]);

        $this->AddPortalAjaxAction("data_for_select", [$this, "data_for_select"]);
    }

    public function data_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $apiResponse->SetResponse(true, "", [
            'result' => [],
            'total' => 0,
        ]);

        echo wp_json_encode($apiResponse);
    }
}
