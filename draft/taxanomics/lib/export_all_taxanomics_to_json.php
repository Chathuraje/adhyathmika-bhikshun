<?php

// Prevent direct access to the file for security reasons
if (!defined('ABSPATH')) {
    exit;
}

function safe_maybe_unserialize($value) {
    $unserialized = maybe_unserialize($value);
    return ($unserialized === false && $value !== 'b:0;') ? $value : $unserialized;
}

if (!function_exists('export_all_taxonomies_for_post_type')) {
    function export_all_taxonomies_for_post_type(string $post_type) {
        // Get all taxonomies for this post type
        $taxonomies = get_object_taxonomies($post_type, 'names');

        $exported_terms = [];

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false, // include all terms even if no posts assigned
            ]);

            if (is_wp_error($terms)) continue;

            foreach ($terms as $term) {
                $meta_raw = get_term_meta($term->term_id);
                $meta = [];

                foreach ($meta_raw as $key => $values) {
                    $meta[$key] = count($values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
                }

                $exported_terms[$taxonomy][] = [
                    'term_id'     => $term->term_id,
                    'name'        => $term->name,
                    'slug'        => $term->slug,
                    'description' => $term->description,
                    'parent'      => $term->parent,
                    'count'       => $term->count,
                    'meta'        => $meta,
                ];
            }
        }

        return ['json_data' => $exported_terms];
    }
}
?>