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
 add_action('wp_ajax_ab_sync_all_posts_with_airtable', function () use ($SECRET_KEY) {
    check_admin_referer('ab_sync_all_posts_with_airtable_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::add_persistent_notice('❌ You are not authorized to create posts.', 'error');
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


// /**
//  * Utility: Sync all posts with Airtable
//  */
// function airtable_sync_multiple_posts(array $posts) {
//     $screen = get_current_screen();
//     $results = [];

//     foreach ($posts as $index => $post_data) {
//         $post_id = isset($post_data['post_id']) ? intval($post_data['post_id']) : 0;
//         $post_uid = isset($post_data['post_uid']) ? sanitize_text_field($post_data['post_uid']) : '';

//         if (!$post_id || empty($post_uid)) {
//             $results[] = [
//                 'airtable_id' => $post_data['airtable_id'] ?? null,
//                 'post_uid' => $post_uid,
//                 'post_title' => $post_data['title'] ?? '',
//                 'post_id' => $post_id,
//                 'status' => 'error',
//                 'message' => 'Missing or invalid post ID or UID at index ' . $index,
//             ];
//             continue;
//         }

//         // Attempt sync
//         $response = send_all_posts_sync_request($screen->post_type);

//         if (is_wp_error($response)) {
//             $results[] = [
//                 'airtable_id' => $post_data['airtable_id'] ?? null,
//                 'post_uid' => $post_uid,
//                 'post_title' => $post_data['post_title'] ?? '',
//                 'post_id' => $post_id,
//                 'status' => 'error',
//                 'message' => $response->get_error_message(),
//             ];
//             continue;
//         }

//         $code = wp_remote_retrieve_response_code($response);
//         $body = wp_remote_retrieve_body($response);

//         if ($code >= 200 && $code < 300) {
//             $results[] = [
//                 'airtable_id' => $post_data['airtable_id'] ?? null,
//                 'post_uid' => $post_uid,
//                 'post_title' => $post_data['post_title'] ?? '',
//                 'post_id' => $post_id,
//                 'status' => 'success',
//                 'message' => 'Post synced successfully',
//             ];
//         } else {
//             $results[] = [
//                 'airtable_id' => $post_data['airtable_id'] ?? null,
//                 'post_uid' => $post_uid,
//                 'post_title' => $post_data['post_title'] ?? '',
//                 'post_id' => $post_id,
//                 'status' => 'http_error',
//                 'message' => "HTTP $code: $body",
//             ];
//         }
//     }

//     return $results;
// }


// ?>