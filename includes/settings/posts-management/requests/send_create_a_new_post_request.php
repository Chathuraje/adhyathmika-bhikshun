<?php
/**
 * Sends a request to trigger the creation of a new post via N8N webhook.
 *
 * Validates the user, builds a JWT token, appends query params, sends the request, 
 * and handles webhook responses.
 *
 * @param string $requested_by        The sanitized username of the user triggering the action.
 * @param bool   $is_testing_enabled  Whether A/B testing is enabled (optional; defaults to true).
 *
 * @return array|WP_Error Response array on success, or WP_Error on failure.
 *
 * Error Handling:
 * - Adds persistent admin notices for any failures or issues during request.
 *
 * Constants:
 * - JWT_SECRET_KEY
 * - N8N_WEBHOOK_URL
 *
 * Dependencies:
 * - jwt_encode() from encode.php
 * - Admin_Notices for persistent messages
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access.
}

require_once __DIR__ . '../../../../../tools/encode.php';

// Define JWT secret and webhook URL constants if not already defined.
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL')) {
    define('N8N_WEBHOOK_URL_CREATE_A_NEW_POST', 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/create_a_new_post');
}

if (!defined('AB_TESTING_ENABLED')) {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false)) ? true : false;
}

function send_create_a_new_post_request($post_type) {

    // Prepare JWT payload
    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300, // 5 minutes
        'post_type' => $post_type,
        'testing' => AB_TESTING_ENABLED,
    ];

    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);

    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    $request_body = json_encode([
        'post_type'   => $post_type,
        'testing'     => AB_TESTING_ENABLED
    ]);

    // Send POST request
    $response = wp_remote_post(N8N_WEBHOOK_URL_CREATE_A_NEW_POST, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'timeout'   => 15,
        'body'      => $request_body,
    ]);


    if (is_wp_error($response)) {
        Admin_Notices::add_persistent_notice('❌ Request failed: ' . $response->get_error_message(), 'error');
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body        = wp_remote_retrieve_body($response);
    $data        = json_decode($body, true);

    if ($status_code >= 200 && $status_code < 300) {
        if (!empty($data['code']) && $data['code'] === '404') {
            Admin_Notices::add_persistent_notice('❌ No data found in the database.', 'error');
            return new WP_Error('no_data', 'No data found.');
        }

        $message = $data['message'] ?? '✅ New post successfully triggered via N8N!';
        if (AB_TESTING_ENABLED) {
            $message .= ' (Testing Mode)';
        }

        Admin_Notices::add_persistent_notice('✅ ' . $message, 'success');
        return $response;
    }

    Admin_Notices::add_persistent_notice("❌ HTTP Error ({$status_code}): {$body}", 'error');
    return new WP_Error('webhook_error', "Webhook returned status code {$status_code}");
}
