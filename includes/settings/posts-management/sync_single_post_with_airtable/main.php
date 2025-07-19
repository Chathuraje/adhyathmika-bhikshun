<?php
/**
 * Features:
 * - Adds a "Sync with Airtable" link to the post row actions in the WordPress admin.
 * - Sends post data to Airtable via a webhook when the sync action is triggered.
 * - Automatically syncs posts with Airtable when they are saved with specific statuses (publish, future, private).
 * 
 * Security:
 * - Prevents direct access to the file.
 * - Verifies nonces to prevent CSRF attacks.
 * - Checks user permissions to ensure only authorized users can sync posts.
 * 
 * Dependencies:
 * - Requires the `send_single_post_sync_request` function to handle the actual data sync with Airtable.
 * - Uses the `Admin_Notices` class for displaying admin notices.
 * 
 * Hooks:
 * - `admin_head-edit.php`: Adds the "Sync with Airtable" link to post row actions.
 * - `wp_ajax_ab_sync_single_post_with_airtable`: Handles the AJAX request for syncing a single post with Airtable.
 * - `save_post_post`: Automatically syncs posts with Airtable on save for specific post statuses.
 * 
 * Usage:
 * - Enable the plugin and ensure the required dependencies are available.
 * - Use the "Sync with Airtable" link in the post row actions to manually sync a post.
 * - Save or update a post with a supported status to trigger automatic sync.
 */


// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/send_single_post_sync_request.php';

// Get A/B testing flag
$is_testing_enabled = get_option('ab_testing_enabled', true);


/**
 * Add "Sync with Airtable" link in post row actions.
 */
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') return;

    $nonce     = wp_create_nonce('ab_sync_single_post_with_airtable_action');
    $admin_url = esc_url(admin_url('admin-post.php'));

    echo <<<JS
<script>
jQuery(document).ready(function($) {
    $(".row-actions .edit a").each(function() {
        const postId = new URL($(this).attr("href")).searchParams.get("post");
        if (postId) {
            const syncUrl = "$admin_url?action=ab_sync_single_post_with_airtable&post_id=" + postId + "&_wpnonce=$nonce";
            $(this).closest(".row-actions").append(' | <a href="' + syncUrl + '">Sync with Airtable</a>');
        }
    });
});
</script>
JS;
});

/**
 * Send post data to Airtable via webhook.
 */
add_action('wp_ajax_ab_sync_single_post_with_airtable', function () use ($is_testing_enabled) {
    // Verify the nonce for security to prevent CSRF attacks.
    check_admin_referer('ab_sync_single_post_with_airtable_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Redirect back to the posts list with an error status.
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync posts.', 'error', admin_url('edit.php'));
        exit;
    }

    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce   = $_GET['_wpnonce'] ?? '';

    // Security checks
    if (!wp_verify_nonce($nonce, 'ab_sync_single_post_with_airtable_action') || !$post_id || !current_user_can('edit_post', $post_id)) {
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync posts.', 'error', admin_url('edit.php'));
        exit;
    }

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        Admin_Notices::redirect_with_notice('❌ Post UID not found.', 'error', admin_url('edit.php'));
        exit;
    }

    $response = send_single_post_sync_request($post_id, $post_uid);

    if (is_wp_error($response)) {
        Admin_Notices::redirect_with_notice('❌ ' . $response->get_error_message(), 'error', admin_url('edit.php'));
        exit;
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        Admin_Notices::redirect_with_notice('✅ Post synced successfully with Airtable!', 'success', admin_url('edit.php'));
    } else {
        Admin_Notices::redirect_with_notice('❌ HTTP Error (' . $code . '): ' . $body, 'error', admin_url('edit.php'));
    }

    exit;


});

/**
 * Automatically sync post on save (publish/future/private only).
 */
add_action('save_post_post', function ($post_id, $post, $update) {
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        defined('DOING_AJAX') && DOING_AJAX ||
        wp_is_post_revision($post_id)
    ) {
        return;
    }

    $status = get_post_status($post_id);
    if (!in_array($status, ['publish', 'future', 'private'])) return;

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) return;

    send_single_post_sync_request($post_id, $post_uid);
}, 10, 3);
