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
?>

