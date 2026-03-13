<?php

/**
 * Ticket reply.
 */

defined('ABSPATH') || exit;

class Apbd_wps_ticket_reply extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
        $this->AddAjaxAction("add", [$this, "add"]);

        $this->AddPortalAjaxAction("add", [$this, "add"]);
    }

    public function add()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        $hasError = false;

        if (ApbdWps_IsPostBack) {
            $ticket_id = absint(ApbdWps_PostValue('ticket_id', ''));
            $is_private = sanitize_text_field(ApbdWps_PostValue('is_private', ''));
            $reply_text = ApbdWps_KsesHtml(ApbdWps_PostValue('reply_text', ''));
            $close = sanitize_text_field(ApbdWps_PostValue('close', ''));

            $reply_text = stripslashes($reply_text);
            $check__reply_text = sanitize_text_field($reply_text);

            $ticket_id = strval($ticket_id);
            $is_private = 'Y' === $is_private ? 'Y' : 'N';
            $close = 'Y' === $close ? 'Y' : 'N';

            $mainobj = Mapbd_wps_ticket::FindBy("id", intval($ticket_id));

            if (
                (1 > strlen($ticket_id)) ||
                (1 > strlen($check__reply_text)) ||
                empty($mainobj)
            ) {
                $hasError = true;
            }

            if (!$hasError) {
                $namespace = ApbdWps_SupportLite::getNamespaceStr();
                $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                $apiObj->SetPayload('ticket_id', $ticket_id);
                $apiObj->SetPayload('is_private', $is_private);
                $apiObj->SetPayload('reply_text', $reply_text);

                $resObj = $apiObj->ticket_reply();
                $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                if ($resStatus) {
                    $apiResponse->SetResponse(true, $this->__('Successfully added.'));

                    $isAgent = Apbd_wps_settings::isAgentLoggedIn();
                    $ticektUserId = isset($mainobj->ticket_user) ? absint($mainobj->ticket_user) : 0;
                    $currentUserId = get_current_user_id();

                    $disable_auto_ticket_assignment = Apbd_wps_settings::GetModuleOption("disable_auto_ticket_assignment", 'N');

                    if ($isAgent && ($ticektUserId !== $currentUserId) && ('Y' !== $disable_auto_ticket_assignment)) {
                        $assignedUserId = isset($mainobj->assigned_on) ? absint($mainobj->assigned_on) : 0;

                        if (empty($assignedUserId)) {
                            $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                            $apiObj->SetPayload('propName', 'assigned_on');
                            $apiObj->SetPayload('value', $currentUserId);
                            $apiObj->SetPayload('ticketId', $ticket_id);

                            $resObj = $apiObj->update_ticket();
                            $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                            if ($resStatus) {
                                $apiResponse->SetResponse(true, $this->__('Successfully added & assigned.'));
                            }
                        }
                    }

                    if (('Y' === $close) && ($isAgent || ($ticektUserId === $currentUserId))) {
                        $apiObj = new ApbdWpsAPI_Ticket($namespace, false);

                        $apiObj->SetPayload('propName', 'status');
                        $apiObj->SetPayload('value', 'C');
                        $apiObj->SetPayload('ticketId', $ticket_id);

                        $resObj = $apiObj->update_ticket();
                        $resStatus = isset($resObj->status) ? rest_sanitize_boolean($resObj->status) : false;

                        if ($resStatus) {
                            $apiResponse->SetResponse(true, $this->__('Successfully added & closed.'));
                        }
                    }
                } else {
                    $apiResponse->SetResponse(false, $this->__('Something went wrong.'));
                }
            } else {
                $apiResponse->SetResponse(false, $this->__('Invalid data.'));
            }
        }

        echo wp_json_encode($apiResponse);
    }
}
