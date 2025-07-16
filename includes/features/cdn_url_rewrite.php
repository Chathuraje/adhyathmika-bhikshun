<?php
function ab_enable_cdn_url_rewrite() {
    $cdn_url = get_option('ab_cdn_url', '');
    $site_url = get_site_url();
    $s3_url = 'https://s3.eu-west-2.amazonaws.com/cdn.adhyathmikabhikshun.org';

    if ($cdn_url) {
        add_filter('as3cf_get_attachment_url', function ($url) use ($cdn_url, $site_url, $s3_url) {
            $url = str_replace($s3_url, $cdn_url, $url);
            $url = str_replace($site_url, $cdn_url, $url);
            return $url;
        });

        add_filter('wp_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
            return str_replace($site_url, $cdn_url, $url);
        });
    }
}
?>