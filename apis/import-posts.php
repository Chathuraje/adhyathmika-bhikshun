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
require_once __DIR__ . '/../includes/settings/posts-management/import_posts_to_site.php';

add_action('rest_api_init', function () {
    register_rest_route('ab-custom-apis/v2', '/import-post', [
        'methods'             => 'POST',
        'callback'            => 'handle_import_custom_posts_endpoint',
        'permission_callback' => 'check_basic_auth_for_import',
    ]);
});

function check_basic_auth_for_import() {
    // Only allow logged-in users via Basic Auth
    return is_user_logged_in() && current_user_can('manage_options');
}

function handle_import_custom_posts_endpoint(WP_REST_Request $request)
{
    $data = json_decode($request->get_body(), true);
	
	if (!is_array($data)) {
        return new WP_REST_Response([
            'error' => 'Payload must be a JSON array',
            'received' => $data
        ], 400);
    }

    try {
        
        $results = import_all_posts_from_data($data);
        
        return new WP_REST_Response([
            'message' => 'Import complete',
            'results' => $results,
        ], 200);

        
    } catch (Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}


?>