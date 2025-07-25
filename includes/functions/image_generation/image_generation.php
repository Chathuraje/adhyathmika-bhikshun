<?php

include_once dirname(__FILE__) . '/requests/send_auto_generate_media_request.php';

if (!defined('ABSPATH')) {
    exit;
}

$image_url = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    $prompt = sanitize_text_field($_POST['prompt']);

    $result = send_auto_generate_media_request($prompt);

    if (!is_wp_error($result) && isset($result['image_url'])) {
        $image_url = esc_url($result['image_url']);
        $message = '✅ Image generated successfully.';
    } else {
        $message = is_wp_error($result) ? $result->get_error_message() : '❌ Failed to generate image.';
    }
}

// Make variables accessible to view
return [
    'image_url' => $image_url,
    'message'   => $message,
];
