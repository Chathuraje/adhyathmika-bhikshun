<?php


require_once __DIR__ . '/shortcodes/get_global_post_position.php';
require_once __DIR__ . '/shortcodes/language_switch.php';
require_once __DIR__ . '/shortcodes/reading_time.php';
require_once __DIR__ . '/shortcodes/language_audio_note.php';

require_once __DIR__ . '/features/auto_generate_image_alt.php';
require_once __DIR__ . '/features/add_dynamic_site_link_to_admin_bar.php';
require_once __DIR__ . '/features/cdn_url_rewrite.php';
require_once __DIR__ . '/features/expose_extra_data_to_restapi.php';

/**
 * Save settings on admin POST
 */
add_action('admin_init', function () {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ab_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_settings_nonce'], 'ab_save_settings')
    ) {
        update_option('ab_post_order_enabled', isset($_POST['ab_post_order_enabled']) ? 1 : 0);
        update_option('ab_language_switch_enabled', isset($_POST['ab_language_switch_enabled']) ? 1 : 0);
        update_option('ab_reading_time_enabled', isset($_POST['ab_reading_time_enabled']) ? 1 : 0);
        update_option('ab_image_alt_enabled', isset($_POST['ab_image_alt_enabled']) ? 1 : 0);
        update_option('ab_cross_site_link_enabled', isset($_POST['ab_cross_site_link_enabled']) ? 1 : 0);
        update_option('ab_language_audio_note_enabled', isset($_POST['ab_language_audio_note_enabled']) ? 1 : 0);
        update_option('ab_rest_api_extras_enabled', isset($_POST['ab_rest_api_extras_enabled']) ? 1 : 0);

        // Save CDN URL rewrite toggle and CDN URL manually
        update_option('ab_use_cdn_urls_enabled', isset($_POST['ab_use_cdn_urls_enabled']) ? 1 : 0);

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
    if (get_option('ab_post_order_enabled', true)) {
        add_shortcode('post_order', 'get_global_post_position');
    }

    if (get_option('ab_language_switch_enabled', true)) {
        add_shortcode('language_switch', 'render_language_switch_shortcode');
    }

    if (get_option('ab_reading_time_enabled', true)) {
        add_shortcode('reading_time', 'custom_reading_time_shortcode');
    }

    if (get_option('ab_language_audio_note_enabled', true)) {
        add_shortcode('language_audio_note', 'ab_language_audio_note_shortcode');
    } 
});

/**
 * Auto-ALT filter conditionally enabled
 */
if (get_option('ab_image_alt_enabled', true)) {
    add_filter('wp_generate_attachment_metadata', 'auto_generate_clean_image_alt', 10, 2);
}

/**
 * Add dynamic .org/.lk site link to admin bar
 */
if (get_option('ab_cross_site_link_enabled', true)) {
    add_action('admin_bar_menu', 'add_dynamic_site_link_to_admin_bar', 100);
}

/**
 * Apply CDN URL rewrite filters conditionally by hooking on init
 */
if (get_option('ab_use_cdn_urls_enabled', false)) {
    add_action('init', 'ab_enable_cdn_url_rewrite');
}

/**
 * Expose extra data to REST API conditionally
 */
if (get_option('ab_rest_api_extras_enabled', false)) {
    add_action('rest_api_init', function () {
        ab_expose_raw_post_content();
        ab_expose_focus_keyword();
    });
}