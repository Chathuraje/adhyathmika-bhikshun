<?php
/**
 * Airtable Sync Plugin
 * - Adds a "Sync with Airtable" button in the post list
 * - Automatically syncs posts on save
 * - Includes status handling, JWT auth, and A/B test flag
 */

require_once __DIR__ . '/../../tools/encode.php';
require_once __DIR__ . '/export-import/export_single_post_to_json.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$is_testing_enabled = get_option('ab_testing_enabled', true);


/**
 * Utility: Generate JWT token for a post
 */
function airtable_sync_generate_jwt($post_id, $post_uid) {
    global $is_testing_enabled;

    $payload = [
        'iat' => time(),
        'exp' => time() + 300,
        'post_id' => $post_id,
        'post_uid' => $post_uid,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    return jwt_encode($payload, JWT_SECRET_KEY);
}

/**
 * Utility: Send post data to Airtable
 */
function airtable_sync_send($post_id, $post_uid) {
    global $is_testing_enabled;

    $AIRTABLE_SYNC_WEBHOOK = "https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_with_airtable";

    $jwt_token = airtable_sync_generate_jwt($post_id, $post_uid);
    if (!$jwt_token) return new WP_Error('jwt_error', 'JWT token generation failed.');

    $export = export_single_post_to_json($post_id, get_post_type($post_id));
    $response = wp_remote_post($AIRTABLE_SYNC_WEBHOOK, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'   => $post_id,
            'post_uid'  => $post_uid,
            'testing'   => $is_testing_enabled ? 'true' : 'false',
            'post_data' => $export['json_data'],
        ]),
    ]);

    return $response;
}

/**
 * 1. Add "Sync with Airtable" link in post list
 */
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') return;

    $nonce = wp_create_nonce('sync_with_airtable_action');
    $admin_url = esc_url(admin_url('admin-post.php'));

    echo <<<JS
<script>
jQuery(document).ready(function($) {
    $(".row-actions .edit a").each(function() {
        const postId = new URL($(this).attr("href")).searchParams.get("post");
        if (postId) {
            const syncUrl = "$admin_url?action=sync_with_airtable&post_id=" + postId + "&_wpnonce=$nonce";
            $(this).closest(".row-actions").append(' | <a href="' + syncUrl + '">Sync with Airtable</a>');
        }
    });
});
</script>
JS;
});

/**
 * 2. Handle manual sync request
 */
add_action('admin_post_sync_with_airtable', function () {
    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce   = $_GET['_wpnonce'] ?? '';

    if (!wp_verify_nonce($nonce, 'sync_with_airtable_action') || !$post_id || !current_user_can('edit_post', $post_id)) {
        wp_redirect(add_query_arg(['post_type' => 'post', 'sync_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'http_error',
            'error_code'     => 400,
            'error_message'  => urlencode('Post UID not found'),
        ], admin_url('edit.php')));
        exit;
    }

    $response = airtable_sync_send($post_id, $post_uid);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'error',
            'error_code'     => 0,
            'error_message'  => urlencode($response->get_error_message()),
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        $success = sprintf('✅ Post ID %d synced successfully%s');
        wp_redirect(add_query_arg([
            'post_type'       => 'post',
            'sync_status'     => 'success',
            'success_message' => urlencode($success),
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'http_error',
            'error_code'     => $code,
            'error_message'  => urlencode($body),
        ], admin_url('edit.php')));
    }

    exit;
});

/**
 * 3. Display admin notices
 */
add_action('admin_notices', function () {
    if (empty($_GET['sync_status'])) return;

    $status = $_GET['sync_status'];
    $error_code = intval($_GET['error_code'] ?? 0);
    $error_message = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            $msg = esc_html(urldecode($_GET['success_message'] ?? '✅ Sync completed successfully!'));
            echo "<div class='notice notice-success is-dismissible'><p>$msg</p></div>";
            break;
        case 'unauthorized':
            echo "<div class='notice notice-error is-dismissible'><p>❌ You are not authorized to sync this post.</p></div>";
            break;
        case 'token_error':
            echo "<div class='notice notice-error is-dismissible'><p>❌ JWT token generation failed.</p></div>";
            break;
        case 'error':
        case 'http_error':
            echo "<div class='notice notice-error is-dismissible'><p>❌ Sync failed with code: $error_code</p><p>$error_message</p></div>";
            break;
    }
});

/**
 * 4. Automatically sync post on save
 */
add_action('save_post_post', function ($post_id, $post, $update) {
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        defined('DOING_AJAX') && DOING_AJAX ||
        wp_is_post_revision($post_id)
    ) {
        return;
    }

    $post_status = get_post_status($post_id);
    if (!in_array($post_status, ['publish', 'future', 'private'])) return;

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) return;

    airtable_sync_send($post_id, $post_uid);
}, 10, 3);
