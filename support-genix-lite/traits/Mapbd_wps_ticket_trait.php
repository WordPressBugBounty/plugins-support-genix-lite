<?php

/**
 * Ticket Trait.
 */

defined('ABSPATH') || exit;

trait Mapbd_wps_ticket_trait
{
    static function getTicketDetails__dashboard($ticket_id, $user_id = '')
    {
        $ticketDetailsObj = new Mapbd_wps_ticket_details();
        $ticketObj = new Mapbd_wps_ticket();
        $ticketObj->id($ticket_id);

        if ($ticketObj->Select()) {
            $is_agent_logged_in = Apbd_wps_settings::isAgentLoggedIn();
            $manage_other_agents_ticket = current_user_can('manage-other-agents-ticket');
            $manage_unassigned_ticket = current_user_can('manage-unassigned-ticket');
            $manage_self_created_ticket = current_user_can('manage-self-created-ticket');

            $current_user_id = get_current_user_id();
            $ticket_user = (isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0);
            $ticket_assigned_on = (isset($ticketObj->assigned_on) ? absint($ticketObj->assigned_on) : 0);
            $ticket_opened_by =  (isset($ticketObj->opened_by) ? absint($ticketObj->opened_by) : 0);

            if ($ticketObj->is_public != 'Y') {
                if ($is_agent_logged_in) {
                    if (!$manage_other_agents_ticket && ! empty($ticket_assigned_on) && ($ticket_assigned_on !== $current_user_id)) {
                        if (!$manage_self_created_ticket || ($ticket_opened_by !== $current_user_id)) {
                            if ($ticket_user !== $current_user_id) {
                                return null;
                            }
                        }
                    }

                    if (!$manage_unassigned_ticket && empty($ticket_assigned_on)) {
                        if (!$manage_self_created_ticket || ($ticket_opened_by !== $current_user_id)) {
                            if ($ticket_user !== $current_user_id) {
                                return null;
                            }
                        }
                    }
                }

                if (!empty($user_id) && $ticketObj->ticket_user != $user_id) {
                    return null;
                }
            } elseif (!$is_agent_logged_in) {
                $is_public_tickets_menu = Apbd_wps_settings::GetModuleOption("is_public_tickets_menu", 'N');

                if ('Y' !== $is_public_tickets_menu && !empty($user_id) && $ticketObj->ticket_user != $user_id) {
                    return null;
                }
            }

            // Ticket user
            $user = new WP_User($ticketObj->ticket_user);
            $getUser = new stdClass();
            $getUser->first_name = $user->first_name;
            $getUser->last_name = $user->last_name;
            $getUser->email = $user->user_email;
            $getUser->display_name = ! empty($user->display_name) ? $user->display_name : $user->user_login;
            $getUser->img = get_user_meta($ticketObj->ticket_user, 'supportgenix_avatar', true) ? get_user_meta($ticketObj->ticket_user, 'supportgenix_avatar', true) : get_avatar_url($user->user_email);

            // Assigned on
            $assigned = new WP_User($ticketObj->assigned_on);
            $getAssignedOn = new stdClass();
            $getAssignedOn->first_name = $assigned->first_name;
            $getAssignedOn->last_name = $assigned->last_name;
            $getAssignedOn->display_name = ! empty($assigned->display_name) ? $assigned->display_name : $assigned->user_login;
            $getAssignedOn->img = get_user_meta($ticketObj->assigned_on, 'supportgenix_avatar', true) ? get_user_meta($ticketObj->assigned_on, 'supportgenix_avatar', true) : get_avatar_url($assigned->user_email);

            $ticketObj->ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticketObj->ticket_track_id);
            $ticketObj->cat_obj = Mapbd_wps_ticket_category::FindBy("id", $ticketObj->cat_id, array('status' => 'A'));
            $ticketObj->assigned_on_obj = $ticketObj->assigned_on ? $getAssignedOn : null;

            // Mailbox
            $mailbox_obj = null;
            $ticketObj->mailbox_obj = $mailbox_obj;

            // Tag IDs
            $ticketObj->tag_ids = [];

            $ticketDetailsObj->user = $getUser;
            $ticketDetailsObj->ticket = $ticketObj;
            $ticketDetailsObj->cannedMsg = Mapbd_wps_canned_msg::GetAllCannedMsgBy($ticketObj);
            $reply_obj = new Mapbd_wps_ticket_reply();
            $reply_obj->ticket_id($ticketObj->id);
            $ticketDetailsObj->attached_files = [];
            $ticketDetailsObj->attached_files = apply_filters("apbd-wps/filter/ticket-read-attached-files", $ticketDetailsObj->attached_files, $ticketDetailsObj->ticket);
            $ticketDetailsObj->replies = $reply_obj->SelectAllGridData('', 'reply_time', 'ASC');

            if (! empty($ticketDetailsObj->replies)) {
                $logged_user = wp_get_current_user();

                if (! empty($logged_user)) {
                    if (Apbd_wps_settings::isAgentLoggedIn()) {
                        Mapbd_wps_ticket_reply::SetSeenAllReply($ticketObj->id, 'U');
                    } elseif ($logged_user->ID == $ticketObj->ticket_user) {
                        Mapbd_wps_ticket_reply::SetSeenAllReply($ticketObj->id, 'A');
                    }
                }
            }

            $ticketDetailsObj->custom_fields = apply_filters('apbd-wps/filter/ticket-custom-properties', $ticketDetailsObj->custom_fields, $ticketObj->id);
            $ticketDetailsObj->custom_fields = apply_filters('apbd-wps/filter/ticket-details-custom-properties', $ticketDetailsObj->custom_fields, $ticketObj->id);
            $ticketDetailsObj->user_custom_fields = apply_filters('apbd-wps/filter/user-custom-properties', [], $ticketObj->ticket_user);
            $ticketDetailsObj->notes = Mapbd_wps_notes::getAllNotesBy($ticketObj->id, false);

            foreach ($ticketDetailsObj->replies as &$reply) {
                $reply->reply_text = ApbdWps_KsesEmailHtml($reply->reply_text);
                $rep_user = new WP_User($reply->replied_by);
                $reUser = new stdClass();
                $reUser->first_name = $rep_user->first_name;
                $reUser->last_name = $rep_user->last_name;
                $reUser->display_name = ! empty($rep_user->display_name) ? $rep_user->display_name : $rep_user->user_login;
                $reUser->img = get_user_meta($rep_user->ID, 'supportgenix_avatar', true) ? get_user_meta($rep_user->ID, 'supportgenix_avatar', true) : get_avatar_url($rep_user->ID);
                $reply->reply_user = $reUser;
                $reply->attached_files = [];
                $reply->attached_files = apply_filters("apbd-wps/filter/reply-read-attached-files", $reply->attached_files, $reply);
            }

            $ticketDetailsObj->logs = Mapbd_wps_ticket_log::getAllLogsBy($ticketObj->id, false);
            if ('Y' === Apbd_wps_woocommerce::GetModuleOption('collect_order_info', 'N')) {
                $ticketDetailsObj->order_details = self::getOrderDetails__dashboard($ticketObj);
                $ticketDetailsObj->wc_orders = array('valid' => false);
            } else {
                $ticketDetailsObj->order_details = array('valid' => false);
                $ticketDetailsObj->wc_orders = Apbd_wps_woocommerce::GetPurchasedOrders($ticketObj);
            }
            $ticketDetailsObj->envato_items = self::getEnvatoItems__dashboard($ticketObj);
            $ticketDetailsObj->tutorlms_items = array();
            $ticketDetailsObj->edd_orders = array('valid' => false);
            $ticketDetailsObj->user_tickets = self::getUserTickets__dashboard($ticketObj);
            $ticketDetailsObj->hotlink = '';

            return apply_filters('apbd-wps/filter/before-get-a-ticket-details', $ticketDetailsObj);
        } else {
            return null;
        }
    }

    static function getTicketDetails__portal($ticket_id, $user_id = '')
    {
        $logged_user = wp_get_current_user();

        if (empty($logged_user)) {
            return null;
        }

        $isAgent = Apbd_wps_settings::isAgentLoggedIn();

        $ticketDetailsObj = new Mapbd_wps_ticket_details();
        $ticketObj = new Mapbd_wps_ticket();
        $ticketObj->id($ticket_id);

        if ($ticketObj->Select()) {
            $manage_other_agents_ticket = current_user_can('manage-other-agents-ticket');
            $manage_unassigned_ticket = current_user_can('manage-unassigned-ticket');
            $manage_self_created_ticket = current_user_can('manage-self-created-ticket');

            $current_user_id = get_current_user_id();
            $ticket_user = (isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0);
            $ticket_assigned_on = (isset($ticketObj->assigned_on) ? absint($ticketObj->assigned_on) : 0);
            $ticket_opened_by =  (isset($ticketObj->opened_by) ? absint($ticketObj->opened_by) : 0);

            if ($ticketObj->is_public != 'Y') {
                if ($isAgent) {
                    if (!$manage_other_agents_ticket && ! empty($ticket_assigned_on) && ($ticket_assigned_on !== $current_user_id)) {
                        if (!$manage_self_created_ticket || ($ticket_opened_by !== $current_user_id)) {
                            if ($ticket_user !== $current_user_id) {
                                return null;
                            }
                        }
                    }

                    if (!$manage_unassigned_ticket && empty($ticket_assigned_on)) {
                        if (!$manage_self_created_ticket || ($ticket_opened_by !== $current_user_id)) {
                            if ($ticket_user !== $current_user_id) {
                                return null;
                            }
                        }
                    }
                }

                if (!empty($user_id) && $ticketObj->ticket_user != $user_id) {
                    return null;
                }
            } elseif (!$isAgent) {
                $is_public_tickets_menu = Apbd_wps_settings::GetModuleOption("is_public_tickets_menu", 'N');

                if ('Y' !== $is_public_tickets_menu && !empty($user_id) && $ticketObj->ticket_user != $user_id) {
                    return null;
                }
            }

            // Ticket user
            $user = new WP_User($ticketObj->ticket_user);
            $getUser = new stdClass();
            $getUser->first_name = $user->first_name;
            $getUser->last_name = $user->last_name;

            if ($isAgent) {
                $getUser->email = $user->user_email;
            } else {
                $getUser->email = (strval($ticketObj->ticket_user) === strval($logged_user->ID)) ? $user->user_email : '';
            }

            $getUser->display_name = ! empty($user->display_name) ? $user->display_name : $user->user_login;
            $getUser->img = get_user_meta($ticketObj->ticket_user, 'supportgenix_avatar', true) ? get_user_meta($ticketObj->ticket_user, 'supportgenix_avatar', true) : get_avatar_url($user->user_email);

            // Assigned on
            if ($isAgent) {
                $assigned = new WP_User($ticketObj->assigned_on);
                $getAssignedOn = new stdClass();
                $getAssignedOn->first_name = $assigned->first_name;
                $getAssignedOn->last_name = $assigned->last_name;
                $getAssignedOn->display_name = ! empty($assigned->display_name) ? $assigned->display_name : $assigned->user_login;
                $getAssignedOn->img = get_user_meta($ticketObj->assigned_on, 'supportgenix_avatar', true) ? get_user_meta($ticketObj->assigned_on, 'supportgenix_avatar', true) : get_avatar_url($assigned->user_email);
            }

            // Mailbox
            $mailbox_obj = null;
            $ticketObj->mailbox_obj = $mailbox_obj;

            $ticketObj->ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticketObj->ticket_track_id);
            $ticketObj->cat_obj = Mapbd_wps_ticket_category::FindBy("id", $ticketObj->cat_id);

            if ($isAgent) {
                $ticketObj->assigned_on_obj = $ticketObj->assigned_on ? $getAssignedOn : null;
            } else {
                $ticketObj->assigned_on_obj = null;
            }

            // Tag IDs
            if ($isAgent) {
                $ticketObj->tag_ids = [];
            }

            $ticketDetailsObj->user = $getUser;
            $ticketDetailsObj->ticket = $ticketObj;

            if ($isAgent) {
                $ticketDetailsObj->cannedMsg = Mapbd_wps_canned_msg::GetAllCannedMsgBy($ticketObj);
            } else {
                $ticketDetailsObj->cannedMsg = [];
            }

            $reply_obj = new Mapbd_wps_ticket_reply();
            $reply_obj->ticket_id($ticketObj->id);
            $ticketDetailsObj->attached_files = [];
            $ticketDetailsObj->attached_files = apply_filters("apbd-wps/filter/ticket-read-attached-files", $ticketDetailsObj->attached_files, $ticketDetailsObj->ticket);
            $ticketDetailsObj->replies = $reply_obj->SelectAllGridData('', 'reply_time', 'ASC');

            if (! empty($ticketDetailsObj->replies)) {
                if (! empty($logged_user)) {
                    if (Apbd_wps_settings::isAgentLoggedIn()) {
                        Mapbd_wps_ticket_reply::SetSeenAllReply($ticketObj->id, 'U');
                    } elseif ($logged_user->ID == $ticketObj->ticket_user) {
                        Mapbd_wps_ticket_reply::SetSeenAllReply($ticketObj->id, 'A');
                    }
                }
            }

            $ticketDetailsObj->custom_fields = apply_filters('apbd-wps/filter/ticket-custom-properties', $ticketDetailsObj->custom_fields, $ticketObj->id);
            $ticketDetailsObj->custom_fields = apply_filters('apbd-wps/filter/ticket-details-custom-properties', $ticketDetailsObj->custom_fields, $ticketObj->id);

            if ($isAgent) {
                $ticketDetailsObj->user_custom_fields = apply_filters('apbd-wps/filter/user-custom-properties', [], $ticketObj->ticket_user);
                $ticketDetailsObj->notes = Mapbd_wps_notes::getAllNotesBy($ticketObj->id, false);
            } else {
                $ticketDetailsObj->notes = [];
            }

            foreach ($ticketDetailsObj->replies as &$reply) {
                $reply->reply_text = ApbdWps_KsesEmailHtml($reply->reply_text);
                $rep_user = new WP_User($reply->replied_by);
                $reUser = new stdClass();
                $reUser->first_name = $rep_user->first_name;
                $reUser->last_name = $rep_user->last_name;
                $reUser->display_name = ! empty($rep_user->display_name) ? $rep_user->display_name : $rep_user->user_login;
                $reUser->img = get_user_meta($rep_user->ID, 'supportgenix_avatar', true) ? get_user_meta($rep_user->ID, 'supportgenix_avatar', true) : get_avatar_url($rep_user->ID);
                $reply->reply_user = $reUser;
                $reply->attached_files = [];
                $reply->attached_files = apply_filters("apbd-wps/filter/reply-read-attached-files", $reply->attached_files, $reply);
            }

            if ($isAgent) {
                $ticketDetailsObj->logs = Mapbd_wps_ticket_log::getAllLogsBy($ticketObj->id, false);
                $ticketDetailsObj->envato_items = self::getEnvatoItems__dashboard($ticketObj);
                $ticketDetailsObj->user_tickets = self::getUserTickets__dashboard($ticketObj);
            } else {
                $ticketDetailsObj->logs = [];
                $ticketDetailsObj->envato_items = [];
                $ticketDetailsObj->user_tickets = [];
            }

            if ('Y' === Apbd_wps_woocommerce::GetModuleOption('collect_order_info', 'N')) {
                $ticketDetailsObj->order_details = self::getOrderDetails__dashboard($ticketObj);
                $ticketDetailsObj->wc_orders = ['valid' => false];
            } else {
                $ticketDetailsObj->order_details = ['valid' => false];
                $ticketDetailsObj->wc_orders = Apbd_wps_woocommerce::GetPurchasedOrders($ticketObj);
            }
            $ticketDetailsObj->tutorlms_items = [];
            $ticketDetailsObj->edd_orders = ['valid' => false];
            $ticketDetailsObj->hotlink = '';

            return apply_filters('apbd-wps/filter/before-get-a-ticket-details', $ticketDetailsObj);
        } else {
            return null;
        }
    }

    private static function getUserTickets__dashboard($ticketObj)
    {
        $is_agent = Apbd_wps_settings::isAgentLoggedIn();
        $agent_id = get_current_user_id();

        if (! $is_agent || empty($agent_id)) {
            return [];
        }

        $ticket_id = (isset($ticketObj->id) ? absint($ticketObj->id) : 0);
        $user_id = (isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0);

        if (empty($ticket_id) || empty($user_id)) {
            return [];
        }

        $manage_other = current_user_can('manage-other-agents-ticket');
        $manage_unassigned = current_user_can('manage-unassigned-ticket');
        $manage_self_created = current_user_can('manage-self-created-ticket');

        $mainobj = new Mapbd_wps_ticket();
        $mainobj->id("!={$ticket_id}", true);
        $mainobj->status("!='D'", true);
        $mainobj->ticket_user($user_id);

        $manage_self_condition = $manage_self_created ? " OR `opened_by`={$agent_id}" : "";
        $manage_self_condition .= " OR `ticket_user`={$agent_id}";

        if (! $manage_other && ! $manage_unassigned) {
            $mainobj->assigned_on("={$agent_id}{$manage_self_condition}", true);
        } elseif ($manage_other && ! $manage_unassigned) {
            $mainobj->assigned_on("NOT IN ('','0'){$manage_self_condition}", true);
        } elseif (! $manage_other && $manage_unassigned) {
            $mainobj->assigned_on("IN ($agent_id,'','0'){$manage_self_condition}", true);
        }

        $tickets = $mainobj->SelectAllGridData('id,title,status,ticket_user,last_replied_by,last_replied_by_type,priority', 'id', 'desc');
        $tickets = is_array($tickets) ? $tickets : [];

        return $tickets;
    }

    private static function getOrderDetails__dashboard($ticketObj)
    {
        $ticket_id = absint($ticketObj->id);
        $order_details = array('valid' => false);

        $order_info = apply_filters('apbd-wps/filter/ticket-order-info', array(), $ticket_id);

        if (is_array($order_info) && ! empty($order_info)) {
            $same_site = (isset($order_info['same_site']) ? rest_sanitize_boolean($order_info['same_site']) : true);
            $order_data = (isset($order_info['order_data']) ? $order_info['order_data'] : null);

            if (is_object($order_data) && ! empty($order_data)) {
                if (true === $same_site) {
                    $order_details = self::getSameSiteOrderDetails__dashboard($order_data);
                }
            }
        }

        return $order_details;
    }

    private static function getSameSiteOrderDetails__dashboard($order_data)
    {
        $order_details = array('valid' => false);

        if (! empty($order_data)) {
            $products = array();
            $shippings = array();
            $payments = array();
            $totals = array();
            $others = array();
            $more_link = '';
            $valid = true;

            $obj = ApbdWps_SupportLite::GetInstance();

            $order_id = $order_data->get_id();
            $order_key = $order_data->get_order_key();

            $order_statuses = call_user_func('wc_get_order_statuses');
            $order_status = $order_data->get_status();
            $order_status = (isset($order_status) ? sprintf('wc-%1$s', $order_status) : '');
            $order_status = (isset($order_statuses[$order_status]) ? $order_statuses[$order_status] : $order_status);

            $date_created = $order_data->get_date_created();
            $date_created = wp_date(get_option('date_format'), strtotime($date_created));

            $currency = $order_data->get_currency();
            $currency_symbol = call_user_func('get_woocommerce_currency_symbol', $currency);

            $line_items = $order_data->get_items('line_item');

            $total = $order_data->get_total();
            $total_discount = $order_data->get_total_discount();
            $subtotal = $order_data->get_subtotal();

            $shipping_lines = $order_data->get_items('shipping');
            $payment_method_title = $order_data->get_payment_method_title();
            $needs_payment = $order_data->needs_payment();

            $more_link = admin_url('post.php?post=' . $order_id . '&action=edit');

            if (is_array($line_items) && ! empty($line_items)) {
                foreach ($line_items as $line_item) {
                    $line_item_name = $line_item->get_name();
                    $line_item_quantity = $line_item->get_quantity();
                    $line_item_subtotal = $line_item->get_subtotal();
                    $line_item_meta_data = $line_item->get_meta();

                    if (! empty($line_item_meta_data)) {
                        $line_item_meta = '';

                        foreach ($line_item_meta_data as $line_item_meta_datum) {
                            $display_key = (isset($line_item_meta_datum->display_key) ? $line_item_meta_datum->display_key : '');
                            $display_value = (isset($line_item_meta_datum->display_value) ? $line_item_meta_datum->display_value : '');

                            if (0 === strpos($display_key, '_')) {
                                continue;
                            }

                            $line_item_meta .= (! empty($line_item_meta) ? ', ' : '');
                            $line_item_meta .= sprintf('%1$s: %2$s', $display_key, $display_value);
                        }

                        if (! empty($line_item_meta)) {
                            $line_item_name = sprintf('%1$s (%2$s)', $line_item_name, $line_item_meta);
                        }
                    }

                    $product = sprintf('%1$s x %2$s &mdash; %3$s%4$s', $line_item_name, $line_item_quantity, $currency_symbol, $line_item_subtotal);

                    $products[] = $product;
                }
            };

            if (! empty($shipping_lines)) {
                foreach ($shipping_lines as $shipping_line) {
                    $shipping_line_total = $shipping_line->get_total();
                    $shipping_line_method_title = $shipping_line->get_method_title();

                    $shipping = sprintf('%1$s &mdash; %2$s%3$s', $shipping_line_method_title, $currency_symbol, $shipping_line_total);

                    $shippings[] = $shipping;
                }
            }

            $totals[] = sprintf('%1$s: %2$s%3$s', $obj->__('Subtotal'), $currency_symbol, $subtotal);
            $totals[] = sprintf('%1$s: %2$s%3$s', $obj->__('Discount'), $currency_symbol, $total_discount);
            $totals[] = sprintf('%1$s: %2$s%3$s', $obj->__('Total'), $currency_symbol, $total);

            $payments[] = $payment_method_title;

            if ($needs_payment) {
                $payments[] = $obj->__('Need payment');
            }

            $others[] = sprintf('%1$s: %2$d', $obj->__('ID'), $order_id);
            $others[] = sprintf('%1$s: %2$s', $obj->__('Status'), $order_status);
            $others[] = sprintf('%1$s: %2$s', $obj->__('Date'), $date_created);

            $config = Mapbd_wps_woocommerce::FindBy('status', 'A', array('same_site' => 'Y'));
            $store_title = (is_object($config) && isset($config->store_title)) ? sanitize_text_field($config->store_title) : '';
            $store_display = ! empty($store_title) ? $store_title : wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            $others[] = sprintf('%1$s: %2$s', $obj->__('Store'), $store_display);

            $order_details = array(
                'products' => $products,
                'shippings' => $shippings,
                'totals' => $totals,
                'payments' => $payments,
                'others' => $others,
                'more_link' => $more_link,
                'valid' => $valid,
            );
        }

        return $order_details;
    }

    private static function getEnvatoItems__dashboard($ticketObj)
    {
        $env_items = array();

        $env_status = Apbd_wps_envato_system::GetModuleOption('login_status');
        $user_id = (isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0);

        if ('A' !== $env_status || empty($user_id)) {
            return $env_items;
        }

        $auth_data = get_user_meta($user_id, 'sglwenvato_auth', true);

        if (! is_array($auth_data) || empty($auth_data)) {
            return $env_items;
        }

        $ex_env_items = get_transient('apbd-wps-envato-items-' . $user_id);

        if (false !== $ex_env_items) {
            if (is_array($ex_env_items)) {
                $env_items = $ex_env_items;
            }

            return $env_items;
        }

        $env_username = Apbd_wps_envato_system::GetModuleOption('login_username');
        $env_client_id = Apbd_wps_envato_system::GetModuleOption('login_client_id');
        $env_client_secret = Apbd_wps_envato_system::GetModuleOption('login_client_secret');

        if (empty($env_username) || empty($env_client_id) || empty($env_client_secret)) {
            return $env_items;
        }

        $access_token = isset($auth_data['access_token']) ? sanitize_text_field($auth_data['access_token']) : '';
        $refresh_token = isset($auth_data['refresh_token']) ? sanitize_text_field($auth_data['refresh_token']) : '';
        $updated_at = isset($auth_data['updated_at']) ? sanitize_text_field($auth_data['updated_at']) : '';
        $expires_in = isset($auth_data['expires_in']) ? absint($auth_data['expires_in']) : 3600;

        $expired_at = strtotime($updated_at) + $expires_in;
        $current_timestamp = current_time('U', true);
        $current_time = current_time('mysql', true);

        if ($current_timestamp > $expired_at) {
            $refresh_response = wp_remote_post('https://api.envato.com/token', array(
                'timeout' => 120,
                'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
                'body' => array(
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id' => $env_client_id,
                    'client_secret' => $env_client_secret,
                ),
            ));

            $refresh_status = wp_remote_retrieve_response_code($refresh_response);

            if ($refresh_status === 200) {
                $refresh_body = wp_remote_retrieve_body($refresh_response);
                $refresh_data = json_decode($refresh_body, true);

                $access_token = isset($refresh_data['access_token']) ? sanitize_text_field($refresh_data['access_token']) : '';
                $expires_in = isset($refresh_data['expires_in']) ? absint($refresh_data['expires_in']) : 3600;

                if (! empty($access_token)) {
                    $auth_data['access_token'] = $access_token;
                    $auth_data['expires_in'] = $expires_in;
                    $auth_data['updated_at'] = $current_time;

                    update_user_meta($user_id, 'sglwenvato_auth', $auth_data);
                }
            }
        }

        $updated_at = isset($auth_data['updated_at']) ? sanitize_text_field($auth_data['updated_at']) : '';
        $expires_in = isset($auth_data['expires_in']) ? absint($auth_data['expires_in']) : 3600;
        $expired_at = strtotime($updated_at) + $expires_in;

        if ($current_timestamp > $expired_at) {
            return $env_items;
        }

        $purchases_response = wp_remote_get('https://api.envato.com/v3/market/buyer/list-purchases', array(
            'timeout' => 120,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        $purchases_status = wp_remote_retrieve_response_code($purchases_response);

        if ($purchases_status !== 200) {
            return $env_items;
        }

        set_transient('apbd-wps-envato-items-' . $user_id, $env_items, 5 * MINUTE_IN_SECONDS);

        $purchases_body = wp_remote_retrieve_body($purchases_response);
        $purchases_data = json_decode($purchases_body, true);

        if (! is_array($purchases_data) || empty($purchases_data)) {
            return $env_items;
        }

        $purchases_items = isset($purchases_data['results']) ? $purchases_data['results'] : array();

        if (! is_array($purchases_items) || empty($purchases_items)) {
            return $env_items;
        }

        $env_items = array_filter($purchases_items, function ($item) use ($env_username) {
            $item_data = isset($item['item']) ? $item['item'] : array();
            $author_username = isset($item_data['author_username']) ? sanitize_text_field($item_data['author_username']) : '';

            return ($env_username === $author_username);
        });

        if (empty($env_items)) {
            return $env_items;
        }

        $env_items = array_map(function ($item) {
            $item_data = isset($item['item']) ? $item['item'] : array();
            $item_name = isset($item_data['name']) ? sanitize_text_field($item_data['name']) : '';
            $item_url = isset($item_data['url']) ? esc_url_raw($item_data['url']) : '';
            $sold_at = isset($item['sold_at']) ? sanitize_text_field($item['sold_at']) : '';
            $license = isset($item['license']) ? sanitize_text_field($item['license']) : '';
            $supported_until = isset($item['supported_until']) ? sanitize_text_field($item['supported_until']) : '';
            $code = isset($item['code']) ? sanitize_text_field($item['code']) : '';

            return array(
                'name' => $item_name,
                'url' => $item_url,
                'sold_at' => $sold_at,
                'supported_until' => $supported_until,
                'license' => $license,
                'code' => $code,
            );
        }, $env_items);

        set_transient('apbd-wps-envato-items-' . $user_id, $env_items, 5 * MINUTE_IN_SECONDS);

        return $env_items;
    }
}
