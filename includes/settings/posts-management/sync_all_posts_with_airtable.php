<?php
// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_all_posts_sync_request.php';

// Get A/B testing flag (usage depends on your logic)
$is_testing_enabled = get_option('ab_testing_enabled', true);


 add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    $sync_url = wp_nonce_url(admin_url('admin-ajax.php?action=ab_sync_all_posts_with_airtable'), 'ab_sync_all_posts_with_airtable_action');

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var syncButton = \'<a href="' . esc_url($sync_url) . '" class="page-title-action">Sync All Posts with Airtable</a>\';
            $(".wrap .page-title-action").first().after(syncButton).after(importButton);
            });
    </script>';
});

/**
 * Sync all posts with Airtable
 */
 add_action('wp_ajax_ab_sync_all_posts_with_airtable', function () {
    check_admin_referer('ab_sync_all_posts_with_airtable_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::add_persistent_notice('❌ You do not have permission to sync posts.', 'error');
        // Redirect back to the posts list with an error status.
        wp_safe_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $screen = get_current_screen();
    send_all_posts_sync_request($screen->post_type);

    // Redirect back to the posts list page.
    wp_safe_redirect(admin_url('edit.php?post_type=post'));
    exit;
});

// ?>