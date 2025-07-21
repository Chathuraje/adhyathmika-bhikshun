<?php
// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_all_posts_sync_request.php';

 add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    $sync_url = wp_nonce_url(admin_url('admin-ajax.php?action=ab_sync_all_posts_with_airtable&type=' . $screen->post_type), 'ab_sync_all_posts_with_airtable_action');

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

    // Get post type from $_GET (passed via AJAX URL)
    $post_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::redirect_with_notice('❌ You do not have permission to sync posts.', 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }

    send_all_posts_sync_request($post_type);

    // Redirect back to the posts list page.
    Admin_Notices::redirect_with_notice('✅ All posts synced successfully!', 'success', admin_url('edit.php?post_type=' . $post_type));
    exit;
});

// ?>