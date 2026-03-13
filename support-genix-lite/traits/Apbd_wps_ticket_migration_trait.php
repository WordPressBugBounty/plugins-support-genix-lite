<?php

/**
 * Migration Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_ticket_migration_trait
{
    public function initialize__migration()
    {
        $this->AddAjaxAction("migration_data", [$this, "migration_data"]);
        $this->AddAjaxAction("migration_handle", [$this, "migration_handle"]);
    }

    public function migration_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $in_progress = get_option('sgnix_ticket_migration_in_progress', false);
        $data = ['in_progress' => $in_progress];

        if ($in_progress) {
            $progress_data = get_option('sgnix_ticket_migration_progress_data', false);

            $total_progress = 0;
            $categories_progress = 0;
            $tags_progress = 0;
            $custom_fields_progress = 0;
            $tickets_progress = 0;

            if ($progress_data) {
                $categories_count = absint($progress_data['categories_count']);
                $tags_count = absint($progress_data['tags_count']);
                $custom_fields_count = absint($progress_data['custom_fields_count']);
                $tickets_count = absint($progress_data['tickets_count']);

                $categories_done = absint($progress_data['categories_done']);
                $tags_done = absint($progress_data['tags_done']);
                $custom_fields_done = absint($progress_data['custom_fields_done']);
                $tickets_done = absint($progress_data['tickets_done']);

                $total_count = $categories_count + $tags_count + $custom_fields_count + $tickets_count;
                $total_done = $categories_done + $tags_done + $custom_fields_done + $tickets_done;

                $total_progress = (0 < $total_count) ? ceil(($total_done / $total_count) * 100) : 100;
                $categories_progress = (0 < $categories_count) ? ceil(($categories_done / $categories_count) * 100) : 100;
                $tags_progress = (0 < $tags_count) ? ceil(($tags_done / $tags_count) * 100) : 100;
                $custom_fields_progress = (0 < $custom_fields_count) ? ceil(($custom_fields_done / $custom_fields_count) * 100) : 100;
                $tickets_progress = (0 < $tickets_count) ? ceil(($tickets_done / $tickets_count) * 100) : 100;
            }

            $data = array_merge($data, [
                'total_progress' => $total_progress,
                'categories_progress' => $categories_progress,
                'tags_progress' => $tags_progress,
                'custom_fields_progress' => $custom_fields_progress,
                'tickets_progress' => $tickets_progress,
            ]);
        } else {
            // System info.
            $current_memory_limit = ini_get('memory_limit');
            $current_memory_limit = wp_convert_hr_to_bytes($current_memory_limit);
            $current_memory_limit = $current_memory_limit / 1024 / 1024;
            $required_memory_limit = 256;
            $memory_limit_status = $current_memory_limit >= $required_memory_limit;

            $current_max_execution_time = ini_get('max_execution_time');
            $required_max_execution_time = 300;
            $max_execution_time_status = $current_max_execution_time >= $required_max_execution_time;

            $system = [
                [
                    'key' => 1,
                    'setting' => 'memory_limit',
                    'current' => $current_memory_limit . 'M',
                    'required' => $required_memory_limit . 'M',
                    'status' => $memory_limit_status,
                ],
                [
                    'key' => 2,
                    'setting' => 'max_execution_time',
                    'current' => $current_max_execution_time,
                    'required' => $required_max_execution_time,
                    'status' => $max_execution_time_status,
                ],
            ];

            $system_error = !$memory_limit_status || !$max_execution_time_status;

            // Content info.
            $tickets_count = 0;
            $categories_count = 0;
            $tags_count = 0;
            $custom_fields_count = 0;

            $migrate_from = sanitize_text_field(ApbdWps_GetValue('migrate_from', ''));

            if (
                ('fluent-support' === $migrate_from) &&
                (is_plugin_active('fluent-support/fluent-support.php'))
            ) {
                $tickets_count = \FluentSupport\App\Models\Ticket::whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('fs_meta')
                        ->where('object_type', 'ticket_meta')
                        ->where('key', '_sgnix_ticket_migration')
                        ->whereColumn('fs_meta.object_id', 'fs_tickets.id');
                })->count();

                $categories_count = \FluentSupport\App\Models\Product::count();
                $tags_count = \FluentSupport\App\Models\Tag::count();

                if (defined('FLUENTSUPPORTPRO')) {
                    $custom_fields = \FluentSupportPro\App\Services\CustomFieldsService::getCustomFields();
                    $custom_fields_count = count($custom_fields);
                }
            }

            $content = [
                [
                    'key' => 1,
                    'content_type' => $this->__('Support tickets'),
                    'count' => $tickets_count,
                ],
                [
                    'key' => 2,
                    'content_type' => $this->__('Products (as categories)'),
                    'count' => $categories_count,
                ],
                [
                    'key' => 3,
                    'content_type' => $this->__('Tags'),
                    'count' => $tags_count,
                ],
                [
                    'key' => 4,
                    'content_type' => $this->__('Custom fields'),
                    'count' => $custom_fields_count,
                ],
            ];

            $content_error = !$tickets_count;

            // Errors.
            $has_error = $system_error || $content_error;
            $error_notice = '';

            if ($content_error) {
                $error_notice = $this->__('No tickets available for migration.');
            } elseif ($system_error) {
                $error_notice = $this->__('System requirements must be met to proceed.');
            }

            $data = array_merge($data, [
                'system' => $system,
                'system_error' => $system_error,
                'content' => $content,
                'content_error' => $content_error,
                'has_error' => $has_error,
                'error_notice' => $error_notice,
            ]);
        }

        $apiResponse->SetResponse(true, "", $data);

        echo wp_json_encode($apiResponse);
    }

    public function migration_handle()
    {
        $apiResponse = new Apbd_Wps_APIResponse();
        $apiResponse->SetResponse(false, $this->__('Invalid request.'));

        if (ApbdWps_IsPostBack) {
            $migrate_from = sanitize_text_field(ApbdWps_PostValue('migrate_from', ''));
            $remove_original = rest_sanitize_boolean(ApbdWps_PostValue('remove_original', ''));

            if (
                ('fluent-support' === $migrate_from) &&
                (is_plugin_active('fluent-support/fluent-support.php'))
            ) {
                try {
                    $this->migration_initialize();
                    $this->migrate_categories();
                    $this->migrate_tags();

                    if (defined('FLUENTSUPPORTPRO')) {
                        $this->migrate_custom_fields();
                    }

                    $this->migrate_tickets($remove_original);

                    $apiResponse->SetResponse(true, $this->__('Successfully migrated.'));
                } catch (Exception $e) {
                    $apiResponse->SetResponse(false, $e->getMessage());
                }

                $this->migrations_clear_status();
            }
        }

        echo wp_json_encode($apiResponse);
    }

    public function migration_initialize()
    {
        if (get_option('sgnix_ticket_migration_in_progress', false)) {
            throw new Exception($this->__('Migration is already in progress. Please wait for it to complete.'));
        }

        $this->migration_validate();

        // Content info.
        $tickets_count = 0;
        $categories_count = 0;
        $tags_count = 0;
        $custom_fields_count = 0;

        if (is_plugin_active('fluent-support/fluent-support.php')) {
            $tickets_count = \FluentSupport\App\Models\Ticket::whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fs_meta')
                    ->where('object_type', 'ticket_meta')
                    ->where('key', '_sgnix_ticket_migration')
                    ->whereColumn('fs_meta.object_id', 'fs_tickets.id');
            })->count();

            $categories_count = \FluentSupport\App\Models\Product::count();
            $tags_count = \FluentSupport\App\Models\Tag::count();

            if (defined('FLUENTSUPPORTPRO')) {
                $custom_fields = \FluentSupportPro\App\Services\CustomFieldsService::getCustomFields();
                $custom_fields_count = count($custom_fields);
            }
        }

        $progress_data = array(
            'tickets_count' => $tickets_count,
            'categories_count' => $categories_count,
            'tags_count' => $tags_count,
            'custom_fields_count' => $custom_fields_count,
            'tickets_done' => 0,
            'categories_done' => 0,
            'tags_done' => 0,
            'custom_fields_done' => 0,
        );

        update_option('sgnix_ticket_migration_start_time', time());
        update_option('sgnix_ticket_migration_in_progress', true);
        update_option('sgnix_ticket_migration_progress_data', $progress_data);
    }

    private function migration_validate()
    {
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        $required_memory = 256 * 1024 * 1024; // 256MB

        if ($memory_limit_bytes > 0 && $memory_limit_bytes < $required_memory) {
            throw new Exception($this->___('Insufficient memory limit (%s). Please increase to at least 256M for large migrations.', $memory_limit));
        }

        // Check execution time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 300) {
            throw new Exception($this->___('Insufficient execution time limit (%d seconds). Please increase to at least 300 seconds or set to 0 for unlimited.', $max_execution_time));
        }

        // Check database connectivity
        global $wpdb;
        if (!$wpdb->check_connection()) {
            throw new Exception($this->__('Database connection is unstable. Please check your database server.'));
        }

        // Check if WordPress is in maintenance mode
        if (wp_maintenance()) {
            throw new Exception($this->__('WordPress is in maintenance mode. Please complete maintenance before running migration.'));
        }

        // Check available disk space for logging (if possible)
        $upload_dir = wp_upload_dir();
        if (function_exists('disk_free_space') && is_dir($upload_dir['basedir'])) {
            $free_space = disk_free_space($upload_dir['basedir']);
            if ($free_space !== false && $free_space < (50 * 1024 * 1024)) { // 50MB
                throw new Exception($this->__('Low disk space detected. Please ensure sufficient space for migration logs.'));
            }
        }
    }

    public function migrate_categories()
    {
        $categories = \FluentSupport\App\Models\Product::all();

        foreach ($categories as $category) {
            $title = $category->title;

            $findObj = new Mapbd_wps_ticket_category();
            $findObj->title($title);

            if (!$findObj->Select()) {
                $createObj = new Mapbd_wps_ticket_category();
                $createObj->title($title);
                $createObj->status('A');

                if ($createObj->Save()) {
                    $this->migration_data_maper('categories', $category->id, $createObj->id);
                }
            } else {
                $updateObj = new Mapbd_wps_ticket_category();
                $updateObj->SetWhereUpdate("id", $findObj->id);
                $updateObj->status('A');
                $updateObj->Update();

                $this->migration_data_maper('categories', $category->id, $findObj->id);
            }

            $this->migration_done_count('categories');
        }
    }

    public function migrate_tags()
    {
        $tags = \FluentSupport\App\Models\Tag::all();

        foreach ($tags as $tag) {
            $title = $tag->title;

            $findObj = new Mapbd_wps_ticket_tag();
            $findObj->title($title);

            if (!$findObj->Select()) {
                $createObj = new Mapbd_wps_ticket_tag();
                $createObj->title($title);
                $createObj->status('A');

                if ($createObj->Save()) {
                    $this->migration_data_maper('tags', $tag->id, $createObj->id);
                }
            } else {
                $updateObj = new Mapbd_wps_ticket_tag();
                $updateObj->SetWhereUpdate("id", $findObj->id);
                $updateObj->status('A');
                $updateObj->Update();

                $this->migration_data_maper('tags', $tag->id, $findObj->id);
            }

            $this->migration_done_count('tags');
        }
    }

    public function migrate_custom_fields()
    {
        $fields = \FluentSupportPro\App\Services\CustomFieldsService::getCustomFields();

        $field_type_map = [
            'text' => 'T',
            'textarea' => 'T',
            'number' => 'N',
            'select-one' => 'W',
            'radio' => 'R',
            'checkbox' => 'T',
        ];

        foreach ($fields as $field) {
            $type = $field['type'];
            $label = $field['label'];
            $slug = $field['slug'];
            $options = isset($field['options']) ? $field['options'] : [];
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
            $admin_only = isset($field['admin_only']) ? $field['admin_only'] : 'no';
            $required = isset($field['required']) ? $field['required'] : 'no';

            $type = isset($field_type_map[$type]) ? $field_type_map[$type] : 'T';
            $slug = preg_replace('/^cf_/', '', $slug);
            $options = is_array($options) ? implode(',', $options) : '';
            $admin_only = ('yes' === $admin_only ? true : false);
            $required = ('yes' === $required ? true : false);

            $findObj = new Mapbd_wps_custom_field();
            $findObj->field_type($type);
            $findObj->field_slug($slug);

            if (in_array($type, ['R', 'W', 'E'], true)) {
                $findObj->fld_option($options);
            }

            if ($admin_only) {
                $findObj->create_for('A');
            }

            if ($required) {
                $findObj->is_required('Y');
            }

            if (!$findObj->Select()) {
                $createObj = new Mapbd_wps_custom_field();
                $createObj->field_type($type);
                $createObj->field_label($label);
                $createObj->field_slug($slug);
                $createObj->help_text($placeholder);
                $createObj->where_to_create('T');
                $createObj->choose_category('0');
                $createObj->status('A');

                if (in_array($type, ['R', 'W', 'E'], true)) {
                    $createObj->fld_option($options);
                }

                if ($admin_only) {
                    $createObj->create_for('A');
                } else {
                    $createObj->create_for('B');
                }

                if ($required) {
                    $createObj->is_required('Y');
                } else {
                    $createObj->is_required('N');
                }

                if ($createObj->Save()) {
                    $this->migration_data_maper('custom_fields', $slug, $createObj->field_slug);
                }
            } else {
                $updateObj = new Mapbd_wps_custom_field();
                $updateObj->SetWhereUpdate("id", $findObj->id);
                $updateObj->status('A');
                $updateObj->Update();

                $this->migration_data_maper('custom_fields', $slug, $findObj->field_slug);
            }

            $this->migration_done_count('custom_fields');
        }
    }

    public function migrate_tickets($remove_original = false)
    {
        $tickets = $this->migration_get_tickets();

        if (!empty($tickets)) {
            $data_map = get_option('sgnix_ticket_migration_mapping_data', []);
            $data_map = is_array($data_map) ? $data_map : [];

            $categories_map = isset($data_map['categories']) ? $data_map['categories'] : [];
            $tags_map = isset($data_map['tags']) ? $data_map['tags'] : [];
            $custom_fields_map = isset($data_map['custom_fields']) ? $data_map['custom_fields'] : [];

            $status_map = [
                'new' => 'N',
                'active' => 'A',
                'closed' => 'C',
            ];

            foreach ($tickets as $ticket) {
                $customer = $ticket->customer;
                $agent = $ticket->agent;
                $mailbox = $ticket->mailbox;
                $tags = $ticket->tags;
                $attachments = $ticket->attachments;

                $product_id = $ticket->product_id;
                $custom_fields = $ticket->custom_fields;
                $status = $ticket->status;

                $created_at = $ticket->created_at;
                $created_date = date('Y-m-d H:i:s', strtotime($created_at));

                $payload = array(
                    'user_email' => $customer->email,
                    'user_first_name' => $customer->first_name,
                    'user_last_name' => $customer->last_name,
                    'ticket_subject' => $ticket->title,
                    'ticket_description' => $ticket->content,
                    'ticket_time' => $created_date,
                );

                if (!empty($agent)) {
                    $agent_user = get_user_by('email', $agent->email);

                    if (!empty($agent_user)) {
                        $payload['ticket_agent_id'] = $agent_user->ID;
                    }
                }

                if (isset($categories_map[$product_id])) {
                    $payload['ticket_category_id'] = $categories_map[$product_id];
                }

                if (!empty($tags)) {
                    $tag_ids = [];

                    foreach ($tags as $tag) {
                        $tag_id = $tag->id;

                        if (isset($tags_map[$tag_id])) {
                            $tag_ids[] = $tags_map[$tag_id];
                        }
                    }

                    $payload['ticket_tag_ids'] = implode(',', array_unique($tag_ids));
                }

                if (!empty($mailbox)) {
                    $mailbox_email = $mailbox->email;

                    if (!empty($mailbox_email)) {
                        $payload['ticket_mailbox_email'] = $mailbox_email;
                    }
                }

                if (!empty($custom_fields)) {
                    foreach ($custom_fields as $custom_field_slug => $custom_field_value) {
                        $custom_field_slug = preg_replace('/^cf_/', '', $custom_field_slug);
                        $custom_field_value = is_array($custom_field_value) ? implode(',', $custom_field_value) : $custom_field_value;

                        if (isset($custom_fields_map[$custom_field_slug])) {
                            $custom_field_slug = $custom_fields_map[$custom_field_slug];
                            $payload['ticket_custom_fields__' . $custom_field_slug] = $custom_field_value;
                        }
                    }
                }

                if (isset($status_map[$status])) {
                    $payload['ticket_status'] = $status_map[$status];
                } else {
                    $payload['ticket_status'] = 'N';
                }

                $ticket_id = $this->migration_create_ticket($payload, $attachments, $remove_original);

                if ($ticket_id) {
                    $ticketObj = new Mapbd_wps_ticket();
                    $ticketObj->id($ticket_id);

                    if ($ticketObj->Select()) {
                        $this->migration_replies($ticket->id, $ticketObj);

                        if ($remove_original) {
                            $ticketService = new \FluentSupport\App\Services\Tickets\TicketService();
                            $ticketService->delete($ticket);
                        } else {
                            \FluentSupport\App\Models\Meta::insert([
                                'object_type' => 'ticket_meta',
                                'object_id' => $ticket->id,
                                'key' => '_sgnix_ticket_migration',
                                'value' => $ticket_id,
                            ]);
                        }

                        $this->migration_done_count('tickets');

                        if ($this->migration_execution_alert()) {
                            $this->migrations_clear_status();
                            break;
                        }
                    }
                }
            }
        }
    }

    public function migration_replies($findById, $ticketObj)
    {
        $replies = $this->migration_get_replies($findById);

        if (!empty($replies)) {
            foreach ($replies as $reply) {
                $person = $reply->person;
                $attachments = $reply->attachments;

                $created_at = $reply->created_at;
                $created_date = date('Y-m-d H:i:s', strtotime($created_at));

                $payload = array(
                    'user_type' => ('agent' === $person->person_type ? 'A' : 'U'),
                    'user_email' => $person->email,
                    'user_first_name' => $person->first_name,
                    'user_last_name' => $person->last_name,
                    'reply_type' => $reply->conversation_type,
                    'reply_content' => $reply->content,
                    'reply_time' => $created_date,
                );

                $this->migration_create_reply($ticketObj, $payload, $attachments);
            }
        }
    }

    public function migration_get_tickets()
    {
        $ticketWith = [
            'customer' => function ($query) {
                $query->select(['id', 'email', 'first_name', 'last_name']);
            },
            'agent' => function ($query) {
                $query->select(['id', 'email', 'first_name', 'last_name']);
            },
            'mailbox',
            'tags',
            'attachments',
        ];

        $tickets = \FluentSupport\App\Models\Ticket::with($ticketWith)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fs_meta')
                    ->where('object_type', 'ticket_meta')
                    ->where('key', '_sgnix_ticket_migration')
                    ->whereColumn('fs_meta.object_id', 'fs_tickets.id');
            })
            ->orderBy('id', 'ASC')
            ->limit(100)
            ->get();

        if (defined('FLUENTSUPPORTPRO')) {
            foreach ($tickets as $ticket) {
                $ticket->custom_fields = $ticket->customData();
            }
        }

        return $tickets;
    }

    public function migration_get_replies($ticketId)
    {
        $replyWith = [
            'person' => function ($query) {
                $query->select(['first_name', 'email', 'person_type', 'last_name', 'id']);
            },
            'attachments',
        ];

        $replies = \FluentSupport\App\Models\Conversation::where('ticket_id', $ticketId)
            ->whereIn('conversation_type', ['response', 'note', 'internal_info'])
            ->with($replyWith)
            ->orderBy('id', 'ASC')
            ->get();

        return $replies;
    }

    public function migration_data_maper($item = '', $old = '', $new = '')
    {
        $data = get_option('sgnix_ticket_migration_mapping_data', []);
        $data = is_array($data) ? $data : [];

        if (!empty($item) && !empty($old) && !empty($new)) {
            $item_data = isset($data[$item]) ? $data[$item] : [];
            $item_data = is_array($item_data) ? $item_data : [];

            $item_data[$old] = $new;
            $data[$item] = $item_data;
        }

        update_option('sgnix_ticket_migration_mapping_data', $data);
    }

    public function migration_done_count($item = '')
    {
        $data = get_option('sgnix_ticket_migration_progress_data', []);
        $data = is_array($data) ? $data : [];

        if (!empty($item)) {
            $item_key = $item . '_done';

            $item_count = isset($data[$item_key]) ? absint($data[$item_key]) : 0;
            $item_count = $item_count + 1;

            $data[$item_key] = $item_count;
        }

        update_option('sgnix_ticket_migration_progress_data', $data);
    }

    public function migration_execution_alert()
    {
        $alert = false;

        $start_time = get_option('sgnix_ticket_migration_start_time', false);

        if ($start_time) {
            $current_time = time();
            $execution_time = $current_time - $start_time;
            $max_execution_time = ini_get('max_execution_time');

            if (($max_execution_time - 30) < $execution_time) {
                $alert = true;
            }
        }

        return $alert;
    }

    public function migrations_clear_status()
    {
        delete_option('sgnix_ticket_migration_start_time');
        delete_option('sgnix_ticket_migration_in_progress');
        delete_option('sgnix_ticket_migration_progress_data');
        delete_option('sgnix_ticket_migration_mapping_data');
    }
}
