<?php
/**
 * Retrieves the global position of the current post in the list of all published posts.
 *
 * This function is designed to be used as a shortcode and returns the 1-based position
 * of the current post in the list of all published posts, ordered by publish date (oldest first).
 * If the current post is not found or an error occurs, an empty string is returned.
 *
 * Shortcode: [ab_get_global_post_position]
 *
 * @param array $atts Shortcode attributes. Currently unused, but allows for future expansion.
 * @return string The 1-based position of the current post, or an empty string if not found.
 *
 * Caching:
 * - The function uses the WordPress object cache to store the list of all post IDs
 *   under the cache key 'ab_global_post_position_ids' to improve performance.
 *
 * Notes:
 * - The function ensures that it does not conflict with other functions by checking
 *   if it already exists before defining it.
 * - The function only processes standard published posts ('post' post type).
 * - The function exits early if the global $post object is invalid or not a WP_Post instance.
 *
 * Example Usage:
 * - Add the shortcode [ab_get_global_post_position] to a post or page to display its global position.
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Check if the function already exists to avoid redeclaration conflicts.
if ( ! function_exists( 'ab_global_post_position_sc' ) ) {
    function ab_global_post_position_sc() {
        global $post;

        // Exit early if $post is not a valid WP_Post object.
        if ( ! isset( $post ) || ! $post instanceof WP_Post ) {
            return '';
        }

        // Get the current post ID in a safe way.
        $post_id = get_the_ID();

        // Define a unique cache key to store the list of post IDs.
        $cache_key = 'ab_global_post_position_ids';

        // Attempt to get the cached post ID list to avoid repeated queries.
        $all_posts = wp_cache_get( $cache_key );

        // If the post IDs are not cached, fetch and cache them.
        if ( false === $all_posts ) {
            $all_posts = get_posts( [
                'posts_per_page'         => -1,       // Get all posts.
                'post_type'              => 'post',   // Only standard posts.
                'post_status'            => 'publish',// Only published posts.
                'orderby'                => 'date',   // Order by publish date.
                'order'                  => 'ASC',    // Oldest first.
                'fields'                 => 'ids',    // Only return post IDs for performance.
                'no_found_rows'          => true,     // Skip pagination count queries.
                'update_post_meta_cache' => false,    // Don’t load post meta.
                'update_post_term_cache' => false,    // Don’t load taxonomy terms.
            ] );

            // Store the result in the object cache for future use.
            wp_cache_set( $cache_key, $all_posts );
        }

        // Return early if the list is empty or invalid.
        if ( empty( $all_posts ) || ! is_array( $all_posts ) ) {
            return '';
        }

        // Find the index of the current post in the ordered list.
        $position = array_search( $post_id, $all_posts, true );

        // If found, return the 1-based position; otherwise, return an empty string.
        return ( false !== $position ) ? ( $position + 1 ) : '';
    }
}
