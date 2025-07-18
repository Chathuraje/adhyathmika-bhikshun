<?php

/**
 * Plugin Name: Adhyathmika Bhikshun
 * Description: A WordPress plugin for managing Adhyathmika Bhikshun Website Activities, including post ordering and language switching.
 * Version: 1.5
 * Author: Adhyathmika Bhikshun
 */

if (!defined('ABSPATH')) exit;

add_action('admin_enqueue_scripts', 'ab_enqueue_admin_assets');
function ab_enqueue_admin_assets($hook) {
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue settings CSS/JS globally or for specific pages as needed
    wp_enqueue_style(
        'ab-settings-css',
        $plugin_url . 'assets/css/settings.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'ab-settings-js',
        $plugin_url . 'assets/js/settings.js',
        [],
        '1.0.0',
        true
    );

    // Load progress bar script conditionally

    if (get_option('ab_import_posts_to_site_enabled', true)) {
        $screen = get_current_screen();
        if (in_array($screen->post_type, allowed_post_types_for_import_button(), true)) {
            wp_enqueue_script(
                'ab-import-progress-bar',
                $plugin_url . 'assets/js/import-progress.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('ab-import-progress-bar', 'abImport', [
                'postType' => $screen->post_type,
                'postTypeCapitalized' => ucfirst($screen->post_type),
                'importUrl' => esc_url(wp_nonce_url(
                    admin_url('admin-ajax.php?action=import_all_custom_posts&type=' . $screen->post_type),
                    'import_all_custom_posts'
                )),
            ]);
        }
    }

}

// 🔧 Filter to specify which post types should show "Import All" button
function allowed_post_types_for_import_button() {
    return apply_filters('custom_allowed_post_types_for_import_all', ['post']);
}


// Register main admin menu and submenus
add_action('admin_menu', function () {
    // Main menu - Dashboard
    add_menu_page(
        'Adhyathmika Bhikshun',
        'Adhyathmika Bhikshun',
        'manage_options',
        'adhyathmika-bhikshun',
        'abh_dashboard',
        'dashicons-admin-generic',
        2
    );

    // Dashboard submenu (same as main)
    add_submenu_page(
        'adhyathmika-bhikshun',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'adhyathmika-bhikshun',
        'abh_dashboard'
    );

    // // Export/Import Manager submenu
    // add_submenu_page(
    //     'adhyathmika-bhikshun',                  // Parent slug
    //     'Export/Import Manager',                // Page title
    //     'Export/Import',                        // Menu title
    //     'manage_options',                       // Capability
    //     'abh-export-import-manager',            // Menu slug
    //     'abh_export_import_manager_page'        // Callback function
    // );

    // Feature Management submenu
    add_submenu_page(
        'adhyathmika-bhikshun',                  
        'Features Management',                     
        'Features Management',                             
        'manage_options',                       
        'abh-features-management',                         
        'abh_featurers_management_page'                     
    );

    // Post Management submenu
    add_submenu_page(
        'adhyathmika-bhikshun',
        'Posts Management',
        'Posts Management',
        'manage_options',
        'abh-post-management',
        'abh_posts_management_page'
    );


});

// Include all pages
require_once plugin_dir_path(__FILE__) . '/pages/main.php';

// Include all functions
require_once plugin_dir_path(__FILE__) . '/includes/admin.php';

// Load Widgets
// require_once plugin_dir_path(__FILE__) . '/includes/widgets.php';

// Load Custom API Endpoints
require_once plugin_dir_path(__FILE__) . '/apis/admin.php';


