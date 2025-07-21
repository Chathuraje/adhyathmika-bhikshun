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
function render_airtable_sync_summary() {
    $cache_key = 'airtable_sync_summary_table_cache';
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        echo $cached;
        return;
    }

    $post_types = allowed_post_types_for_import_button();
    if (empty($post_types)) {
        echo '<p>No post types found for Airtable sync.</p>';
        return;
    }

    $summary_rows = '';
    $total_synced = $total_success = $total_failed = 0;
    $latest_sync_global = 0;

    foreach ($post_types as $post_type) {
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
        if (!$posts) {
            continue;
        }

        $synced = $success = $failed = 0;
        $latest_sync = 0;

        foreach ($posts as $post_id) {
            $status = get_post_meta($post_id, '_ab_airtable_last_sync_status', true);
            $time = get_post_meta($post_id, '_ab_airtable_last_sync', true);
            $timestamp = $time ? strtotime($time) : 0;

            if ($status === 'success') {
                $success++;
            } elseif ($status === 'failed') {
                $failed++;
            }

            if ($timestamp > $latest_sync) {
                $latest_sync = $timestamp;
            }

            if ($timestamp > $latest_sync_global) {
                $latest_sync_global = $timestamp;
            }

            $synced++;
        }

        $total_synced += $synced;
        $total_success += $success;
        $total_failed += $failed;

        $summary_rows .= sprintf(
            '<tr>
                <td>%s</td>
                <td style="text-align:center;">%d</td>
                <td style="text-align:center; color:green;">%d</td>
                <td style="text-align:center; color:red;">%d</td>
                <td style="text-align:center;">%s</td>
            </tr>',
            esc_html(ucfirst($post_type)),
            $synced,
            $success,
            $failed,
            $latest_sync ? date('Y-m-d H:i', $latest_sync) : '—'
        );
    }

    if (!$summary_rows) {
        echo '<p>No synced posts found.</p>';
        return;
    }

    $summary_table = '
    <div style="margin-top: 30px;">
        <h2>Airtable Sync Overview</h2>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Post Type</th>
                    <th style="text-align:center;">Total Synced</th>
                    <th style="text-align:center;">Success</th>
                    <th style="text-align:center;">Failed</th>
                    <th style="text-align:center;">Latest Sync</th>
                </tr>
            </thead>
            <tbody>
                ' . $summary_rows . '
            </tbody>
            <tfoot>
                <tr style="font-weight:bold;">
                    <td>Total</td>
                    <td style="text-align:center;">' . $total_synced . '</td>
                    <td style="text-align:center; color:green;">' . $total_success . '</td>
                    <td style="text-align:center; color:red;">' . $total_failed . '</td>
                    <td style="text-align:center;">' . ($latest_sync_global ? date('Y-m-d H:i', $latest_sync_global) : '—') . '</td>
                </tr>
            </tfoot>
        </table>
    </div>';

    // Cache for 5 minutes
    set_transient($cache_key, $summary_table, 5 * MINUTE_IN_SECONDS);

    echo $summary_table;
}
?>


?>