<?php

/**
 * Report email.
 */

defined('ABSPATH') || exit;

class Apbd_wps_report_email extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
    }

    public function OnInit()
    {
        parent::OnInit();
        add_action('support_genix_scheduled_five_minutes_tasks', array($this, 'SendReportEmail'));
    }

    public function data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $status = $this->GetOption('status', 'I');
        $frequency = $this->GetOption('frequency', 'daily');
        $weak_day = $this->GetOption('weak_day', 1);
        $month_day = $this->GetOption('month_day', 1);
        $day_time = $this->GetOption('day_time', '00:00');
        $custom_minutes = $this->GetOption('custom_minutes', 5);
        $recipients = $this->GetOption('recipients', get_bloginfo('admin_email'));

        $status = ('A' === $status) ? true : false;

        $data = [
            'status' => $status,
            'frequency' => $frequency,
            'weak_day' => $weak_day,
            'month_day' => $month_day,
            'day_time' => $day_time,
            'custom_minutes' => $custom_minutes,
            'recipients' => $recipients,
        ];

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function AjaxRequestCallback()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));
        $beforeSave = $this->options;

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $status = sanitize_text_field(ApbdWps_PostValue('status', ''));

            if ('A' === $status) {
                $frequency = sanitize_text_field(ApbdWps_PostValue('frequency', ''));
                $weak_day = absint(ApbdWps_PostValue('weak_day', ''));
                $month_day = absint(ApbdWps_PostValue('month_day', ''));
                $day_time = sanitize_text_field(ApbdWps_PostValue('day_time', ''));
                $custom_minutes = absint(ApbdWps_PostValue('custom_minutes', ''));
                $recipients = sanitize_text_field(ApbdWps_PostValue('recipients', ''));

                $weak_day = max(1, min(intval($weak_day), 7));
                $month_day = max(1, min(intval($month_day), 31));
                $custom_minutes = max(5, min(intval($custom_minutes), 60));

                $day_time = !empty($day_time) ? $day_time : '00:00';

                $recipients = explode(',', $recipients);
                $recipients = array_map('sanitize_email', $recipients);
                $recipients = array_filter($recipients, function ($recipient) {
                    return !empty($recipient);
                });
                $recipients = implode(',', array_unique($recipients));

                if (
                    (1 > strlen($frequency)) ||
                    (1 > strlen($recipients))
                ) {
                    $hasError = true;
                }

                if ('weekly' !== $frequency) {
                    $weak_day = $this->GetOption('weak_day', 1);
                }

                if ('monthly' !== $frequency) {
                    $month_day = $this->GetOption('month_day', 1);
                }

                if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
                    $day_time = $this->GetOption('day_time', '00:00');
                }

                if ('custom' !== $frequency) {
                    $custom_minutes = $this->GetOption('custom_minutes', 5);
                }

                $this->AddIntoOption('status', 'A');
                $this->AddIntoOption('frequency', $frequency);
                $this->AddIntoOption('weak_day', $weak_day);
                $this->AddIntoOption('month_day', $month_day);
                $this->AddIntoOption('day_time', $day_time);
                $this->AddIntoOption('custom_minutes', $custom_minutes);
                $this->AddIntoOption('recipients', $recipients);
            } else {
                $this->AddIntoOption('status', 'I');
            }

            if (!$hasError) {
                if ($beforeSave !== $this->options) {
                    if ($this->UpdateOption()) {
                        $apiResponse->SetResponse(true, $this->__('Saved Successfully'));
                    } else {
                        $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Nothing to save.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function SendReportEmail()
    {
        $coreObject = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($coreObject->pluginFile));

        $status = $this->GetOption('status', 'I');

        if ('A' !== $status) {
            return;
        }

        $recipients = $this->GetOption('recipients', '');
        $recipients = explode(',', $recipients);
        $recipients = array_map('sanitize_email', $recipients);
        $recipients = array_filter($recipients, function ($recipient) {
            return !empty($recipient);
        });
        $recipients = array_unique($recipients);

        if (empty($recipients)) {
            return;
        }

        $shouldSend = $this->GetReportShouldSend();

        if (!$shouldSend) {
            return;
        }

        $dateRange = $this->GetReportDateRange();
        $dateFormat = get_option('date_format') . ' ' . get_option('time_format');

        $dateStart = $dateRange['start'];
        $dateEnd = $dateRange['end'];
        $dateRangeStr = $dateStart . '-to-' . $dateEnd;

        $dateStartLocal = wp_date($dateFormat, strtotime($dateStart));
        $dateEndLocal = wp_date($dateFormat, strtotime($dateEnd));
        $localDateRangeStr = sprintf('%s - %s', $dateStartLocal, $dateEndLocal);

        $reportObj = new Apbd_wps_report($coreObject->pluginBaseName, $coreObject);

        $categoryData = $reportObj->GetReportData([
            'base' => 'category',
            'baseItem' => 0,
            'dateRange' => $dateRangeStr,
        ]);

        $agentData = $reportObj->GetReportData([
            'base' => 'agent',
            'baseItem' => 0,
            'dateRange' => $dateRangeStr,
        ]);

        $subject = $this->__('Support Performance Insights') . ' | ' . $localDateRangeStr;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $content = '';

        if ($categoryData && $agentData) {
            ob_start();
            include_once $pluginPath . '/views/report_email/main.php';
            $content = ob_get_clean();
        }

        if (empty($content)) {
            return;
        }

        $success = false;

        foreach ($recipients as $toEmail) {
            $sent = wp_mail($toEmail, $subject, $content, $headers);

            if ($sent) {
                $success = true;
            }
        }

        if ($success) {
            $this->AddIntoOption('last_sent', current_time('mysql', true));
            $this->UpdateOption();
        }
    }

    private function GetReportShouldSend()
    {
        $frequency = $this->GetOption('frequency', 'daily');
        $weak_day = $this->GetOption('weak_day', 1);
        $month_day = $this->GetOption('month_day', 1);
        $day_time = $this->GetOption('day_time', '00:00');
        $custom_minutes = $this->GetOption('custom_minutes', 5);
        $last_sent_time = $this->GetOption('last_sent', '');

        if (empty($last_sent_time)) {
            return true;
        }

        $weak_day = max(1, min(intval($weak_day), 7));
        $month_day = max(1, min(intval($month_day), 31));
        $custom_minutes = max(5, min(intval($custom_minutes), 60));

        $current_time = current_time('mysql');
        $current_timestamp = strtotime($current_time);

        $last_sent_time = get_date_from_gmt($last_sent_time);
        $last_sent_timestamp = !empty($last_sent_time) ? strtotime($last_sent_time) : 0;

        switch ($frequency) {
            case 'custom':
                $diff_minutes = floor(($current_timestamp - $last_sent_timestamp) / MINUTE_IN_SECONDS);
                $shouldSend = ($diff_minutes >= $custom_minutes);
                break;

            case 'hourly':
                $last_hour = date('Y-m-d H', $last_sent_timestamp);
                $current_hour = date('Y-m-d H', $current_timestamp);
                $shouldSend = ($last_hour !== $current_hour);
                break;

            case 'daily':
                $current_time_parts = explode(':', $day_time);
                $target_hour = isset($current_time_parts[0]) ? intval($current_time_parts[0]) : 0;
                $target_minute = isset($current_time_parts[1]) ? intval($current_time_parts[1]) : 0;

                $current_hour = intval(date('H', $current_timestamp));
                $current_minute = intval(date('i', $current_timestamp));

                $last_date = date('Y-m-d', $last_sent_timestamp);
                $current_date = date('Y-m-d', $current_timestamp);

                $shouldSend = (
                    ($last_date !== $current_date) &&
                    (
                        ($current_hour > $target_hour) ||
                        (
                            ($current_hour === $target_hour) &&
                            ($current_minute >= $target_minute)
                        )
                    )
                );
                break;

            case 'weekly':
                $current_week_day = intval(date('N', $current_timestamp));
                $last_week = date('W', $last_sent_timestamp);
                $current_week = date('W', $current_timestamp);

                $time_parts = explode(':', $day_time);
                $target_hour = isset($time_parts[0]) ? intval($time_parts[0]) : 0;
                $target_minute = isset($time_parts[1]) ? intval($time_parts[1]) : 0;

                $current_hour = intval(date('H', $current_timestamp));
                $current_minute = intval(date('i', $current_timestamp));

                $shouldSend = (
                    ($last_week !== $current_week) &&
                    ($current_week_day === intval($weak_day)) &&
                    (
                        ($current_hour > $target_hour) ||
                        (
                            ($current_hour === $target_hour) &&
                            ($current_minute >= $target_minute)
                        )
                    )
                );
                break;

            case 'monthly':
                $current_month_day = intval(date('j', $current_timestamp));
                $last_month = date('Y-m', $last_sent_timestamp);
                $current_month = date('Y-m', $current_timestamp);

                $time_parts = explode(':', $day_time);
                $target_hour = isset($time_parts[0]) ? intval($time_parts[0]) : 0;
                $target_minute = isset($time_parts[1]) ? intval($time_parts[1]) : 0;

                $current_hour = intval(date('H', $current_timestamp));
                $current_minute = intval(date('i', $current_timestamp));

                $shouldSend = (
                    ($last_month !== $current_month) &&
                    ($current_month_day >= intval($month_day)) &&
                    (
                        ($current_hour > $target_hour) ||
                        (
                            ($current_hour === $target_hour) &&
                            ($current_minute >= $target_minute)
                        )
                    )
                );
                break;
        }

        return $shouldSend;
    }

    private function GetReportDateRange()
    {
        $frequency = $this->GetOption('frequency', 'daily');
        $last_sent_time = $this->GetOption('last_sent', '');

        $current_time = current_time('mysql', true);
        $current_timestamp = strtotime($current_time);

        $start_date = $last_sent_time;
        $end_date = $current_time;

        if (empty($last_sent_time)) {
            switch ($frequency) {
                case 'custom':
                case 'hourly':
                    $start_date = date('Y-m-d H:i:s', strtotime('-1 hour', $current_timestamp + 1));
                    break;

                case 'daily':
                    $start_date = date('Y-m-d H:i:s', strtotime('-1 day', $current_timestamp + 1));
                    break;

                case 'weekly':
                    $start_date = date('Y-m-d H:i:s', strtotime('-7 days', $current_timestamp + 1));
                    break;

                case 'monthly':
                    $start_date = date('Y-m-d H:i:s', strtotime('-1 month', $current_timestamp + 1));
                    break;
            }
        }

        return array(
            'start' => $start_date,
            'end' => $end_date
        );
    }
}
