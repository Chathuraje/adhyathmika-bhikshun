<?php

if (!function_exists('import_all_taxonomies_from_data')) {
    function import_all_taxonomies_from_data(array $taxonomies_data){
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

?>