<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access.
}

require_once __DIR__ . '../../../../../tools/encode.php';

// Define JWT secret and webhook URL constants if not already defined.
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL_IMPORT_ALL_POSTS')) {
    define('N8N_WEBHOOK_URL_IMPORT_ALL_POSTS', 'https://n8n.digitix365.com/webhook/import_all_posts_froma_airtable');
}

// Get A/B testing flag (usage depends on your logic)
if (!defined('AB_TESTING_ENABLED')) {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false)) ? true : false;
}

function send_import_all_posts_request($post_type) {
    // Prepare JWT payload
    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300, // 5 minutes
        'testing' => AB_TESTING_ENABLED,
        'post_type' => $post_type,
    ];

    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);

    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    // Build webhook URL with query parameters
    $request_body = json_encode([
        'post_type' => $post_type,
        'testing'   => AB_TESTING_ENABLED,
    ]);

    // Send GET request
     $response = wp_remote_post(N8N_WEBHOOK_URL_IMPORT_ALL_POSTS, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'timeout'   => 15,
        'body'      => $request_body
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

        $message = $data['message'] ?? '✅ All posts import successfully.';
        if (AB_TESTING_ENABLED) {
            $message .= ' (Testing Mode)';
        }

        Admin_Notices::add_persistent_notice('✅ ' . $message, 'success');
        return $response;
    }

    Admin_Notices::add_persistent_notice("❌ HTTP Error ({$status_code}): {$body}", 'error');
    return new WP_Error('webhook_error', "Webhook returned status code {$status_code}");
}
