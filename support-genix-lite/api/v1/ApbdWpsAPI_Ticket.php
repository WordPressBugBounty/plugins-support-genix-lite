<?php

/**
 * Ticket API.
 */

defined('ABSPATH') || exit;

require_once dirname(__DIR__, 2) . '/traits/ApbdWpsAPI_TicketTrait.php';

class ApbdWpsAPI_Ticket extends Apbd_Wps_APIBase
{
    use ApbdWpsAPI_TicketTrait;

    public function __construct($namespace, $register = true)
    {
        parent::__construct($namespace, $register);
    }

    function setAPIBase()
    {
        return 'ticket';
    }

    function routes()
    {
        $this->RegisterRestRoute('GET', 'file-dl/(?P<type>[a-zA-Z0-9-]+)/(?P<ticket_or_reply_id>[0-9_]+)/(?P<file>[^/]+)', [$this, "file_dl"]);
    }

    function SetRoutePermission($route)
    {

        if ($route == "delete-ticket") {
            if (current_user_can('delete-ticket')) {
                return true;
            } else {
                return false;
            }
        } elseif ($route == "move-to-trash") {
            if (current_user_can('move-to-trash')) {
                return true;
            } else {
                return false;
            }
        } elseif ($route == "suggested-docs") {
            return true;
        } elseif ($route == "logout") {
            return true;
        } elseif ($route == "file-dl") {
            return true;
        }
        return parent::SetRoutePermission($route);
    }
    function ticket_stat()
    {
        $this->response->SetResponse(true, "", Mapbd_wps_ticket::getTicketStat());
        return $this->response;
    }
    function unseen_notifications()
    {
        $responseData = Mapbd_wps_notification::getUnseenNotification($this->get_current_user_id());
        $this->response->SetResponse(true, "", $responseData);
        return $this->response;
    }
    function notifications()
    {

        $page = (int)$this->GetPayload("page", 1);
        $limit = (int)$this->GetPayload("limit", 10);
        if (! empty($limit)) {
            if (empty($page)) {
                $page = 1;
            }
            $limitStart = (($page * $limit) - $limit);
        }

        $order_by_prop = "entry_time";
        $order_by_value = "DESC";
        if (! empty($this->payload['sort_by'][0])) {
            $order_by_prop = $this->payload['sort_by'][0]['prop'];
            $order_by_value = $this->payload['sort_by'][0]['ord'];
        }
        $mainobj = new Mapbd_wps_notification();
        $src_by = $this->GetPayload("src_by", []);
        foreach ($src_by as $src_item) {
            if ($src_item['opr'] == 'like') {
                $mainobj->{$src_item['prop']}("like '%" . $src_item['val'] . "%'", true);
            } else {
                $mainobj->{$src_item['prop']}($src_item['val']);
            }
        }
        $mainobj->user_id($this->get_current_user_id());
        $responseData = new Apbd_Wps_APIDataResponse();
        $responseData->page = $page;
        $responseData->limit = $limit;
        $responseData->total = (int) $mainobj->CountALL();
        $responseData->pagetotal = ceil($responseData->total / $responseData->limit);
        $responseData->rowdata = $mainobj->SelectAllGridData('', $order_by_prop, $order_by_value, $limit, $limitStart);
        foreach ($responseData->rowdata as &$data) {
            $msg_body = ((is_string($data->msg) && ! empty($data->msg)) ? $data->msg : '');
            $msg_body = str_replace('%s', '%{user_name}', $msg_body);

            $data->msg_body = $msg_body;
            $data->user_name = ((is_string($data->msg_param) && ! empty($data->msg_param)) ? $data->msg_param : '');
            $data->msg_param = array_merge([$data->msg], explode('|', $data->msg_param));
            $data->msg = call_user_func_array([Apbd_wps_settings::GetModuleInstance(), '__'], $data->msg_param);
            unset($data->msg_param);
        }
        $responseData = apply_filters('apbd-wps/filter/before-notification', $responseData);
        $this->response->SetResponse(true, "", $responseData);
        return $this->response;
    }

