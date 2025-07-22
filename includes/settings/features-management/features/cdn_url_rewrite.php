<?php
/**
 * Initializes CDN URL rewriting functionality for WordPress.
 *
 * Features:
 * 1. Rewrites S3 and native media URLs to use a configured CDN.
 * 2. Updates Media Library "View" links.
 * 3. Optionally redirects attachment pages to CDN URLs.
 * 4. Rewrites URLs in post content and Elementor content blocks.
 *
 * Requirements:
 * - A valid CDN URL must be configured in `ab_cdn_url` option.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ab_cdn_url_rewrite')) {
    function ab_cdn_url_rewrite() {
        // Get CDN and Site URLs with trailing slashes
        $cdn_url  = trailingslashit(get_option('ab_cdn_url', ''));
        $site_url = trailingslashit(get_site_url());

        // Hardcoded S3 base URL (for WP Offload Media compatibility)
        $s3_url = 'https://s3.eu-west-2.amazonaws.com/cdn.adhyathmikabhikshun.org';

        // Only proceed if the CDN URL is valid
        if (!empty($cdn_url) && filter_var($cdn_url, FILTER_VALIDATE_URL)) {

            // 1. Replace S3 URLs with CDN URLs
            add_filter('as3cf_get_attachment_url', function ($url) use ($cdn_url, $s3_url) {
                return (is_string($url) && strpos($url, $s3_url) !== false)
                    ? str_replace($s3_url, $cdn_url, $url)
                    : $url;
            });

            // 2. Replace native attachment URLs with CDN URLs
            add_filter('wp_get_attachment_url', function ($url) use ($cdn_url, $site_url) {
                return (is_string($url) && strpos($url, $site_url) !== false)
                    ? str_replace($site_url, $cdn_url, $url)
                    : $url;
            });

            // 3. Override "View" link in Media Library
            add_filter('attachment_link', function ($link, $post_id) {
                $file_url = wp_get_attachment_url($post_id);
                return $file_url ?: $link;
            }, 10, 2);

            // 4. Redirect attachment pages to CDN
            add_action('template_redirect', function () {
                if (is_attachment()) {
                    global $post;
                    if (!empty($post->ID)) {
                        $redirect_url = wp_get_attachment_url($post->ID);
                        if ($redirect_url && filter_var($redirect_url, FILTER_VALIDATE_URL)) {
                            wp_safe_redirect($redirect_url, 301);
                            exit;
                        }
                    }
                }
            });

            // 5. Rewrite media URLs in post content
            add_filter('the_content', function ($content) use ($cdn_url, $site_url) {
                if (is_string($content)) {
                    $pattern = '#'.preg_quote($site_url, '#').'/wp-content/uploads#';
                    $replacement = rtrim($cdn_url, '/');
                    return preg_replace($pattern, $replacement . '/wp-content/uploads', $content);
                }
                return $content;
            });

            // 6. Rewrite media URLs in Elementor content
            add_filter('elementor/frontend/the_content', function ($content) use ($cdn_url, $site_url) {
                if (is_string($content)) {
                    $pattern = '#'.preg_quote($site_url, '#').'/wp-content/uploads#';
                    $replacement = rtrim($cdn_url, '/');
                    return preg_replace($pattern, $replacement . '/wp-content/uploads', $content);
                }
                return $content;
            });
        }
    }
}
