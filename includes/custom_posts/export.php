<?php
// export.php

$allowed_post_types = ['post', 'daily-spiritual-offe', 'testimonial', 'small-quote'];

if (!function_exists('export_custom_posts')) {
    function export_custom_posts(string $post_type)
    {
        global $allowed_post_types;
        if (!in_array($post_type, $allowed_post_types)) return null;

        $posts = get_posts(['post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any']);
        $export_data = [];

        foreach ($posts as $post) {
            $post_data = [
                'post_title'   => $post->post_title,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_status'  => $post->post_status,
                'post_date'    => $post->post_date,
                'post_type'    => $post->post_type,
                'post_name'    => $post->post_name,
                'post_author'  => $post->post_author,
                'post_parent'  => $post->post_parent,
                'menu_order'   => $post->menu_order,
                'is_sticky'    => is_sticky($post->ID),
                'meta' => array_filter(
                    array_map(fn($v) => count($v) === 1 ? $v[0] : $v, get_post_meta($post->ID)),
                    fn($_, $key) => !str_starts_with($key, '_elementor'),
                    ARRAY_FILTER_USE_BOTH
                ),

                'featured_image' => ($id = get_post_thumbnail_id($post->ID)) ? wp_get_attachment_url($id) : null,
                'taxonomies'   => [],
                'attached_files' => array_map('wp_get_attachment_url', wp_list_pluck(get_attached_media('', $post->ID), 'ID')),
                'comments'     => array_map('get_object_vars', get_comments(['post_id' => $post->ID])),
            ];

            foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy);

                $term_data = [];
                foreach ($terms as $term) {
                    $meta = [];
                    // Get all meta keys for this term (custom meta)
                    $meta_keys = get_term_meta($term->term_id);

                    foreach ($meta_keys as $meta_key => $meta_value) {
                        // You might want to simplify meta_value if it is an array with 1 value
                        $meta[$meta_key] = maybe_unserialize($meta_value[0]);
                    }

                    $term_data[] = [
                        'term_id'     => $term->term_id,
                        'name'        => $term->name,
                        'slug'        => $term->slug,
                        'description' => $term->description,
                        'parent'      => $term->parent,
                        'meta'        => $meta,
                    ];
                }

                $post_data['taxonomies'][$taxonomy] = $term_data;
            }


            $export_data[] = $post_data;
        }

        return $export_data;
    }
}

if (!function_exists('export_all_taxonomies_for_post_type')) {
    function export_all_taxonomies_for_post_type(string $post_type)
    {
        global $allowed_post_types;

        if (!in_array($post_type, $allowed_post_types)) {
            return []; // invalid post type
        }

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

        return $exported_terms;
    }
}
