<?php
/**
 * Sync with Airtable Feature (Updated)
 * Adds a button to posts and auto-syncs on save, with A/B testing status and structured response handling.
 */

require_once __DIR__ . '../../../tools/encode.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$is_testing_enabled = get_option('ab_testing_enabled', true);
$WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_with_airtable';
$SECRET_KEY = JWT_SECRET_KEY;

// 1. Add "Sync with Airtable" button to post list screen via JS
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

// 2. Admin POST handler for manual sync
add_action('admin_post_sync_with_airtable', function () use ($WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled) {
    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce = $_GET['_wpnonce'] ?? '';

    if (!wp_verify_nonce($nonce, 'sync_with_airtable_action')) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'unauthorized'
        ], admin_url('edit.php')));
        exit;
    }

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'unauthorized'
        ], admin_url('edit.php')));
        exit;
    }

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'http_error',
            'error_code' => 400,
            'error_message' => urlencode('Post UID not found')
        ], admin_url('edit.php')));
        exit;
    }

    $payload = [
        'iat' => time(),
        'exp' => time() + 300,
        'post_id' => $post_id,
        'post_uid' => $post_uid,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);
    if (!$jwt_token) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'token_error'
        ], admin_url('edit.php')));
        exit;
    }

    $response = wp_remote_post($WEBHOOK_URL, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'    => $post_id,
            'post_uid'   => $post_uid,
            'testing'    => $is_testing_enabled ? 'true' : 'false',
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'error',
            'error_code' => 0,
            'error_message' => urlencode($response->get_error_message())
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'success',
            'success_message' => urlencode("Post ID $post_id synced successfully" . ($is_testing_enabled ? ' (Testing Mode)' : ''))
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'sync_status' => 'http_error',
            'error_code' => $code,
            'error_message' => urlencode($body)
        ], admin_url('edit.php')));
    }

    exit;
});

// 3. Show admin notices
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

// 4. Automatic sync on post save
add_action('save_post_post', function ($post_id, $post, $update) use ($WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled) {
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        wp_is_post_revision($post_id) ||
        defined('DOING_AJAX') && DOING_AJAX
    ) {
        return;
    }

    $post_status = get_post_status($post_id);
    if (!in_array($post_status, ['publish', 'future', 'private'])) return;

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) return;

    $payload = [
        'iat' => time(),
        'exp' => time() + 300,
        'post_id' => $post_id,
        'post_uid' => $post_uid,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);
    if (!$jwt_token) return;

    wp_remote_post($WEBHOOK_URL, [
        'method'  => 'POST',
        'blocking' => false,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'    => $post_id,
            'post_uid'   => $post_uid,
            'testing'    => $is_testing_enabled ? 'true' : 'false',
        ]),
    ]);
}, 10, 3);
