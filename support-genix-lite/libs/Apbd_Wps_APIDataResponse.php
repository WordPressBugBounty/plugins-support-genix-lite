<?php

/**
 * API data response.
 */

defined('ABSPATH') || exit;

class  Apbd_Wps_APIDataResponse
{
    public $page = 1;
    public $limit = 10;
    public $total = 0;
    public $pagetotal = 1;
    public $rowdata = [];
    public $data = null;
    // @ Dynamic
    public $ticket_rcount;
    public $ticket_stat;

    function SetRowData($data)
    {
        $this->rowdata = $data;
    }
    function SetData($data)
    {
        $this->data = $data;
    }
}
