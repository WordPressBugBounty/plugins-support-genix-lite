<?php

/**
 * Ticket details.
 */

defined('ABSPATH') || exit;

class Mapbd_wps_ticket_details
{
    public $user = null;
    public $ticket = null;
    public $replies = [];
    public $custom_fields = [];
    public $user_custom_fields = [];
    public $cannedMsg = [];
    public $attached_files = [];
    public $order_details = [];
    public $edd_orders = [];
    public $wc_orders = [];
    public $envato_items = [];
    public $tutorlms_items = [];
    public $user_tickets = [];
    public $notes = [];
    public $logs = [];
    public $hotlink = '';
    public $pop_notices = [];
}
