<?php
require_once __DIR__ . '../../../tools/encode.php';
require_once __DIR__ . '/export-import/export_single_post_to_json.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$is_testing_enabled = get_option('ab_testing_enabled', true);
$WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_with_airtable';
$SECRET_KEY = JWT_SECRET_KEY;

// Helper: Generate JWT Token
function generate_jwt($post_id, $post_uid, $secret, $testing) {
    return jwt_encode([
        'iat' => time(),
        'exp' => time() + 300,
        'post_id' => $post_id,
        'post_uid' => $post_uid,
        'testing' => $testing ? 'true' : 'false',
    ], $secret);
}

// Helper: Redirect with sync status
function redirect_with_status($args) {
    wp_redirect(add_query_arg(array_merge(['post_type' => 'post'], $args), admin_url('edit.php')));
    exit;
}

// Helper: Perform sync with Airtable
function sync_with_airtable($post_id, $post_uid, $testing) {
    global $WEBHOOK_URL, $SECRET_KEY;

    $token = generate_jwt($post_id, $post_uid, $SECRET_KEY, $testing);
    if (!$token) return ['error' => 'token_error'];

    $post_data = export_single_post_to_json($post_id, get_post_type($post_id));
    $response = wp_remote_post($$WEBHOOK_URL, [
        'method'  => 'POST',
        'blocking' => true,
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'    => $post_id,
            'post_uid'   => $post_uid,
            'testing'    => $testing ? 'true' : 'false',
            'post_data'  => $post_data['json_data']
        ]),
    ]);

    return $response;
}

// 1. Add "Sync with Airtable" button
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') return;

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $(".row-actions .edit a").each(function() {
                const postId = new URL($(this).attr("href")).searchParams.get("post");
                if (postId) {
                    const syncUrl = "' . esc_url(admin_url('admin-post.php')) . '?action=sync_with_airtable&post_id=" + postId + "&_wpnonce=" + "' . wp_create_nonce('sync_with_airtable_action') . '";
                    $(this).closest(".row-actions").append(\' | <a href="\' + syncUrl + \'">Sync with Airtable</a>\');
                }
            });
        });
    </script>';
});

// 2. Manual sync handler
add_action('admin_post_sync_with_airtable', function () use ($WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled) {
    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce = $_GET['_wpnonce'] ?? '';

    if (!wp_verify_nonce($nonce, 'sync_with_airtable_action') || !$post_id || !current_user_can('edit_post', $post_id)) {
        redirect_with_status(['sync_status' => 'unauthorized']);
    }

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        redirect_with_status(['sync_status' => 'http_error', 'error_code' => 400, 'error_message' => urlencode('Post UID not found')]);
    }

    $response = sync_with_airtable($post_id, $post_uid, $SECRET_KEY, $is_testing_enabled, $WEBHOOK_URL);
    if (is_wp_error($response)) {
        redirect_with_status(['sync_status' => 'error', 'error_code' => 0, 'error_message' => urlencode($response->get_error_message())]);
    } elseif (isset($response['error']) && $response['error'] === 'token_error') {
        redirect_with_status(['sync_status' => 'token_error']);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        redirect_with_status([
            'sync_status' => 'success',
            'success_message' => urlencode("Post ID $post_id synced successfully" . ($is_testing_enabled ? ' (Testing Mode)' : ''))
        ]);
    } else {
        redirect_with_status([
            'sync_status' => 'http_error',
            'error_code' => $code,
            'error_message' => urlencode($body)
        ]);
    }
});

// 3. Admin notices
add_action('admin_notices', function () {
    if (!isset($_GET['sync_status'])) return;

    $status = $_GET['sync_status'];
    $error_code = intval($_GET['error_code'] ?? 0);
    $error_message = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            $msg = !empty($_GET['success_message'])
                ? esc_html(urldecode($_GET['success_message']))
                : '✅ Sync completed successfully!';
            echo '<div class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
            break;
        case 'unauthorized':
            echo '<div class="notice notice-error is-dismissible"><p>❌ You are not authorized to sync this post.</p></div>';
            break;
        case 'token_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ JWT token generation failed.</p></div>';
            break;
        case 'error':
        case 'http_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ Sync failed with code: ' . esc_html($error_code) . '</p><p>' . esc_html($error_message) . '</p></div>';
            break;
    }
});

// 4. Automatic sync on save
add_action('save_post_post', function ($post_id, $post, $update) use ($WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled) {
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        wp_is_post_revision($post_id) ||
        defined('DOING_AJAX') && DOING_AJAX
    ) {
        return;
    }

    if (!in_array(get_post_status($post_id), ['publish', 'future', 'private'])) return;

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) return;

    sync_with_airtable($post_id, $post_uid, $SECRET_KEY, $is_testing_enabled, $WEBHOOK_URL, false);
}, 10, 3);
