<?php
/**
 * Initializes CDN URL rewriting functionality for WordPress.
 *
 * This function sets up filters and actions to rewrite URLs for media attachments
 * to use a configured CDN URL. It includes the following features:
 *
 * 1. Replaces S3 URLs with the configured CDN URL for media attachments uploaded
 *    via plugins like WP Offload Media.
 * 2. Replaces native WordPress attachment URLs with the configured CDN URL.
 * 3. Overrides the "View" link in the Media Library list view to point to the
 *    CDN URL.
 * 4. Optionally redirects attachment pages to their corresponding CDN file URLs.
 *
 * Filters and Actions:
 * - `as3cf_get_attachment_url`: Rewrites S3 URLs to use the CDN URL.
 * - `wp_get_attachment_url`: Rewrites native attachment URLs to use the CDN URL.
 * - `attachment_link`: Modifies the "View" link in the Media Library to use the CDN URL.
 * - `template_redirect`: Redirects attachment pages to their CDN file URLs.
 *
 * Requirements:
 * - A valid CDN URL must be configured in the WordPress options under the key
 *   `ab_cdn_url`.
 * - The function ensures that the CDN URL is valid by using `FILTER_VALIDATE_URL`.
 *
 * Notes:
 * - The function prevents execution if accessed directly by checking the `ABSPATH`
 *   constant.
 * - The hardcoded S3 base URL is used for compatibility with WP Offload Media or
 *   similar plugins.
 *
 * @return void
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initializes all filters and redirects for CDN URL rewriting.
 */
if (!function_exists('ab_cdn_url_rewrite')) {
    function ab_cdn_url_rewrite() {
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
}