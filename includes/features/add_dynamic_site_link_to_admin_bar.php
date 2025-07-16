<?php
function add_dynamic_site_link_to_admin_bar($wp_admin_bar) {
    $site_url = get_site_url();
    $parsed_url = parse_url($site_url);
    $host = $parsed_url['host'] ?? '';

    if (strpos($host, '.org') !== false) {
        $wp_admin_bar->add_node([
            'id'    => 'lk-site-link',
            'title' => '🌐 Visit LK Site',
            'href'  => 'https://adhyathmikabhikshun.lk/',
            'meta'  => [
                'target' => '_blank',
                'title'  => 'Visit the .lk version of the site'
            ]
        ]);
    } elseif (strpos($host, '.lk') !== false) {
        $wp_admin_bar->add_node([
            'id'    => 'org-site-link',
            'title' => '🌍 Visit Global Site',
            'href'  => 'https://adhyathmikabhikshun.org/',
            'meta'  => [
                'target' => '_blank',
                'title'  => 'Visit the .org version of the site'
            ]
        ]);
    }
}
?>