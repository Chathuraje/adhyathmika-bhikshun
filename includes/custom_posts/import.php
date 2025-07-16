<?php
// import.php

if (!function_exists('import_custom_posts_from_data')) {
    function import_custom_posts_from_data(array $data)
    {
        global $allowed_post_types;

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $types_to_clear = array_unique(array_column($data, 'post_type'));
        foreach ($types_to_clear as $type) {
            if (in_array($type, $allowed_post_types)) {
                foreach (
                    get_posts([
                        'post_type'      => $type,
                        'posts_per_page' => -1,
                        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future', 'inherit', 'trash'],
                        'fields'         => 'ids',
                    ]) as $id
                ) {
                    wp_delete_post($id, true);
                }
            }
        }

        foreach ($data as $post) {
            if (!in_array($post['post_type'], $allowed_post_types)) continue;

            $post_id = wp_insert_post([
                'post_title'   => wp_slash($post['post_title']),
                'post_content' => wp_slash($post['post_content']),
                'post_excerpt' => wp_slash($post['post_excerpt']),
                'post_status'  => $post['post_status'],
                'post_type'    => $post['post_type'],
                'post_date'    => $post['post_date'],
                'post_name'    => $post['post_name'],
                'post_author'  => $post['post_author'],
                'post_parent'  => $post['post_parent'],
                'menu_order'   => $post['menu_order'],
            ], true);

            if (is_wp_error($post_id)) continue;

            if (!empty($post['is_sticky'])) stick_post($post_id);
            foreach ($post['meta'] ?? [] as $key => $value) update_post_meta($post_id, $key, $value);

            if (!empty($post['featured_image'])) {
                $att_id = attachment_url_to_postid($post['featured_image']);
                if (!$att_id) $att_id = media_sideload_image($post['featured_image'], $post_id, null, 'id');
                if (!is_wp_error($att_id)) set_post_thumbnail($post_id, $att_id);
            }

            foreach ($post['taxonomies'] ?? [] as $taxonomy => $terms) {
                $term_ids = [];

                foreach ($terms as $term_data) {
                    $existing = get_term_by('slug', $term_data['slug'], $taxonomy);

                    if ($existing) {
                        $term_id = $existing->term_id;
                    } else {
                        $inserted = wp_insert_term($term_data['name'], $taxonomy, [
                            'slug'        => $term_data['slug'],
                            'description' => $term_data['description'],
                            'parent'      => $term_data['parent'],
                        ]);
                        $term_id = !is_wp_error($inserted) && isset($inserted['term_id']) ? $inserted['term_id'] : null;
                    }

                    if ($term_id && !empty($term_data['meta'])) {
                        foreach ($term_data['meta'] as $key => $value) {
                            update_term_meta($term_id, $key, $value);
                        }
                    }

                    if ($term_id) {
                        $term_ids[] = (int) $term_id;
                    }
                }

                if (!empty($term_ids)) {
                    // Assign terms to the post by IDs
                    wp_set_post_terms($post_id, $term_ids, $taxonomy);
                }
            }



            foreach ($post['comments'] ?? [] as $comment) {
                $comment['comment_post_ID'] = $post_id;
                unset($comment['comment_ID']);
                wp_insert_comment(wp_slash($comment));
            }
        }
    }
}


if (!function_exists('import_all_taxonomies_from_data')) {
    function import_all_taxonomies_from_data(array $taxonomies_data)
    {
        foreach ($taxonomies_data as $taxonomy => $terms) {
            foreach ($terms as $term_data) {
                // Try to find by slug
                $existing = get_term_by('slug', $term_data['slug'], $taxonomy);

                if ($existing) {
                    wp_update_term($existing->term_id, $taxonomy, [
                        'name'        => $term_data['name'],
                        'description' => $term_data['description'],
                        'parent'      => $term_data['parent'],
                    ]);
                    $term_id = $existing->term_id;
                } else {
                    $inserted = wp_insert_term($term_data['name'], $taxonomy, [
                        'slug'        => $term_data['slug'],
                        'description' => $term_data['description'],
                        'parent'      => $term_data['parent'],
                    ]);
                    if (is_wp_error($inserted)) {
                        error_log('Failed to insert term: ' . $term_data['slug']);
                        continue;
                    }
                    $term_id = $inserted['term_id'];
                }

                // Import term meta
                if (!empty($term_id) && !empty($term_data['meta'])) {
                    foreach ($term_data['meta'] as $key => $value) {
                        update_term_meta($term_id, $key, maybe_serialize($value));
                    }
                }
            }
        }
    }
}
