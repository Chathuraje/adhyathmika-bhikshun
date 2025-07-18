<?php
/**
 * Adhyathmika Bhikshun Plugin
 * Admin Functions
 *
 * @package Adhyathmika_Bhikshun
 */

// Register plugin settings
add_action('admin_init', function () {
    $options = [
        'ab_post_order_enabled',
        'ab_language_switch_enabled',
        'ab_reading_time_enabled',
        'ab_image_alt_enabled',
        'ab_cross_site_link_enabled',
        'ab_language_audio_note_enabled',
        'ab_rest_api_extras_enabled',
        'ab_use_cdn_urls_enabled',
        'ab_cdn_url'
    ];

    foreach ($options as $option) {
        register_setting('ab_settings_group', $option);
    }
});

/**
 * Handle POST settings saving securely
 */
add_action('admin_init', function () {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ab_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_settings_nonce'], 'ab_save_settings')
    ) {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized user', 'adhyathmika-bhikshun'));
        }

        // Cast checkboxes to integers (0 or 1)
        $checkboxes = [
            'ab_post_order_enabled',
            'ab_language_switch_enabled',
            'ab_reading_time_enabled',
            'ab_image_alt_enabled',
            'ab_cross_site_link_enabled',
            'ab_language_audio_note_enabled',
            'ab_rest_api_extras_enabled',
            'ab_use_cdn_urls_enabled',
        ];

        foreach ($checkboxes as $key) {
            update_option($key, isset($_POST[$key]) ? 1 : 0);
        }

        // Sanitize and save CDN URL
        if (isset($_POST['ab_cdn_url'])) {
            $cdn_url = esc_url_raw(trim($_POST['ab_cdn_url']));
            update_option('ab_cdn_url', $cdn_url);
        }
    }
});

/**
 * Register shortcodes conditionally
 */
add_action('init', function () {
    $settings = [
        'post_order'               => get_option('ab_post_order_enabled', true),
        'language_switch'          => get_option('ab_language_switch_enabled', true),
        'reading_time'             => get_option('ab_reading_time_enabled', true),
        'language_audio_note'      => get_option('ab_language_audio_note_enabled', true),
    ];

    if ($settings['post_order']) {
        require_once __DIR__ . '/shortcodes/get_global_post_position.php';
        add_shortcode('post_order', 'get_global_post_position');
    }

    if ($settings['language_switch']) {
        require_once __DIR__ . '/shortcodes/language_switch.php';
        add_shortcode('language_switch', 'render_language_switch_shortcode');
    }

    if ($settings['reading_time']) {
        require_once __DIR__ . '/shortcodes/reading_time.php';
        add_shortcode('reading_time', 'custom_reading_time_shortcode');
    }

    if ($settings['language_audio_note']) {
        require_once __DIR__ . '/shortcodes/language_audio_note.php';
        add_shortcode('language_audio_note', 'ab_language_audio_note_shortcode');
    }
});


/**
 * Register features conditionally
 */

/**
 * Auto-generate image ALT text
 */
if (get_option('ab_image_alt_enabled', true)) {
    require_once __DIR__ . '/features/auto_generate_image_alt.php';
    add_filter('wp_generate_attachment_metadata', 'auto_generate_clean_image_alt', 10, 2);
}

/**
 * Add dynamic cross-site link to admin bar
 */
if (get_option('ab_cross_site_link_enabled', true)) {
    require_once __DIR__ . '/features/add_dynamic_site_link_to_admin_bar.php';
    add_action('admin_bar_menu', 'add_dynamic_site_link_to_admin_bar', 100);
}

/**
 * Enable CDN URL rewriting
 */
if (get_option('ab_use_cdn_urls_enabled', false)) {
    require_once __DIR__ . '/features/cdn_url_rewrite.php';
    add_action('init', 'ab_enable_cdn_url_rewrite');
}

/**
 * Expose extra fields to REST API
 */
if (get_option('ab_rest_api_extras_enabled', false)) {
    add_action('rest_api_init', function () {
        require_once __DIR__ . '/features/expose_extra_data_to_restapi.php';
        ab_expose_raw_post_content();
        ab_expose_focus_keyword();
    });
}
