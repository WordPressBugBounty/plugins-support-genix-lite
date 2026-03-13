<?php

/**
 * Chat Ticket Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_chatticket_trait
{
    public function initialize__chatticket() {}

    /* Create Ticket */

    public function create_ticket_from_data($request_data)
    {
        $user_email = (isset($request_data['user_email']) ? sanitize_email($request_data['user_email']) : '');
        $user_first_name = (isset($request_data['user_first_name']) ? sanitize_text_field($request_data['user_first_name']) : '');
        $user_last_name = (isset($request_data['user_last_name']) ? sanitize_text_field($request_data['user_last_name']) : '');

        $ticket_category_id = (isset($request_data['ticket_category_id']) ? absint($request_data['ticket_category_id']) : 0);
        $ticket_subject = (isset($request_data['ticket_subject']) ? sanitize_text_field($request_data['ticket_subject']) : '');
        $ticket_description = (isset($request_data['ticket_description']) ? wp_kses_post($request_data['ticket_description']) : '');

        if (empty($user_email)) {
            $response_data = array(
                'success' => false,
                'message' => $this->__('User Email is required.'),
            );

            return $response_data;
        }

        if (! is_email($user_email)) {
            $response_data = array(
                'success' => false,
                'message' => $this->__('User email is invalid.'),
            );

            return $response_data;
        }

        if (empty($ticket_subject)) {
            $response_data = array(
                'success' => false,
                'message' => $this->__('Ticket Subject is required.'),
            );

            return $response_data;
        }

        if (empty($ticket_description)) {
            $response_data = array(
                'success' => false,
                'message' => $this->__('Ticket Description is required.'),
            );

            return $response_data;
        }

        $user = get_user_by('email', $user_email);
        $custom_fields = [];

        if (empty($user_first_name)) {
            if (! empty($user_last_name)) {
                $user_first_name = $user_last_name;
                $user_last_name = "";
            } else {
                $user_first_name = substr($user_email, 0, strpos($user_email, '@'));
            }
        }

        if (empty($user)) {
            if (!Apbd_wps_settings::RegistrationAllowed()) {
                $response_data = array(
                    'success' => false,
                    'message' => $this->__('User not found.'),
                );

                return $response_data;
            }

            $newUser = new Apbd_Wps_User();

            $newUser->email = $user_email;
            $newUser->first_name = $user_first_name;
            $newUser->last_name = $user_last_name;
            $newUser->role = Apbd_wps_settings::GetModuleOption('client_role', 'subscriber');
            $newUser->username = ApbdWps_GenerateBaseUsername($user_first_name, $user_last_name, '', $user_email);
            $newUser->username = ApbdWps_GenerateUniqueUsername($newUser->username);

            if ($newUser->Save()) {
                add_user_meta($newUser->id, 'is_guest', 'Y');
                do_action('apbd-wps/action/user-created', $newUser, $custom_fields);
                $user = get_user_by('ID', $newUser->id);
            } else {
                $response_data = array(
                    'success' => false,
                    'message' => $this->__('Ticket user creation failed.'),
                );

                return $response_data;
            }
        }

        $ticketObj = new Mapbd_wps_ticket();

        $ticketObj->ticket_user($user->ID);
        $ticketObj->status('N');

        if (! empty($ticket_category_id)) {
            $ticketObj->cat_id($ticket_category_id);
        }

        $ticketObj->title($ticket_subject);
        $ticketObj->ticket_body($ticket_description);
        $ticketObj->reply_counter(0);
        $ticketObj->opened_time(gmdate('Y-m-d H:i:s'));
        $ticketObj->last_reply_time(gmdate('Y-m-d H:i:s'));

        if (Mapbd_wps_ticket::create_ticket($ticketObj, null, true)) {
            Mapbd_wps_ticket::AddTicketMeta($ticketObj->id, "_opened_by_chatbot_form", $user_email);
            Mapbd_wps_ticket::create_ticket_action($ticketObj, $custom_fields);

            $response_data = array(
                'success' => true,
                'message' => $this->__('Ticket creation success.'),
            );

            return $response_data;
        } else {
            $response_data = array(
                'success' => false,
                'message' => $this->__('Ticket creation failed.'),
            );

            return $response_data;
        }
    }

}
