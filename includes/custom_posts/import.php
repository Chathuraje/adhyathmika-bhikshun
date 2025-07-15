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
                $image_url = $post['featured_image'];

                // Try to get attachment ID from URL
                $att_id = attachment_url_to_postid($image_url);

                // If not found and it's a CDN URL, create a dummy attachment
                if (!$att_id && filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $filename = basename(parse_url($image_url, PHP_URL_PATH));
                    $filetype = wp_check_filetype($filename, null);

                    $attachment = [
                        'post_mime_type' => $filetype['type'] ?? 'image/jpeg',
                        'post_title'     => sanitize_file_name($filename),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                        'guid'           => $image_url,
                    ];

                    // Insert the attachment without downloading it
                    $att_id = wp_insert_attachment($attachment, $image_url, $post_id);

                    // Generate attachment metadata (fake metadata just to be complete)
                    if (!is_wp_error($att_id)) {
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        wp_update_attachment_metadata($att_id, []);
                    }
                }

                if (!is_wp_error($att_id)) {
                    set_post_thumbnail($post_id, $att_id);
                }

                // Attach media files from CDN
                if (!empty($post['attached_files']) && is_array($post['attached_files'])) {
                    foreach ($post['attached_files'] as $media_url) {
                        if (filter_var($media_url, FILTER_VALIDATE_URL)) {
                            $filename = basename(parse_url($media_url, PHP_URL_PATH));
                            $filetype = wp_check_filetype($filename, null);

                            $attachment = [
                                'post_mime_type' => $filetype['type'] ?? 'application/octet-stream',
                                'post_title'     => sanitize_file_name($filename),
                                'post_content'   => '',
                                'post_status'    => 'inherit',
                                'guid'           => $media_url,
                            ];

                            $att_id = wp_insert_attachment($attachment, $media_url, $post_id);

                            if (!is_wp_error($att_id)) {
                                require_once ABSPATH . 'wp-admin/includes/image.php';
                                wp_update_attachment_metadata($att_id, []);
                            }
                        }
                    }
                }
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
