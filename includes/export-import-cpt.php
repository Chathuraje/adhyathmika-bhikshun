<?php
/**
 * CPT Export & Import Logic (with wipe on import)
 */

$allowed_post_types = ['post', 'daily-spiritual-offe', 'testimonial', 'small-quote'];

if (!function_exists('export_cpt_posts')) {
    function export_cpt_posts(string $post_type) {
        global $allowed_post_types;

        if (!in_array($post_type, $allowed_post_types)) return null;

        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ];

        $posts = get_posts($args);
        $export_data = [];

        foreach ($posts as $post) {
            $post_data = [
                'post_title'        => $post->post_title,
                'post_content'      => $post->post_content,
                'post_excerpt'      => $post->post_excerpt,
                'post_status'       => $post->post_status,
                'post_date'         => $post->post_date,
                'post_date_gmt'     => $post->post_date_gmt,
                'post_modified'     => $post->post_modified,
                'post_modified_gmt' => $post->post_modified_gmt,
                'menu_order'        => $post->menu_order,
                'post_type'         => $post->post_type,
                'post_name'         => $post->post_name,
                'post_author'       => $post->post_author,
                'post_parent'       => $post->post_parent,
                'is_sticky'         => is_sticky($post->ID),
            ];

            // Meta
            $meta = get_post_meta($post->ID);
            $custom_fields = [];
            foreach ($meta as $key => $values) {
                $custom_fields[$key] = count($values) === 1 ? $values[0] : $values;
            }

            // Featured image
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $featured_image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;

            // Taxonomies
            $tax_data = [];
            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy, ['fields' => 'names']);
                $tax_data[$taxonomy] = $terms;
            }

            // Attached media
            $attached_files = [];
            $attachments = get_attached_media('', $post->ID);
            foreach ($attachments as $attachment) {
                $attached_files[] = wp_get_attachment_url($attachment->ID);
            }

            // Comments
            $comments = get_comments(['post_id' => $post->ID, 'status' => 'all']);
            $comment_data = [];
            foreach ($comments as $comment) {
                $comment_data[] = (array) $comment;
            }

            $post_data['meta'] = $custom_fields;
            $post_data['featured_image'] = $featured_image_url;
            $post_data['taxonomies'] = $tax_data;
            $post_data['attached_files'] = $attached_files;
            $post_data['comments'] = $comment_data;

            $export_data[] = $post_data;
        }

        return $export_data;
    }
}


if (!function_exists('export_all_categories')) {
    function export_all_categories() {
        global $allowed_post_types;

        $all_taxonomies = [];
        foreach ($allowed_post_types as $post_type) {
            $taxes = get_object_taxonomies($post_type);
            $all_taxonomies = array_merge($all_taxonomies, $taxes);
        }
        $all_taxonomies = array_unique($all_taxonomies);

        $exported_terms = [];

        foreach ($all_taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false, // export all terms, assigned or not
            ]);
            if (is_wp_error($terms)) continue;

            foreach ($terms as $term) {
                $term_meta = get_term_meta($term->term_id);
				// Convert meta array with single values to simple values
				$simple_meta = [];
				foreach ($term_meta as $meta_key => $meta_values) {
					$simple_meta[$meta_key] = count($meta_values) === 1 ? $meta_values[0] : $meta_values;
				}
				
				error_log($term_meta);

				$exported_terms[$taxonomy][] = [
					'term_id'       => $term->term_id,
					'name'          => $term->name,
					'slug'          => $term->slug,
					'term_group'    => $term->term_group,
					'term_taxonomy_id' => $term->term_taxonomy_id,
					'taxonomy'      => $term->taxonomy,
					'description'   => $term->description,
					'parent'        => $term->parent,
					'count'         => $term->count,
					'meta'          => $simple_meta,  // <--- include term meta here
				];
            }
        }

        return $exported_terms;
    }
}

if (!function_exists('import_all_categories_from_data')) {
    function import_all_categories_from_data(array $data) {
        if (empty($data) || !is_array($data)) return;

        foreach ($data as $taxonomy => $terms) {
            if (!taxonomy_exists($taxonomy)) continue;

            foreach ($terms as $term_data) {
                // Check if term exists by slug
                $existing = get_term_by('slug', $term_data['slug'], $taxonomy);

                if ($existing && !is_wp_error($existing)) {
                    // Update term if needed
                    wp_update_term($existing->term_id, $taxonomy, [
                        'name'        => $term_data['name'],
                        'description' => $term_data['description'],
                        'parent'      => $term_data['parent'],
                        'slug'        => $term_data['slug'],
                    ]);
                } else {
                    // Insert term
                    wp_insert_term($term_data['name'], $taxonomy, [
                        'slug'        => $term_data['slug'],
                        'description' => $term_data['description'],
                        'parent'      => $term_data['parent'],
                    ]);
                }
				
				if (!empty($term_data['meta']) && is_array($term_data['meta'])) {
					foreach ($term_data['meta'] as $meta_key => $meta_value) {
						update_term_meta($term_id, $meta_key, $meta_value);
					}
				}
            }
        }
    }
}



