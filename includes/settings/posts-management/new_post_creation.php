<?php
/**
 * New Post Creator Feature with Testing Flag
 * Adds a "Create a New Post" button and triggers a JWT-authenticated GET request with testing status.
 */

require_once __DIR__ . '/../../../tools/encode.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$is_testing_enabled = get_option('ab_testing_enabled', true);
$N8N_WEBHOOK_URL = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/create_a_new_post';

$SECRET_KEY = JWT_SECRET_KEY;

// 1. Add "Create a New Post" button to post list
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') return;

    $url = wp_nonce_url(admin_url('admin-ajax.php?action=create_a_new_post'), 'create_a_new_post_action');

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var button = \'<a href="' . esc_url($url) . '" class="page-title-action">Create a New Post</a>\';
            $(".wrap .page-title-action").after(button);
        });
    </script>';
});

// 2. AJAX handler for new post trigger
add_action('wp_ajax_create_a_new_post', function () use ($N8N_WEBHOOK_URL, $SECRET_KEY, $is_testing_enabled) {
    check_admin_referer('create_a_new_post_action');

    if (!current_user_can('edit_posts')) {
        wp_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $current_user = wp_get_current_user();
    $requested_by = sanitize_user($current_user->user_login);

    $payload = [
        'iat'     => time(),
        'exp'     => time() + 300,
        'user'    => $requested_by,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);

    if (!$jwt_token) {
        wp_redirect(add_query_arg(['post_type' => 'post', 'create_status' => 'token_error'], admin_url('edit.php')));
        exit;
    }

    $url_with_query = add_query_arg([
        'requested_by' => $requested_by,
        'testing'      => $is_testing_enabled ? 'true' : 'false',
    ], $N8N_WEBHOOK_URL);

    $response = wp_remote_get($url_with_query, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'create_status' => 'error',
            'error_code' => 0,
            'error_message' => urlencode($response->get_error_message())
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code >= 200 && $code < 300) {
        if (isset($data['code']) && $data['code'] === '404') {
            wp_redirect(add_query_arg([
                'post_type' => 'post',
                'create_status' => 'http_error',
                'error_code' => 404,
                'error_message' => urlencode('No data in database')
            ], admin_url('edit.php')));
            exit;
        }

        $message = $data['message'] ?? '';
        $testing_msg = $is_testing_enabled ? ' (Testing Mode)' : '';

        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'create_status' => 'success',
            'success_message' => urlencode($message . $testing_msg)
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'post_type' => 'post',
            'create_status' => 'http_error',
            'error_code' => $code,
            'error_message' => urlencode($body)
        ], admin_url('edit.php')));
    }

    exit;
});

// 3. Display status notice in admin
add_action('admin_notices', function () {
    if (!isset($_GET['create_status'])) return;

    $status = $_GET['create_status'];
    $error_code = intval($_GET['error_code'] ?? 0);
    $error_message = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            $msg = !empty($_GET['success_message'])
                ? esc_html(urldecode($_GET['success_message']))
                : '✅ New post successfully triggered via N8N!';
            echo '<div class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
            break;
        case 'unauthorized':
            echo '<div class="notice notice-error is-dismissible"><p>❌ You are not authorized to create posts.</p></div>';
            break;
        case 'token_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ JWT token generation failed.</p></div>';
            break;
        case 'error':
        case 'http_error':
            if ($error_code === 404 && $error_message === 'No data in database') {
                echo '<div class="notice notice-error is-dismissible"><p>❌ No data in database.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Request failed with code: ' . esc_html($error_code) . '</p> <p>' . esc_html($error_message) . '</p></div>';
            }
            break;
    }
});
