<?php
/**
 * Custom API endpoint to import posts.
 *
 * @package Adhyathmika_Bhikshun
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
} 

// Include necessary files
require_once __DIR__ . '/../includes/settings/posts-management/sync_all_posts_with_airtable.php';

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

            // Validate that each entry has post_id and post_uid
            $valid_posts = array_filter($payload, function ($post) {
                return isset($post['post_id'], $post['post_uid']);
            });

            if (empty($valid_posts)) {
                return new WP_REST_Response([
                    'error'    => 'No valid post_id/post_uid pairs in payload',
                    'received' => $payload,
                ], 422);
            }

            // Call batch sync

            
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
 * Utility: Sync all posts with API [WordPress REST API]
 */
function sync_all_posts_with_api(array $posts) {
    $results = [];

    foreach ($posts as $index => $post_data) {
        $post_id = isset($post_data['post_id']) ? intval($post_data['post_id']) : 0;
        $post_uid = isset($post_data['post_uid']) ? sanitize_text_field($post_data['post_uid']) : '';

        if (!$post_id || empty($post_uid)) {
            $results[] = [
                'airtable_id' => $post_data['airtable_id'] ?? null,
                'post_uid' => $post_uid,
                'post_title' => $post_data['title'] ?? '',
                'post_id' => $post_id,
                'status' => 'error',
                'message' => 'Missing or invalid post ID or UID at index ' . $index,
            ];
            continue;
        }

        $headers = getallheaders();
        $post_type = $headers['X-Post-Type'] ?? null;

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