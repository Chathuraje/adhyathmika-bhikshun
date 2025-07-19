<?php
/**
 * File: register_custom_rest_fields.php
 * 
 * This file contains functions to register custom REST API fields and meta fields
 * for WordPress posts. These fields extend the REST API functionality by exposing
 * additional data for posts.
 * 
 * Functions:
 * - ab_register_content_raw_field(): Registers the `content_raw` field for the REST API,
 *   which provides the raw post content.
 * - ab_register_rank_math_focus_keyword_meta(): Registers the `rank_math_focus_keyword`
 *   meta field for the REST API, allowing access to the Rank Math focus keyword metadata.
 * 
 * Notes:
 * - Direct access to this file is prevented for security purposes.
 * - The `content_raw` field is read-only and does not include a schema definition.
 * - The `rank_math_focus_keyword` meta field is only accessible to users with the
 *   `edit_posts` capability.
 */

 
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the `content_raw` field for REST API.
 */
if (!function_exists('ab_register_content_raw_field')) {
    function ab_register_content_raw_field() {
        register_rest_field('post', 'content_raw', [
            'get_callback' => function ($post) {
                return get_post_field('post_content', $post['id']);
            },
            'schema' => null,
        ]);
    }
}

/**
 * Registers the `rank_math_focus_keyword` meta field for REST API.
 */
if (!function_exists('ab_register_rank_math_focus_keyword_meta')) {
    function ab_register_rank_math_focus_keyword_meta() {
        register_post_meta('post', 'rank_math_focus_keyword', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}
