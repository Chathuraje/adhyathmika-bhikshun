<?php
/**
 * Airtable Sync Plugin
 * - Adds a "Sync with Airtable" button in the post list
 * - Automatically syncs posts on save
 * - Includes status handling, JWT auth, and A/B test flag
 */

require_once __DIR__ . '/../../../tools/encode.php';

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', '');
}

$is_testing_enabled = get_option('ab_testing_enabled', true);


/**
 * Utility: Generate JWT token for a post
 */
function airtable_sync_generate_jwt($post_id, $post_uid) {
    global $is_testing_enabled;

    $payload = [
        'iat' => time(),
        'exp' => time() + 300,
        'post_id' => $post_id,
        'post_uid' => $post_uid,
        'testing' => $is_testing_enabled ? 'true' : 'false',
    ];

    return jwt_encode($payload, JWT_SECRET_KEY);
}


/**
 * Utility: Export a single post to JSON
 */
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


/**
 * Utility: Send post data to Airtable
 */
/**
 * The function `airtable_sync_send` sends a POST request to a webhook URL with specific data and
 * headers for syncing with Airtable.
 * 
 * @param post_id The `post_id` parameter in the `airtable_sync_send` function is used to specify the
 * ID of the post that you want to synchronize with Airtable. This ID is typically a unique identifier
 * assigned to each post in WordPress.
 * @param post_uid The `post_uid` parameter in the `airtable_sync_send` function is used to uniquely
 * identify the post being synced with Airtable. It is passed as an argument to the function along with
 * the `post_id`. This unique identifier helps in ensuring that the correct post is synced and updated
 * in Air
 * 
 * @return The function `airtable_sync_send` is returning the response from the `wp_remote_post`
 * function, which is the result of sending a POST request to the specified ``
 * URL with the provided parameters and headers.
 */
function airtable_sync_send($post_id, $post_uid) {
    global $is_testing_enabled;

    $AIRTABLE_SYNC_WEBHOOK = "https://digibot365-n8n.kdlyj3.easypanel.host/webhook/sync_with_airtable";

    $jwt_token = airtable_sync_generate_jwt($post_id, $post_uid);
    if (!$jwt_token) return new WP_Error('jwt_error', 'JWT token generation failed.');

    $export = export_single_post_to_json($post_id, get_post_type($post_id));
    $response = wp_remote_post($AIRTABLE_SYNC_WEBHOOK, [
        'method'    => 'POST',
        'blocking'  => true,
        'headers'   => [
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'post_id'   => $post_id,
            'post_uid'  => $post_uid,
            'testing'   => $is_testing_enabled ? 'true' : 'false',
            'post_data' => $export['json_data'],
        ]),
    ]);

    return $response;
}

/**
 * 1. Add "Sync with Airtable" link in post list
 */
add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();
    if ($screen->post_type !== 'post') return;

    $nonce = wp_create_nonce('sync_with_airtable_action');
    $admin_url = esc_url(admin_url('admin-post.php'));

    echo <<<JS
<script>
jQuery(document).ready(function($) {
    $(".row-actions .edit a").each(function() {
        const postId = new URL($(this).attr("href")).searchParams.get("post");
        if (postId) {
            const syncUrl = "$admin_url?action=sync_with_airtable&post_id=" + postId + "&_wpnonce=$nonce";
            $(this).closest(".row-actions").append(' | <a href="' + syncUrl + '">Sync with Airtable</a>');
        }
    });
});
</script>
JS;
});

/**
 * 2. Handle manual sync request
 */
add_action('admin_post_sync_with_airtable', function () {
    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce   = $_GET['_wpnonce'] ?? '';

    if (!wp_verify_nonce($nonce, 'sync_with_airtable_action') || !$post_id || !current_user_can('edit_post', $post_id)) {
        wp_redirect(add_query_arg(['post_type' => 'post', 'sync_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'http_error',
            'error_code'     => 400,
            'error_message'  => urlencode('Post UID not found'),
        ], admin_url('edit.php')));
        exit;
    }

    $response = airtable_sync_send($post_id, $post_uid);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'error',
            'error_code'     => 0,
            'error_message'  => urlencode($response->get_error_message()),
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        $success = "Post synced successfully with Airtable!";
        wp_redirect(add_query_arg([
            'post_type'       => 'post',
            'sync_status'     => 'success',
            'success_message' => urlencode($success),
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'post_type'      => 'post',
            'sync_status'    => 'http_error',
            'error_code'     => $code,
            'error_message'  => urlencode($body),
        ], admin_url('edit.php')));
    }

    exit;
});

