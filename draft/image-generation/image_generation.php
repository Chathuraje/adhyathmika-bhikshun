<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

include_once __DIR__ . '/requests/send_image_generation_request.php';

/**
 * AJAX handler for generating an image with the prompt and settings.
 */
add_action('wp_ajax_ab_generate_image', function () {
    // Verify the nonce for security to prevent CSRF attacks.
    check_ajax_referer('ab_generate_image_action', 'nonce');

    $prompt = sanitize_text_field($_POST['prompt'] ?? '');
    $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : [];

    if (empty($prompt)) {
        wp_send_json_error(['message' => 'Prompt cannot be empty.']);
        return;
    }

    // Call the function to handle image generation.
    $result = send_image_generation_request($prompt, $settings);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['image_url' => $result['data']['data']['image_url']]);
    }
});

?>