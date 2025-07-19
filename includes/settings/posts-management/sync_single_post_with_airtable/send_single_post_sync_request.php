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
require_once __DIR__ . '/export_single_post_to_json.php'; // Ensure this is the correct path

// Define constants
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
const AIRTABLE_WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_single_post_with_airtable';

function send_single_post_sync_request($post_id, $post_uid) {
    // Make sure the post ID is valid
    if (empty($post_id) || !get_post($post_id)) {
        Admin_Notices::add_persistent_notice('❌ Invalid post ID.', 'error');
        return new WP_Error('invalid_post', 'Invalid post ID.');
    }

    // Check post UID validity (optional, assuming string and not empty)
    if (empty($post_uid) || !is_string($post_uid)) {
        Admin_Notices::add_persistent_notice('❌ Invalid post UID.', 'error');
        return new WP_Error('invalid_post_uid', 'Invalid post UID.');
    }

    // Use global if exists; else fallback to false
    global $is_testing_enabled;
    $is_testing_enabled = isset($is_testing_enabled) && $is_testing_enabled ? true : false;
    $testing_flag = $is_testing_enabled ? 'true' : 'false';

    // Prepare JWT payload
    $payload = [
        'iat'      => time(),
        'exp'      => time() + 300,  // 5 minutes expiration
        'post_id'  => $post_id,
        'post_uid' => $post_uid,
        'testing'  => $testing_flag,
    ];

    // Generate JWT token
    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);
    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    // Get post type safely
    $post_type = get_post_type($post_id);
    if (!$post_type) {
        Admin_Notices::add_persistent_notice('❌ Could not determine post type.', 'error');
        return new WP_Error('post_type_error', 'Could not determine post type.');
    }

    // Export post to JSON
    $export = export_single_post_to_json($post_id, $post_type);
    if (empty($export['json_data'])) {
        Admin_Notices::add_persistent_notice('❌ Post export failed or returned empty data.', 'error');
        return new WP_Error('export_error', 'Post export failed or returned empty data.');
    }

    // Prepare body for request
    $request_body = json_encode([
        'post_id'   => $post_id,
        'post_uid'  => $post_uid,
        'testing'   => $testing_flag,
        'post_data' => $export['json_data'],
    ]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Admin_Notices::add_persistent_notice('❌ JSON encoding error: ' . json_last_error_msg(), 'error');
        return new WP_Error('json_encode_error', 'JSON encoding error: ' . json_last_error_msg());
    }

    // Make the POST request
    $response = wp_remote_post(AIRTABLE_WEBHOOK_URL, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body'      => $request_body
    ]);

    // Handle request errors
    if (is_wp_error($response)) {
        Admin_Notices::add_persistent_notice('❌ Airtable request failed: ' . $response->get_error_message(), 'error');
        return $response;
    }

    // Check response status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        Admin_Notices::add_persistent_notice("❌ Airtable returned unexpected status code: $status_code", 'error');
        return new WP_Error('airtable_response_error', 'Unexpected response code from Airtable: ' . $status_code);
    }

    return $response;
}
?>
