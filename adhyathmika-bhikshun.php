<?php

/**
 * Plugin Name: Adhyathmika Bhikshun
 * Description: A WordPress plugin for managing Adhyathmika Bhikshun Website Activities, including post ordering and language switching.
 * Version: 1.5
 * Author: Adhyathmika Bhikshun
 */

if (!defined('ABSPATH')) exit;

// Load CSS and JS files
require_once __DIR__ . 'assets/admin.php';

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


