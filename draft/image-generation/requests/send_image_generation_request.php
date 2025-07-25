<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access.
}

require_once __DIR__ . '../../../../../tools/encode.php';

// Define JWT secret and webhook URL constants if not already defined.
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL_IMAGE_GENERATION')) {
    define('N8N_WEBHOOK_URL_IMAGE_GENERATION', 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/generate_image');
}

if (!defined('AB_TESTING_ENABLED')) {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false)) ? true : false;
}

function send_image_generation_request($prompt) {
    // Prepare JWT payload
    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300, // 5 minutes
        'testing' => AB_TESTING_ENABLED,
    ];

    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);

    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    $request_body = json_encode([
        'prompt'  => $prompt,
        'testing'   => AB_TESTING_ENABLED
    ]);

    // Send POST request
    $response = wp_remote_post(N8N_WEBHOOK_URL_IMAGE_GENERATION, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'timeout'   => 30,
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
        if (empty($data)) {
            Admin_Notices::add_persistent_notice('❌ No data returned from webhook.', 'error');
            return new WP_Error('no_data', 'No data returned from webhook.');
        }

        if (isset($data['error'])) {
            Admin_Notices::add_persistent_notice("❌ Error from webhook: {$data['error']}", 'error');
            return new WP_Error('webhook_error', $data['error']);
        }
        
        return $data;
    }
    

    Admin_Notices::add_persistent_notice("❌ HTTP Error ({$status_code}): {$body}", 'error');
    return new WP_Error('webhook_error', "Webhook returned status code {$status_code}");
}
