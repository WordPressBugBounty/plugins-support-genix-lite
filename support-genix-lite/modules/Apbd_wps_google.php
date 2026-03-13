<?php

/**
 * Google.
 */

defined('ABSPATH') || exit;

class Apbd_wps_google extends ApbdWpsBaseModuleLite
{
    function initialize()
    {
        parent::initialize();
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(true, "", []);

        echo wp_json_encode($apiResponse);
    }
}
