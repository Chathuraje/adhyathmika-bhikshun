<?php
function export_single_post_to_json($post_id, $post_type)
{
    // $allowed_post_types = ['post', 'daily-spiritual-offe', 'testimonial', 'small-quote'];

    // if (!in_array($post_type, $allowed_post_types)) return null;

    $post = get_post($post_id);
    if (!$post || $post->post_type !== $post_type) return null;

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
            array_map(fn($v) => count($v) === 1 ? maybe_unserialize($v[0]) : array_map('maybe_unserialize', $v), get_post_meta($post->ID)),
            fn($_, $key) => !str_starts_with($key, '_elementor'),
            ARRAY_FILTER_USE_BOTH
        ),
        'featured_image' => ($id = get_post_thumbnail_id($post->ID)) ? wp_get_attachment_url($id) : null,
        'taxonomies' => [],
        'attached_files' => array_map('wp_get_attachment_url', wp_list_pluck(get_attached_media('', $post->ID), 'ID')),
        'comments' => array_map('get_object_vars', get_comments(['post_id' => $post->ID])),
    ];

    // Add taxonomy and term data
    foreach (get_object_taxonomies($post_type) as $taxonomy) {
        $terms = wp_get_post_terms($post->ID, $taxonomy);
        $term_data = [];

        foreach ($terms as $term) {
            $meta = [];
            $meta_raw = get_term_meta($term->term_id);
            foreach ($meta_raw as $key => $values) {
                $meta[$key] = count($values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', $values);
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

    // Save JSON to file (inside wp-content/uploads/sync_exports/)
    // $upload_dir = wp_upload_dir();
    // $export_dir = WP_CONTENT_DIR . '/adhyathmika-bhikshun_data/' . $post_type;
    // wp_mkdir_p($export_dir);

    // $post_uid = get_post_meta($post->ID, 'post_uid', true);
    // if (!$post_uid) {
    //     $post_uid = $post->ID; // fallback to post ID if post_uid not found
    // }
    // $filename = sanitize_title('post_' . $post_uid) . '.json';
    // $filepath = $export_dir . '/' . $filename;

    // file_put_contents($filepath, json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return [
        'json_data' => $post_data,
        // 'file_path' => $filepath,
        // 'file_url' => $upload_dir['baseurl'] . '/adhyathmika-bhikshun_data/' . $post_type . '/' . $filename,
    ];
}
?>