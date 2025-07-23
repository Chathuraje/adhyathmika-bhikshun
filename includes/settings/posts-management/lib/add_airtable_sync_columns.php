<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('add_airtable_sync_columns')) {

    foreach (allowed_post_types_for_import_button() as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'add_airtable_sync_columns');
        add_action("manage_{$post_type}_posts_custom_column", 'render_airtable_sync_columns', 10, 2);
    }
    
    function add_airtable_sync_columns($columns) {
        $columns['airtable_sync_status'] = __('Airtable Sync Status', 'airtable-sync');
        $columns['airtable_sync_time'] = __('Last Sync Time', 'airtable-sync');
        return $columns;
    }

    function render_airtable_sync_columns($column, $post_id) {
        if ($column === 'airtable_sync_status') {
            $status = get_post_meta($post_id, '_ab_airtable_last_sync_status', true);
            if ($status === 'success') {
                echo '<span style="color:green;">✅ Success</span>';
            } elseif ($status === 'failed') {
                echo '<span style="color:red;">❌ Failed</span>';
            } else {
                echo '<em>—</em>';
            }
        }

        if ($column === 'airtable_sync_time') {
            $time = get_post_meta($post_id, '_ab_airtable_last_sync', true);
            if ($time) {
                echo esc_html(date('Y-m-d H:i', strtotime($time)));
            } else {
                echo '<em>—</em>';
            }
        }
    }
}

/**
 * Get Airtable sync summary with caching
 *
 * @return string Summary of Airtable sync status
 */
function get_airtable_sync_data() {
    // $cache_key = 'airtable_sync_summary_data_cache';
    // $cached = get_transient($cache_key);
    // if ($cached !== false) {
    //     return $cached;
    // }

    $post_types = allowed_post_types_for_import_button();
    if (empty($post_types)) {
        return [];
    }

    $data = [];

    foreach ($post_types as $post_type) {
        $total_posts = wp_count_posts($post_type)->publish ?? 0;

        $args = [
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_ab_airtable_last_sync',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $posts = get_posts($args);
        $synced = $success = $failed = 0;
        $sync_dates = [];
        $post_details = [];

        foreach ($posts as $post_id) {
            $status = get_post_meta($post_id, '_ab_airtable_last_sync_status', true);
            $sync_time = get_post_meta($post_id, '_ab_airtable_last_sync', true);
            $timestamp = $sync_time ? strtotime($sync_time) : 0;

            if ($status === 'success') $success++;
            elseif ($status === 'failed') $failed++;

            if ($timestamp) {
                $hourly_timestamp = strtotime(date('Y-m-d H:00:00', $timestamp));
                $sync_dates[] = $hourly_timestamp;
                $post_details[] = [
                    'title' => get_the_title($post_id),
                    'status' => $status,
                    'date' => date('Y-m-d H:i', $hourly_timestamp),
                ];
            }

            $synced++;
        }

        $latest_sync = !empty($sync_dates) ? max($sync_dates) : null;

        $data[] = [
            'post_type'    => $post_type,
            'total'        => $total_posts,
            'synced'       => $synced,
            'success'      => $success,
            'failed'       => $failed,
            'latest_sync'  => $latest_sync ? date('Y-m-d H:i', $latest_sync) : null,
            'details'      => $post_details,
            'unique_times' => array_unique($sync_dates),
        ];
    }

    // set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
    return $data;
}

?>