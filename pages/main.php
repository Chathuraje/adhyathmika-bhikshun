<?php
// Load dashboard page
function abh_dashboard()
{
    require_once plugin_dir_path(__FILE__) . '/dashboard-page.php';
}

// Load unified export/import manager page
function abh_export_import_manager_page()
{
    require_once plugin_dir_path(__FILE__) . '/export-import-manager-page.php';
}

// Load feature page
function abh_features_page()
{
    require_once plugin_dir_path(__FILE__) . '/features-page.php';
}

// Load post management page
function abh_post_management_page()
{
    require_once plugin_dir_path(__FILE__) . '/post-management-page.php';
}
?>