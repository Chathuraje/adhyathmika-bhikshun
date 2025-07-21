<?php
// Load dashboard page
function abh_dashboard() //callback function for the dashboard page
{
    require_once plugin_dir_path(__FILE__) . '/dashboard-page.php';
}

// Load features management page
function abh_featurers_management_page()
{
    require_once plugin_dir_path(__FILE__) . 'settings/features-management-page.php';
}

// Load post management page
function abh_posts_management_page()
{
    require_once plugin_dir_path(__FILE__) . 'settings/posts-management-page.php';
}

// Load site management page
function abh_site_management_page()
{
    require_once plugin_dir_path(__FILE__) . 'settings/site-management-page.php';
}   
?>