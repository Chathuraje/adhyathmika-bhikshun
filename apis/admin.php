<?php
/**
 * Custom API endpoints.
 *
 * @package Adhyathmika_Bhikshun
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (get_option('ab_import_posts_to_site_enabled', true)) {
    include_once __DIR__ . '/import-posts.php';
}

if (get_option('ab_sync_posts_to_airtable_enabled', true)) {
    include_once __DIR__ . '/sync-post.php';
}

?>