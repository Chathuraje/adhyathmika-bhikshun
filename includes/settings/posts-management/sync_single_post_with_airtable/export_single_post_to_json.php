<?php

/**
 * Export Single Post to JSON
 *
 * This file contains the implementation of a function to export a single post
 * of a specific post type to a structured JSON array. It includes helper
 * functions for safely unserializing data and processing post meta, taxonomies,
 * attachments, and comments.
 *
 * Functions:
 * - safe_maybe_unserialize($value): Safely unserializes a value, returning the
 *   original value if unserialization fails.
 * - export_single_post_to_json($post_id, $post_type): Exports a single post
 *   with its metadata, taxonomies, attachments, and comments to a structured
 *   JSON array.
 *
 * Security:
 * - Prevents direct access to the file by checking if `ABSPATH` is defined.
 * - Sanitizes inputs such as `$post_id` and `$post_type` to ensure safe usage.
 *
 * Features:
 * - Filters post meta to exclude keys starting with `_elementor`.
 * - Includes featured image URL, attached media files, and comments in the
 *   exported data.
 * - Retrieves and structures taxonomy terms and their metadata.
 *
 * Usage:
 * Call `export_single_post_to_json($post_id, $post_type)` with the desired post
 * ID and post type slug to retrieve the post data as an array suitable for JSON
 * encoding.
 *
 * Example:
 * ```php
 * $post_data = export_single_post_to_json(123, 'post');
 * if ($post_data) {
 *     echo json_encode($post_data);
 * }
 * ```
 */

// Prevent direct access to the file for security reasons
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safely unserialize a value.
 * If maybe_unserialize returns false and the original value is not 'b:0;' (serialized boolean false),
 * return the original value instead of false.
 *
 * @param mixed $value The value to unserialize.
 * @return mixed The unserialized value or the original value if unserialization failed.
 */
function safe_maybe_unserialize($value) {
    $unserialized = maybe_unserialize($value);
    return ($unserialized === false && $value !== 'b:0;') ? $value : $unserialized;
}

/**
 * Export a single post of a specific post type to a structured JSON array.
 *
 * @param int $post_id The ID of the post to export.
 * @param string $post_type The post type slug.
 * @return array|null The post data array suitable for JSON encoding, or null if post not found or type mismatch.
 */
function export_single_post_to_json($post_id, $post_type) {
    // Sanitize inputs
    $post_id = absint($post_id);
    $post_type = sanitize_text_field($post_type);

    // Retrieve the post object
    $post = get_post($post_id);

    // Return null if post not found or post type doesn't match the requested one
    if (!$post || $post->post_type !== $post_type) return null;

    // Get all post meta for the post
    $meta = get_post_meta($post->ID);

    // Filter meta, excluding keys that start with '_elementor'
    $filtered_meta = [];
    foreach ($meta as $key => $values) {
        if (strpos($key, '_elementor') === 0) continue;

        // If only one value, unserialize safely, else map unserialize to each value
        if (count($values) === 1) {
            $filtered_meta[$key] = safe_maybe_unserialize($values[0]);
        } else {
            $filtered_meta[$key] = array_map('safe_maybe_unserialize', $values);
        }
    }

    // Prepare main post data array with key post properties and meta data
    $post_data = [
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_status' => $post->post_status,
        'post_date' => $post->post_date,
        'post_type' => $post->post_type,
        'post_name' => $post->post_name,
        'post_author' => $post->post_author,
        'post_parent' => $post->post_parent,
        'menu_order' => $post->menu_order,
        'is_sticky' => is_sticky($post->ID), // Check if post is sticky
        'meta' => $filtered_meta,
        // Get URL of featured image or null if none
        'featured_image' => ($thumb_id = get_post_thumbnail_id($post->ID)) ? wp_get_attachment_image_url($thumb_id, 'full') : null,
        'taxonomies' => [], // To be filled later
        'attached_files' => [], // To be filled later
        'comments' => [], // To be filled later
    ];

    // Get all media attachments linked to the post
    $attachments = get_attached_media('', $post->ID);
    foreach ($attachments as $attachment) {
        $url = wp_get_attachment_url($attachment->ID);
        if ($url) {
            $post_data['attached_files'][] = $url;
        }
    }

    // Get all comments related to the post
    $comments = get_comments(['post_id' => $post->ID]);
    foreach ($comments as $comment) {
        $post_data['comments'][] = [
            'comment_ID' => $comment->comment_ID,
            'comment_author' => $comment->comment_author,
            'comment_author_email' => $comment->comment_author_email,
            'comment_date' => $comment->comment_date,
            'comment_content' => $comment->comment_content,
            'comment_approved' => $comment->comment_approved,
        ];
    }

    // Get all taxonomies registered for the post type
    $taxonomies = get_object_taxonomies($post_type);

    if (!empty($taxonomies)) {
        foreach ($taxonomies as $taxonomy) {
            // Get terms assigned to this post in the current taxonomy
            $terms = wp_get_post_terms($post->ID, $taxonomy);
            $term_data = [];

            foreach ($terms as $term) {
                // Get term meta for each term
                $meta = [];
                $meta_raw = get_term_meta($term->term_id);
                foreach ($meta_raw as $key => $values) {
                    if (count($values) === 1) {
                        $meta[$key] = safe_maybe_unserialize($values[0]);
                    } else {
                        $meta[$key] = array_map('safe_maybe_unserialize', $values);
                    }
                }

                // Store relevant term data and meta
                $term_data[] = [
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                    'parent' => $term->parent,
                    'meta' => $meta,
                ];
            }
            // Assign all terms data for this taxonomy
            $post_data['taxonomies'][$taxonomy] = $term_data;
        }
    }

    // Return the full post data wrapped inside 'json_data' key
    return ['json_data' => $post_data];
}
?>
