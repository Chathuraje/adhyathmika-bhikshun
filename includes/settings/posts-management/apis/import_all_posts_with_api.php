<?php
/**
 * Custom API endpoint to import posts.
 *
 * @package Adhyathmika_Bhikshun
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
} 

require_once __DIR__ . '/../lib/import_single_post_from_json.php';

add_action('rest_api_init', function () {
    register_rest_route('ab-custom-apis/v2', '/import_all_posts_with_api', [
        'methods'             => 'POST',
        'callback'            => 'handle_import_all_posts_with_api_endpoint',
        'permission_callback' => 'check_basic_auth_for_import',
    ]);
});

function check_basic_auth_for_import() {
    // Only allow logged-in users via Basic Auth
    return is_user_logged_in() && current_user_can('manage_options');
}

function handle_import_all_posts_with_api_endpoint(WP_REST_Request $request)
{
    $headers = getallheaders();
    $batch_id = $headers['X-Batch-Id'] ?? null;
    $batch_total = $headers['X-Batch-Total'] ?? null;
    $post_type = $headers['X-Post-Type'] ?? null;

    $data = json_decode($request->get_body(), true);
	
	if (!is_array($data)) {
        return new WP_REST_Response([
            'error' => 'Payload must be a JSON array',
            'received' => $data
        ], 400);
    }

    try {
        
        $results = import_all_posts_with_api($data);

        // Save progress transient: key by a job ID or user session, here simple example
        $progress_key = sanitize_key($post_type . '_import_progress');
        set_transient($progress_key, [
            'batch_id' => (int)$batch_id + 1,
            'batch_total' => (int)$batch_total,
            'completed' => true,
            'timestamp' => time(),
        ], 60 * 30); // 30 minutes expiration
        
        return new WP_REST_Response([
            'message' => 'Import complete',
            'results' => $results,
            'batch_id' => $batch_id,
            'batch_total' => $batch_total,
        ], 200);

        
    } catch (Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}

if (!function_exists('import_all_posts_with_api')) {
    function import_all_posts_with_api(array $posts) {
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






/**
 * Register a REST API endpoint to check import progress.
 * This will return the current batch ID and total count.
 */
// add_action('rest_api_init', function () {
//     register_rest_route('ab-custom-apis/v2', '/import-progress', [
//         'methods' => 'GET',
//         'callback' => function (WP_REST_Request $request) {
//             $post_type = sanitize_key($request['post_type']);
//             $progress_key = $post_type . '_import_progress';

//             $progress = get_transient($progress_key);

//             if (!$progress) {
//                 return new WP_REST_Response([
//                     'status' => 'no_import',
//                     'percent' => 0,
//                 ], 200);
//             }

//             $percent = 0;
//             if ($progress['batch_total'] > 0) {
//                 $percent = ($progress['batch_id'] / $progress['batch_total']) * 100;
//             }

//             return new WP_REST_Response([
//                 'status' => 'in_progress',
//                 'batch_id' => $progress['batch_id'],
//                 'batch_total' => $progress['batch_total'],
//                 'percent' => round($percent, 2),
//                 'last_updated' => $progress['timestamp'],
//             ], 200);
//         },
//         'permission_callback' => function () {
//             return is_user_logged_in(); // adjust permissions as needed
//         }
//     ]);
// });
?>

