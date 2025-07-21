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
        $columns['airtable_sync_status'] = __('Airtable Sync Status', 'your-textdomain');
        $columns['airtable_sync_time'] = __('Last Sync Time', 'your-textdomain');
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
?>