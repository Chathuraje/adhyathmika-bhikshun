<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// require_once __DIR__ . '/single_airtable_sync.php';
require_once __DIR__ . '/../../../tools/encode.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$IMPORT_ALL_WEBHOOK = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook/import_posts_to_wp';
$SECRET_KEY = JWT_SECRET_KEY;

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


if (!function_exists('import_single_post_from_data')) {
    function import_single_post_from_data(array $post)
    {
        // global $allowed_post_types;

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // if (!in_array($post['post_type'], $allowed_post_types)) return;

        $result = [
            'post_title' => $post['post_title'] ?? '',
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
                    'status' => 'skipped',
                    'post_id' => $existing[0],
                    'reason' => 'Duplicate post_uid'
                ];
            }
        }

        // Check for existing post by slug
        if (!empty($post['post_name'])) {
            $existing = get_page_by_path($post['post_name'], OBJECT, $post['post_type']);
            if (!empty($existing)) {
                return [
                    'post_title' => $post['post_title'],
                    'status' => 'skipped',
                    'post_id' => $existing->ID,
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

        return $result;
        // airtable_sync_send($post_id, $post['meta']['post_uid']); 
    }
}


if (!function_exists('import_all_posts_from_data')) {
    function import_all_posts_from_data(array $posts) {
        wp_suspend_cache_invalidation(true);
        $results = [];

        foreach ($posts as $index => $post) {
            try {
                $result = import_single_post_from_data($post);
                $result['index'] = $index;
                $results[] = $result;
            } catch (Exception $e) {
                $result = [
                    'status' => 'failed',
                    'error'  => $e->getMessage(),
                    'index'  => $index,
                ];
            }
        }
        wp_suspend_cache_invalidation(false);

        return $results;
    }
}



// Logic to handle "Import All" button in admin screens

// 🔧 Filter to specify which post types should show "Import All" button
function allowed_post_types_for_import_button() {
    return apply_filters('custom_allowed_post_types_for_import_all', ['post']);
}

// 1. Inject "Import All" button into post list screens
// add_action('admin_head', function () {
//     $screen = get_current_screen();
//     if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

//     $url = wp_nonce_url(admin_url('admin-ajax.php?action=import_all_custom_posts&type=' . $screen->post_type), 'import_all_custom_posts');

//     echo '<script type="text/javascript">
//         jQuery(document).ready(function($) {
//             var button = \'<a href="' . esc_url($url) . '" class="page-title-action">Import All ' . ucfirst($screen->post_type) . '</a>\';
//             $(".wrap .page-title-action").first().after(button);
//         });
//     </script>';
// });


// 2. Handle AJAX to trigger import
add_action('wp_ajax_import_all_custom_posts', function () use ($IMPORT_ALL_WEBHOOK, $SECRET_KEY) {
    check_admin_referer('import_all_custom_posts');

    if (!current_user_can('manage_options')) {
        wp_redirect(add_query_arg(['post_type' => $_GET['type'] ?? 'post', 'import_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $current_user = wp_get_current_user();
    $requested_by = sanitize_user($current_user->user_login);
    $post_type = sanitize_key($_GET['type'] ?? 'post');

    $payload = [
        'iat'       => time(),
        'exp'       => time() + 300,
        'user'      => $requested_by,
        'post_type' => $post_type,
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);

    if (!$jwt_token) {
        wp_redirect(add_query_arg(['post_type' => $post_type, 'import_status' => 'token_error'], admin_url('edit.php')));
        exit;
    }

    $url_with_query = add_query_arg([
        'requested_by' => $requested_by,
        'post_type'    => $post_type,
    ], $IMPORT_ALL_WEBHOOK);

    $response = wp_remote_get($url_with_query, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'post_type' => $post_type,
            'import_status' => 'error',
            'error_message' => urlencode($response->get_error_message())
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code >= 200 && $code < 300) {
        wp_redirect(add_query_arg([
            'post_type' => $post_type,
            'import_status' => 'success',
            'success_message' => urlencode($data['message'] ?? 'Imported successfully.'),
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'post_type' => $post_type,
            'import_status' => 'http_error',
            'error_code' => $code,
            'error_message' => urlencode($body)
        ], admin_url('edit.php')));
    }

    exit;
});

// 3. Show admin notice
add_action('admin_notices', function () {
    if (!isset($_GET['import_status'])) return;

    $status = $_GET['import_status'];
    $message = esc_html(urldecode($_GET['success_message'] ?? ''));
    $error = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . $message . '</p></div>';
            break;
        case 'unauthorized':
            echo '<div class="notice notice-error is-dismissible"><p>❌ You are not authorized to import posts.</p></div>';
            break;
        case 'token_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ JWT token creation failed.</p></div>';
            break;
        case 'error':
        case 'http_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ Import failed: ' . $error . '</p></div>';
            break;
    }
}); 



// 4. Add a progress bar popup
// This will show the import progress in a modal popup
add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    $post_type = esc_js($screen->post_type);
    $url = wp_nonce_url(admin_url('admin-ajax.php?action=import_all_custom_posts&type=' . $screen->post_type), 'import_all_custom_posts');

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add the "Import All" button
            var button = \'<a href="' . esc_url($url) . '" id="trigger-import" class="page-title-action">Import All ' . ucfirst($screen->post_type) . '</a>\';
            $(".wrap .page-title-action").first().after(button);

            // Inject the popup HTML
            $("body").append(`
                <div id="import-progress-modal" style="position:fixed; top:20%; left:50%; transform:translateX(-50%); width:400px; background:#fff; border:1px solid #ccc; z-index:9999; padding:20px; display:none; box-shadow:0 0 10px rgba(0,0,0,0.3); border-radius:8px;">
                    <h2 style="margin-top:0;">Importing Posts...</h2>
                    <div style="background:#e5e5e5; border-radius:4px; overflow:hidden; margin:15px 0;">
                        <div id="progress-bar" style="height:20px; width:0%; background:#0073aa; color:#fff; text-align:center; line-height:20px;">0%</div>
                    </div>
                    <p id="progress-status">Starting import...</p>
                    <button onclick="jQuery(\'#import-progress-modal\').hide();" style="float:right;" class="button">Close</button>
                    <div style="clear:both;"></div>
                </div>
            `);

            // When "Import All" button clicked, show the popup
            $("#trigger-import").on("click", function() {
                $("#import-progress-modal").show();
                setTimeout(function() {
                    pollProgress("' . $post_type . '");
                }, 2000); // Start polling after slight delay
            });

            // Polling function to update the progress bar
            window.pollProgress = function(postType) {
                const endpoint = `/wp-json/ab-custom-apis/v2/import-progress/` + postType;
                const bar = document.getElementById("progress-bar");
                const status = document.getElementById("progress-status");

                if (!bar || !status) return;

                const interval = setInterval(() => {
                    fetch(endpoint)
                        .then(r => r.json())
                        .then(data => {
                            let percent = Math.round(data.percent || 0);
                            bar.style.width = percent + "%";
                            bar.textContent = percent + "%";
                            status.textContent = "Imported " + (data.completed || 0) + " of " + (data.total || "?") + " posts.";

                            if (percent >= 100) {
                                clearInterval(interval);
                                status.textContent = "✅ Import Complete!";
                            }
                        })
                        .catch(e => {
                            status.textContent = "⚠️ Error polling progress.";
                            clearInterval(interval);
                        });
                }, 3000);
            };
        });
    </script>';
});
