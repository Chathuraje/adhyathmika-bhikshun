<?php
/**
 * Sends a single post synchronization request to Airtable via a webhook.
 *
 * This function validates the provided post ID and UID, generates a JWT token,
 * exports the post data to JSON, and sends it to the specified Airtable webhook URL.
 *
 * @param int    $post_id  The ID of the post to be synchronized.
 * @param string $post_uid The unique identifier (UID) of the post.
 *
 * @return array|WP_Error The response from the Airtable webhook or a WP_Error object on failure.
 *
 * @throws WP_Error If the post ID or UID is invalid, JWT token generation fails,
 *                  post export fails, JSON encoding fails, or the webhook request fails.
 *
 * Error Handling:
 * - Adds persistent admin notices for various errors, such as invalid post ID/UID,
 *   JWT generation failure, post export failure, JSON encoding errors, or webhook request errors.
 *
 * Workflow:
 * 1. Validates the post ID and UID.
 * 2. Checks if testing mode is enabled via a global variable.
 * 3. Generates a JWT token with a 5-minute expiration.
 * 4. Determines the post type and exports the post data to JSON.
 * 5. Encodes the request body as JSON and sends a POST request to the Airtable webhook.
 * 6. Handles errors and unexpected response codes from the webhook.
 *
 * Constants:
 * - `JWT_SECRET_KEY`: The secret key used for JWT encoding.
 * - `AIRTABLE_WEBHOOK_URL`: The URL of the Airtable webhook.
 *
 * Dependencies:
 * - Requires `jwt_encode()` for JWT token generation.
 * - Requires `export_single_post_to_json()` for exporting post data to JSON.
 * - Uses WordPress functions like `get_post()`, `get_post_type()`, `wp_remote_post()`, and `wp_remote_retrieve_response_code()`.
 */

// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/../../../../tools/encode.php';
require_once __DIR__ . '/../lib/export_single_post_to_json.php';

// Define constants
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL_SYNC_SINGLE_POST')) {
    define('N8N_WEBHOOK_URL_SYNC_SINGLE_POST', 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_all_taxanomics_with_airtable');
}

if (!defined('AB_TESTING_ENABLED')) {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false)) ? true : false;
}

function set_airtable_sync_status($post_id, $status) {
    update_post_meta($post_id, '_ab_airtable_last_sync', current_time('mysql'));
    update_post_meta($post_id, '_ab_airtable_last_sync_status', $status);
}


function send_single_post_sync_request($post_id, $post_uid) {
    set_airtable_sync_status($post_id, 'in_progress');
    // Make sure the post ID is valid
    if (empty($post_id) || !get_post($post_id)) {
        Admin_Notices::add_persistent_notice('❌ Invalid post ID.', 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('invalid_post', 'Invalid post ID.');
    }

    // Check post UID validity (optional, assuming string and not empty)
    if (empty($post_uid) || !is_string($post_uid)) {
        Admin_Notices::add_persistent_notice('❌ Invalid post UID.', 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('invalid_post_uid', 'Invalid post UID.');
    }

    // Get post type safely
    $post_type = get_post_type($post_id);
    if (!$post_type) {
        Admin_Notices::add_persistent_notice('❌ Could not determine post type.', 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('post_type_error', 'Could not determine post type.');
    }

    // Prepare JWT payload
    $payload = [
        'iat'      => time(),
        'exp'      => time() + 300,  // 5 minutes expiration
        'post_id'  => $post_id,
        'post_uid' => $post_uid,
        'post_type' => $post_type,
        'testing'  => AB_TESTING_ENABLED,
    ];

    // Generate JWT token
    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);
    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    // Export post to JSON
    $export = export_single_post_to_json($post_id, $post_type);
    if (empty($export['json_data'])) {
        Admin_Notices::add_persistent_notice('❌ Post export failed or returned empty data.', 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('export_error', 'Post export failed or returned empty data.');
    }

    // Prepare body for request
    $request_body = json_encode([
        'post_id'   => $post_id,
        'post_uid'  => $post_uid,
        'post_type' => $post_type,
        'testing'   => AB_TESTING_ENABLED,
        'post_data' => $export['json_data'],
    ]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Admin_Notices::add_persistent_notice('❌ JSON encoding error: ' . json_last_error_msg(), 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('json_encode_error', 'JSON encoding error: ' . json_last_error_msg());
    }

    // Make the POST request
    $response = wp_remote_post(N8N_WEBHOOK_URL_SYNC_SINGLE_POST, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'timeout'   => 15,
        'body'      => $request_body
    ]);

    // Handle request errors
    if (is_wp_error($response)) {
        Admin_Notices::add_persistent_notice('❌ Airtable request failed: ' . $response->get_error_message(), 'error');
        set_airtable_sync_status($post_id, 'failed');
        return $response;
    }

    // Check response status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        $body = wp_remote_retrieve_body($response);
        Admin_Notices::add_persistent_notice("❌ Airtable returned unexpected status code: {$status_code}. Response: {$body}", 'error');
        set_airtable_sync_status($post_id, 'failed');
        return new WP_Error('airtable_response_error', 'Unexpected response code from Airtable: ' . $status_code);
    }

    // If everything is successful, update the sync status
    set_airtable_sync_status($post_id, 'success');
    return $response;
}
?>
