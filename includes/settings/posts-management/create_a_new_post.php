<?php
/**
 * This file handles the creation of a "Create a New Post" button in the WordPress admin posts list page
 * and the associated AJAX functionality to trigger a new post creation via an external N8N webhook.
 *
 * Key Features:
 * - Adds a "Create a New Post" button to the WordPress admin posts list page.
 * - Sends an HTTP GET request to an external N8N webhook to trigger post creation.
 * - Uses JWT (JSON Web Token) for secure communication with the webhook.
 * - Includes nonce verification and user capability checks for security.
 *
 * Functions and Constants:
 * - `JWT_SECRET_KEY`: A constant for the JWT secret key, defined if not already set.
 * - `N8N_WEBHOOK_URL`: A constant for the N8N webhook URL.
 * - `add_action('admin_head-edit.php')`: Adds the "Create a New Post" button to the admin posts list page.
 * - `add_action('wp_ajax_create_a_new_post')`: Handles the AJAX request for creating a new post.
 *
 * Security Measures:
 * - Prevents direct access to the file using `ABSPATH` check.
 * - Verifies nonce to prevent CSRF attacks.
 * - Checks user capabilities to ensure only authorized users can create posts.
 * - Uses JWT for secure communication with the external webhook.
 *
 * Error Handling:
 * - Displays admin notices for various error scenarios, such as unauthorized access, token generation failure,
 *   HTTP request errors, or webhook response errors.
 * - Redirects back to the posts list page after handling the AJAX request.
 *
 * Dependencies:
 * - Requires an external file (`encode.php`) for the JWT encoding function.
 * - Relies on the `Admin_Notices` class for displaying persistent admin notices.
 *
 * Notes:
 * - The JWT token is valid for 5 minutes (`exp` claim).
 * - Includes an A/B testing flag (`testing`) in the JWT payload, based on a WordPress option.
 * - The webhook URL includes query parameters for the requesting user and testing flag.
 */

// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_create_a_new_post_request.php';

/**
 * Add "Create a New Post" button to the WordPress admin posts list page.
 */
add_action('admin_head-edit.php', function () {
    // Get the current admin screen object.
    $screen = get_current_screen();

    // Only add the button if viewing the 'post' post type list.
    if ($screen->post_type !== 'post') {
        return;
    }

    // Create a URL for the AJAX action with a nonce for security.
    $url = wp_nonce_url(
        admin_url('admin-ajax.php?action=ab_create_a_new_post'),
        'ab_create_a_new_post_action'
    );

    // Output JavaScript to insert the button next to the existing page title action buttons.
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            const buttonHtml = \'<a href="' . esc_url($url) . '" class="page-title-action">Create a New Post</a>\';
            $(".wrap .page-title-action").after(buttonHtml);
        });
    </script>';
});

/**
 * AJAX handler for creating a new post via an external N8N webhook.
 */
add_action('wp_ajax_ab_create_a_new_post_action', function () use ($is_testing_enabled) {
    // Verify the nonce for security to prevent CSRF attacks.
    check_admin_referer('ab_create_a_new_post_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::add_persistent_notice('❌ You are not authorized to create posts.', 'error');
        // Redirect back to the posts list with an error status.
        wp_safe_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    send_create_a_new_post_request($is_testing_enabled);
    
    // Redirect back to the posts list page.
    wp_safe_redirect(admin_url('edit.php?post_type=post'));
    exit;
});
