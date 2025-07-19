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
 * - `admin_post_ab_sync_single_post_with_airtable`: Handles the GET request for syncing a single post with Airtable via admin-post.php.
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

// Get A/B testing flag (usage depends on your logic)
$is_testing_enabled = get_option('ab_testing_enabled', true);

/**
 * Add "Sync with Airtable" link in post row actions on the Posts list page.
 */
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') {
        return; // Only add link for 'post' post type.
    }

    // Create nonce for security.
    $nonce = wp_create_nonce('ab_sync_single_post_with_airtable_action');
    // URL for the admin-post.php handler (we use admin-post.php, so hook to admin_post_).
    $admin_post_url = esc_url(admin_url('admin-post.php'));

    // Output inline JavaScript to append the "Sync with Airtable" link to each post row action.
    echo <<<JS
<script>
jQuery(document).ready(function($) {
    $(".row-actions .edit a").each(function() {
        // Extract post ID from the edit link URL.
        const postId = new URL($(this).attr("href")).searchParams.get("post");
        if (postId) {
            // Construct sync URL pointing to admin-post.php with nonce and action.
            const syncUrl = "{$admin_post_url}?action=ab_sync_single_post_with_airtable&post_id=" + postId + "&_wpnonce={$nonce}";
            // Append the "Sync with Airtable" link after the Edit link.
            $(this).closest(".row-actions").append(' | <a href="' + syncUrl + '">Sync with Airtable</a>');
        }
    });
});
</script>
JS;
});


/**
 * Handle the sync request triggered via admin-post.php (GET request).
 *
 * Note:
 * - Because the link points to admin-post.php, we hook into 'admin_post_'.
 * - Verifies nonce and user capabilities.
 * - Redirects back to post list with an admin notice on success or failure.
 */
add_action('admin_post_ab_sync_single_post_with_airtable', function () {
    // Verify nonce for security.
    check_admin_referer('ab_sync_single_post_with_airtable_action');

    // Check user permission.
    if (!current_user_can('edit_posts')) {
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync posts.', 'error', admin_url('edit.php'));
        exit;
    }

    // Sanitize and get post ID from GET params.
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    // Check if user can edit this specific post.
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync this post.', 'error', admin_url('edit.php'));
        exit;
    }

    // Get post_uid from custom field.
    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        Admin_Notices::redirect_with_notice('❌ Post UID not found.', 'error', admin_url('edit.php'));
        exit;
    }

    // Send the sync request.
    $response = send_single_post_sync_request($post_id, $post_uid);

    if (is_wp_error($response)) {
        Admin_Notices::redirect_with_notice('❌ ' . $response->get_error_message(), 'error', admin_url('edit.php'));
        exit;
    }

    // Check HTTP response code from Airtable.
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        Admin_Notices::redirect_with_notice('✅ Post synced successfully with Airtable!', 'success', admin_url('edit.php'));
    } else {
        Admin_Notices::redirect_with_notice("❌ HTTP Error ({$code}): {$body}", 'error', admin_url('edit.php'));
    }

    exit;
});


/**
 * Automatically sync post on save for statuses: publish, future, private.
 *
 * This runs during post save and skips autosave, ajax, and revisions.
 */
add_action('save_post_post', function ($post_id, $post, $update) {
    // Bail early if autosave, ajax, or revision.
    if (
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        wp_is_post_revision($post_id)
    ) {
        return;
    }

    // Only sync for these statuses.
    $status = get_post_status($post_id);
    if (!in_array($status, ['publish', 'future', 'private'], true)) {
        return;
    }

    // Get post_uid custom field.
    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        return;
    }

    // Fire the sync request. We don't handle the response here.
    send_single_post_sync_request($post_id, $post_uid);
}, 10, 3);

