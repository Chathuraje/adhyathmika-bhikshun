<?php
/**
 * Enable CDN URL Rewrite Filters
 */
function ab_enable_cdn_url_rewrite() {
    $cdn_url = get_option('ab_cdn_url', '');
    $site_url = get_site_url();

    if ($cdn_url) {
        add_filter('as3cf_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
            return str_replace($site_url, $cdn_url, $url);
        });

        add_filter('wp_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
            return str_replace($site_url, $cdn_url, $url);
        });
    }
}
