<?php

/**
 * Email to ticket.
 */

defined('ABSPATH') || exit;

class Apbd_wps_email_to_ticket extends ApbdWpsBaseModuleLite
{
    function initialize()
    {
        $this->AddAjaxAction("both_for_select", [$this, "both_for_select"]);

        $this->AddPortalAjaxAction("both_for_select", [$this, "both_for_select"]);
    }

    public function both_for_select($except_id = 0, $select = false, $select_all = false, $with_id = false, $no_value = false)
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $apiResponse->SetResponse(true, "", [
            'result' => [],
            'total' => 0,
        ]);

        echo wp_json_encode($apiResponse);
    }
}
