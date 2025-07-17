<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/../single_airtable_sync.php';

function get_attachment_id_by_url_slug($url)
{
    $filename = basename(parse_url($url, PHP_URL_PATH));

    $query = new WP_Query([
        'post_type'  => 'attachment',
        'post_status'=> 'inherit',
        'meta_query' => [[
            'key'     => '_wp_attached_file',
            'value'   => $filename,
            'compare' => 'LIKE'
        ]],
        'fields'     => 'ids',
        'posts_per_page' => 1
    ]);

    return $query->have_posts() ? $query->posts[0] : 0;
}


if (!function_exists('import_custom_posts_from_data')) {
    function import_custom_posts_from_data(array $post)
    {
        // global $allowed_post_types;

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // if (!in_array($post['post_type'], $allowed_post_types)) return;

        // Check if post already exists by unique identifier (e.g., post_name + post_type)
        $existing_post = get_page_by_path($post['slug'], OBJECT, $post['post_type']);

        // If it exists, only do the sync and return
        if ($existing_post) {
            airtable_sync_send($existing_post->ID, $post['meta']['post_uid']);
            return;
        }

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

        if (is_wp_error($post_id)) return;

        if (!empty($post['is_sticky'])) stick_post($post_id);

        if (!empty($post['meta']) && is_array($post['meta'])) {
            foreach ($post['meta'] as $key => $value) {
                // If it's a flat key-value pair:
                if (is_string($key)) {
                    update_post_meta($post_id, $key, $value);
                }
                // If it's a structured array like ['key' => '...', 'value' => '...']
                elseif (is_array($value) && isset($value['key'], $value['value'])) {
                    update_post_meta($post_id, $value['key'], $value['value']);
                }
            }
        }
        

        // --- FEATURED IMAGE ---
        if (!empty($post['featured_image'])) {
            $att_id = get_attachment_id_by_url_slug($post['featured_image']);
            if (!$att_id) {
                $att_id = media_sideload_image($post['featured_image'], $post_id, null, 'id');
            }
            if (!is_wp_error($att_id)) {
                set_post_thumbnail($post_id, $att_id);
            }
        }

        // --- ATTACHED FILES ---
        foreach ($post['attached_files'] ?? [] as $file_url) {
            $att_id = get_attachment_id_by_url_slug($file_url);
            if (!$att_id) {
                $att_id = media_sideload_image($file_url, $post_id, null, 'id');
            }
        }

        // --- TAXONOMIES ---
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
                    $term_id = !is_wp_error($inserted) ? $inserted['term_id'] : null;
                }

                if ($term_id && !empty($term_data['meta'])) {
                    foreach ($term_data['meta'] as $key => $value) {
                        update_term_meta($term_id, $key, $value);
                    }
                }

                if ($term_id) $term_ids[] = (int) $term_id;
            }

            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, $taxonomy);
            }
        }

        // --- COMMENTS ---
        foreach ($post['comments'] ?? [] as $comment) {
            $comment['comment_post_ID'] = $post_id;
            unset($comment['comment_ID']);
            wp_insert_comment(wp_slash($comment));
        }

        airtable_sync_send($post_id, $post['meta']['post_uid']); 
    }
}
