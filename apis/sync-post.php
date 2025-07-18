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
require_once __DIR__ . '/../includes/settings/posts-management/single_airtable_sync.php';

add_action('rest_api_init', function () {
    register_rest_route('ab-custom-apis/v2', '/sync_all_posts', [
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
            $results = airtable_sync_multiple_posts(array_values($valid_posts));

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
