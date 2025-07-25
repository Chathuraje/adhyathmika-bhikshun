<?php
// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_auto_generate_media_request.php';


add_action('wp_ajax_send_image_prompt', 'handle_send_image_prompt');

function handle_send_image_prompt() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    check_ajax_referer('send_image_prompt_nonce', '_ajax_nonce');

    $prompt = sanitize_text_field($_POST['prompt']);

    if (empty($prompt)) {
        wp_send_json_error('Prompt is required', 400);
    }

    $result = send_auto_generate_media_request($prompt); // YOUR FUNCTION

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success('Image prompt sent successfully.');
    }

    wp_die(); // Required for AJAX handlers
}

