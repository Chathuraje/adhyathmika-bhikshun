<?php
/**
 * Custom API endpoint to import posts.
 *
 * @package Adhyathmika_Bhikshun
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
} 

add_action('rest_api_init', function () {
    register_rest_route('ab-custom-apis/v2', '/sync_all_posts_with_api', [
        'methods'  => 'POST',
        'callback' => function (WP_REST_Request $request) {
            $payload = json_decode($request->get_body(), true);

            if (!is_array($payload)) {
                return new WP_REST_Response([
                    'error'    => 'Payload must be a JSON array of post objects',
                    'received' => $payload,
                ], 400);
            }

            // Validate that each entry has post_uid
            $valid_posts = array_filter($payload, function ($post) {
                return isset($post['post_uid'], $post['post_type']) && !empty($post['post_uid']) && !empty($post['post_type']);
            });

            if (empty($valid_posts)) {
                return new WP_REST_Response([
                    'error'    => 'No valid posts found in the payload. Each post must have a post_uid and post_type.',
                    'received' => $payload,
                ], 422);
            }

            // Call batch sync function
            $results = sync_all_posts_with_api(array_values($valid_posts));

            return new WP_REST_Response([
                'message' => 'Batch sync completed',
                'count'   => count($results),
                'results' => $results,
            ], 200);
        },
        'permission_callback' => function () {
            // Adjust this as needed (you could check for an API token here instead)
            return current_user_can('manage_options');
        },
    ]);
});

/**
 * Utility: Get post ID by UID
 */
function get_post_id_by_uid($uid, $meta_key) {
    global $wpdb;

    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
        $meta_key,
        $uid
    ));

    return (int)$post_id;
}

/**
 * Utility: Sync all posts with API [WordPress REST API]
 */
function sync_all_posts_with_api(array $posts) {
    $results = [];

    foreach ($posts as $index => $post_data) {
        $post_uid = isset($post_data['post_uid']) ? sanitize_text_field($post_data['post_uid']) : '';
        
        if (!$post_uid) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['title'] ?? '',

                'status' => 'error',
                'message' => 'Missing or invalid post UID at index ' . $index,
            ];
            continue;
        }

        // Get the post type from the post data
        $post_type = isset($post_data['post_type']) ? sanitize_text_field($post_data['post_type']) : 'post';
        if (empty($post_type)) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['title'] ?? '',
                'status' => 'error',
                'message' => 'Post type is required for syncing.',
            ];
            continue;
        }

        // Get the meta key for post UID
        $post_uid_meta_key = get_post_uid_meta_key($post_type);
        if (!$post_uid_meta_key) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['title'] ?? '',
                'status' => 'error',
                'message' => 'Invalid post type or UID meta key not found for post type: ' . ($post_data['post_type'] ?? 'unknown'),
            ];
            continue;
        }

        // Get post ID from post UID
        $post_id = get_post_id_by_uid($post_uid, $post_uid_meta_key);
        if (!$post_id) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['title'] ?? '',
                'status' => 'error',
                'message' => 'Post not found for UID: ' . $post_uid,
            ];
            continue;
        }

        // Attempt sync
        $response = send_single_post_sync_request($post_id, $post_uid);

        if (is_wp_error($response)) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['post_title'] ?? '',
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
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['post_title'] ?? '',
                'post_id' => $post_id,
                'status' => 'success',
                'message' => 'Post synced successfully',
            ];
        } else {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['post_title'] ?? '',
                'post_id' => $post_id,
                'status' => 'http_error',
                'message' => "HTTP $code: $body",
            ];
        }
    }

    return $results;
}