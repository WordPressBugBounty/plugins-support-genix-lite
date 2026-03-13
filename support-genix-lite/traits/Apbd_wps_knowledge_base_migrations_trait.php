<?php

/**
 * Migrations Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_migrations_trait
{
    private $migration_config;

    public function initialize__migrations()
    {
        $this->AddAjaxAction("migrations_data", [$this, "migrations_data"]);
        $this->AddAjaxAction("migration_handle", [$this, "migration_handle"]);
    }

    public function migrations_data()
    {
        $apiResponse = new Apbd_Wps_APIResponse();

        $in_progress = get_option('migration_in_progress', false);
        $data = ['in_progress' => $in_progress];

        if ($in_progress) {
            $progress_data = get_option('migration_progress_data', false);

            $total_progress = 0;
            $categories_progress = 0;
            $tags_progress = 0;
            $docs_progress = 0;

            if ($progress_data) {
                $categories_count = absint($progress_data['categories_count']);
                $tags_count = absint($progress_data['tags_count']);
                $docs_count = absint($progress_data['docs_count']);

                $categories_done = absint($progress_data['categories_done']);
                $tags_done = absint($progress_data['tags_done']);
                $docs_done = absint($progress_data['docs_done']);

                $total_count = $categories_count + $tags_count + $docs_count;
                $total_done = $categories_done + $tags_done + $docs_done;

                $total_progress = (0 < $total_count) ? ceil(($total_done / $total_count) * 100) : 100;
                $categories_progress = (0 < $categories_count) ? ceil(($categories_done / $categories_count) * 100) : 100;
                $tags_progress = (0 < $tags_count) ? ceil(($tags_done / $tags_count) * 100) : 100;
                $docs_progress = (0 < $docs_count) ? ceil(($docs_done / $docs_count) * 100) : 100;
            }

            $data = array_merge($data, [
                'total_progress' => $total_progress,
                'categories_progress' => $categories_progress,
                'tags_progress' => $tags_progress,
                'docs_progress' => $docs_progress,
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
            $multiple_kb = 0;
            $posts_count = 0;
            $categories_count = 0;
            $tags_count = 0;

            if (is_plugin_active('betterdocs/betterdocs.php')) {
                if (taxonomy_exists('knowledge_base')) {
                    $multiple_kb = 1;
                }

                $count_posts = wp_count_posts('docs');

                if ($count_posts) {
                    $publish_posts_count = isset($count_posts->publish) ? absint($count_posts->publish) : 0;
                    $draft_posts_count = isset($count_posts->draft) ? absint($count_posts->draft) : 0;
                    $private_posts_count = isset($count_posts->private) ? absint($count_posts->private) : 0;
                    $posts_count = $publish_posts_count + $draft_posts_count + $private_posts_count;
                }

                $categories_count = wp_count_terms([
                    'taxonomy' => 'doc_category',
                    'hide_empty' => false
                ]);

                $tags_count = wp_count_terms([
                    'taxonomy' => 'doc_tag',
                    'hide_empty' => false
                ]);
            }

            $content = [
                [
                    'key' => 1,
                    'content_type' => $this->__('Docs (Posts)'),
                    'count' => $posts_count,
                ],
                [
                    'key' => 2,
                    'content_type' => $this->__('Categories'),
                    'count' => $categories_count,
                ],
                [
                    'key' => 3,
                    'content_type' => $this->__('Tags'),
                    'count' => $tags_count,
                ],
            ];

            $content_error = !$posts_count;

            // Errors.
            $has_error = $system_error || $multiple_kb || $content_error;
            $error_notice = '';

            if ($multiple_kb) {
                $error_notice = $this->__('Multiple Knowledge Base is enabled in BetterDocs. This requires Support Genix Pro. Please upgrade to continue the migration.');
            } elseif ($content_error) {
                $error_notice = $this->__('No docs (posts) available for migration.');
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

            if ('betterdocs' === $migrate_from) {
                $this->migration_config = [
                    'source' => [
                        'post_type' => 'docs',
                        'taxonomies' => [
                            'doc_category',
                            'doc_tag',
                        ],
                    ],
                    'target' => [
                        'post_type' => 'sgkb-docs',
                        'taxonomies' => [
                            'sgkb-docs-category',
                            'sgkb-docs-tag',
                        ],
                    ],
                ];
            }

            if ($this->migration_config) {
                try {
                    $result = $this->migration_perform();
                    $apiResponse->SetResponse(true, $this->__('Successfully migrated.'), $result);
                } catch (Exception $e) {
                    $apiResponse->SetResponse(false, $e->getMessage());
                }
            }
        }

        echo wp_json_encode($apiResponse);
    }

    private function migration_perform()
    {
        global $wpdb;

        // Check if migration is already in progress
        if (get_option('migration_in_progress', false)) {
            throw new Exception($this->__('Migration is already in progress. Please wait for it to complete.'));
        }

        if (taxonomy_exists('knowledge_base')) {
            throw new Exception($this->__('Multiple Knowledge Base is not supported yet.'));
        }

        // Validate environment before starting
        $this->migration_validate_environment();

        // Set migration status
        update_option('migration_in_progress', true);
        update_option('migration_start_time', current_time('timestamp'));

        $this->migration_initialize();

        // $wpdb->query('START TRANSACTION');

        try {
            $results = [
                'migrated_posts' => 0,
                'migrated_terms' => 0,
                'term_mappings' => [],
                'errors' => [],
                'warnings' => [],
                'start_time' => current_time('mysql'),
                'batch_size' => 100
            ];

            // Step 1: Validate prerequisites
            $this->migration_validate_prerequisites();

            // Step 2: Migrate taxonomy terms
            $term_mappings = $this->migration_migrate_taxonomy_terms();
            $results['term_mappings'] = $term_mappings;
            $results['migrated_terms'] = $this->migration_count_migrated_terms($term_mappings);

            // Step 3: Migrate posts with batch processing
            $migrated_posts = $this->migration_migrate_posts_with_batching($term_mappings, $results['batch_size']);
            $results['migrated_posts'] = $migrated_posts;

            // Step 4: Final cleanup and term count updates
            $this->migration_finalize();

            $results['end_time'] = current_time('mysql');
            $results['duration'] = current_time('timestamp') - get_option('migration_start_time');

            // Commit transaction
            // $wpdb->query('COMMIT');

            // Log successful migration
            $this->migration_log_success($results);

            // Clear migration status
            $this->migrations_clear_status();

            return $results;
        } catch (Exception $e) {
            // Rollback transaction
            // $wpdb->query('ROLLBACK');

            // Log error
            $this->migration_log_error($e->getMessage());

            // Clear migration status
            $this->migrations_clear_status();

            throw $e;
        }
    }

    private function migration_validate_environment()
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

    private function migration_validate_prerequisites()
    {
        $source_post_type = $this->migration_config['source']['post_type'];
        $target_post_type = $this->migration_config['target']['post_type'];

        if (!post_type_exists($source_post_type)) {
            throw new Exception($this->___('Source post type does not exist: %s', $source_post_type));
        }

        if (!post_type_exists($target_post_type)) {
            throw new Exception($this->___('Target post type does not exist: %s', $target_post_type));
        }

        $count_posts = wp_count_posts($source_post_type);
        $total_posts_count = 0;

        if ($count_posts) {
            $publish_posts_count = isset($count_posts->publish) ? absint($count_posts->publish) : 0;
            $draft_posts_count = isset($count_posts->draft) ? absint($count_posts->draft) : 0;
            $private_posts_count = isset($count_posts->private) ? absint($count_posts->private) : 0;
            $total_posts_count = $publish_posts_count + $draft_posts_count + $private_posts_count;
        }

        if ($total_posts_count === 0) {
            throw new Exception($this->___('No posts found for source post type: %s', $source_post_type));
        }

        // Validate source taxonomies exist
        foreach ($this->migration_config['source']['taxonomies'] as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                throw new Exception($this->___('Source taxonomy does not exist: %s', $taxonomy));
            }
        }

        // Validate target taxonomies exist
        foreach ($this->migration_config['target']['taxonomies'] as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                throw new Exception($this->___('Target taxonomy does not exist: %s', $taxonomy));
            }
        }

        // Check if target post type supports the target taxonomies
        $target_post_type_obj = get_post_type_object($target_post_type);
        if (!$target_post_type_obj) {
            throw new Exception($this->___('Cannot retrieve target post type object: %s', $target_post_type));
        }

        // Validate taxonomy support for target post type
        foreach ($this->migration_config['target']['taxonomies'] as $taxonomy) {
            if (!is_object_in_taxonomy($target_post_type, $taxonomy)) {
                throw new Exception($this->___('Target post type "%s" does not support taxonomy "%s"', $target_post_type, $taxonomy));
            }
        }
    }

    private function migration_migrate_taxonomy_terms()
    {
        $term_mappings = [];
        $source_taxonomies = $this->migration_config['source']['taxonomies'];
        $target_taxonomies = $this->migration_config['target']['taxonomies'];

        for ($i = 0; $i < count($source_taxonomies); $i++) {
            $source_taxonomy = $source_taxonomies[$i];
            $target_taxonomy = $target_taxonomies[$i];

            $source_terms = get_terms([
                'taxonomy' => $source_taxonomy,
                'hide_empty' => false,
                'get' => 'all'
            ]);

            if (is_wp_error($source_terms) || empty($source_terms)) {
                continue;
            }

            // Initialize term mapping for this taxonomy
            $term_mappings[$source_taxonomy] = [];

            // First pass: create all terms without parents
            foreach ($source_terms as $term) {
                $new_term = $this->migration_create_equivalent_term($term, $target_taxonomy);
                if ($new_term && !is_wp_error($new_term)) {
                    $term_mappings[$source_taxonomy][$term->term_id] = $new_term['term_id'];
                }

                // Store done count
                if ('sgkb-docs-category' === $target_taxonomy) {
                    $this->migrations_update_done_count('categories_done');
                } elseif ('sgkb-docs-tag' === $target_taxonomy) {
                    $this->migrations_update_done_count('tags_done');
                }
            }

            // Second pass: set up parent relationships
            foreach ($source_terms as $term) {
                if ($term->parent > 0 && isset($term_mappings[$source_taxonomy][$term->term_id])) {
                    $parent_id = isset($term_mappings[$source_taxonomy][$term->parent])
                        ? $term_mappings[$source_taxonomy][$term->parent]
                        : 0;

                    $this->migration_update_term_parent(
                        $term_mappings[$source_taxonomy][$term->term_id],
                        $parent_id,
                        $target_taxonomy
                    );
                }
            }

            // Update term counts for the target taxonomy
            if (!empty($term_mappings[$source_taxonomy])) {
                $new_term_ids = array_values($term_mappings[$source_taxonomy]);
                wp_update_term_count($new_term_ids, $target_taxonomy);
            }
        }

        return $term_mappings;
    }

    private function migration_create_equivalent_term($source_term, $target_taxonomy)
    {
        // Check if term already exists by slug
        $existing_term = get_term_by('slug', $source_term->slug, $target_taxonomy);
        if ($existing_term) {
            return ['term_id' => $existing_term->term_id];
        }

        // Check if term already exists by name (fallback)
        $existing_term_by_name = get_term_by('name', $source_term->name, $target_taxonomy);
        if ($existing_term_by_name) {
            return ['term_id' => $existing_term_by_name->term_id];
        }

        // Handle potential slug conflicts by creating unique slug
        $original_slug = $source_term->slug;
        $term_slug = $original_slug;
        $counter = 1;

        while (get_term_by('slug', $term_slug, $target_taxonomy)) {
            $term_slug = $original_slug . '-' . $counter;
            $counter++;
            if ($counter > 100) {
                // Prevent infinite loop - use timestamp as fallback
                $term_slug = $original_slug . '-' . time();
                break;
            }
        }

        // Create new term
        $new_term = wp_insert_term(
            $source_term->name,
            $target_taxonomy,
            [
                'description' => $source_term->description,
                'slug' => $term_slug,
                'parent' => 0 // Will be set in second pass
            ]
        );

        // Copy term meta if any exists
        if (!is_wp_error($new_term)) {
            $this->migration_copy_term_meta($source_term->term_id, $new_term['term_id']);
        }

        return $new_term;
    }

    private function migration_update_term_parent($term_id, $parent_id, $taxonomy)
    {
        wp_update_term($term_id, $taxonomy, [
            'parent' => $parent_id
        ]);
    }

    private function migration_copy_term_meta($source_term_id, $target_term_id)
    {
        $meta_keys = get_term_meta($source_term_id);

        foreach ($meta_keys as $meta_key => $meta_values) {
            foreach ($meta_values as $meta_value) {
                add_term_meta($target_term_id, $meta_key, maybe_unserialize($meta_value));
            }
        }
    }

    private function migration_migrate_posts_with_batching($term_mappings, $batch_size = 100)
    {
        $migrated_count = 0;
        $offset = 0;
        $start_time = time();
        $max_execution_time = ini_get('max_execution_time');
        $time_buffer = 15; // Leave 15 seconds buffer

        do {
            // Check execution time (if limit is set)
            if ($max_execution_time > 0 && (time() - $start_time) > ($max_execution_time - $time_buffer)) {
                throw new Exception($this->__('Migration timeout approaching. Please increase max_execution_time or run migration again to continue from where it left off.'));
            }

            // Memory management
            $this->migration_manage_memory();

            $posts = get_posts([
                'post_type' => $this->migration_config['source']['post_type'],
                'post_status' => ['publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'trash'],
                'numberposts' => $batch_size,
                'offset' => $offset,
                'suppress_filters' => false
            ]);

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                // Update post type
                $updated = wp_update_post([
                    'ID' => $post->ID,
                    'post_type' => $this->migration_config['target']['post_type']
                ], true, false);

                if (is_wp_error($updated)) {
                    error_log($this->___('Failed to migrate post ID %s: %s', $post->ID, $updated->get_error_message()));
                    continue;
                }

                // Migrate taxonomy relationships
                $this->migration_migrate_post_taxonomy_relationships($post->ID, $term_mappings);

                // Migrate post meta if needed
                $this->migration_migrate_post_meta($post->ID);

                // Update done count
                $this->saveDocsHook($post->ID);

                // Store done count
                $this->migrations_update_done_count('docs_done');

                $migrated_count++;
            }

            $offset += $batch_size;

            // Prevent memory issues and timeout
            if (function_exists('wp_suspend_cache_addition')) {
                wp_suspend_cache_addition(true);
            }

            // Clear object cache periodically
            if ($offset % 500 === 0) {
                wp_cache_flush();
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        } while (count($posts) === $batch_size);

        return $migrated_count;
    }

    private function migration_manage_memory()
    {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $current_usage = memory_get_usage(true);

        if ($memory_limit > 0 && $current_usage > ($memory_limit * 0.8)) {
            wp_cache_flush();

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            // If still high memory usage, throw exception
            $current_usage_after = memory_get_usage(true);
            if ($current_usage_after > ($memory_limit * 0.9)) {
                throw new Exception($this->___('Memory usage too high (%s of %s). Please increase memory_limit or reduce batch size.', size_format($current_usage_after), size_format($memory_limit)));
            }
        }
    }

    private function migration_migrate_post_meta($post_id)
    {
        // Get all post meta
        $post_meta = get_post_meta($post_id);

        foreach ($post_meta as $meta_key => $meta_values) {
            if (strpos($meta_key, 'betterdocs') === false) {
                continue;
            }

            $new_meta_key = str_replace('betterdocs', 'sg_docs', $meta_key);

            if (!get_post_meta($post_id, $new_meta_key, true)) {
                foreach ($meta_values as $meta_value) {
                    $meta_value = maybe_unserialize($meta_value);
                    add_post_meta($post_id, $new_meta_key, $meta_value);
                }
            }

            // Handle BetterDocs specific meta that might reference old taxonomies
            if (
                (strpos($meta_key, 'doc_category') !== false) ||
                (strpos($meta_key, 'doc_tag') !== false)
            ) {
                // Log potential meta that might need manual review
                error_log($this->___('Post %d has meta key "%s" that might reference old taxonomies. Please review manually.', $post_id, $meta_key));
            }
        }
    }

    private function migration_migrate_post_taxonomy_relationships($post_id, $term_mappings)
    {
        $source_taxonomies = $this->migration_config['source']['taxonomies'];
        $target_taxonomies = $this->migration_config['target']['taxonomies'];

        for ($i = 0; $i < count($source_taxonomies); $i++) {
            $source_taxonomy = $source_taxonomies[$i];
            $target_taxonomy = $target_taxonomies[$i];

            // Get current terms
            $current_terms = wp_get_post_terms($post_id, $source_taxonomy, ['fields' => 'ids']);

            if (is_wp_error($current_terms) || empty($current_terms)) {
                continue;
            }

            // Map to new term IDs
            $new_term_ids = [];
            foreach ($current_terms as $term_id) {
                if (isset($term_mappings[$source_taxonomy][$term_id])) {
                    $new_term_ids[] = $term_mappings[$source_taxonomy][$term_id];
                }
            }

            // Set new terms
            if (!empty($new_term_ids)) {
                $set_result = wp_set_post_terms($post_id, $new_term_ids, $target_taxonomy);
                if (is_wp_error($set_result)) {
                    error_log($this->___('Failed to set terms for post %d in taxonomy %s: %s', $post_id, $target_taxonomy, $set_result->get_error_message()));
                }
            }

            // Remove old taxonomy relationships
            wp_set_post_terms($post_id, [], $source_taxonomy);
        }
    }

    private function migration_count_migrated_terms($term_mappings)
    {
        $total = 0;
        foreach ($term_mappings as $taxonomy => $mappings) {
            $total += count($mappings);
        }
        return $total;
    }

    private function migration_initialize()
    {
        $docs_count = 0;
        $count_posts = wp_count_posts('docs');

        if ($count_posts) {
            $publish_posts_count = isset($count_posts->publish) ? absint($count_posts->publish) : 0;
            $draft_posts_count = isset($count_posts->draft) ? absint($count_posts->draft) : 0;
            $private_posts_count = isset($count_posts->private) ? absint($count_posts->private) : 0;
            $docs_count = $publish_posts_count + $draft_posts_count + $private_posts_count;
            $docs_count = min($docs_count, 100);
        }

        $categories_count = wp_count_terms([
            'taxonomy' => 'doc_category',
            'hide_empty' => false
        ]);

        $tags_count = wp_count_terms([
            'taxonomy' => 'doc_tag',
            'hide_empty' => false
        ]);

        $progress_data = [
            'categories_count' => $categories_count,
            'tags_count' => $tags_count,
            'docs_count' => $docs_count,
            'categories_done' => 0,
            'tags_done' => 0,
            'docs_done' => 0,
        ];

        update_option('migration_progress_data', $progress_data);
    }

    private function migration_finalize()
    {
        // Update term counts for all target taxonomies
        foreach ($this->migration_config['target']['taxonomies'] as $taxonomy) {
            // Get all terms for this taxonomy
            $all_terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids'
            ]);

            if (!is_wp_error($all_terms) && !empty($all_terms)) {
                wp_update_term_count($all_terms, $taxonomy);
            }
        }

        // Clear relevant caches
        wp_cache_flush();

        // Clean up orphaned term relationships (if any)
        $this->migration_cleanup_orphaned_term_relationships();

        // Regenerate rewrite rules if needed
        flush_rewrite_rules();
    }

    private function migration_cleanup_orphaned_term_relationships()
    {
        global $wpdb;

        // Clean up term relationships for source taxonomies in smaller batches
        foreach ($this->migration_config['source']['taxonomies'] as $taxonomy) {
            $batch_size = 1000;
            $processed = 0;

            do {
                // Get orphaned relationship IDs in batches
                $orphaned_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT tr.object_id
                    FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                    WHERE tt.taxonomy = %s AND p.ID IS NULL
                    LIMIT %d OFFSET %d
                ", $taxonomy, $batch_size, $processed));

                if (!empty($orphaned_ids)) {
                    $placeholders = implode(',', array_fill(0, count($orphaned_ids), '%d'));
                    $query = "
                        DELETE tr FROM {$wpdb->term_relationships} tr
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        WHERE tt.taxonomy = %s AND tr.object_id IN ({$placeholders})
                    ";
                    $params = array_merge([$taxonomy], $orphaned_ids);
                    $prepared_query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($query), $params));
                    $wpdb->query($prepared_query);
                }

                $processed += $batch_size;
            } while (count($orphaned_ids) === $batch_size);
        }
    }

    public function migrations_get_status()
    {
        return [
            'in_progress' => get_option('migration_in_progress', false),
            'start_time' => get_option('migration_start_time', null),
            'last_logs' => $this->migrations_get_recent_logs(5),
            'memory_usage' => [
                'current' => size_format(memory_get_usage(true)),
                'peak' => size_format(memory_get_peak_usage(true)),
                'limit' => ini_get('memory_limit')
            ],
            'execution_time' => [
                'limit' => ini_get('max_execution_time'),
                'elapsed' => get_option('migration_start_time') ? (current_time('timestamp') - get_option('migration_start_time')) : 0
            ]
        ];
    }

    public function migrations_get_recent_logs($limit = 10)
    {
        $logs = get_option('post_type_migration_logs', []);
        return array_slice($logs, -$limit);
    }

    public function migrations_update_done_count($item = '')
    {
        $progress_data = get_option('migration_progress_data', false);

        if (isset($progress_data[$item])) {
            $progress_data[$item] = absint($progress_data[$item]) + 1;
            update_option('migration_progress_data', $progress_data);
        }
    }

    public function migrations_clear_status()
    {
        delete_option('migration_in_progress');
        delete_option('migration_progress_data');
        delete_option('migration_start_time');
    }

    private function migration_log_success($results)
    {
        $duration = isset($results['duration']) ? $results['duration'] : 0;
        $memory_peak = size_format(memory_get_peak_usage(true));

        $message = $this->___(
            'Migration completed successfully. Posts: %d, Terms: %d, Duration: %d seconds, Peak Memory: %s',
            $results['migrated_posts'],
            $results['migrated_terms'],
            $duration,
            $memory_peak
        );

        error_log($message);

        // Store migration log in option for admin display
        $this->migration_store_log('success', $message, $results);
    }

    private function migration_log_error($error_message)
    {
        $full_message = $this->___('Migration failed: %s', $error_message);
        error_log($full_message);

        // Add system info to error log
        $system_info = [
            'memory_usage' => size_format(memory_get_usage(true)),
            'memory_peak' => size_format(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ];

        $this->migration_store_log('error', $error_message, $system_info);
    }

    private function migration_store_log($status, $message, $data = [])
    {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];

        $existing_logs = get_option('post_type_migration_logs', []);
        $existing_logs[] = $log_entry;

        // Keep only last 20 logs (increased from 10)
        if (count($existing_logs) > 20) {
            $existing_logs = array_slice($existing_logs, -20);
        }

        update_option('post_type_migration_logs', $existing_logs);
    }

    public function migrations_get_logs()
    {
        return get_option('post_type_migration_logs', []);
    }

    public function migrations_clear_logs()
    {
        delete_option('post_type_migration_logs');
    }
}