/**
 * 3. Display admin notices
 */
add_action('admin_notices', function () {
    if (empty($_GET['sync_status'])) return;

    $status = $_GET['sync_status'];
    $error_code = intval($_GET['error_code'] ?? 0);
    $error_message = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            $msg = esc_html(urldecode($_GET['success_message'] ?? '✅ Sync completed successfully!'));
            echo "<div class='notice notice-success is-dismissible'><p>$msg</p></div>";
            break;
        case 'unauthorized':
            echo "<div class='notice notice-error is-dismissible'><p>❌ You are not authorized to sync this post.</p></div>";
            break;
        case 'token_error':
            echo "<div class='notice notice-error is-dismissible'><p>❌ JWT token generation failed.</p></div>";
            break;
        case 'error':
        case 'http_error':
            echo "<div class='notice notice-error is-dismissible'><p>❌ Sync failed with code: $error_code</p><p>$error_message</p></div>";
            break;
    }
});

/**
 * 4. Automatically sync post on save
 */
add_action('save_post_post', function ($post_id, $post, $update) {
    if (
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        defined('DOING_AJAX') && DOING_AJAX ||
        wp_is_post_revision($post_id)
    ) {
        return;
    }

    $post_status = get_post_status($post_id);
    if (!in_array($post_status, ['publish', 'future', 'private'])) return;

    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) return;

    airtable_sync_send($post_id, $post_uid);
}, 10, 3);

// /**
//  * 5. Sync comments on various actions
//  */

// add_action('wp_insert_comment', function($comment_id, $comment_object) {
//     if ($comment_object->comment_approved != 1) return;

//     $post_id = $comment_object->comment_post_ID;
//     $post_uid = get_field('post_uid', $post_id);
//     if ($post_uid) {
//         airtable_sync_send($post_id, $post_uid);
//     }
// }, 10, 2);


// add_action('comment_unapproved_to_approved', function($comment) {
//     $post_id = $comment->comment_post_ID;
//     $post_uid = get_field('post_uid', $post_id);
//     if ($post_uid) {
//         airtable_sync_send($post_id, $post_uid);
//     }
// });


// add_action('edit_comment', function($comment_id) {
//     $comment = get_comment($comment_id);
//     if ($comment && $comment->comment_approved == 1) {
//         $post_id = $comment->comment_post_ID;
//         $post_uid = get_field('post_uid', $post_id);
//         if ($post_uid) {
//             airtable_sync_send($post_id, $post_uid);
//         }
//     }
// });


// add_action('delete_comment', function($comment_id) {
//     $comment = get_comment($comment_id);
//     if ($comment) {
//         $post_id = $comment->comment_post_ID;
//         $post_uid = get_field('post_uid', $post_id);
//         if ($post_uid) {
//             airtable_sync_send($post_id, $post_uid);
//         }
//     }
// });

// add_action('wp_set_comment_status', function($comment_id, $status) {
//     $comment = get_comment($comment_id);
//     if (!$comment) return;

//     if (in_array($status, ['approve', 'spam', 'trash'])) {
//         $post_id = $comment->comment_post_ID;
//         $post_uid = get_field('post_uid', $post_id);
//         if ($post_uid) {
//             airtable_sync_send($post_id, $post_uid);
//         }
//     }
// }, 10, 2);

// add_action('wpdiscuz_post_rating_added', function($post_id, $rating) {
//     $post_uid = get_field('post_uid', $post_id);
//     if ($post_uid) {
//         airtable_sync_send($post_id, $post_uid);
//     }
// }, 10, 2);

// add_action('wpdiscuz_comment_liked', function($comment_id, $user_id, $is_like) {
//     $comment = get_comment($comment_id);
//     if (!$comment) return;

