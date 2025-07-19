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

// Include external file for JWT encoding function.
require_once __DIR__ . '/../../../tools/encode.php';

// Define JWT secret key constant if not already defined.
if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

// Retrieve the A/B testing enabled option from WordPress settings (default true).
$is_testing_enabled = get_option('ab_testing_enabled', true);

// Define constant for the N8N webhook URL.
const N8N_WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/create_a_new_post';

// Define constant for the JWT secret key.
const SECRET_KEY = JWT_SECRET_KEY;

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
        admin_url('admin-ajax.php?action=create_a_new_post'),
        'create_a_new_post_action'
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
add_action('wp_ajax_create_a_new_post', function () use ($is_testing_enabled) {
    // Verify the nonce for security to prevent CSRF attacks.
    check_admin_referer('create_a_new_post_action');

    // Check if the current user has permission to edit posts.
    if (!current_user_can('edit_posts')) {
        // Add an admin notice if unauthorized.
        Admin_Notices::add_persistent_notice('❌ You are not authorized to create posts.', 'error');
        // Redirect back to the posts list with an error status.
        wp_safe_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    // Get current WordPress user object.
    $current_user = wp_get_current_user();
    // Sanitize the user login name for safety.
    $requested_by = sanitize_user($current_user->user_login);

    // Prepare JWT payload data with issued-at and expiration times.
    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300, // Token valid for 5 minutes.
        'user'    => $requested_by,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    // Generate JWT token using secret key.
    $jwt_token = jwt_encode($payload, SECRET_KEY);

    // Handle token generation failure.
    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        wp_safe_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'token_error'], admin_url('edit.php')));
        exit;
    }

    // Build the webhook URL with query parameters for requested user and testing flag.
    $webhook_url = add_query_arg([
        'requested_by' => $requested_by,
        'testing'      => $is_testing_enabled ? 'true' : 'false',
    ], N8N_WEBHOOK_URL);

    // Send an HTTP GET request to the webhook with Authorization header containing the JWT.
    $response = wp_remote_get($webhook_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
        ],
        'timeout' => 15,
    ]);

    // Check if the request failed (network or HTTP errors).
    if (is_wp_error($response)) {
        Admin_Notices::add_persistent_notice('❌ Request failed: ' . $response->get_error_message(), 'error');
        wp_safe_redirect(admin_url('edit.php?post_type=post'));
        exit;
    }

    // Get HTTP response status code.
    $status_code = wp_remote_retrieve_response_code($response);
    // Get response body content.
    $response_body = wp_remote_retrieve_body($response);
    // Decode JSON response into an array.
    $response_data = json_decode($response_body, true);

    // Handle successful HTTP responses (status 2xx).
    if ($status_code >= 200 && $status_code < 300) {
        // Check if webhook responded with a 404 code indicating no data.
        if (!empty($response_data['code']) && $response_data['code'] === '404') {
            Admin_Notices::add_persistent_notice('❌ No data in database.', 'error');
        } else {
            // Display success message from webhook response or default.
            $message = $response_data['message'] ?? 'New post successfully triggered via N8N!';
            // Append testing mode note if enabled.
            if ($is_testing_enabled) {
                $message .= ' (Testing Mode)';
            }
            Admin_Notices::add_persistent_notice('✅ ' . $message, 'success');
        }
    } else {
        // Handle HTTP errors by showing status code and response body.
        Admin_Notices::add_persistent_notice("❌ HTTP Error ({$status_code}): {$response_body}", 'error');
    }

    // Redirect back to the posts list page.
    wp_safe_redirect(admin_url('edit.php?post_type=post'));
    exit;
});
