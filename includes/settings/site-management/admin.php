<?php
/**
 * Adhyathmika Bhikshun Plugin
 * Admin Functions
 *
 * @package Adhyathmika_Bhikshun
 */
/**
 * Register settings and handle form submissions
 */
add_action('admin_init', function () {
    // Register plugin options
    $settings = [
        'ab_testing_enabled',
        'ab_sync_site_contetns_enabled'
    ];

    foreach ($settings as $setting) {
        register_setting('ab_settings_group', $setting);
    }

    // Handle form submission
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ab_site_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_site_settings_nonce'], 'ab_site_save_settings')
    ) {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized user', 'adhyathmika-bhikshun'));
        }

        $checkboxes = [
            'ab_testing_enabled',
            'ab_sync_site_contetns_enabled'
        ];

        foreach ($checkboxes as $key) {
            update_option($key, isset($_POST[$key]) ? 1 : 0);
        }
    }
});

/**
 * Conditionally load features
 */
add_action('init', function () {
    define('AB_TESTING_ENABLED', get_option('ab_testing_enabled', false));

    if (get_option('ab_sync_site_contetns_enabled', true)) {
        require_once __DIR__ . '/sync_site_contetns.php';
    }
});