if (!function_exists('import_cpt_posts_from_data')) {
    function import_cpt_posts_from_data(array $data) {
        global $allowed_post_types;

        if (empty($data)) return;

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Clear existing posts
        $post_types_to_clear = array_unique(array_filter(array_column($data, 'post_type'), function ($type) use ($allowed_post_types) {
            return in_array($type, $allowed_post_types);
        }));

        foreach ($post_types_to_clear as $post_type) {
            $existing_posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);
            foreach ($existing_posts as $post_id) {
                wp_delete_post($post_id, true);
            }
        }

        $old_to_new_ids = [];

        // First pass: import posts
        foreach ($data as $post_data) {
            if (!isset($post_data['post_type'])) continue;

            $post_type = sanitize_text_field($post_data['post_type']);
            if (!in_array($post_type, $allowed_post_types)) continue;

            $postarr = [
                'post_title'        => wp_slash($post_data['post_title']),
                'post_content'      => isset($post_data['post_content']) ? wp_slash($post_data['post_content']) : '',
                'post_excerpt'      => isset($post_data['post_excerpt']) ? wp_slash($post_data['post_excerpt']) : '',
                'post_status'       => isset($post_data['post_status']) ? sanitize_text_field($post_data['post_status']) : 'publish',
                'post_type'         => $post_type,
                'menu_order'        => intval($post_data['menu_order'] ?? 0),
                'post_date'         => $post_data['post_date'] ?? current_time('mysql'),
                'post_date_gmt'     => $post_data['post_date_gmt'] ?? current_time('mysql', 1),
                'post_modified'     => $post_data['post_modified'] ?? current_time('mysql'),
                'post_modified_gmt' => $post_data['post_modified_gmt'] ?? current_time('mysql', 1),
                'post_name'         => sanitize_title($post_data['post_name'] ?? ''),
                'post_author'       => intval($post_data['post_author'] ?? get_current_user_id()),
                'post_parent'       => intval($post_data['post_parent'] ?? 0),
            ];

            $post_id = wp_insert_post($postarr, true);

            if (is_wp_error($post_id)) continue;

            if (!empty($post_data['is_sticky'])) {
                stick_post($post_id);
            }

            $old_to_new_ids[$post_data['post_name']] = $post_id;

            // Meta
            if (!empty($post_data['meta'])) {
                foreach ($post_data['meta'] as $key => $value) {
                    if (is_array($value)) {
                        delete_post_meta($post_id, $key);
                        foreach ($value as $v) {
                            add_post_meta($post_id, $v);
                        }
                    } else {
                        update_post_meta($post_id, $key, $value);
                    }
                }
            }

            // Featured image
            if (!empty($post_data['featured_image'])) {
                $image_url = esc_url_raw($post_data['featured_image']);
                $attachment_id = attachment_url_to_postid($image_url);

                if (!$attachment_id && $image_url) {
                    $attachment_id = media_sideload_image($image_url, $post_id, null, 'id');
                }

                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }

            // Taxonomies
			if (!empty($post_data['taxonomies'])) {
				foreach ($post_data['taxonomies'] as $taxonomy => $terms) {
					if (taxonomy_exists($taxonomy) && is_array($terms)) {
						$term_ids = [];
						foreach ($terms as $term_name) {
							$term_obj = term_exists($term_name, $taxonomy);
							if (!$term_obj || is_wp_error($term_obj)) {
								$term_obj = wp_insert_term($term_name, $taxonomy);
							}

							if (!is_wp_error($term_obj)) {
								$term_ids[] = is_array($term_obj) ? $term_obj['term_id'] : $term_obj;
							}
						}

						// Only assign if terms exist
						if (!empty($term_ids)) {
							wp_set_post_terms($post_id, $term_ids, $taxonomy);
						}
					}
				}
			}


            // Comments
            if (!empty($post_data['comments'])) {
                foreach ($post_data['comments'] as $comment) {
                    $comment['comment_post_ID'] = $post_id;
                    unset($comment['comment_ID']); // prevent conflict
                    wp_insert_comment(wp_slash($comment));
                }
            }
        }
    }
}

add_action('admin_init', function () {
    global $allowed_post_types;

    if (isset($_POST['abh_export_cpt']) && check_admin_referer('abh_export_cpt_nonce')) {
        if (!current_user_can('manage_options')) return;

        $post_type = sanitize_text_field($_POST['export_cpt']);
        if (!in_array($post_type, $allowed_post_types)) {
            wp_die('Invalid post type selected.');
        }
		
		// Export categories + posts
        $categories_data = export_all_categories();
        $posts_data = export_cpt_posts($post_type);

        $export_data = [
            'categories' => $categories_data,
            'posts' => $posts_data,
        ];

        if ($export_data === null) wp_die('Export failed.');

        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'cpt-posts-export.json';

        if (!empty($_POST['export_cpt_filename'])) {
            $name = sanitize_file_name(trim($_POST['export_cpt_filename']));
            if (strtolower(substr($name, -5)) !== '.json') {
                $name .= '.json';
            }
            if ($name !== '') {
                $filename = $name;
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    if (isset($_POST['abh_import_cpt']) && check_admin_referer('abh_import_cpt_nonce')) {
        if (!current_user_can('manage_options')) return;

        if (!empty($_FILES['cpt_import_file']['tmp_name'])) {
            $file = $_FILES['cpt_import_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (!is_array($data)) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in CPT import file.</p></div>';
                });
                return;
            }

            // Import categories first, then posts
            if (!empty($data['categories'])) {
                import_all_categories_from_data($data['categories']);
            }
            if (!empty($data['posts'])) {
                import_cpt_posts_from_data($data['posts']);
            }

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>CPT posts imported successfully. Previous posts were deleted and replaced.</p></div>';
            });
        }
    }
});
