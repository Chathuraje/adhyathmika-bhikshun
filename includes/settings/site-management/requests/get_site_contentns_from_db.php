<?php
// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/../../../../tools/encode.php';
require_once __DIR__ . '/../lib/export_site_contents_to_json.php';
require_once __DIR__ . '/../lib/import_site_contents_from_json.php';

// Define constants
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL_GET_SITE_CONTENTS_FROM_DB')) {
    define('N8N_WEBHOOK_URL_GET_SITE_CONTENTS_FROM_DB', 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/get_site_contents_from_db');
}

if (!defined('AB_TESTING_ENABLED')) {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false)) ? true : false;
}
function get_site_contents_from_db() {
    // Prepare JWT payload
    $payload = [
        'iat'      => time(),
        'exp'      => time() + 300,  // 5 minutes expiration
        'testing'  => AB_TESTING_ENABLED,
    ];

    // Generate JWT token
    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);
    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    // Export post to JSON
    $export = export_site_contents_to_json();
    if (empty($export['json_data'])) {
        Admin_Notices::add_persistent_notice('❌ Post export failed or returned empty data.', 'error');
        return new WP_Error('export_error', 'Post export failed or returned empty data.');
    }

    // Prepare body for request
    $request_body = json_encode([
        'testing'   => AB_TESTING_ENABLED,
        'post_data' => $export['json_data'],
    ]);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Admin_Notices::add_persistent_notice('❌ JSON encoding error: ' . json_last_error_msg(), 'error');
        return new WP_Error('json_encode_error', 'JSON encoding error: ' . json_last_error_msg());
    }

    // Make the POST request
    $response = wp_remote_post(N8N_WEBHOOK_URL_GET_SITE_CONTENTS_FROM_DB, [
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
        return $response;
    }

    // Check response status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        $body = wp_remote_retrieve_body($response);
        Admin_Notices::add_persistent_notice("❌ Airtable returned unexpected status code: {$status_code}. Response: {$body}", 'error');
        return new WP_Error('airtable_response_error', 'Unexpected response code from Airtable: ' . $status_code);
    }

    // import site contents from JSON
    $response_body = wp_remote_retrieve_body($response);
    if (empty($response_body)) {
        Admin_Notices::add_persistent_notice('❌ No response body received from Airtable.', 'error');
        return new WP_Error('empty_response', 'No response body received from Airtable.');
    }

    $response_data = json_decode($response_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Admin_Notices::add_persistent_notice('❌ JSON decoding error: ' . json_last_error_msg(), 'error');
        return new WP_Error('json_decode_error', 'JSON decoding error: ' . json_last_error_msg());
    }

    // Import site contents from JSON
    $site_json_raw = $response_data['site_json'];
    $site_data = json_decode($site_json_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Admin_Notices::add_persistent_notice('❌ JSON decoding error: ' . json_last_error_msg(), 'error');
        return new WP_Error('json_decode_error', 'JSON decoding error: ' . json_last_error_msg());
    }

    import_site_contents_from_json($site_data);
    Admin_Notices::add_persistent_notice('✅ Site contents imported successfully!', 'success');
}
?>
