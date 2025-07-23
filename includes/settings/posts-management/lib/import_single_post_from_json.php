<?php

function get_attachment_id_by_url_slug($url) {
    global $wpdb;

    $filename = basename(parse_url($url, PHP_URL_PATH));

    // Step 1: Try direct match via _wp_attached_file meta
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta 
         WHERE meta_key = '_wp_attached_file' 
         AND meta_value LIKE %s 
         LIMIT 1",
        '%' . $wpdb->esc_like($filename) . '%'
    ));

    if ($attachment_id) {
        return (int) $attachment_id;
    }

    // Step 2: Try match via guid (for old attachments)
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts 
         WHERE post_type = 'attachment' 
         AND guid LIKE %s 
         LIMIT 1",
        '%' . $wpdb->esc_like($filename) . '%'
    ));

    if ($attachment_id) {
        return (int) $attachment_id;
    }

    // Step 3: Optionally – match via hashed URL as a custom meta key you track
    // e.g. use sha1($url) stored as 'import_source_hash'

    return 0; // Not found
}

function attach_or_import_media($url, $post_id = 0) {
    $att_id = get_attachment_id_by_url_slug($url);

    if (!$att_id) {
        // Media not found – download and sideload it
        $att_id = media_sideload_image($url, $post_id, null, 'id');

        // Optional: Store a reference for future match
        if (!is_wp_error($att_id)) {
            update_post_meta($att_id, 'import_source_url', esc_url_raw($url));
        }
    }

    return (!is_wp_error($att_id)) ? $att_id : 0;
}


function import_single_post_from_data(array $post) {

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $result = [
        'post_title' => $post['post_title'] ?? ''
    ];

    $required_fields = ['post_title', 'post_status', 'post_type'];
    foreach ($required_fields as $field) {
        if (empty($post[$field])) {
            $result['status'] = 'failed';
            $result['error'] = "Missing required field: $field";
            return $result;
        }
    }

    // Check for existing post by some unique identifier
    if (!empty($post['meta']['post_uid'])) {
        $existing = get_posts([
            'meta_key' => 'post_uid',
            'meta_value' => $post['meta']['post_uid'],
            'post_type' => $post['post_type'],
            'fields' => 'ids',
            'posts_per_page' => 1
        ]);
            
        if (!empty($existing)) {
            return [
                'post_title' => $post['post_title'],
                'airtable_id' => $post['airtable_id'],
                'status' => 'skipped',
                'post_id' => $existing[0],
                'reason' => 'Duplicate post_uid'
            ];
        }
    }

    // Check for existing post by slug
    if (!empty($post['post_name'])) {
        $existing = get_posts([
            'name' => $post['post_name'],
            'post_type' => $post['post_type'],
            'post_status' => 'any',
            'numberposts' => 1

        ]);
        if (!empty($existing)) {
            $existing_post = $existing[0];
            return [
                'post_title' => $post['post_title'],
                'airtable_id' => $post['airtable_id'],
                'status' => 'skipped',
                'post_id' => $existing_post->ID,
                'reason' => 'Duplicate post slug'
            ];
        }
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

    if (is_wp_error($post_id)) {
        $result['status'] = 'failed';
        $result['error'] = $post_id->get_error_message();
        return $result;
    }

    $result['status'] = 'success';
    $result['post_id'] = $post_id;
    $result['airtable_id'] = $post['airtable_id'] ?? null;

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
        $att_id = attach_or_import_media($post['featured_image'], $post_id);
        if ($att_id) set_post_thumbnail($post_id, $att_id);
    }

    // ATTACHED FILES
    foreach ($post['attached_files'] ?? [] as $file_url) {
        attach_or_import_media($file_url, $post_id);
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

    return $result;
}
?>