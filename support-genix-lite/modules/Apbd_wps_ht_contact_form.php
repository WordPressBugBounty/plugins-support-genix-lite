<?php

/**
 * HT contact form.
 */

defined('ABSPATH') || exit;

class Apbd_wps_ht_contact_form extends ApbdWpsBaseModuleLite
{
    public function initialize()
    {
        parent::initialize();
        $this->disableDefaultForm();
    }

    public function OnInit()
    {
        parent::OnInit();
    }

    public function CreateTicket($request_data, $cf_validate = true)
    {
        $user_email = (isset($request_data['user_email']) ? sanitize_email($request_data['user_email']) : '');
        $user_first_name = (isset($request_data['user_first_name']) ? sanitize_text_field($request_data['user_first_name']) : '');
        $user_last_name = (isset($request_data['user_last_name']) ? sanitize_text_field($request_data['user_last_name']) : '');

        $ticket_category_id = (isset($request_data['ticket_category_id']) ? absint($request_data['ticket_category_id']) : 0);
        $ticket_subject = (isset($request_data['ticket_subject']) ? sanitize_text_field($request_data['ticket_subject']) : '');
        $ticket_description = (isset($request_data['ticket_description']) ? wp_kses_post($request_data['ticket_description']) : '');
        $ticket_attachment = (isset($request_data['ticket_attachment']) ? esc_url_raw($request_data['ticket_attachment']) : '');
        $ticket_attachments = (isset($request_data['ticket_attachments']) ? $request_data['ticket_attachments'] : array());

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
        $user_exists = (! empty($user) ? true : false);

        $custom_fields = $this->custom_field_generate($request_data);
        $custom_fields_validate = ($cf_validate ? $this->custom_field_validate($custom_fields, $user_email, $user_exists, $ticket_category_id) : array());

        if (! empty($custom_fields_validate)) {
            $response_data = $custom_fields_validate;

            return $response_data;
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
            Mapbd_wps_ticket::AddTicketMeta($ticketObj->id, "_opened_by_ht_contact_form", $user_email);

            $ticket_attachments = (is_string($ticket_attachments) ? explode(',', $ticket_attachments) : $ticket_attachments);
            $ticket_attachments = (is_array($ticket_attachments) ? $ticket_attachments : array());
            $ticket_attachments = array_map('esc_url_raw', $ticket_attachments);

            if (! empty($ticket_attachment)) {
                $ticket_attachments[] = $ticket_attachment;
            }

            if (! empty($ticket_attachments)) {
                $this->AddAttachment($ticketObj, $ticket_attachments);
            }

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

    public function custom_field_generate($request_data)
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

                $custom_fields[$field_key] = $field_value;
            }
        }

        return $custom_fields;
    }

    public function custom_field_validate($custom_fields, $user_email, $user_exists, $ticket_category_id)
    {
        $response = apply_filters('apbd-wps/filter/ht-contact-form-custom-field-valid', array(), $custom_fields, $user_email, $user_exists, $ticket_category_id);
        $status = (! empty($response) ? (isset($response['status']) ? $response['status'] : false) : true);

        return ((false === $status) ? $response : array());
    }

    private function AddAttachment($ticketObj, $ticket_attachments)
    {
        if (is_array($ticket_attachments) && ! empty($ticket_attachments)) {
            $ticket_path = Apbd_wps_settings::GetModuleInstance()->getTicketAttachedPath($ticketObj->id);

            $allowed_type = Apbd_wps_settings::GetModuleAllowedFileType();
            $allowed_type = array_map(function ($value) {
                return strtoupper($value);
            }, $allowed_type);

            foreach ($ticket_attachments as $file_url) {
                $file_name = pathinfo($file_url, PATHINFO_BASENAME);

                $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
                $file_extension = strtoupper($file_extension);
                $file_extension = ("JPEG" === $file_extension ? "JPG" : $file_extension);

                if (!empty($ticket_path) && !empty($file_url)) {
                    $file_content = file_get_contents($file_url);

                    if ($file_content) {
                        $file_path = $ticket_path . $file_name;
                        $file_pointer = fopen($file_path, 'wb');
                        fwrite($file_pointer, $file_content);
                        fclose($file_pointer);

                        if (in_array($file_extension, $allowed_type)) {
                            $file_save_name = md5(uniqid(rand())) . '___' . sanitize_file_name($file_name);
                            rename($file_path, $ticket_path . $file_save_name);
                        } else {
                            unlink($file_path);
                            Mapbd_wps_debug_log::AddGeneralLog("Unauthorized file type, {$file_name} Deleted", $file_name);
                        }
                    } else {
                        Mapbd_wps_debug_log::AddGeneralLog("File is not readable", $file_name);
                    }
                }
            }
        }

        Mapbd_wps_debug_log::AddGeneralLog("Finished attachment process");
    }
}
