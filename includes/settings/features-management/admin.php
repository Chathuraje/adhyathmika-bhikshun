<?php

/**
 * Plugin Admin Settings and Feature Management
 *
 * This file handles the registration of plugin settings, secure saving of settings,
 * and conditional enabling of features and shortcodes based on user-defined options.
 *
 * @package AdhyathmikaBhikshun
 */

// Prevent direct access to the file
// Ensures the file is only executed within the WordPress environment.

/**
 * Registers plugin settings and handles secure saving of settings.
 *
 * - Registers all plugin options to the 'ab_settings_group' settings group.
 * - Validates and processes POST requests to securely save settings.
 * - Updates checkbox options as 0 or 1 based on user input.
 * - Sanitizes and updates the CDN URL option.
 *
 * @hook admin_init
 */

/**
 * Registers shortcodes and enables features conditionally.
 *
 * - Retrieves plugin options and casts them to boolean with default values.
 * - Registers shortcodes for features like post order, language switcher,
 *   reading time, and language audio notes based on settings.
 * - Enables features such as:
 *   - Auto-generating image ALT text on upload.
 *   - Adding a dynamic cross-site link in the WordPress admin bar.
 *   - Enabling CDN URL rewriting for assets.
 *   - Registering additional custom fields in REST API responses.
 *
 * @hook init
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Register plugin settings and handle saving of settings securely
add_action('admin_init', function () {
    // List of all option keys used by the plugin
    $options = [
        'ab_auto_generate_image_alt_enabled',
        'ab_admin_bar_dynamic_site_link_enabled',
        'ab_register_custom_rest_fields_enabled',
        'ab_cdn_url_rewrite_enabled',
        'ab_cdn_url',
        'ab_global_post_position_enabled',
        'ab_render_audio_note_enabled',
        'ab_language_switcher_enabled',
        'ab_reading_time_enabled',
    ];

    // Register all options to the 'ab_settings_group' settings group
    foreach ($options as $option) {
        register_setting('ab_settings_group', $option);
    }

    // Handle POST request to save settings securely
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||                   // Only proceed on POST requests
        !isset($_POST['ab_settings_nonce']) ||                      // Nonce must be present
        !wp_verify_nonce($_POST['ab_settings_nonce'], 'ab_save_settings') // Verify nonce for security
    ) {
        return; // Abort if conditions not met
    }

    // Check user capabilities to prevent unauthorized access
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized user', 'adhyathmika-bhikshun'));
    }

    // List of checkbox option keys to cast as 0 or 1
    $checkboxes = [
        'ab_auto_generate_image_alt_enabled',
        'ab_admin_bar_dynamic_site_link_enabled',
        'ab_register_custom_rest_fields_enabled',
        'ab_cdn_url_rewrite_enabled',
        'ab_global_post_position_enabled',
        'ab_render_audio_note_enabled',
        'ab_language_switcher_enabled',
        'ab_reading_time_enabled',
    ];

    // Update checkbox options based on whether checkbox is set in POST
    foreach ($checkboxes as $key) {
        update_option($key, isset($_POST[$key]) ? 1 : 0);
    }

    // Sanitize and update CDN URL option if present
    if (isset($_POST['ab_cdn_url'])) {
        update_option('ab_cdn_url', esc_url_raw(trim($_POST['ab_cdn_url'])));
    }
});

// Register shortcodes and enable features conditionally on init
add_action('init', function () {
    // Retrieve plugin options once and cast to boolean with defaults
    $settings = [
        'post_order'               => (bool) get_option('ab_global_post_position_enabled', true),
        'language_switch'          => (bool) get_option('ab_language_switcher_enabled', true),
        'reading_time'             => (bool) get_option('ab_reading_time_enabled', true),
        'language_audio_note'      => (bool) get_option('ab_render_audio_note_enabled', true),
        'auto_generate_alt'        => (bool) get_option('ab_auto_generate_image_alt_enabled', true),
        'admin_bar_link'           => (bool) get_option('ab_admin_bar_dynamic_site_link_enabled', true),
        'cdn_rewrite'              => (bool) get_option('ab_cdn_url_rewrite_enabled', false),
        'custom_rest_fields'       => (bool) get_option('ab_register_custom_rest_fields_enabled', false),
    ];

    // Register shortcodes conditionally based on settings

    if ($settings['post_order']) {
        require_once __DIR__ . '/shortcodes/global_post_position.php';
        add_shortcode('ab_get_global_post_position', 'ab_global_post_position_sc');
    }

    if ($settings['language_switch']) {
        require_once __DIR__ . '/shortcodes/language_switcher.php';
        add_shortcode('ab_language_switcher', 'ab_language_switcher_sc');
    }

    if ($settings['reading_time']) {
        require_once __DIR__ . '/shortcodes/reading_time.php';
        add_shortcode('ab_reading_time', 'ab_reading_time_sc');
    }

    if ($settings['language_audio_note']) {
        require_once __DIR__ . '/shortcodes/render_audio_note.php';
        add_shortcode('ab_language_audio_note', 'ab_render_audio_note_sc');
    }

    // Enable features conditionally based on settings

    // Auto-generate image ALT text on image upload
    if ($settings['auto_generate_alt']) {
        require_once __DIR__ . '/features/auto_generate_image_alt.php';
        add_filter('wp_generate_attachment_metadata', 'ab_auto_generate_clean_image_alt', 10, 2);
    }

    // Add dynamic cross-site link in the WordPress admin bar
    if ($settings['admin_bar_link']) {
        require_once __DIR__ . '/features/admin_bar_dynamic_site_link.php';
        add_action('admin_bar_menu', 'ab_admin_bar_dynamic_site_link', 100);
    }

    // Enable CDN URL rewriting for assets
    if ($settings['cdn_rewrite']) {
        require_once __DIR__ . '/features/cdn_url_rewrite.php';
        add_action('init', 'ab_enable_cdn_url_rewrite');
    }

    // Register additional custom fields in REST API responses
    if ($settings['custom_rest_fields']) {
        add_action('rest_api_init', function () {
            require_once __DIR__ . '/features/register_custom_rest_fields.php';
            ab_register_content_raw_field();
            ab_register_rank_math_focus_keyword_meta();
        });
    }
});
