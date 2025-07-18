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

function sync_all_posts(array $posts_data) {
    $results = [
        'total' => count($posts_data),
        'success' => 0,
        'failures' => [],
    ];

    foreach ($posts_data as $index => $post) {
        if (!isset($post['post_id'], $post['post_uid'])) {
            $results['failures'][] = [
                'index' => $index,
                'error' => 'Missing post_id or post_uid'
            ];
            continue;
        }

        $post_id = (int)$post['post_id'];
        $post_uid = sanitize_text_field($post['post_uid']);

        $response = airtable_sync_send($post_id, $post_uid);

        if (is_wp_error($response)) {
            $results['failures'][] = [
                'post_id' => $post_id,
                'error' => $response->get_error_message(),
            ];
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code >= 200 && $code < 300) {
                $results['success']++;
            } else {
                $body = wp_remote_retrieve_body($response);
                $results['failures'][] = [
                    'post_id' => $post_id,
                    'error_code' => $code,
                    'error_body' => $body,
                ];
            }
        }
    }

    return $results;
}

// Example: Add a REST API endpoint for n8n to call with batch posts JSON payload
add_action('rest_api_init', function () {
    register_rest_route('ab-custom-apis/v2', '/sync_all_posts', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            $data = json_decode($request->get_body(), true);

            if (!is_array($data)) {
                return new WP_REST_Response([
                    'error' => 'Payload must be a JSON array of posts data',
                    'received' => $data,
                ], 400);
            }

            $headers = getallheaders();
            $post_type = $headers['X-Post-Type'] ?? null;

            $results = sync_all_posts($data);

            return new WP_REST_Response([
                'message' => 'Batch sync complete',
                'results' => $results,
            ], 200);
        },
        'permission_callback' => function () {
            // Adjust permission as needed (e.g., API key, nonce, JWT)
            return current_user_can('manage_options');
        },
    ]);
});