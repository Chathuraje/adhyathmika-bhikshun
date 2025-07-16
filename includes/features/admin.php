<?php

require_once __DIR__ . '/get_global_post_position.php';

// Add the shortcode only if enabled
add_action('init', function () {
    if (get_option('ab_post_order_enabled', true)) {
        add_shortcode('post_order', 'get_global_post_position');
    }
});
