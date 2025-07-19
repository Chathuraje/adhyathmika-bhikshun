<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access.
}

require_once __DIR__ . '../../../../../tools/encode.php';

// Define JWT secret and webhook URL constants if not already defined.
if (!defined('JWT_SECRET_KEY')) define('JWT_SECRET_KEY', '');
if (!defined('N8N_WEBHOOK_URL')) {
    define('N8N_WEBHOOK_URL', 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_all_post_with_airtable');
}

// Get A/B testing flag (usage depends on your logic)
$is_testing_enabled = get_option('ab_testing_enabled', true);

function send_all_posts_sync_request($post_type = 'post') {
    global $is_testing_enabled;

    $testing_flag = $is_testing_enabled ? 'true' : 'false';

    // Prepare JWT payload
    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300, // 5 minutes
        'testing' => $testing_flag,
        'post_type' => $post_type,
    ];

    $jwt_token = jwt_encode($payload, JWT_SECRET_KEY);

    if (!$jwt_token) {
        Admin_Notices::add_persistent_notice('❌ JWT token generation failed.', 'error');
        return new WP_Error('jwt_error', 'JWT token generation failed.');
    }

    // Build webhook URL with query parameters
    $webhook_url = add_query_arg([
        'testing'      => $testing_flag,
    ], N8N_WEBHOOK_URL);

    // Send GET request
    $response = wp_remote_get($webhook_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
        ],
        'timeout' => 15,
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

        $message = $data['message'] ?? '✅ All posts synchronized successfully.';
        if ($is_testing_enabled) {
            $message .= ' (Testing Mode)';
        }

        Admin_Notices::add_persistent_notice('✅ ' . $message, 'success');
        return $response;
    }

    Admin_Notices::add_persistent_notice("❌ HTTP Error ({$status_code}): {$body}", 'error');
    return new WP_Error('webhook_error', "Webhook returned status code {$status_code}");
}
