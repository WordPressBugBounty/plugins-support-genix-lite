<?php

/**
 * Ajax data response.
 */

defined('ABSPATH') || exit;

if (!class_exists("ApbdWpsAjaxDataResponse")) {
    class ApbdWpsAjaxDataResponse
    {
        public $orderBy;
        public $order;
        public $rows = 20;
        public $pageNo = 1;
        public $limit = 20;
        public $limitStart = 0;
        public $srcItem = "";
        public $srcText = "";
        public $toDate = "";
        public $fromDate = "";
        public $srcOption = "";
        public $searchOper = "";

        public $multiparam = array();
        public $multiOperator = array();
        public $isMultisearch = array();
        private $response;
        // @ Dynamic
        public $srcTex;

        function __construct($skipSessionCheck = '')
        {
            $this->response               = new stdClass();
            $this->response->rowdata      = array();
            $this->response->redirect_url = "";


            if (ApbdWps_IsPostBack) {
                $this->orderBy = ApbdWps_RequestValue("sidx");
                $this->order   = ApbdWps_RequestValue('sord');
                $this->rows    = ApbdWps_RequestValue('rows', $this->rows);
                if ($this->rows > 200) {
                    $this->rows = 200;
                }
                $this->pageNo = (int) ApbdWps_RequestValue('page');
                if ($this->pageNo == 0) {
                    $this->pageNo = 1;
                }
                if (ApbdWps_RequestValue('first', "false") == "true") {
                    $this->pageNo = 1;
                }
                $this->srcItem = ApbdWps_RequestValue('searchField');
                $this->srcText = ApbdWps_RequestValue('searchString');
                if (empty($this->srcText) || $this->srcText == "*") {
                    $this->srcText = "";
                    $this->srcItem = "";
                }
                $this->searchOper = ApbdWps_RequestValue('searchOper');
                $this->toDate     = ApbdWps_RequestValue('toString');
                if ($this->searchOper == "bt") {
                    $this->fromDate = $this->srcText;
                    $this->srcTex   = "";
                }
                $this->limitStart    = ($this->pageNo - 1) * $this->rows;
                $this->limit         = &$this->rows;
                $this->multiparam    = array();
                $this->multiOperator = array();
                $this->isMultisearch = false;
                $oplist              = array("lg" => "<", "gr" => ">");
                $this->isMultisearch = ApbdWps_RequestValue('isMultiSearch', "") == "true" || $this->isMultisearch == true;
                if ($this->isMultisearch) {
                    $ptext = ApbdWps_RequestValue('ms', "", false);
                    if (! empty($ptext)) {
                        $ptext         = urldecode($ptext);
                        $multi_options = array();

                        if ('string' !== gettype($ptext)) {
                            $ptext = '';
                        }

                        parse_str($ptext, $multi_options);
                        if (isset($multi_options['ms'])) {
                            $this->multiparam = $multi_options['ms'];
                            foreach ($this->multiparam as &$_mp) {
                                if (is_string($_mp)) {
                                    $_mp = sanitize_text_field($_mp);
                                }
                            }
                            if (! empty($multi_options['op']) && is_array($multi_options['op'])) {
                                foreach ($multi_options['op'] as $opkey => $_op) {
                                    if (! empty($oplist[$_op])) {
                                        $this->multiOperator[$opkey] = $oplist[$_op];
                                    }
                                }
                            }
                        }
                    }
                    $this->multiparam = array_filter($this->multiparam, function ($value) {
                        return ! empty($value) && $value != "*";
                    });
                }
            }
        }

        function setOrderByIfEmpty($property, $order = "ASC")
        {
            if (empty($this->orderBy)) {
                $this->orderBy = $property;
                $this->order   = $order;
            }
        }

        /**
         * @param ApbdWpsModel $mainobj
         */
        function setDateRange(&$mainobj)
        {
            if ($this->searchOper == "bt") {
                if (! empty($this->fromDate) && property_exists($mainobj, $this->srcItem)) {
                    if (empty($this->toDate)) {
                        $this->toDate = $this->fromDate;
                    }
                    $this->fromDate = ApbdWps_GetSystemFromWPTimezone($this->fromDate . " 00:00:00", "Y-m-d H:i:00");
                    $this->toDate   = ApbdWps_GetSystemFromWPTimezone($this->toDate . " 23:59:59", "Y-m-d H:i:s");

                    $mainobj->{$this->srcItem}("BETWEEN '" . $this->fromDate . "' AND '" . $this->toDate . "'", true);
                    $this->srcText = "";
                    $this->srcItem = "";
                } else {
                    die("Failed");
                }
            }
        }
        function setMultiParams(&$mainobj, $except = '')
        {
            $except = explode(",", $except);
            if ($this->isMultisearch) {
                foreach ($this->multiparam as $key => $value) {
                    if (property_exists($mainobj, $key)) {
                        $mainobj->{$key}($value);
                    }
                }
            }
        }

        function setDownloadFileName($filename)
        {
            if (! empty($filename)) {
                $this->download_filename = $filename;
            }
        }

        function getMultiParam($key = '', $defaultValue = '')
        {
            if (empty($key)) {
                return $defaultValue;
            }
            if (isset($this->multiparam[$key])) {
                return $this->multiparam[$key];
            }

            return $defaultValue;
        }

        function SetGridRecords($records)
        {
            $this->response->records = $records;
        }

        function SetGridData($data, $key = 'rowdata')
        {
            $this->response->$key = $data;
        }

        protected function DisplayGridPermissionDenied($redirect_url = '')
        {
            $mainobj = ApbdWps_SupportLite::GetInstance();
            $this->response->records      = 0;
            $this->response->page         = 0;
            $this->response->total        = 0;
            $this->response->rowdata      = array();
            $this->response->msg          = $mainobj->__("Permission Denied");
            $this->response->redirect_url = $redirect_url;
            echo json_encode($this->response);
            die;
        }

        protected function AddIntoPageList() {}
    }
}
