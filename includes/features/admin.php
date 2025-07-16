<?php

require_once __DIR__ . '/get_global_post_position.php';

// Get Global Post Position
add_action('admin_init', function () {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ab_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_settings_nonce'], 'ab_save_settings')
    ) {

        $enabled = isset($_POST['ab_post_order_enabled']) ? 1 : 0;
        update_option('ab_post_order_enabled', $enabled);
    }
});
