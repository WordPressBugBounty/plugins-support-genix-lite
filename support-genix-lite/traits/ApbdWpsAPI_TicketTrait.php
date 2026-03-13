<?php

/**
 * Ticket API Trait.
 */

defined('ABSPATH') || exit;

trait ApbdWpsAPI_TicketTrait
{
    function ticket_details__dashboard($data)
    {
        $obj = ApbdWps_SupportLite::GetInstance();
        $ticketId = isset($data['ticketId']) ? absint($data['ticketId']) : 0;

        $this->SetResponse(false, $obj->__('Invalid request.'));

        if (empty($ticketId)) {
            return $this->response;
        }

        $user_id = 0;
        $current_user_id = $this->get_current_user_id();

        if (!Apbd_wps_settings::isAgentLoggedIn()) {
            $user = wp_get_current_user();
            $user_id = isset($user->ID) ? absint($user->ID) : 0;

            if (empty($user_id)) {
                return $this->response;
            }
        }

        $ticketObj = Mapbd_wps_ticket::getTicketDetails__dashboard($ticketId, $user_id);

        if (empty($ticketObj)) {
            return $this->response;
        }

        Mapbd_wps_notification::SetSeenNotification($ticketId, $current_user_id);

        if (!current_user_can('show-ticket-email')) {
            $ticketObj->user->email = '';
        }

        $hotlinkDisabled = Apbd_wps_settings::GetModuleOption('disable_ticket_hotlink', 'N');

        if (!current_user_can('show-ticket-hotlink') || ('Y' === $hotlinkDisabled)) {
            $ticketObj->hotlink = '';
        }

        $this->SetResponse(true, '', $ticketObj);

        return $this->response;
    }

    function ticket_details__portal($data)
    {
        $obj = ApbdWps_SupportLite::GetInstance();
        $ticketId = isset($data['ticketId']) ? absint($data['ticketId']) : 0;

        $this->SetResponse(false, $obj->__('Invalid request.'));

        if (empty($ticketId)) {
            return $this->response;
        }

        $user_id = 0;
        $current_user_id = $this->get_current_user_id();

        if (!Apbd_wps_settings::isAgentLoggedIn()) {
            $user = wp_get_current_user();
            $user_id = isset($user->ID) ? absint($user->ID) : 0;

            if (empty($user_id)) {
                return $this->response;
            }
        }

        $ticketObj = Mapbd_wps_ticket::getTicketDetails__portal($ticketId, $user_id);

        if (empty($ticketObj)) {
            return $this->response;
        }

        Mapbd_wps_notification::SetSeenNotification($ticketId, $current_user_id);

        $isAgent = Apbd_wps_settings::isAgentLoggedIn();

        if ($isAgent && !current_user_can('show-ticket-email')) {
            $ticketObj->user->email = '';
        }

        $hotlinkDisabled = Apbd_wps_settings::GetModuleOption('disable_ticket_hotlink', 'N');

        if (!$isAgent || !current_user_can('show-ticket-hotlink') || ('Y' === $hotlinkDisabled)) {
            $ticketObj->hotlink = '';
        }

        $this->SetResponse(true, '', $ticketObj);

        return $this->response;
    }
}
