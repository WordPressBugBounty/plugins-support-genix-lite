<?php

/**
 * Migration Create Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_ticket_migration_create_trait
{
    public function initialize__migration_create() {}

    public function migration_create_ticket($request_data, $attachments = array(), $remove_original = false, $cf_validate = false)
    {
        $ticket_id = 0;

        $user_email = (isset($request_data['user_email']) ? sanitize_email($request_data['user_email']) : '');
        $user_first_name = (isset($request_data['user_first_name']) ? sanitize_text_field($request_data['user_first_name']) : '');
        $user_last_name = (isset($request_data['user_last_name']) ? sanitize_text_field($request_data['user_last_name']) : '');

        $ticket_agent_id = (isset($request_data['ticket_agent_id']) ? absint($request_data['ticket_agent_id']) : 0);
        $ticket_category_id = (isset($request_data['ticket_category_id']) ? absint($request_data['ticket_category_id']) : 0);
        $ticket_tag_ids = (isset($request_data['ticket_tag_ids']) ? sanitize_text_field($request_data['ticket_tag_ids']) : '');

        $ticket_mailbox_email = (isset($request_data['ticket_mailbox_email']) ? sanitize_email($request_data['ticket_mailbox_email']) : '');
        $ticket_status = (isset($request_data['ticket_status']) ? sanitize_text_field($request_data['ticket_status']) : '');

        $ticket_subject = (isset($request_data['ticket_subject']) ? sanitize_text_field($request_data['ticket_subject']) : '');
        $ticket_description = (isset($request_data['ticket_description']) ? wp_kses_post($request_data['ticket_description']) : '');
        $ticket_time = isset($request_data['ticket_time']) ? sanitize_text_field($request_data['ticket_time']) : '';

        if (empty($user_email)) {
            return $ticket_id;
        }

        $user = get_user_by('email', $user_email);
        $user_exists = (! empty($user) ? true : false);

        $custom_fields = $this->migration_custom_field_generate($request_data);
        $custom_fields_validate = ($cf_validate ? $this->migration_custom_field_validate($custom_fields, $user_email, $user_exists, $ticket_category_id) : array());

        if (!empty($custom_fields_validate)) {
            return $ticket_id;
        }

        if (empty($user_first_name)) {
            if (! empty($user_last_name)) {
                $user_first_name = $user_last_name;
                $user_last_name = "";
            } else {
                $user_first_name = substr($user_email, 0, strpos($user_email, '@'));
            }
        }

        if (empty($user)) {
            $newUser = new Apbd_Wps_User();

            $newUser->email = $user_email;
            $newUser->first_name = $user_first_name;
            $newUser->last_name = $user_last_name;
            $newUser->role = Apbd_wps_settings::GetModuleOption('client_role', 'subscriber');
            $newUser->username = ApbdWps_GenerateBaseUsername($user_first_name, $user_last_name, '', $user_email);
            $newUser->username = ApbdWps_GenerateUniqueUsername($newUser->username);

            if ($newUser->Save()) {
                $user = get_user_by('id', $newUser->id);
            } else {
                return $ticket_id;
            }
        } else {
            $current_first_name = $user->first_name;
            $current_last_name = $user->last_name;

            if (
                (
                    empty($current_first_name) &&
                    ($current_first_name !== $user_first_name)
                ) ||
                (
                    empty($current_last_name) &&
                    ($current_last_name !== $user_last_name)
                )
            ) {
                wp_update_user([
                    "ID" => $user->ID,
                    "first_name" => $user_first_name,
                    "last_name" => $user_last_name,
                    "display_name" => trim($user_first_name . " " . $user_last_name),
                ]);
            }
        }

        $ticketObj = new Mapbd_wps_ticket();

        if (empty($ticket_subject)) {
            $ticket_subject = $ticketObj->___("Ticket opened by %s at %s", $user_first_name, $ticket_time);
        }

        if (empty($ticket_description)) {
            $ticket_description = 'N/A';
        }

        $ticketObj->ticket_user($user->ID);
        $ticketObj->status('N');

        if (! empty($ticket_category_id)) {
            $ticketObj->cat_id($ticket_category_id);
        }

        if (! empty($ticket_agent_id)) {
            $ticketObj->assigned_on($ticket_agent_id);
            $ticketObj->assigned_date($ticket_time);
        }

        $ticketObj->title($ticket_subject);
        $ticketObj->ticket_body($ticket_description);
        $ticketObj->reply_counter(0);
        $ticketObj->opened_time($ticket_time);
        $ticketObj->last_reply_time($ticket_time);
        $ticketObj->is_public('N');
        $ticketObj->opened_by($user->ID);
        $ticketObj->opened_by_type('U');

        if (!empty($ticket_mailbox_email)) {
            $mailbox = Mapbd_wps_imap_api_settings::FindBy("connected_email", $ticket_mailbox_email, array("status" => "A"));

            if ($mailbox) {
                $ticketObj->mailbox_id($mailbox->id);
                $ticketObj->mailbox_type('M');
            } else {
                $mailbox = Mapbd_wps_imap_settings::FindBy("user_email", $ticket_mailbox_email, array("status" => "A"));

                if ($mailbox) {
                    $ticketObj->mailbox_id($mailbox->id);
                    $ticketObj->mailbox_type('T');
                }
            }
        }

        if (!empty($ticket_status)) {
            $ticketObj->status($ticket_status);

            if ('N' !== $ticket_status) {
                $ticketObj->last_status_update_time($ticket_time);
            }
        }

        if (Mapbd_wps_ticket::create_ticket($ticketObj, null, true)) {
            $ticket_id = $ticketObj->id;

            Mapbd_wps_ticket::AddTicketMeta($ticket_id, "_opened_by_migration", get_current_user_id());

            $ticket_tag_ids = (is_string($ticket_tag_ids) ? explode(',', $ticket_tag_ids) : $ticket_tag_ids);

            if (! empty($ticket_tag_ids)) {
                Mapbd_wps_ticket::AddTicketTags($ticket_id, $ticket_tag_ids);
            }

            $settingsObj = Apbd_wps_settings::GetModuleInstance();
            $settingsObj->save_ticket_meta($ticketObj, $custom_fields);

            $this->migration_move_attachments($ticket_id, 0, $attachments, $remove_original);
        }

        return $ticket_id;
    }

    public function migration_create_reply($ticketObj, $request_data, $attachments = array(), $remove_original = false)
    {
        $reply_id = 0;

        $user_type = (isset($request_data['user_type']) ? sanitize_text_field($request_data['user_type']) : '');
        $user_email = (isset($request_data['user_email']) ? sanitize_email($request_data['user_email']) : '');
        $user_first_name = (isset($request_data['user_first_name']) ? sanitize_text_field($request_data['user_first_name']) : '');
        $user_last_name = (isset($request_data['user_last_name']) ? sanitize_text_field($request_data['user_last_name']) : '');

        $reply_type = isset($request_data['reply_type']) ? sanitize_text_field($request_data['reply_type']) : '';
        $reply_content = isset($request_data['reply_content']) ? wp_kses_post($request_data['reply_content']) : '';
        $reply_time = isset($request_data['reply_time']) ? sanitize_text_field($request_data['reply_time']) : '';

        if (
            empty($user_email) ||
            empty($reply_content)
        ) {
            return $reply_id;
        }

        $user = get_user_by('email', $user_email);

        if (empty($user_first_name)) {
            if (! empty($user_last_name)) {
                $user_first_name = $user_last_name;
                $user_last_name = "";
            } else {
                $user_first_name = substr($user_email, 0, strpos($user_email, '@'));
            }
        }

        if (empty($user)) {
            $newUser = new Apbd_Wps_User();

            $newUser->email = $user_email;
            $newUser->first_name = $user_first_name;
            $newUser->last_name = $user_last_name;
            $newUser->role = Apbd_wps_settings::GetModuleOption('client_role', 'subscriber');
            $newUser->username = ApbdWps_GenerateBaseUsername($user_first_name, $user_last_name, '', $user_email);
            $newUser->username = ApbdWps_GenerateUniqueUsername($newUser->username);

            if ($newUser->Save()) {
                $user = get_user_by('id', $newUser->id);
            } else {
                return $reply_id;
            }
        } else {
            $current_first_name = $user->first_name;
            $current_last_name = $user->last_name;

            if (
                (
                    empty($current_first_name) &&
                    ($current_first_name !== $user_first_name)
                ) ||
                (
                    empty($current_last_name) &&
                    ($current_last_name !== $user_last_name)
                )
            ) {
                wp_update_user([
                    "ID" => $user->ID,
                    "first_name" => $user_first_name,
                    "last_name" => $user_last_name,
                    "display_name" => trim($user_first_name . " " . $user_last_name),
                ]);
            }
        }

        if ('internal_info' === $reply_type) {
            if (
                ('C' !== $ticketObj->status) ||
                ('Ticket has been closed' !== $reply_content)
            ) {
                return $reply_id;
            }

            $statusArray = $ticketObj->GetPropertyRawOptions('status');
            $statusName = $statusArray[$ticketObj->status];

            $logObj = new Mapbd_wps_ticket_log();

            $log_id = $logObj->GetNewLogId($ticketObj->id, 1);
            $log_msg = $ticketObj->___("Ticket status changed to %s Automatically", $statusName);

            $log_by = isset($ticketObj->assigned_on) ? absint($ticketObj->assigned_on) : 0;
            $log_by_type = 'A';

            if (empty($log_by)) {
                $log_by = isset($ticketObj->last_replied_by) ? absint($ticketObj->last_replied_by) : 0;
                $log_by_type = isset($ticketObj->last_replied_by_type) ? sanitize_text_field($ticketObj->last_replied_by_type) : 'A';

                if (empty($log_by)) {
                    $log_by = isset($ticketObj->ticket_user) ? absint($ticketObj->ticket_user) : 0;
                    $log_by_type = 'U';
                }
            }

            $logObj->ticket_id($ticketObj->id);
            $logObj->log_id($log_id);
            $logObj->log_by($log_by);
            $logObj->log_by_type($log_by_type);
            $logObj->log_msg($log_msg);
            $logObj->ticket_status($ticketObj->status);
            $logObj->log_for('B');
            $logObj->entry_time($reply_time);

            if ($logObj->Save()) {
                $reply_id = $logObj->log_id;

                $updateTicket = new Mapbd_wps_ticket();
                $updateTicket->last_status_update_time($reply_time);
                $updateTicket->SetWhereUpdate("id", $ticketObj->id);
                $updateTicket->Update();
            }
        } elseif ('note' === $reply_type) {
            $noteObj = new Mapbd_wps_notes();
            $noteObj->ticket_id($ticketObj->id);
            $noteObj->added_by($user->ID);
            $noteObj->note_text($reply_content);
            $noteObj->entry_date($reply_time);

            if ($noteObj->Save()) {
                $reply_id = $noteObj->id;
            }
        } else {
            $replyObj = new Mapbd_wps_ticket_reply();
            $replyObj->ticket_id($ticketObj->id);
            $replyObj->ticket_status($ticketObj->status);
            $replyObj->is_private("N");
            $replyObj->reply_text($reply_content);
            $replyObj->replied_by_type($user_type);
            $replyObj->replied_by($user->ID);
            $replyObj->is_user_seen("N");
            $replyObj->seen_time($reply_time);
            $replyObj->reply_time($reply_time);
            $replyObj->asigned_by($ticketObj->assigned_on);

            $replyObj = apply_filters("apbd-wps/filter/before-ticket-reply", $replyObj);

            if ($replyObj->Save()) {
                $reply_id = $replyObj->reply_id;

                Mapbd_wps_ticket::increaseReplyCounter($ticketObj->id, $user->ID, $user_type);

                $updateTicket = new Mapbd_wps_ticket();
                $updateTicket->last_reply_time($reply_time);
                $updateTicket->SetWhereUpdate("id", $ticketObj->id);
                $updateTicket->Update();

                $this->migration_move_attachments($ticketObj->id, $reply_id, $attachments, $remove_original);
            }
        }

        return $reply_id;
    }

    public function migration_custom_field_generate($request_data)
    {
        $ticket_wc_store_id = (isset($request_data['ticket_wc_store_id']) ? sanitize_text_field($request_data['ticket_wc_store_id']) : '');
        $ticket_wc_order_id = (isset($request_data['ticket_wc_order_id']) ? sanitize_text_field($request_data['ticket_wc_order_id']) : '');
        $ticket_el_purchase_code = (isset($request_data['ticket_el_purchase_code']) ? sanitize_text_field($request_data['ticket_el_purchase_code']) : '');
        $ticket_envato_purchase_code = (isset($request_data['ticket_envato_purchase_code']) ? sanitize_text_field($request_data['ticket_envato_purchase_code']) : '');
        $ticket_custom_fields = ((isset($request_data['ticket_custom_fields']) && is_array($request_data['ticket_custom_fields'])) ? $request_data['ticket_custom_fields'] : array());

        if (! empty($ticket_wc_order_id) && empty($ticket_wc_store_id)) {
            $wc_stores = Mapbd_wps_woocommerce::FindAllBy('status', 'A', array(), 'int_order', 'ASC');
            $wc_store = ((is_array($wc_stores) && isset($wc_stores[0])) ? $wc_stores[0] : null);
            $ticket_wc_store_id = ((is_object($wc_store) && isset($wc_store->id)) ? sanitize_text_field($wc_store->id) : '');
        }

        $custom_fields = array(
            'wc_store_id' => $ticket_wc_store_id,
            'wc_order_id' => $ticket_wc_order_id,
            'L1' => $ticket_el_purchase_code,
            'E1' => $ticket_envato_purchase_code,
        );

        $predfn_custom_fields = Mapbd_wps_custom_field::FindAllBy("status", "A");

        if (! empty($predfn_custom_fields)) {
            foreach ($predfn_custom_fields as $predfn_custom_field) {
                $id = absint($predfn_custom_field->id);
                $field_slug = sanitize_key($predfn_custom_field->field_slug);
                $field_key = sprintf('D%1$d', $id);
                $field_value = '';

                if (0 < strlen($field_slug) && isset($ticket_custom_fields[$field_slug])) {
                    $field_value = $ticket_custom_fields[$field_slug];
                } elseif (0 < strlen($field_slug) && isset($request_data['ticket_custom_fields__' . $field_slug])) {
                    $field_value = $request_data['ticket_custom_fields__' . $field_slug];
                } elseif (isset($ticket_custom_fields[$field_key])) {
                    $field_value = $ticket_custom_fields[$field_key];
                } elseif (isset($request_data['ticket_custom_fields__' . $field_key])) {
                    $field_value = $request_data['ticket_custom_fields__' . $field_key];
                }

                $custom_fields[$field_key] = strip_tags($field_value);
            }
        }

        return $custom_fields;
    }

    public function migration_custom_field_validate($custom_fields, $user_email, $user_exists, $ticket_category_id)
    {
        $response = apply_filters('apbd-wps/filter/ht-contact-form-custom-field-valid', array(), $custom_fields, $user_email, $user_exists, $ticket_category_id);
        $status = (! empty($response) ? (isset($response['status']) ? $response['status'] : false) : true);

        return ((false === $status) ? $response : array());
    }

    public function migration_move_attachments($ticket_id, $reply_id, $attachments, $remove_original = false)
    {
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $existing_path = $attachment->file_path;
                $move_to_path = Apbd_wps_settings::GetModuleInstance()->getTicketAttachedPath($ticket_id, $reply_id);

                if (
                    !file_exists($existing_path) ||
                    !is_dir($move_to_path)
                ) {
                    continue;
                }

                $filename = basename($existing_path);
                $filename = preg_replace('/^fluent_support-/', '', $filename);

                $destination_file = rtrim($move_to_path, '/') . '/' . $filename;

                if ($remove_original) {
                    rename($existing_path, $destination_file);
                } else {
                    copy($existing_path, $destination_file);
                }
            }
        }
    }
}
