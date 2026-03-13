<?php

/**
 * Report.
 */

defined('ABSPATH') || exit;

class Apbd_wps_report extends ApbdWpsBaseModuleLite
{
    public $reportBase;
    public $reportBaseItem;
    public $reportDateRange;
    public $reportChartType;

    public function initialize()
    {
        parent::initialize();
    }

    public function OnInit()
    {
        parent::OnInit();

        $this->AddAjaxAction('select', [$this, 'SelectData']);
        $this->AddAjaxAction('generate', [$this, 'GenerateData']);
        $this->AddAjaxAction('export', [$this, 'ExportData']);
    }

    public function SelectData()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $data = [
            'categories' => [],
            'agents' => [],
        ];

        $categories = $this->GetCategories(true);
        $agents = $this->GetAgents(true);

        foreach ($categories as $id => $title) {
            $id = strval($id);

            $data['categories'][] = [
                'label' => $title,
                'value' => $id,
            ];
        }

        foreach ($agents as $id => $name) {
            $id = strval($id);

            $data['agents'][] = [
                'label' => $name,
                'value' => $id,
            ];
        }

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function GenerateData()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $filters = $this->GetFilters('get');
        $data = $this->GetReportData($filters);

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function ExportData()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $filters = $this->GetFilters('post');
        $data = $this->GetReportData($filters);
        $tableData = $this->CleanExportData($data['table_data']);
        $domain = wp_parse_url(home_url(), PHP_URL_HOST);

        $base = $data['base'];
        $baseItem = $data['base_item'];
        $dateRange = $data['date_range'];

        $tableHead = $tableData['head'];
        $tableBody = $tableData['body'];

        $rows = array($tableHead);
        $rows = array_merge($rows, $tableBody);

        if (1 < count($tableBody)) {
            $rows[] = array_merge(array('title' => $this->__('Total')), $tableData['total']);
        }

        $nameVar = array($base);

        if (0 === $baseItem) {
            $nameVar[] = 'all';
        } elseif (999999 !== $baseItem) {
            $nameVar[] = $baseItem;
        }

        $nameVar[] = sprintf('%1$s-to-%2$s', $dateRange['start'], $dateRange['end']);
        $nameStr = implode('-', $nameVar);
        $domainr = str_replace('.', '-dot-', $domain);

        ob_start();
        $output = fopen('php://output', 'w');
        foreach ($rows as $row) {
            if (isset($row['id']) && ('999999' === $row['id'])) {
                $row['id'] = '0';
            }
            $csvRow = '"' . implode('","', $row) . '"' . "\n";
            fwrite($output, $csvRow);
        }
        fclose($output);
        $fileContent = ob_get_clean();

        $fileName = sprintf('%1$s-sg-report-%2$s-%3$s.csv', current_time('U'), $nameStr, $domainr);
        $fileName = sanitize_file_name($fileName);

        $data = array(
            'fileName' => $fileName,
            'fileContent' => $fileContent,
        );

        $apiResponse->SetResponse(true, $this->__('Report exported.'), $data);

