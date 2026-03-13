<?php

/**
 * API response.
 */

defined('ABSPATH') || exit;

if (!class_exists("ApbdWpsAPIResponse")) {
    class ApbdWpsAPIResponse
    {

        public $status = false;
        public $msg = "";
        public $data = NULL;

        function SetResponse($status, $msg, $data = NULL)
        {
            $this->status = $status;
            $this->msg    = $msg;
            $this->data   = $data;
        }

        function DisplayWithResponse($status, $msg, $data = NULL)
        {
            $this->SetResponse($status, $msg, $data);
            $this->Display();
        }

        function Display()
        {
            die(json_encode($this));
        }

        static function DirectDisplay($status, $msg, $data = NULL)
        {
            $n = new self();
            $n->DisplayWithResponse($status, $msg, $data);
        }
    }
}
