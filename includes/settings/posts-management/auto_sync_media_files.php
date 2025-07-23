<?php
// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_auto_sync_media_request.php';

/**
 * Add "Create a New Post" button to the WordPress admin posts list page.
 */

add_action('add_attachment', 'trigger_media_upload_webhook');

function trigger_media_upload_webhook($post_ID) {
    $post = get_post($post_ID);
    if ($post->post_type !== 'attachment') {
        Admin_Notices::add_persistent_notice('❌ The post is not an attachment.', 'error');
        return new WP_Error('invalid_post_type', 'The post is not an attachment.');
    }

    $file_url  = wp_get_attachment_url($post_ID);
    $file_name = basename(get_attached_file($post_ID));

    send_auto_sync_media_request($file_url, $file_name);
}