<?php
/**
 * Features:
 * - Adds a "Sync with Airtable" link to the post row actions in the WordPress admin.
 * - Sends post data to Airtable via a webhook when the sync action is triggered.
 * - Automatically syncs posts with Airtable when they are saved with specific statuses (publish, future, private).
 * 
 * Security:
 * - Prevents direct access to the file.
 * - Verifies nonces to prevent CSRF attacks.
 * - Checks user permissions to ensure only authorized users can sync posts.
 * 
 * Dependencies:
 * - Requires the `send_single_post_sync_request` function to handle the actual data sync with Airtable.
 * - Uses the `Admin_Notices` class for displaying admin notices.
 * 
 * Hooks:
 * - `admin_head-edit.php`: Adds the "Sync with Airtable" link to post row actions.
 * - `admin_post_ab_sync_single_post_with_airtable`: Handles the GET request for syncing a single post with Airtable via admin-post.php.
 * - `save_post_post`: Automatically syncs posts with Airtable on save for specific post statuses.
 * 
 * Usage:
 * - Enable the plugin and ensure the required dependencies are available.
 * - Use the "Sync with Airtable" link in the post row actions to manually sync a post.
 * - Save or update a post with a supported status to trigger automatic sync.
 */

// Prevent direct access to this file for security reasons.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/requests/send_single_post_sync_request.php';

// Get A/B testing flag (usage depends on your logic)
$is_testing_enabled = get_option('ab_testing_enabled', true);

/**
 * Add "Sync with Airtable" link in post row actions on the Posts list page.
 */
add_action('admin_head-edit.php', function () {
    // Get the current admin screen object.
    $screen = get_current_screen();
    if (!in_array($screen->post_type, allowed_post_types_for_import_button(), true)) return;

    // Create a URL for the AJAX action with a nonce for security.
    $url = wp_nonce_url(admin_url('admin-post.php?action=ab_sync_single_post_with_airtable&type=' . $screen->post_type), 'ab_sync_single_post_with_airtable_action');
    
    // Append the "Sync with Airtable" link to each post row action.
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $(".row-actions .edit a").each(function() {
                // Extract post ID from the edit link URL.
                const postId = new URL($(this).attr("href")).searchParams.get("post");
                if (postId) {
                    // Construct sync URL pointing to admin-post.php with nonce and action.
                    const syncUrl = "' . esc_url($url) . '&post_id=" + postId;
                    // Append the "Sync with Airtable" link after the Edit link.
                    $(this).closest(".row-actions").append(\' | <a href="\' + syncUrl + \'">Sync with Airtable</a>\');
                }
            });
        });
    </script>';
});

/**
 * Handle the sync request triggered via admin-post.php (GET request).
 *
 * Note:
 * - Because the link points to admin-post.php, we hook into 'admin_post_'.
 * - Verifies nonce and user capabilities.
 * - Redirects back to post list with an admin notice on success or failure.
 */
add_action('admin_post_ab_sync_single_post_with_airtable', function () {
    // Verify nonce for security.
    check_admin_referer('ab_sync_single_post_with_airtable_action');

    // Sanitize and get post ID from GET params.
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    // Get the post type
    $post_type = get_post_type($post_id);
    if (!$post_type || !in_array($post_type, allowed_post_types_for_import_button(), true)) {
        Admin_Notices::redirect_with_notice('❌ Invalid post type for sync.', 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }
    
    // Check user permission.
    if (!current_user_can('edit_posts')) {
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync posts.', 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }

    // Check if user can edit this specific post.
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        Admin_Notices::redirect_with_notice('❌ You are not authorized to sync this post.', 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }

    $post_uid = get_post_uid_or_redirect($post_type, $post_id);
    if (!$post_uid) {
        Admin_Notices::redirect_with_notice('❌ Post UID not found.', 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }

    // Send the sync request.
    $response = send_single_post_sync_request($post_id, $post_uid);
    if (is_wp_error($response)) {
        Admin_Notices::redirect_with_notice('❌ ' . $response->get_error_message(), 'error', admin_url('edit.php?post_type=' . $post_type));
        exit;
    }

    // Check HTTP response code from Airtable.
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code >= 200 && $code < 300) {
        Admin_Notices::redirect_with_notice('✅ Post synced successfully with Airtable!', 'success', admin_url('edit.php?post_type=' . $post_type));
    } else {
        Admin_Notices::redirect_with_notice("❌ HTTP Error ({$code}): {$body}", 'error', admin_url('edit.php?post_type=' . $post_type));
    }

    exit;
});


/**
 * Automatically sync post on save for statuses: publish, future, private.
 *
 * This runs during post save and skips autosave, ajax, and revisions.
 */
add_action('save_post ', function ($post_id) {
    // Bail early if autosave, ajax, or revision.
    if (
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        (defined('DOING_AJAX') && DOING_AJAX) ||
        wp_is_post_revision($post_id)
    ) {
        return;
    }

    // Check if the post is of a type that we want to sync.
    $post_type = get_post_type($post_id);
    if (!$post_type || !in_array($post_type, allowed_post_types_for_import_button(), true)) {
        return;
    }

    // Only sync for these statuses.
    $status = get_post_status($post_id);
    if (!in_array($status, ['publish', 'future', 'private'], true)) {
        return;
    }

    // Get post_uid custom field.
    $post_uid = get_field('post_uid', $post_id);
    if (!$post_uid) {
        return;
    }

    // Fire the sync request. We don't handle the response here.
    send_single_post_sync_request($post_id, $post_uid);
    
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