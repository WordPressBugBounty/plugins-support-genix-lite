<?php

/**
 * Corn jobs.
 */

defined('ABSPATH') || exit;

class Apbd_Wps_CornJobs extends ApbdWpsModel
{
    function __construct()
    {
        $this->cronSchedules();
        $this->createSchedules();
        $this->createActions();
    }

    function cronSchedules()
    {
        add_filter('cron_schedules', function ($schedules = array()) {
            if (!is_array($schedules)) {
                $schedules = [];
            }

            $items = [
                [
                    'recurrence' => 'support_genix_five_minutes',
                    'interval' => (5 * MINUTE_IN_SECONDS),
                    'display' => $this->__('Every Five Minutes'),
                ],
                [
                    'recurrence' => 'support_genix_twicedaily',
                    'interval' => (12 * HOUR_IN_SECONDS),
                    'display' => $this->__('Twice Daily'),
                ],
            ];

            foreach ($items as $item) {
                $recurrence = $item['recurrence'];
                $interval = $item['interval'];
                $display = $item['display'];

                if (!isset($schedules[$recurrence])) {
                    $schedules[$recurrence] = array(
                        'interval' => $interval,
                        'display' => $display
                    );
                }
            }

            return $schedules;
        }, 20);
    }

    function createSchedules()
    {
        $timestamp  = time();

        $items = [
            [
                'recurrence' => 'support_genix_five_minutes',
                'hook' => 'support_genix_scheduled_five_minutes_tasks',
            ],
            [
                'recurrence' => 'support_genix_twicedaily',
                'hook' => 'support_genix_scheduled_twicedaily_tasks',
            ],
        ];

        foreach ($items as $item) {
            $recurrence = $item['recurrence'];
            $hook = $item['hook'];

            if (!wp_next_scheduled($hook)) {
                wp_schedule_event($timestamp, $recurrence, $hook);
            }

            if (false !== get_option($hook)) {
                delete_option($hook);
            }
        }
    }

    function createActions()
    {
        add_action('support_genix_scheduled_twicedaily_tasks', array($this, 'performTwicedailyTasks'));
    }

    function performTwicedailyTasks()
    {
        $this->autoChatbotCleanup();
    }

    /**
     * Auto cleanup old chatbot conversations based on storage settings.
     */
    function autoChatbotCleanup()
    {
        // Get knowledge_base module settings
        $kb_module = Apbd_wps_knowledge_base::GetModuleInstance();
        if (!$kb_module) {
            return;
        }

        // Get all cleanup-related settings
        $retention_period = $kb_module->GetModuleOption('chatbot_retention_period', 'forever');
        $auto_cleanup = $kb_module->GetModuleOption('chatbot_auto_cleanup', 'N');
        $cleanup_days = absint($kb_module->GetModuleOption('chatbot_cleanup_days', 30));

        // Convert retention period to days
        $retention_days = null;
        switch ($retention_period) {
            case '7days':
                $retention_days = 7;
                break;
            case '30days':
                $retention_days = 30;
                break;
            case '90days':
                $retention_days = 90;
                break;
            case 'forever':
            default:
                $retention_days = null; // No retention limit
                break;
        }

        // Determine the effective cleanup days
        // If retention period is set (not forever), use it
        // If auto-cleanup is also enabled, use the shorter of the two
        $effective_days = null;

        if ($retention_days !== null) {
            $effective_days = $retention_days;
        }

        if ('Y' === $auto_cleanup && $cleanup_days > 0) {
            if ($effective_days === null) {
                $effective_days = $cleanup_days;
            } else {
                // Use the shorter period (more restrictive)
                $effective_days = min($effective_days, $cleanup_days);
            }
        }

        // If no cleanup is needed, return
        if ($effective_days === null) {
            return;
        }

        $effective_days = max(1, $effective_days); // Minimum 1 day

        // Delete old history records
        Mapbd_wps_chatbot_history::deleteOlderThan($effective_days);

        // Delete old session records
        Mapbd_wps_chatbot_session::deleteOlderThan($effective_days);
    }
}
