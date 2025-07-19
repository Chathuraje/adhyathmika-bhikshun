<?php
/**
 * Adds a dynamic site link to the WordPress admin bar based on the current site's domain.
 *
 * This function checks the current site's domain and adds a context-aware link
 * to the WordPress admin bar. If the current site is on a `.org` domain, it adds
 * a link to the `.lk` version of the site. Conversely, if the current site is on
 * a `.lk` domain, it adds a link to the `.org` version of the site.
 *
 * The function ensures compatibility by checking if the admin bar is visible
 * and prevents conflicts by verifying if the function is already declared.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WordPress Admin Bar object used to add nodes.
 *
 * @return void
 *
 * @example
 * When on a `.org` site:
 * Adds a link titled "🌐 Visit LK Site" pointing to the `.lk` version.
 *
 * When on a `.lk` site:
 * Adds a link titled "🌍 Visit Global Site" pointing to the `.org` version.
 *
 * @note The function uses `wp_parse_url()` to extract the host from the site URL
 *       and determines the appropriate link to add based on the domain.
 *
 * @see WP_Admin_Bar::add_node() For adding nodes to the admin bar.
 * @see get_site_url() For retrieving the current site's URL.
 * @see is_admin_bar_showing() For checking if the admin bar is visible.
 */

// Check if the function is already declared to prevent conflicts
if (!function_exists('ab_admin_bar_dynamic_site_link')) {

    /**
     * Adds a context-aware external site link to the WordPress admin bar.
     * Displays a link to the .lk site when on .org, and vice versa.
     *
     * @param WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
     */
    function ab_admin_bar_dynamic_site_link($wp_admin_bar) {
        // Only show the admin bar if it's visible to the user
        if (!is_admin_bar_showing()) {
            return;
        }

        // Get the current site's URL
        $site_url = get_site_url();

        // Parse the URL to extract the host (e.g., adhyathmikabhikshun.org)
        $parsed_url = wp_parse_url($site_url);
        $host = $parsed_url['host'] ?? '';

        // Bail early if host is not available
        if (empty($host)) {
            return;
        }

        // Define the nodes (admin bar links) for each environment
        $nodes = [
            'org' => [
                'id'    => 'lk-site-link', // Unique ID for this admin bar node
                'title' => '🌐 Visit LK Site', // Display title
                'href'  => 'https://adhyathmikabhikshun.lk/', // Destination URL
                'meta'  => [
                    'target' => '_blank', // Open in a new tab
                    'title'  => 'Visit the .lk version of the site' // Tooltip text
                ]
            ],
            'lk' => [
                'id'    => 'org-site-link',
                'title' => '🌍 Visit Global Site',
                'href'  => 'https://adhyathmikabhikshun.org/',
                'meta'  => [
                    'target' => '_blank',
                    'title'  => 'Visit the .org version of the site'
                ]
            ]
        ];

        // Conditionally add the appropriate node based on current host
        if (strpos($host, '.org') !== false) {
            $wp_admin_bar->add_node($nodes['org']); // Show link to .lk site
        } elseif (strpos($host, '.lk') !== false) {
            $wp_admin_bar->add_node($nodes['lk']); // Show link to .org site
        }
    }

}
