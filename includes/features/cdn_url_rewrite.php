<?php
function ab_enable_cdn_url_rewrite() {
    $cdn_url = get_option('ab_cdn_url', '');
    $site_url = get_site_url();
    $s3_url = 'https://s3.eu-west-2.amazonaws.com/cdn.adhyathmikabhikshun.org';

    if ($cdn_url) {
        add_filter('as3cf_get_attachment_url', function ($url) use ($cdn_url, $s3_url) {
            return str_replace($s3_url, $cdn_url, $url);
        });

        add_filter('wp_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
            return str_replace($site_url, $cdn_url, $url);
        });
    }

    // Redirect attachment pages to their media files (disable attachment pages)
    add_action('template_redirect', function() {
        if (is_attachment()) {
            global $post;
            if ($post && $post->guid) {
                wp_redirect($post->guid, 301);
                exit;
            }
        }
    });
}
?>