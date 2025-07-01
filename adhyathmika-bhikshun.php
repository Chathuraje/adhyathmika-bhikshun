<?php
/**
 * Plugin Name: Adhyathmika Bhikshun
 * Description: Admin support tools for adhyathmikabhikshun.org spiritual blog.
 *              Includes ACF export/import and CPT posts export/import.
 * Version: 1.5
 * Author: Adhyathmika Bhikshun
 */

if (!defined('ABSPATH')) exit;

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
});

// Load dashboard page
function abh_dashboard() {
    require_once plugin_dir_path(__FILE__) . 'pages/dashboard.php';
}

// Load unified export/import manager page
function abh_export_import_manager_page() {
    require_once plugin_dir_path(__FILE__) . 'pages/export-import-manager.php';
}

// Include Content export/import logic
require_once plugin_dir_path(__FILE__) . 'includes/export-import-content.php';

// Include CPT posts export/import logic
require_once plugin_dir_path(__FILE__) . 'includes/export-import-cpt.php';

// Include combined export/import logic
require_once plugin_dir_path(__FILE__) . 'includes/export-import-all.php';
