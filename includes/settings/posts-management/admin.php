<?php
/**
 * Adhyathmika Bhikshun Plugin
 * Admin Functions
 *
 * @package Adhyathmika_Bhikshun
 */


// Filter for allowed post types (currently not in use)
function allowed_post_types_for_import_button() {
    return apply_filters('custom_allowed_post_types_for_import_all', ['post', 'small-quote', 'daily-spiritual-offe', 'testimonial']);
}

$post_uid_fields = [
    'post'                => 'post_uid',
    'daily-spiritual-offe' => 'dso_uid',
    'small-quote'         => 'sq_uid',
    'testimonial'         => 'testimonial_uid',
];

function get_post_uid_meta_key($post_type) {
    global $post_uid_fields;

    return isset($post_uid_fields[$post_type]) ? $post_uid_fields[$post_type] : '';
}

function get_post_uid($post_type, $post_id) {
    global $post_uid_fields;

    $post_uid = get_field($post_uid_fields[$post_type], $post_id);
    return $post_uid;
}

/**
 * Register settings and handle form submissions
 */
add_action('admin_init', function () {
    // Register plugin options
    $settings = [
        'ab_testing_enabled',
        'ab_create_a_new_post_enabled',
        'ab_sync_single_post_with_airtable_enabled',
        'ab_sync_all_posts_with_airtable_enabled',
        'ab_import_all_posts_from_airtable_enabled',
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
            'ab_sync_all_posts_with_airtable_enabled',
            'ab_import_all_posts_from_airtable_enabled',
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

    if (get_option('ab_create_a_new_post_enabled', true)) {
        require_once __DIR__ . '/create_a_new_post.php';
    }

   if (get_option('ab_sync_single_post_with_airtable_enabled', true)) {
        require_once __DIR__ . '/sync_single_post_with_airtable.php';
   }

    if (get_option('ab_sync_all_posts_with_airtable_enabled', true)) {
        require_once __DIR__ . '/sync_all_posts_with_airtable.php';
        require_once __DIR__ . '/apis/sync_all_posts_with_api.php';
    }
   
   if (get_option('ab_import_all_posts_from_airtable_enabled', true)) {
        require_once __DIR__ . '/import_all_posts_from_airtable.php';
        require_once __DIR__ . '/apis/import_all_posts_with_api.php';
   }

   if (
        get_option('ab_sync_single_post_with_airtable_enabled', true) || 
        get_option('ab_sync_all_posts_with_airtable_enabled', true)
    ) {
        require_once __DIR__ . '/lib/add_airtable_sync_columns.php';
    }



});