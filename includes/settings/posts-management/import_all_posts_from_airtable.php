<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_import_all_posts_request.php';

// 1. Inject "Import All" button into post list screens
add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    $url = wp_nonce_url(admin_url('admin-ajax.php?action=ab_import_all_posts_from_airtable&type=' . $screen->post_type), 'ab_import_all_posts_from_airtable_action');

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var button = \'<a href="' . esc_url($url) . '" class="page-title-action">Import All ' . ucfirst($screen->post_type) . ' From Airtable</a>\';
            $(".wrap .page-title-action").first().after(button);
        });
    </script>';
});

// 2. Handle AJAX to trigger import
add_action('wp_ajax_ab_import_all_posts_from_airtable', function () {
    // Verify the nonce for security to prevent CSRF attacks.
    check_admin_referer('ab_import_all_posts_from_airtable_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('manage_options')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::add_persistent_notice('❌ You are not authorized to import posts.', 'error');
        // Redirect back to the posts list with an error status.
        wp_safe_redirect(add_query_arg(['post_type' => 'post', 'import_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    send_import_all_posts_request();

    // Redirect back to the posts list page.
    wp_safe_redirect(admin_url('edit.php'));
    exit;
});