<?php
function ab_enable_cdn_url_rewrite() {
    $cdn_url = rtrim(get_option('ab_cdn_url', ''), '/') . '/';
    $site_url = rtrim(get_site_url(), '/') . '/';
    $s3_url   = 'https://s3.eu-west-2.amazonaws.com/cdn.adhyathmikabhikshun.org';

    if ($cdn_url) {
        // Rewrite URLs returned by plugins (like WP Offload Media)
        add_filter('as3cf_get_attachment_url', function ($url) use ($cdn_url, $s3_url) {
            return str_replace($s3_url, $cdn_url, $url);
        });

        // Rewrite WordPress native attachment URLs
        add_filter('wp_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
            return str_replace($site_url, $cdn_url, $url);
        });

        // Override the attachment "View" link in Media Library
        add_filter('attachment_link', function ($link, $post_id) use ($cdn_url) {
            $file_url = wp_get_attachment_url($post_id);
            return $file_url ? $file_url : $link;
        }, 10, 2);
    }

    // Optional: Redirect attachment pages to the actual file URL
    add_action('template_redirect', function () use ($cdn_url) {
        if (is_attachment()) {
            global $post;
            $redirect_url = wp_get_attachment_url($post->ID);
            if ($redirect_url) {
                wp_redirect($redirect_url, 301);
                exit;
            }
        }
    });
}
