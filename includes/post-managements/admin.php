<?php
/**
 * Adhyathmika Bhikshun Plugin
 * Admin Functions (Extended)
 *
 * @package Adhyathmika_Bhikshun
 */

// Register additional plugin settings
add_action('admin_init', function () {
    $options = [
        'ab_testing_enabled',
        'ab_new_post_creation_enabled',
        'ab_single_airtable_sync_enabled'
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
        isset($_POST['ab_post_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_post_settings_nonce'], 'ab_post_save_settings')
    ) {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized user', 'adhyathmika-bhikshun'));
        }

        $checkboxes = [
            'ab_testing_enabled',
            'ab_new_post_creation_enabled',
            'ab_single_airtable_sync_enabled'
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
    if (get_option('ab_new_post_creation_enabled', true)) {
        require_once __DIR__ . '/new_post_creation.php';
    }

    if (get_option('ab_single_airtable_sync_enabled', true)) {
        require_once __DIR__ . '/single_airtable_sync.php';
    }

    if (get_option('ab_testing_enabled', false)) {
        // Optional: Add a testing-specific include or hook here
        // require_once __DIR__ . '/testing_feature.php';
    }
});
