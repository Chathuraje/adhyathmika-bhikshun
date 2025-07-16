<?php
/**
 * Sync with Airtable Feature
 * Adds a button to posts and auto-sync on save, conditionally enabled by plugin settings.
 */

require_once __DIR__ . '../../../tools/encode.php';

// Define JWT_SECRET_KEY if not already defined
if (!defined('JWT_SECRET_KEY')) {
    return; // Ensure the secret key is defined before proceeding
}

// check if testing is enabled
$is_testing_enabled = get_option('ab_testing_enabled', true);

$WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_with_airtable';
$SECRET_KEY = defined('JWT_SECRET_KEY');

// 1. Add "Sync with Airtable" link next to Edit/Trash
add_filter('post_row_actions', function($actions, $post) {
    if ($post->post_type === 'post') {
        $url = admin_url('admin-post.php?action=sync_with_airtable&post_id=' . $post->ID);
        $actions['sync_with_airtable'] = '<a href="' . esc_url($url) . '">Sync with Airtable</a>';
    }
    return $actions;
}, 10, 2);

// 2. Admin POST handler for manual sync
add_action('admin_post_sync_with_airtable', function () {
    global $WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled;

    $post_id = intval($_GET['post_id'] ?? 0);
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_die('Unauthorized', 403);
    }

    $youtube_url = get_field('youtube_url', $post_id);
    if (!$youtube_url) {
        wp_die('YouTube URL not found for this post.', 400);
    }

    preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtube_url, $matches);
    $youtube_id = $matches[1] ?? $youtube_url;

    if (!$youtube_id) {
        wp_die('Could not extract YouTube ID.', 400);
    }

    $payload = [
        'iat' => time(),
        'exp' => time() + 180,
        'post_id' => $post_id,
        'youtube_id' => $youtube_id,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);

    $response = wp_remote_post($WEBHOOK_URL, [
        'method'  => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'    => $post_id,
            'youtube_id' => $youtube_id,
            'testing' => $is_testing_enabled ? 'true' : 'false',
        ]),
    ]);

    if (is_wp_error($response)) {
        $msg_type = 'error';
        $msg_text = 'Webhook failed: ' . $response->get_error_message();
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $msg_type = ($code >= 200 && $code < 300) ? 'success' : 'error';
        $msg_text = $msg_type === 'success'
            ? "✅ Post ID $post_id and YouTube ID $youtube_id sent successfully!"
            : '❌ Remote responded with error code: ' . $code;
    }

    set_transient('remote_sync_notice', [
        'type' => $msg_type,
        'text' => $msg_text
    ], 30);

    wp_redirect(admin_url('edit.php?post_type=post'));
    exit;
});

// 3. Display admin notice
add_action('admin_notices', function () {
    if ($notice = get_transient('remote_sync_notice')) {
        $class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['text']) . '</p></div>';
        delete_transient('remote_sync_notice');
    }
});

// 4. Automatic sync on post save
add_action('save_post_post', function($post_id, $post, $update) {
    global $WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled;

    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        wp_is_post_revision($post_id) ||
        defined('DOING_AJAX') && DOING_AJAX
    ) {
        return;
    }

    $post_status = get_post_status($post_id);
    if (!in_array($post_status, ['publish', 'future', 'private'])) {
        return;
    }

    $youtube_url = get_field('youtube_url', $post_id);
    if (!$youtube_url) return;

    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([a-zA-Z0-9_-]{11})/', $youtube_url, $matches);
    $youtube_id = $matches[1] ?? $youtube_url;

    $payload = [
        'iat' => time(),
        'exp' => time() + 180,
        'post_id' => $post_id,
        'youtube_id' => $youtube_id,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];
    $jwt_token = jwt_encode($payload, $SECRET_KEY);

    wp_remote_post($WEBHOOK_URL, [
        'method'  => 'POST',
        'blocking' => false,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'    => $post_id,
            'youtube_id' => $youtube_id,
            'testing' => $is_testing_enabled ? 'true' : 'false',
        ]),
    ]);
}, 10, 3);
