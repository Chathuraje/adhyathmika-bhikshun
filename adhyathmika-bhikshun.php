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
    // Optional: Limit loading to specific admin page if needed
    // if ($hook !== 'toplevel_page_ab-settings') return;

    $plugin_url = plugin_dir_url(__FILE__);

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

    // Export/Import Manager submenu
    add_submenu_page(
        'adhyathmika-bhikshun',                  // Parent slug
        'Export/Import Manager',                // Page title
        'Export/Import',                        // Menu title
        'manage_options',                       // Capability
        'abh-export-import-manager',            // Menu slug
        'abh_export_import_manager_page'        // Callback function
    );

    // Feature Settings submenu
    add_submenu_page(
        'adhyathmika-bhikshun',                  
        'Feature Settings',                     
        'Features',                             
        'manage_options',                       
        'abh-features',                         
        'abh_features_page'                     
    );

    // Post Management submenu
    add_submenu_page(
        'adhyathmika-bhikshun',
        'Post Management',
        'Posts Management',
        'manage_options',
        'abh-post-management',
        'abh_post_management_page'
    );


});

// Include all pages
require_once plugin_dir_path(__FILE__) . '/pages/main.php';

// Include all functions
require_once plugin_dir_path(__FILE__) . '/includes/admin.php';

// Load Widgets
require_once plugin_dir_path(__FILE__) . '/includes/widgets.php';


