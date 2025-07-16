<?php

/**
 * Save settings on admin POST
 */

 add_action('admin_init', function () {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['ab_post_settings_nonce']) &&
        wp_verify_nonce($_POST['ab_post_settings_nonce'], 'ab_post_save_settings')
    ) {
        update_option('ab_single_airtable_sync_enabled', isset($_POST['ab_single_airtable_sync_enabled']) ? 1 : 0);
        update_option('ab_testing_enabled', isset($_POST['ab_testing_enabled']) ? 1 : 0);
        update_option('ab_new_post_creation_enabled', isset($_POST['ab_new_post_creation_enabled']) ? 1 : 0);
    }
});

// load Single Airtable Sync feature conditionally
add_action('init', function () {
    if (get_option('ab_single_airtable_sync_enabled', true)) {
        require_once __DIR__ . '/single_airtable_sync.php';
    }

    if (get_option('ab_new_post_creation_enabled', true)) {
        require_once __DIR__ . '/new_post_creation.php';
    }
});
