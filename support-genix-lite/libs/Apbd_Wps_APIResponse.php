<?php

/**
 * API response.
 */

defined('ABSPATH') || exit;

if (!class_exists("Apbd_Wps_APIResponse")) {
    class Apbd_Wps_APIResponse
    {
        public $status = false;
        public $msg = "";
        public $data = NULL;

        function SetResponse($status, $message = '', $data = NULL)
        {
            $this->status = $status;
            $this->msg = $message;
            $this->data = $data;
        }
    }
}