//     $post_id = $comment->comment_post_ID;
//     $post_uid = get_field('post_uid', $post_id);
//     if ($post_uid) {
//         airtable_sync_send($post_id, $post_uid);
//     }
// }, 10, 3);


/**
 * Sync all posts with Airtable
 */

 add_action('wp_ajax_sync_all_with_airtable', function () use ($SECRET_KEY) {
    check_admin_referer('sync_all_with_airtable');

    if (!current_user_can('manage_options')) {
        wp_redirect(add_query_arg(['sync_status' => 'unauthorized'], admin_url('edit.php')));
        exit;
    }

    $current_user = wp_get_current_user();
    $requested_by = sanitize_user($current_user->user_login);

    $payload = [
        'iat'  => time(),
        'exp'  => time() + 300,
        'user' => $requested_by,
    ];

    $jwt_token = jwt_encode($payload, $SECRET_KEY);

    if (!$jwt_token) {
        wp_redirect(add_query_arg(['sync_status' => 'token_error'], admin_url('edit.php')));
        exit;
    }

    $url = 'https://digibot365-n8n.kdlyj3.easypanel.host/webhook-/sync_all_with_airtable';

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt_token,
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        wp_redirect(add_query_arg([
            'sync_status' => 'error',
            'error_message' => urlencode($response->get_error_message())
        ], admin_url('edit.php')));
        exit;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code >= 200 && $code < 300) {
        wp_redirect(add_query_arg([
            'sync_status' => 'success',
            'success_message' => urlencode($data['message'] ?? 'Sync successful.')
        ], admin_url('edit.php')));
    } else {
        wp_redirect(add_query_arg([
            'sync_status' => 'http_error',
            'error_code' => $code,
            'error_message' => urlencode($body)
        ], admin_url('edit.php')));
    }

    exit;
});


add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    $sync_url = wp_nonce_url(admin_url('admin-ajax.php?action=sync_all_with_airtable'), 'sync_all_with_airtable');

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var syncButton = \'<a href="' . esc_url($sync_url) . '" class="page-title-action">Sync All in Airtable</a>\';
            $(".wrap .page-title-action").first().after(syncButton).after(importButton);
        });
    </script>';
});


add_action('admin_notices', function () {
    if (!isset($_GET['sync_status'])) return;

    $status = $_GET['sync_status'];
    $message = esc_html(urldecode($_GET['success_message'] ?? ''));
    $error = esc_html(urldecode($_GET['error_message'] ?? ''));

    switch ($status) {
        case 'success':
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . $message . '</p></div>';
            break;
        case 'unauthorized':
            echo '<div class="notice notice-error is-dismissible"><p>❌ You are not authorized to sync with Airtable.</p></div>';
            break;
        case 'error':
        case 'http_error':
            echo '<div class="notice notice-error is-dismissible"><p>❌ Sync failed: ' . $error . '</p></div>';
            break;
    }
});


/**
 * Utility: Sync all posts with Airtable
 */
function airtable_sync_multiple_posts(array $posts) {
    $results = [];

    foreach ($posts as $index => $post_data) {
        $post_id = isset($post_data['post_id']) ? intval($post_data['post_id']) : 0;
        $post_uid = isset($post_data['post_uid']) ? sanitize_text_field($post_data['post_uid']) : '';

        if (!$post_id || empty($post_uid)) {
            $results[] = [
                'post_id' => $post_id,
                'status' => 'error',
                'message' => 'Missing or invalid post ID or UID at index ' . $index,
            ];
            continue;
        }

        // Attempt sync
        $response = airtable_sync_send($post_id, $post_uid);

        if (is_wp_error($response)) {
            $results[] = [
                'post_id' => $post_id,
                'status' => 'error',
                'message' => $response->get_error_message(),
            ];
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300) {
            $results[] = [
                'post_id' => $post_id,
                'status' => 'success',
                'message' => 'Post synced successfully',
            ];
        } else {
            $results[] = [
                'post_id' => $post_id,
                'status' => 'http_error',
                'message' => "HTTP $code: $body",
            ];
        }
    }

    return $results;
}