    function update_notification($data)
    {
        if (! empty($data['notification_id'])) {
            $notification = new Mapbd_wps_notification();
            $notification->status('V');
            $notification->SetWhereUpdate('id', intval($data['notification_id']));
            $notification->UnsetAllExcepts('status');
            if ($notification->Update()) {
                $this->response->SetResponse(true, "Successfully updated");
                do_action('apbd-wps/action/ticket-notification-updated');
                do_action('apbd-wps/action/data-change');
                return $this->response;
            } else {
                $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
            }
        } else {
            $this->SetResponse(true, "data not found");
            return $this->response;
        }
    }
    function ticket_list()
    {
        $id = $this->get_current_user_id();
        $tkt_type = $this->GetPayload("data", "");
        $sub_type = $this->GetPayload("sub_type", "");
        $category = $this->GetPayload("category", "");
        $agent = $this->GetPayload("agent", "");
        $priority = $this->GetPayload("priority", "");
        $page = (int) $this->GetPayload("page", 1);
        $limit = (int) $this->GetPayload("limit", 10);
        $filter_prop = strtolower($this->GetPayload("filter_prop", ''));

        $tkt_type = !empty($tkt_type) ? $tkt_type : 'T';
        $need_reply = 'nr' === $filter_prop ? true : false;

        $category = !empty($category) ? "'" . implode("','", array_filter(array_map('absint', explode(",", $category)))) . "'" : '';
        $agent = !empty($agent) ? "'" . implode("','", array_filter(array_map('absint', explode(",", $agent)))) . "'" : '';
        $priority = !empty($priority) ? "'" . implode("','", array_filter(array_map('sanitize_text_field', explode(",", $priority)))) . "'" : '';

        if (! empty($limit)) {
            if (empty($page)) {
                $page = 1;
            }
            $limitStart = (($page * $limit) - $limit);
        }

        $order_by_prop  = "opened_time";
        $order_by_value = "DESC";
        if (! empty($this->payload['sort_by'][0])) {
            $order_by_prop  = $this->payload['sort_by'][0]['prop'];
            $order_by_value = $this->payload['sort_by'][0]['ord'];
        }

        $is_agent_logged_in = Apbd_wps_settings::isAgentLoggedIn();
        $manage_other_agents_ticket = current_user_can('manage-other-agents-ticket');
        $manage_unassigned_ticket = current_user_can('manage-unassigned-ticket');
        $manage_self_created_ticket = current_user_can('manage-self-created-ticket');

        $mainobj = new Mapbd_wps_ticket();
        $aps_user = new Mapbd_wps_users();
        $aps_support_meta = new Mapbd_wps_support_meta();
        $aps_ticket_reply = new Mapbd_wps_ticket_reply();

        $tableName = $mainobj->GetTableName();
        $userTableName = $aps_user->GetTableName();
        $metaTableName = $aps_support_meta->GetTableName();
        $replyTableName = $aps_ticket_reply->GetTableName();

        $mainobj->Join($aps_user, "ID", "ticket_user", "LEFT");

        if ($category) {
            $mainobj->cat_id("IN ($category)", true);
        }

        if ($agent && !in_array($tkt_type, ['MY', 'UA'], true)) {
            $mainobj->assigned_on("IN ($agent)", true);
        }

        if ($priority) {
            $mainobj->priority("IN ($priority)", true);
        }

        $check_assigned_on = false;
        $filter_assigned_on = 0;

        if ($is_agent_logged_in) {
            if (in_array($tkt_type, ['T', 'MY', 'UA'], true)) {
                if ('A' === $sub_type) {
                    $mainobj->status("in ('A','N','R','P')", true);
                } elseif (in_array($sub_type, ['I', 'C'], true)) {
                    $mainobj->status($sub_type);
                } else {
                    if ($need_reply) {
                        $mainobj->status("NOT IN ('C','D','I')", true);
                    } else {
                        $mainobj->status("!='D'", true);
                    }
                }

                if ('MY' === $tkt_type) {
                    if (Apbd_wps_settings::isAgentLoggedIn()) {
                        $mainobj->assigned_on($id);
                    }
                } elseif ('UA' === $tkt_type) {
                    $mainobj->assigned_on("IN ('','0')", true);
                } else {
                    $check_assigned_on = true;
                }
            } else {
                $mainobj->status($tkt_type);
                $check_assigned_on = true;
            }
        } else {
            if ('A' === $sub_type) {
                $mainobj->status("in ('A','N','R','P')", true);
            } elseif (in_array($sub_type, ['I', 'C'], true)) {
                $mainobj->status($sub_type);
            } else {
                if ($need_reply) {
                    $mainobj->status("NOT IN ('C','D','I')", true);
                } else {
                    $mainobj->status("!='D'", true);
                }
            }

            $is_public_tickets_menu = Apbd_wps_settings::GetModuleOption("is_public_tickets_menu", 'N');

            if ('Y' === $is_public_tickets_menu) {
                $mainobj->ticket_user("={$id} OR `is_public`='Y'", true);
            } else {
                $mainobj->ticket_user($id);
            }

            $check_assigned_on = false;
        }

        $src_by = $this->GetPayload("src_by", []);

        foreach ($src_by as $src_item) {
            if (('assigned_on' === $src_item['prop']) && $is_agent_logged_in && $check_assigned_on) {
                $filter_assigned_on = absint($src_item['val']);
                continue;
            }
            $src_item['val'] = preg_replace('#[^a-z0-9@ _\-\.]#i', "", $src_item['val']);
            if (! empty($src_item['val'])) {
                if ($src_item['prop'] == '*') {
                    if ($src_item['opr'] == 'like') {
                        $prop_like_str = "like '%" . $src_item['val'] . "%'";

                        $src_by_query = "";
                        $src_by_query .= " OR ($tableName.title $prop_like_str)";
                        $src_by_query .= " OR ($tableName.ticket_body $prop_like_str)";
                        $src_by_query .= " OR ($userTableName.user_email $prop_like_str)";
                        $src_by_query .= " OR ($userTableName.display_name $prop_like_str)";

                        $meta_item_qry = "SELECT GROUP_CONCAT(item_id) AS item_ids FROM {$metaTableName} WHERE item_type='T' AND meta_type<>'C' AND meta_value $prop_like_str";
                        $meta_item_rlt = $aps_support_meta->SelectQuery($meta_item_qry);
                        $meta_item_ids = strval($meta_item_rlt[0]->item_ids);
                        $meta_item_ids_arr = array_unique(array_map('absint', explode(",", $meta_item_ids)));
                        $meta_item_ids_str = implode(",", $meta_item_ids_arr);

                        if (! empty($meta_item_ids_str)) {
                            $src_by_query .= " OR ($tableName.id IN ($meta_item_ids_str))";
                        }

                        $ticket_track_id_like_str = apply_filters('apbd-wps/filter/query-track-id', $src_item['val']);
                        $ticket_track_id_like_str = "like '%" . $ticket_track_id_like_str . "%'";

                        $mainobj->ticket_track_id($ticket_track_id_like_str . $src_by_query, true);
                    }
                } else {
                    if ('ticket_track_id' === $src_item['prop']) {
                        $src_item['val'] = apply_filters('apbd-wps/filter/query-track-id', $src_item['val']);
                    }

                    if ($src_item['opr'] == 'like') {
                        $mainobj->{$src_item['prop']}("like '%" . $src_item['val'] . "%'", true);
                    } else {
                        $mainobj->{$src_item['prop']}($src_item['val']);
                    }
                }
            }
        }

        if ($is_agent_logged_in && $check_assigned_on) {
            $manage_self_condition = $manage_self_created_ticket ? " OR `opened_by`={$id}" : "";
            $manage_self_condition .= " OR `ticket_user`={$id}";

            if (! $manage_other_agents_ticket && ! $manage_unassigned_ticket) {
                $mainobj->assigned_on("={$id}{$manage_self_condition}", true);
            } elseif ($manage_other_agents_ticket && ! $manage_unassigned_ticket) {
                if ($filter_assigned_on) {
                    $mainobj->assigned_on("={$filter_assigned_on}", true);
                } else {
                    $mainobj->assigned_on("NOT IN ('','0'){$manage_self_condition}", true);
                }
            } elseif (! $manage_other_agents_ticket && $manage_unassigned_ticket) {
                $mainobj->assigned_on("IN ($id,'','0'){$manage_self_condition}", true);
            } elseif ($filter_assigned_on) {
                $mainobj->assigned_on("={$filter_assigned_on}", true);
            }
        }

        if ($need_reply) {
            $nrconds = "!='' AND (";
            $nrconds .= "(last_replied_by_type='U' AND ticket_user!={$id})";
            $nrconds .= " OR (ticket_user={$id} AND last_replied_by!=$tableName.ticket_user)";
            $nrconds .= " OR (ticket_user!={$id} AND last_replied_by=$tableName.ticket_user)";
            $nrconds .= ")";

            $mainobj->last_replied_by_type($nrconds, true);
        }

        $select_fields = "*,user_login,display_name";

        // Add subquery for last reply text
        $select_fields .= ",(
            SELECT reply_text
            FROM {$replyTableName} r
            WHERE r.ticket_id = {$tableName}.id
            ORDER BY r.reply_id DESC
            LIMIT 1
        ) as last_reply_text";

