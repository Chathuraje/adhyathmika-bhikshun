<?php
/**
 * Adhyathmika Bhikshun Plugin
 * Admin Functions
 *
 * @package Adhyathmika_Bhikshun
 */

// 🔧 Filter for allowed post types (currently not in use)
// function allowed_post_types_for_import_button() {
//     return apply_filters('custom_allowed_post_types_for_import_all', ['post']);
// }

/**
 * Register settings and handle form submissions
 */
add_action('admin_init', function () {
    // Register plugin options
    $settings = [
        'ab_testing_enabled',
        'ab_create_a_new_post_enabled',
        'ab_sync_single_post_with_airtable_enabled',
        // 'ab_import_posts_to_site_enabled',
    ];

    foreach ($settings as $setting) {
        register_setting('ab_settings_group', $setting);
    }

    // Handle form submission
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
            'ab_create_a_new_post_enabled',
            'ab_sync_single_post_with_airtable_enabled',
            // 'ab_import_posts_to_site_enabled',
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
    if (get_option('ab_create_a_new_post_enabled', true)) {
       require_once __DIR__ . '/create_a_new_post.php';
   }

   if (get_option('ab_sync_single_post_with_airtable_enabled', true)) {
       require_once __DIR__ . '/sync_single_post_with_airtable/main.php';
   }
   
//    if (get_option('ab_import_posts_to_site_enabled', true)) {
//        require_once __DIR__ . '/import_posts_to_site.php';
//    }

});