        echo wp_json_encode($apiResponse);
    }

    public function GetFilters($reqType = 'get')
    {
        $func = (('get' === $reqType) ? 'ApbdWps_GetValue' : 'ApbdWps_PostValue');

        $base = sanitize_text_field($func('base', ''));
        $baseItem = sanitize_text_field($func('baseItem', ''));
        $dateRange = sanitize_text_field($func('dateRange', ''));
        $chartType = sanitize_text_field($func('chartType', ''));

        $baseItem = absint($baseItem);

        $filters = [
            'base' => $base,
            'baseItem' => $baseItem,
            'dateRange' => $dateRange,
            'chartType' => $chartType,
        ];

        return $filters;
    }

    public function GetRangeDates()
    {
        $rangeDates = [];

        $dateRange = $this->reportDateRange;

        $startDate = (isset($dateRange['start']) ? $dateRange['start'] : '');
        $endDate = (isset($dateRange['end']) ? $dateRange['end'] : '');

        if (! $startDate || ! $endDate) {
            return $rangeDates;
        }

        $startDateObj = ($startDate ? date_create($startDate) : null);
        $endDateObj = ($endDate ? date_create($endDate) : null);

        if (! $startDateObj || ! $endDateObj) {
            return $rangeDates;
        }

        $startDateUnx = strtotime($startDate);
        $endDateUnx = strtotime($endDate);

        if (! $startDateUnx || ! $endDateUnx || ($startDateUnx > $endDateUnx)) {
            return $rangeDates;
        }

        $startMonth = gmdate('m', $startDateUnx);
        $startYear = gmdate('Y', $startDateUnx);
        $endYear = gmdate('Y', $endDateUnx);

        $dateDiff = $startDateObj->diff($endDateObj->add(new DateInterval('P1D')));

        $dayDiff = (isset($dateDiff->days) ? absint($dateDiff->days) : 0);
        $monthDiff = (isset($dateDiff->m) ? absint($dateDiff->m) : 0);
        $yearDiff = (isset($dateDiff->y) ? absint($dateDiff->y) : 0);

        $dayCount = $dayDiff;
        $monthCount = (($yearDiff * 12) + $monthDiff);
        $yearCount = ($endYear - $startYear);

        if (36 < $monthCount) {
            for ($i = 0; $i <= $yearCount; $i++) {
                $crntYear = $startYear + $i;

                $crntYearStartDate = sprintf('%s-01-01 00:00:00', $crntYear);
                $crntYearEndDate = sprintf('%s-12-31 23:59:59', $crntYear);
                $crntLabel = date_i18n('Y', strtotime($crntYearStartDate));

                $rangeDates[] = [
                    'start' => $crntYearStartDate,
                    'end' => $crntYearEndDate,
                    'label' => $crntLabel,
                ];
            }
        } elseif (3 < $monthCount) {
            for ($i = 0; $i <= $monthCount; $i++) {
                $crntMonth = $startMonth + $i;
                $crntYear = $startYear;
                $passedYearCount = floor(($crntMonth - 1) / 12);

                if (0 < $passedYearCount) {
                    $crntMonth = $crntMonth - ($passedYearCount * 12);
                    $crntYear = $crntYear + $passedYearCount;
                }

                $crntMonth = absint($crntMonth);
                $crntYear = absint($crntYear);
                $passedYearCount = absint($passedYearCount);

                $crntMonthEndDay = cal_days_in_month(CAL_GREGORIAN, $crntMonth, $crntYear);

                $crntMonthStartDate = sprintf('%s-%s-01 00:00:00', $crntYear, $crntMonth);
                $crntMonthEndDate = sprintf('%s-%s-%s 23:59:59', $crntYear, $crntMonth, $crntMonthEndDay);
                $crntLabel = date_i18n('F Y', strtotime($crntMonthStartDate));

                $rangeDates[] = [
                    'start' => $crntMonthStartDate,
                    'end' => $crntMonthEndDate,
                    'label' => $crntLabel,
                ];
            }
        } else {
            for ($i = 0; $i < $dayCount; $i++) {
                $crntStartDateUnx = $startDateUnx + ($i * DAY_IN_SECONDS);

                $crntStartDate = gmdate('Y-m-d', $crntStartDateUnx) . ' 00:00:00';
                $crntEndDate = gmdate('Y-m-d', $crntStartDateUnx) . ' 23:59:59';
                $crntLabel = date_i18n('F j, Y', strtotime($crntStartDate));

                $rangeDates[] = [
                    'start' => $crntStartDate,
                    'end' => $crntEndDate,
                    'label' => $crntLabel,
                ];
            }
        }

        return $rangeDates;
    }

    public function GetReportData($filters = [])
    {
        $base = $this->GetReportBase($filters);
        $baseItem = $this->GetReportBaseItem($filters);
        $dateRange = $this->GetReportDateRange($filters);
        $chartType = $this->GetReportChartType($filters);

        $this->reportBase = $base;
        $this->reportBaseItem = $baseItem;
        $this->reportDateRange = $dateRange;
        $this->reportChartType = $chartType;

        $tableData = $this->GetTableData();
        $chartData = $this->GetChartData();

        $totalData = $this->GetTotalData($tableData);

        $data = [
            'base' => $base,
            'base_item' => $baseItem,
            'date_range' => $dateRange,
            'total_data' => $totalData,
            'table_data' => $tableData,
            'chart_data' => $chartData,
        ];

        return $data;
    }

    public function GetReportBase($filters = [])
    {
        $reportBase = (isset($filters['base']) ? sanitize_text_field($filters['base']) : 'category');
        $reportBase = (in_array($reportBase, ['category', 'agent']) ? $reportBase : 'category');

        return $reportBase;
    }

    public function GetReportBaseItem($filters = [])
    {
        return (isset($filters['baseItem']) ? absint($filters['baseItem']) : 0);
    }

    public function GetReportDateRange($filters = [])
    {
        $range = (isset($filters['dateRange']) ? sanitize_text_field($filters['dateRange']) : '');
        $range = explode('-to-', $range);

        $start = (isset($range[0]) ? sanitize_text_field($range[0]) : '');
        $end = (isset($range[1]) ? sanitize_text_field($range[1]) : '');

        $startObj = ($start ? date_create($start) : null);
        $endObj = ($end ? date_create($end) : null);

        if (! $startObj || ! $endObj) {
            $current = current_time('Y-m-d');
            $start = date_format(date_create($current)->sub(new DateInterval('P1M'))->add(new DateInterval('P1D')), 'Y-m-d');
            $end = $current;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    public function GetReportChartType($filters = [])
    {
        $chartType = (isset($filters['chartType']) ? sanitize_text_field($filters['chartType']) : 'bar');
        $chartType = (in_array($chartType, ['bar', 'line']) ? $chartType : 'bar');

        return $chartType;
    }

    public function GetCategories($all = false)
    {
        $categories = Mapbd_wps_ticket_category::FetchAllKeyValue('id', 'title', false, 'fld_order', 'ASC');
        $categories = (is_array($categories) ? $categories : []);
        $categories = ($all ? ([0 => $this->__('All Categories')] + $categories) : $categories);
        $categories = ($categories + [999999 => $this->__('Uncategorized')]);

        return $categories;
    }

    public function GetAgents($all = false)
    {
        $roles = Mapbd_wps_role::getAgentRoles();

        if (! is_array($roles) || empty($roles)) {
            return [];
        }

        $users = get_users(['role__in' => $roles]);

        if (! is_array($users) || empty($users)) {
            return [];
        }

        $agents = [];

        foreach ($users as $user) {
            $id = (isset($user->ID) ? absint($user->ID) : 0);
            $first_name = (isset($user->first_name) ? sanitize_text_field($user->first_name) : '');
            $last_name = (isset($user->last_name) ? sanitize_text_field($user->last_name) : '');
            $display_name = (isset($user->display_name) ? sanitize_text_field($user->display_name) : '');

            $name = trim($first_name . ' ' . $last_name);
            $name = (0 !== strlen($name) ? $name : $display_name);

            $agents[$id] = $name;
        }

        $agents = ($all ? ([0 => $this->__('All Agents')] + $agents) : $agents);
        $agents = ($agents + [999999 => $this->__('Unassigned')]);

        return $agents;
    }

    public function GetTableData()
    {
        $base = $this->reportBase;

        if ('category' === $base) {
            return $this->GetCategoryData();
        }

        if ('agent' === $base) {
            return $this->GetAgentData();
        }

        return [];
    }

    public function GetCategoryData()
    {
        global $wpdb;

        $ticketObj = new Mapbd_wps_ticket();
        $ticketTable = $ticketObj->GetTableName();

        $replyObj = new Mapbd_wps_ticket_reply();
        $replyTable = $replyObj->GetTableName();

        $logObj = new Mapbd_wps_ticket_log();
        $logTable = $logObj->GetTableName();

        $baseItem = absint($this->reportBaseItem);
        $categories = $this->GetCategories();

        if (! empty($baseItem) && isset($categories[$baseItem])) {
            $categories = [$baseItem => $categories[$baseItem]];
        }

        $bodyData = [];

        $totalTicketsCount = 0;
        $totalResponsesCount = 0;
        $totalClosedCount = 0;
        $totalNeedReplyCount = 0;

        if (is_array($categories) && ! empty($categories)) {
            $dateRange = $this->reportDateRange;
            $startDate = (isset($dateRange['start']) ? $dateRange['start'] : '');
            $endDate = (isset($dateRange['end']) ? $dateRange['end'] : '');

            $categoryIdsStr = "'" . implode("','", array_filter(array_map('absint', array_keys($categories)), function ($id) {
                return ($id && (999999 !== $id));
            })) . "'";

            foreach ($categories as $id => $title) {
                $mainId = absint($id);
                $categoryId = (999999 !== $mainId ? $mainId : 0);
                $categoryTitle = sanitize_text_field($title);
                $categoryQueryStr = $categoryId ? "IN ('{$categoryId}')" : "NOT IN ($categoryIdsStr)";

                $ticketsCountResult = null;
                $responsesCountResult = null;
                $needReplyCountResult = null;
                $closedCountResult = null;

                if ($startDate && $endDate) {
                    $startDate = (10 === strlen($startDate) ? $startDate . ' 00:00:00' : $startDate);
                    $endDate = (10 === strlen($endDate) ? $endDate . ' 23:59:59' : $endDate);

                    $ticketsCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `cat_id` ' . $categoryQueryStr . ' AND `opened_time` BETWEEN %s AND %s AND `status` <> %s', $startDate, $endDate, 'D'), true);
                    $responsesCountResult = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(r.reply_id) AS `reply_count` FROM ' . $ticketTable . ' `t` JOIN ' . $replyTable . ' `r` ON `t`.`id` = `r`.`ticket_id` WHERE `t`.`cat_id` ' . $categoryQueryStr . ' AND `r`.`replied_by_type` = %s AND `r`.`reply_time` BETWEEN %s AND %s', 'A', $startDate, $endDate), true);
                    $closedCountResult = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`cat_id` ' . $categoryQueryStr . ' AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s AND `l`.`entry_time` BETWEEN %s AND %s', 'C', 'A', $startDate, $endDate), true);
                } else {
                    $ticketsCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `cat_id` ' . $categoryQueryStr . ' AND `status` <> %s', 'D'), true);
                    $responsesCountResult = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(r.reply_id) AS `reply_count` FROM ' . $ticketTable . ' `t` JOIN ' . $replyTable . ' `r` ON `t`.`id` = `r`.`ticket_id` WHERE `t`.`cat_id` ' . $categoryQueryStr . ' AND `r`.`replied_by_type` = %s', 'A'), true);
                    $closedCountResult = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`cat_id` ' . $categoryQueryStr . ' AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s', 'C', 'A'), true);
                }

                $needReplyCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `cat_id` ' . $categoryQueryStr . ' AND `status` NOT IN(%s, %s, %s) AND (`last_replied_by_type` = %s OR `last_replied_by` = ' . $ticketTable . '.`ticket_user`)', 'C', 'D', 'I', 'U'), true);

                $ticketsCount = (is_array($ticketsCountResult) && ! empty($ticketsCountResult) ? absint(array_values($ticketsCountResult[0])[0]) : 0);
                $responsesCount = (is_array($responsesCountResult) && ! empty($responsesCountResult) ? absint(array_values($responsesCountResult[0])[0]) : 0);
                $needReplyCount = (is_array($needReplyCountResult) && ! empty($needReplyCountResult) ? absint(array_values($needReplyCountResult[0])[0]) : 0);
                $closedCount = (is_array($closedCountResult) && ! empty($closedCountResult) ? absint(array_values($closedCountResult[0])[0]) : 0);

                $totalTicketsCount += $ticketsCount;
                $totalResponsesCount += $responsesCount;
                $totalNeedReplyCount += $closedCount;
                $totalClosedCount += $needReplyCount;

                $bodyData[] = [
                    'id' => $mainId,
                    'title' => $categoryTitle,
                    'tickets' => $ticketsCount,
                    'responses' => $responsesCount,
                    'need_reply' => $needReplyCount,
                    'closed' => $closedCount,
                ];
            }
        }

        $totalData = [
            'tickets' => $totalTicketsCount,
            'responses' => $totalResponsesCount,
            'need_reply' => $totalClosedCount,
            'closed' => $totalNeedReplyCount,
        ];

        return [
            'head' => [
                'title' => $this->__('Category'),
                'tickets' => $this->__('Tickets'),
                'responses' => $this->__('Responses'),
                'need_reply' => $this->__('Need Reply'),
                'closed' => $this->__('Closed'),
            ],
            'body' => $bodyData,
            'total' => $totalData,
            'cols' => 5,
        ];
    }

    public function GetAgentData()
    {
        global $wpdb;

        $ticketObj = new Mapbd_wps_ticket();
        $ticketTable = $ticketObj->GetTableName();

        $replyObj = new Mapbd_wps_ticket_reply();
        $replyTable = $replyObj->GetTableName();

        $logObj = new Mapbd_wps_ticket_log();
        $logTable = $logObj->GetTableName();

        $baseItem = absint($this->reportBaseItem);
        $agents = $this->GetAgents();

        if (! empty($baseItem) && isset($agents[$baseItem])) {
            $agents = [$baseItem => $agents[$baseItem]];
        }

        $bodyData = [];

        $totalTicketsCount = 0;
        $totalResponsesCount = 0;
        $totalNeedReplyCount = 0;
        $totalClosedCount = 0;

        if (is_array($agents) && ! empty($agents)) {
            $dateRange = $this->reportDateRange;
            $startDate = (isset($dateRange['start']) ? $dateRange['start'] : '');
            $endDate = (isset($dateRange['end']) ? $dateRange['end'] : '');

            $agentIdsStr = "'" . implode("','", array_filter(array_map('absint', array_keys($agents)), function ($id) {
                return ($id && (999999 !== $id));
            })) . "'";

            foreach ($agents as $id => $title) {
                $mainId = absint($id);
                $agentId = (999999 !== $mainId ? $mainId : 0);
                $agentTitle = sanitize_text_field($title);
                $agentQueryStr = $agentId ? "IN ('{$agentId}')" : "NOT IN ($agentIdsStr)";

                $ticketsCountResult = null;
                $responsesCountResult = null;
                $needReplyCountResult = null;
                $closedCountResult = null;

                if ($startDate && $endDate) {
                    $startDate = (10 === strlen($startDate) ? $startDate . ' 00:00:00' : $startDate);
                    $endDate = (10 === strlen($endDate) ? $endDate . ' 23:59:59' : $endDate);

                    $ticketsCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `assigned_on` ' . $agentQueryStr . ' AND `opened_time` BETWEEN %s AND %s AND `status` <> %s', $startDate, $endDate, 'D'), true);
                    $responsesCountResult = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $replyTable . ' WHERE `replied_by_type` = %s AND `replied_by` ' . $agentQueryStr . ' AND `reply_time` BETWEEN %s AND %s', 'A', $startDate, $endDate), true);
                    $closedCountResult = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`assigned_on` ' . $agentQueryStr . ' AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s AND `l`.`entry_time` BETWEEN %s AND %s', 'C', 'A', $startDate, $endDate), true);
                } else {
                    $ticketsCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `assigned_on` ' . $agentQueryStr . ' AND `status` <> %s', 'D'), true);
                    $responsesCountResult = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $replyTable . ' WHERE `replied_by_type` = %s AND `replied_by` ' . $agentQueryStr, 'A'), true);
                    $closedCountResult = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`assigned_on` ' . $agentQueryStr . ' AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s', 'C', 'A'), true);
                }

                $needReplyCountResult = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `assigned_on` ' . $agentQueryStr . ' AND `status` NOT IN(%s, %s, %s) AND (`last_replied_by_type` = %s OR `last_replied_by` = ' . $ticketTable . '.`ticket_user`)', 'C', 'D', 'I', 'U'), true);

                $ticketsCount = (is_array($ticketsCountResult) && ! empty($ticketsCountResult) ? absint(array_values($ticketsCountResult[0])[0]) : 0);
                $responsesCount = (is_array($responsesCountResult) && ! empty($responsesCountResult) ? absint(array_values($responsesCountResult[0])[0]) : 0);
                $needReplyCount = (is_array($needReplyCountResult) && ! empty($needReplyCountResult) ? absint(array_values($needReplyCountResult[0])[0]) : 0);
                $closedCount = (is_array($closedCountResult) && ! empty($closedCountResult) ? absint(array_values($closedCountResult[0])[0]) : 0);

                $totalTicketsCount += $ticketsCount;
                $totalResponsesCount += $responsesCount;
                $totalNeedReplyCount += $closedCount;
                $totalClosedCount += $needReplyCount;

                $bodyData[] = [
                    'id' => $mainId,
                    'title' => $agentTitle,
                    'tickets' => $ticketsCount,
                    'responses' => $responsesCount,
                    'need_reply' => $needReplyCount,
                    'closed' => $closedCount,
                ];
            }
        }

        $totalData = [
            'tickets' => $totalTicketsCount,
            'responses' => $totalResponsesCount,
            'need_reply' => $totalClosedCount,
            'closed' => $totalNeedReplyCount,
        ];

        return [
            'head' => [
                'title' => $this->__('Agent'),
                'tickets' => $this->__('Tickets'),
                'responses' => $this->__('Responses'),
                'need_reply' => $this->__('Need Reply'),
                'closed' => $this->__('Closed'),
            ],
            'body' => $bodyData,
            'total' => $totalData,
            'cols' => 5,
        ];
    }

    public function GetTotalData($tableData = [])
    {
        $totalData = (isset($tableData['total']) ? $tableData['total'] : []);
        $totalData = wp_parse_args($totalData, [
            'tickets' => 0,
            'responses' => 0,
            'need_reply' => 0,
            'closed' => 0,
        ]);

        return $totalData;
    }

    public function GetChartData()
    {
        global $wpdb;

        $labels = [];
        $datasets = [
            'tickets' => [
                'label' => $this->__('Tickets'),
                'backgroundColor' => '#80b4fd',
                'borderColor' => '#80b4fd',
            ],
            'responses' => [
                'label' => $this->__('Responses'),
                'backgroundColor' => '#80e6e6',
                'borderColor' => '#80e6e6',
            ],
            'closed' => [
                'label' => $this->__('Closed'),
                'backgroundColor' => '#00d25e',
                'borderColor' => '#00d25e',
            ],
        ];

        $ticketsData = [];
        $responsesData = [];
        $closedData = [];

        $rangeDates = $this->GetRangeDates();

        foreach ($rangeDates as $rangeDate) {
            $start = $rangeDate['start'];
            $end = $rangeDate['end'];
            $label = $rangeDate['label'];

            if (! $start || ! $end || ! $label) {
                continue;
            }

            $base = $this->reportBase;
            $baseItem = $this->reportBaseItem;

            $ticketObj = new Mapbd_wps_ticket();
            $ticketTable = $ticketObj->GetTableName();

            $replyObj = new Mapbd_wps_ticket_reply();
            $replyTable = $replyObj->GetTableName();

            $logObj = new Mapbd_wps_ticket_log();
            $logTable = $logObj->GetTableName();

            $tickets = 0;
            $responses = 0;
            $closed = 0;

            if (! empty($baseItem)) {
                $baseItem = (999999 !== $baseItem ? $baseItem : 0);

                if ('category' === $base) {
                    $tickets = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `cat_id` = %d AND `opened_time` BETWEEN %s AND %s AND `status` <> %s', $baseItem, $start, $end, 'D'), true);
                    $responses = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(r.reply_id) AS `reply_count` FROM ' . $ticketTable . ' `t` JOIN ' . $replyTable . ' `r` ON `t`.`id` = `r`.`ticket_id` WHERE `r`.`replied_by_type` = %s AND `t`.`cat_id` = %d AND `r`.`reply_time` BETWEEN %s AND %s', 'A', $baseItem, $start, $end), true);
                    $closed = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`cat_id` = %d AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s AND `l`.`entry_time` BETWEEN %s AND %s', $baseItem, 'C', 'A', $start, $end), true);
                } elseif ('agent' === $base) {
                    $tickets = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `assigned_on` = %d AND `opened_time` BETWEEN %s AND %s AND `status` <> %s', $baseItem, $start, $end, 'D'), true);
                    $responses = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $replyTable . ' WHERE `replied_by_type` = %s AND `replied_by` = %d AND `reply_time` BETWEEN %s AND %s', 'A', $baseItem, $start, $end), true);
                    $closed = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(l.log_id) AS `log_count` FROM ' . $ticketTable . ' `t` JOIN ' . $logTable . ' `l` ON `t`.`id` = `l`.`ticket_id` WHERE `t`.`assigned_on` = %d AND `l`.`ticket_status` = %s AND `l`.`log_by_type` = %s AND `l`.`entry_time` BETWEEN %s AND %s', $baseItem, 'C', 'A', $start, $end), true);
                }
            } else {
                $tickets = $ticketObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $ticketTable . ' WHERE `opened_time` BETWEEN %s AND %s AND `status` <> %s', $start, $end, 'D'), true);
                $responses = $replyObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $replyTable . ' WHERE `replied_by_type` = %s AND `reply_time` BETWEEN %s AND %s', 'A', $start, $end), true);
                $closed = $logObj->SelectQuery($wpdb->prepare('SELECT COUNT(*) FROM ' . $logTable . ' WHERE `ticket_status` = %s AND `entry_time` BETWEEN %s AND %s', 'C', $start, $end), true);
            }

            $labels[] = $label;
            $ticketsData[] = (is_array($tickets) && ! empty($tickets) ? absint(array_values($tickets[0])[0]) : 0);
            $responsesData[] = (is_array($responses) && ! empty($responses) ? absint(array_values($responses[0])[0]) : 0);
            $closedData[] = (is_array($closed) && ! empty($closed) ? absint(array_values($closed[0])[0]) : 0);
        }

        $datasets['tickets']['data'] = $ticketsData;
        $datasets['responses']['data'] = $responsesData;
        $datasets['closed']['data'] = $closedData;

        $datasets = array_values($datasets);

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function CleanExportData($data = [])
    {
        $output = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $output[$key] = $this->CleanExportData($value);
                continue;
            }

            $value = sanitize_text_field($value);
            $value = str_replace('"', '""', $value);

            $output[$key] = $value;
        }

        return $output;
    }
}