        $responseData        = new Apbd_Wps_APIDataResponse();
        $responseData->limit = $limit;
        $responseData->page  = $page;
        $responseData->total = (int) $mainobj->CountALL();
        $responseData->pagetotal     = ceil($responseData->total / $limit);
        $responseData->rowdata       = $mainobj->SelectAllGridData($select_fields, $order_by_prop, $order_by_value, $limit, $limitStart);
        global $wpdb;
        $responseData->ticket_rcount = (int) Mapbd_wps_ticket_reply::FetchCountAll();
        if (! empty($src_by)) {
            $responseData->ticket_stat = Mapbd_wps_ticket::getTicketStat($src_by);
        }
        foreach ($responseData->rowdata as &$ticket) {
            $ticket->ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticket->ticket_track_id);
            $ticket->last_time_str = "-";
            $ticket->display_name  = ! empty($ticket->display_name) ? $ticket->display_name : $ticket->user_login;
            $ticket->user_img      = get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) ? get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) : get_avatar_url($ticket->ticket_user);
            $ticket->last_reply_text = ! empty($ticket->last_reply_text) ? $ticket->last_reply_text : $ticket->ticket_body;
            $ticket->tag_ids = [];
            unset($ticket->user_login);
        }
        $responseData = apply_filters('apbd-wps/filter/before-get-ticket-list', $responseData);
        $this->response->SetResponse(true, "", $responseData);

        return $this->response;
    }


    function public_tickets()
    {
        global $wpdb;
        $id = $this->get_current_user_id();
        $page = 1;
        $limit = 50;
        $whereCondition = "WHERE t.is_public ='Y'";
        $mainobj = new Mapbd_wps_ticket();
        $responseData = new stdClass();
        $responseData->page = $page;
        $responseData->total = 0;
        $responseData->tickets = $mainobj->SelectQuery("SELECT t.*,u.user_login,u.display_name FROM {$wpdb->prefix}apbd_wps_ticket AS t JOIN {$wpdb->base_prefix}users AS u ON u.ID = t.ticket_user {$whereCondition}");
        foreach ($responseData->tickets as &$ticket) {
            $ticket->ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticket->ticket_track_id);
            $ticket->last_time_str = "2 Days Ago";
            $ticket->display_name = ! empty($ticket->display_name) ? $ticket->display_name : $ticket->user_login;
            $ticket->user_img = get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) ? get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) : get_avatar_url($ticket->ticket_user);
            unset($ticket->user_login);
        }
        $responseData = apply_filters('apbd-wps/filter/before-get-ticket-list', $responseData);
        $this->response->SetResponse(true, "", $responseData);
        return $this->response;
    }

    function trashed_ticket_list()
    {
        global $wpdb;
        $id = $this->get_current_user_id();
        $whereCondition = "WHERE t.status='D'";
        $page = 1;
        $limit = 50;
        $mainobj = new Mapbd_wps_ticket();
        $responseData = new stdClass();
        $responseData->page = $page;
        $responseData->total = 0;
        $responseData->tickets = $mainobj->SelectQuery("SELECT t.*,u.user_login,u.display_name FROM {$wpdb->prefix}apbd_wps_ticket AS t JOIN {$wpdb->base_prefix}users AS u ON u.ID = t.ticket_user {$whereCondition}");
        foreach ($responseData->tickets as &$ticket) {
            $ticket->ticket_track_id = apply_filters('apbd-wps/filter/display-track-id', $ticket->ticket_track_id);
            $ticket->last_time_str = "2 Days Ago";
            $ticket->display_name = ! empty($ticket->display_name) ? $ticket->display_name : $ticket->user_login;
            $ticket->user_img = get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) ? get_user_meta($ticket->ticket_user, 'supportgenix_avatar', true) : get_avatar_url($ticket->ticket_user);
            unset($ticket->user_login);
        }
        $responseData = apply_filters('apbd-wps/filter/before-get-trashed-ticket-list', $responseData);
        $this->response->SetResponse(true, "", $responseData);
        return $this->response;
    }

    function move_to_trash($data)
    {
        if (! empty($data['ticketId'])) {
            $ticket_id = intval($data['ticketId']);
            $Mainticket = new Mapbd_wps_ticket();
            $Mainticket->id($ticket_id);
            if ($Mainticket->Select()) {
                if ($Mainticket->status != "D") {
                    $ticket = new Mapbd_wps_ticket();
                    $ticket->status('D');
                    $ticket->SetWhereUpdate('id', intval($data['ticketId']));
                    $ticket->UnsetAllExcepts('status');
                    if ($ticket->Update()) {
                        $updatedObj = Mapbd_wps_ticket::FindBy("id", intval($data['ticketId']));
                        $response = new stdClass();
                        $response->ticket_stat = Mapbd_wps_ticket::getTicketStat();
                        $this->response->SetResponse(true, "Successfully deleted", $response->ticket_stat);
                        do_action('apbd-wps/action/ticket-soft-deleted', $updatedObj);
                        do_action('apbd-wps/action/data-change');
                        return $this->response;
                    } else {
                        $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                    }
                } else {
                    $this->response->SetResponse(true, "Successfully deleted");
                    return $this->response;
                }
                return $this->response;
            } else {
                $this->SetResponse(true, "Invalid request param");
            }
        } else {
            $this->SetResponse(true, "data not found");
            return $this->response;
        }
    }

    function update_privacy()
    {
        if (! empty($this->payload['ticketId'])) {
            $ticket = new Mapbd_wps_ticket();
            $ticket->is_public($this->payload['privacy']);
            $ticket->SetWhereUpdate('id', intval($this->payload['ticketId']));
            $ticket->UnsetAllExcepts('is_public');
            if ($ticket->Update()) {
                $updatedObj = Mapbd_wps_ticket::FindBy("id", intval($this->payload['ticketId']));
                $response = new stdClass();
                $response->ticket_stat = Mapbd_wps_ticket::getTicketStat();
                $this->response->SetResponse(true, "Successfully updated", $response);
                do_action('apbd-wps/action/ticket-privacy-updated', $updatedObj);
                do_action('apbd-wps/action/data-change');
                return $this->response;
            } else {
                $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
            }
        } else {
            $this->SetResponse(true, "data not found");
            return $this->response;
        }
    }

    function restore_ticket($data)
    {
        if (! empty($data['ticketId'])) {
            $ticket_id = intval($data['ticketId']);
            $Mainticket = new Mapbd_wps_ticket();
            $Mainticket->id($ticket_id);
            if ($Mainticket->Select()) {
                if ($Mainticket->status == "D") {
                    $ticket = new Mapbd_wps_ticket();
                    $ticket->status('A');
                    $ticket->SetWhereUpdate('id', intval($data['ticketId']));
                    $ticket->UnsetAllExcepts('status');
                    if ($ticket->Update()) {
                        $updatedObj = Mapbd_wps_ticket::FindBy("id", intval($data['ticketId']));
                        $response = new stdClass();
                        $response->ticket_stat = Mapbd_wps_ticket::getTicketStat();
                        $this->response->SetResponse(true, "Successfully restored", $response);
                        do_action('apbd-wps/action/restore-deleted-ticket', $updatedObj);
                        do_action('apbd-wps/action/data-change');
                        return $this->response;
                    } else {
                        $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                    }
                } else {
                    $this->response->SetResponse(true, "This is already restored");
                    return $this->response;
                }
                return $this->response;
            } else {
                $this->SetResponse(true, "Invalid request param");
            }
        } else {
            $this->SetResponse(true, "data not found");
            return $this->response;
        }
    }

    function delete_ticket($data)
    {
        if (! empty($data['ticketId'])) {
            $ticket_id = intval($data['ticketId']);
            $Mainticket = new Mapbd_wps_ticket();
            $Mainticket->id($ticket_id);
            if ($Mainticket->Select()) {
                do_action('apbd-wps/action/before-ticket-delete', $Mainticket);
                if (Mapbd_wps_ticket::DeleteByID($Mainticket->id)) {
                    $response = new stdClass();
                    $response->ticket_stat = Mapbd_wps_ticket::getTicketStat();
                    $this->response->SetResponse(true, "Successfully deleted", $response);
                    do_action('apbd-wps/action/ticket-deleted', $Mainticket);
                    do_action('apbd-wps/action/data-change');
                    return $this->response;
                } else {
                    $this->response->SetResponse(false, ApbdWps_GetMsgAPI());
                }
                return $this->response;
            } else {
                $this->SetResponse(true, "Invalid request param");
            }
        } else {
            $this->SetResponse(true, "data not found");
            return $this->response;
        }
    }

    function ticket_details($data)
    {
        if (! empty($data['ticketId'])) {
            $user_id = "";
            if (!Apbd_wps_settings::isAgentLoggedIn()) {
                $user = wp_get_current_user();
                if (! empty($user->ID)) {
                    $user_id = $user->ID;
                } else {
                    $this->SetResponse(false, "data not found or invalid param");
                    return $this->response;
                }
            }
            $ticketDetailsObj = Mapbd_wps_ticket::getTicketDetails($data['ticketId'], $user_id);
            if (! empty($ticketDetailsObj)) {
                Mapbd_wps_notification::SetSeenNotification($data['ticketId'], $this->get_current_user_id());
                if (!current_user_can('show-ticket-email')) {
                    if (! empty($ticketDetailsObj->user->email)) {
                        $ticketDetailsObj->user->email = "";
                    }
                }
                $disableHotlink = Apbd_wps_settings::GetModuleOption('disable_ticket_hotlink', 'N');
                if (!current_user_can('show-ticket-hotlink') || ('Y' === $disableHotlink)) {
                    if (! empty($ticketDetailsObj->hotlink)) {
                        $ticketDetailsObj->hotlink = '';
                    }
                }
                $this->SetResponse(true, "data found", $ticketDetailsObj);
            } else {
                $this->SetResponse(false, "data not found or invalid param");
            }
        } else {
            $this->SetResponse(false, "data not found or invalid param");
        }
        return $this->response;
    }

    function file_dl($data)
    {
        $file = null;
        if ($data['type'] && $data['ticket_or_reply_id'] && $data['file']) {
            ob_start();
            $filePath = Apbd_wps_settings::get_upload_path();
            if (strtoupper($data['type']) == "T") {
                $file = $filePath . $data['ticket_or_reply_id'] . "/attached_files/" . urldecode($data['file']);
            } elseif (strtoupper($data['type']) == "R") {
                $replyinfo = explode('_', $data['ticket_or_reply_id']);
                if (is_array($replyinfo) && count($replyinfo) == 2) {
                    $rep = Mapbd_wps_ticket_reply::FindBy("ticket_id", $replyinfo[0], ["reply_id" => $replyinfo[1]]);
                    if ($rep) {
                        $file = $filePath . $rep->ticket_id . "/replied/" . $rep->reply_id . '/attached_files/' . urldecode($data['file']);
                    }
                }
            }
            ob_get_clean();
            if (!is_null($file) && file_exists($file)) {

                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $allowedExtensions = Apbd_wps_settings::GetModuleAllowedFileType();
                if (in_array($ext, $allowedExtensions)) {
                    $mime = ApbdWps_GetMimeType($file);
                    if (!headers_sent()) {
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header('Content-Type: ' . $mime);
                        header('Content-Disposition: attachment; filename=' . $data['file']);
                    }

                    global $wp_filesystem;

                    if (empty($wp_filesystem)) {
                        require_once(ABSPATH . '/wp-admin/includes/file.php');
                        WP_Filesystem();
                    }

                    // Raw file content - deliberately not escaped as this is a direct file download
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $wp_filesystem->get_contents($file);
                }
            }
        }
    }

    function ticket_reply()
    {
        $user = get_user_by("ID", $this->get_current_user_id());
        $repliedbyType = Apbd_wps_settings::isClientLoggedIn() ? 'U' : 'A';
        if (Mapbd_wps_ticket_reply::AddReplyByPayload($user->ID, $repliedbyType, $this->payload, $replyObj, $ticket)) {
            $replyObj->attached_files = [];
            $reUser = new stdClass();
            $reUser->first_name = $user->first_name;
            $reUser->last_name = $user->last_name;
            $reUser->display_name = ! empty($user->display_name) ? $user->display_name : $user->user_login;
            $reUser->img = get_user_meta($user->ID, 'supportgenix_avatar', true) ? get_user_meta($user->ID, 'supportgenix_avatar', true) : get_avatar_url($user->ID);
            $replyObj->reply_user = $reUser;
            $replyObj->ticket_reply_counter = $ticket->reply_counter;
            $replyObj->last_replied_by = $ticket->last_replied_by;
            $replyObj->last_replied_by_type = $ticket->last_replied_by_type;
            $replyObj->attached_files = apply_filters("apbd-wps/filter/reply-read-attached-files", $replyObj->attached_files, $replyObj);
            do_action('apbd-wps/action/data-change');
            $this->SetResponse(true, 'Successfully replied', $replyObj);
        } else {
            $this->SetResponse(false, ApbdWps_GetMsgAPI());
        }
        return $this->response;
    }

    function search_ticket()
    {
        if ($this->payload) {
            //Search
            return $this->ticket_list();
        } else {
            $this->response->SetResponse(false, "Ticket search failed", null);
            return $this->response;
        }
    }

    function update_ticket()
    {
        $allowedProps = ['status', 'cat_id', 'assigned_on', 'priority', 'related_url', 'email_notification'];
        if (! empty($this->payload['ticketId']) && ! empty($this->payload['propName']) && in_array($this->payload['propName'], $allowedProps)) {
            $prop = $this->payload['propName'];
            if ($prop == "assigned_on") {
                if (! current_user_can('edit-assigned')) {
                    if (current_user_can('assign-me')) {
                        if ($this->payload['value'] != $this->get_current_user_id()) {
                            $this->response->SetResponse(false, "Permission denied", null);
                            return $this->response;
                        }
                    } else {
                        $this->response->SetResponse(false, "Permission denied", null);
                        return $this->response;
                    }
                }
            }
            $mainObj = Mapbd_wps_ticket::FindBy("id", intval($this->payload['ticketId']));
            if (! empty($mainObj)) {
                if ($mainObj->{$prop} != $this->payload['value']) {
                    $ticket = new Mapbd_wps_ticket();
                    $ticket->$prop($this->payload['value']);
                    if ($prop == "status") {
                        if ($this->payload['value'] == "R") {
                            $ticket->re_open_time(gmdate('Y-m-d H:i:s'));
                        }
                        $ticket->last_status_update_time(gmdate('Y-m-d H:i:s'));
                    } elseif ($prop == "assigned_on") {
                        $ticket->assigned_date(gmdate('Y-m-d H:i:s'));
                    }
                    $ticket->SetWhereUpdate('id', intval($this->payload['ticketId']));
                    if ($prop == "status") {
                        if ($this->payload['value'] == "R") {
                            $ticket->UnsetAllExcepts("{$prop},re_open_time,last_status_update_time");
                        } else {
                            $ticket->UnsetAllExcepts("{$prop},last_status_update_time");
                        }
                    } elseif ($prop == "assigned_on") {
                        $ticket->UnsetAllExcepts("{$prop},assigned_date");
                    } else {
                        $ticket->UnsetAllExcepts($prop);
                    }
                    if ($ticket->Update()) {
                        $updatedObj = Mapbd_wps_ticket::FindBy("id", intval($this->payload['ticketId']));
                        if ($prop == "assigned_on") {
                            do_action('apbd-wps/action/ticket-assigned', $updatedObj);
                            do_action('apbd-wps/action/ticket-assigned-notice', $updatedObj);
                        } elseif ($prop == "status") {
                            do_action('apbd-wps/action/ticket-status-change', $updatedObj, $this->get_current_user_id());
                        } elseif ($prop == "email_notification") {
                            do_action('apbd-wps/action/ticket-email-notification-change', $updatedObj, $this->get_current_user_id());
                        }
                        $updatedObj->ticket_stat = Mapbd_wps_ticket::getTicketStat();
                        $this->response->SetResponse(true, "Successfully updated", $updatedObj);
                        do_action('apbd-wps/action/ticket-property-update', $updatedObj, $prop);
                        do_action('apbd-wps/action/data-change');
                        return $this->response;
                    } else {
                        $this->response->SetResponse(false, ApbdWps_GetMsgAPI(), $ticket);
                    }
                } else {
                    $updatedObj = Mapbd_wps_ticket::FindBy("id", intval($this->payload['ticketId']));
                    $this->response->SetResponse(true, "Successfully updated", $updatedObj);
                    return $this->response;
                }
            }
        } else {
            $this->response->SetResponse(false, $this->payload['propName'] . " Is empty or don't have proper permission", null);
        }
        return $this->response;
    }

    function update_custom_field()
    {
        if (! empty($this->payload['ticket_id']) && ! empty($this->payload['propName'])) {
            $value = sanitize_text_field(! empty($this->payload['value']) ? $this->payload['value'] : "");
            do_action('apbd-wps/action/ticket-custom-field-update', $this->payload['ticket_id'], $this->payload['propName'], $value);
            do_action('apbd-wps/action/data-change');
            $custom_fields = apply_filters('apbd-wps/filter/ticket-custom-properties', [], $this->payload['ticket_id']);
            $custom_fields = apply_filters('apbd-wps/filter/ticket-details-custom-properties', $custom_fields, $this->payload['ticket_id']);
            $this->response->SetResponse(true, $this->payload['propName'] . " Changed successfully ", $custom_fields);
        } else {
            $this->response->SetResponse(false, $this->payload['propName'] . " Is empty or don't have proper permission", null);
        }
        return $this->response;
    }

    function create_ticket()
    {
        if (! empty($this->payload['title'] && $this->payload['ticket_body'])) {
            if (is_user_logged_in()) {
                $currentUserID = $this->get_current_user_id();

                if (Apbd_wps_settings::isClientLoggedIn()) {
                    $this->payload['ticket_user'] = $currentUserID;
                    $this->payload['opened_by'] = $currentUserID;
                    $this->payload['opened_by_type'] = 'U';
                } else {
                    $this->payload['opened_by'] = $currentUserID;
                    $this->payload['opened_by_type'] = 'A';
                }
            } else {
                $ticketUserID = $this->payload['ticket_user'];

                $this->payload['opened_by'] = $ticketUserID;
                $this->payload['opened_by_type'] = 'U';
            }

            $userId = $this->payload['ticket_user'];
            if (Mapbd_wps_ticket::create_ticket_by_payload($this->payload, $userId, $ticketObj, true)) {
                $this->response->SetResponse(true, "Ticket created successfully", ((object)$ticketObj->getPropertiesArray('ticket_body,re_open_time,re_open_by,re_open_by_type,user_type,assigned_on,assigned_date,last_replied_by,last_replied_by_type,last_reply_time,ticket_rating,priority,is_public,is_open_using_email,reply_counter,is_user_seen_last_reply,email_notification')));
                return $this->response;
            } else {
                $msg = trim(ApbdWps_GetMsgAPI());
                $obj = ApbdWps_SupportLite::GetInstance();
                if (empty($msg)) {
                    $msg = $obj->__("Creating a ticket has failed.");
                }
                $this->response->SetResponse(false, $msg);
                return $this->response;
            }
        } else {
            $this->response->SetResponse(false, "Fields are empty");
            return $this->response;
        }
    }
    function create_note()
    {
        if (! empty($this->payload['ticket_id']) && ! empty($this->payload['note_text'])) {
            $note = new Mapbd_wps_notes();
            $note->SetFromArray($this->payload);
            $note->added_by($this->get_current_user_id());
            $note->entry_date(gmdate("Y-m-d H:i:s"));
            if ($note->IsValidForm(true)) {
                if ($note->Save()) {
                    do_action('apbd-wps/action/create-note', $note);
                    do_action('apbd-wps/action/data-change');
                    $this->response->SetResponse(true, "successfully created", Mapbd_wps_notes::getNoteString($note));
                } else {
                    $this->response->SetResponse(false, ApbdWps_GetMsgAPI(), $note);
                }
                return $this->response;
            } else {
                $this->response->SetResponse(false, ApbdWps_GetMsgAPI(), $note);
            }
            return $this->response;
        }
    }
    function download_ticket($data)
    {
        $obj = ApbdWps_SupportLite::GetInstance();
        $pluginPath = untrailingslashit(plugin_dir_path($obj->pluginFile));

        $error = true;
        $ticketId = isset($data['ticketId']) ? absint($data['ticketId']) : 0;

        if (! empty($ticketId) && Apbd_wps_settings::isAgentLoggedIn()) {
            $detailsObj = Mapbd_wps_ticket::getTicketDetails($ticketId);

            if (! empty($detailsObj)) {
                $userObj = $detailsObj->user;
                $ticketObj = $detailsObj->ticket;
                $replies = $detailsObj->replies;

                $firstReplyObj = new stdClass();
                $firstReplyObj->replied_by = $ticketObj->ticket_user;
                $firstReplyObj->replied_by_type = 'U';
                $firstReplyObj->reply_text = $ticketObj->ticket_body;
                $firstReplyObj->reply_time = $ticketObj->opened_time;
                $firstReplyObj->is_private = 'N';
                $firstReplyObj->reply_user = $userObj;
                $firstReplyObj->attached_files = array();

                array_unshift($replies, $firstReplyObj);

                if (! empty($userObj) && ! empty($ticketObj)) {
                    $cssPath = $pluginPath . "/views/download_ticket/style.min.php";
                    $htmlPath = $pluginPath . "/views/download_ticket/main.php";

                    ob_start();
                    include $cssPath;
                    include $htmlPath;
                    $fileHtml = ob_get_clean();

                    $fileContent = base64_encode($fileHtml);

                    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
                    $domainr = str_replace('.', '-dot-', $domain);

                    $fileName = sprintf('%1$s-sg-ticket-%2$s-%3$s.pdf', current_time('U'), $ticketId, $domainr);
                    $fileName = sanitize_file_name($fileName);

                    $responseData = array(
                        'fileName' => $fileName,
                        'fileContent' => $fileContent,
                    );

                    $this->SetResponse(true, 'Data found.', $responseData);

                    $error = false;
                }
            }
        }

        if ($error) {
            $this->SetResponse(false, 'Data not found or invalid param.');
        }

        return $this->response;
    }
    function suggested_docs()
    {
        $docs = [];

        $this->response->SetResponse(true, '', $docs);

        return $this->response;
    }
}
