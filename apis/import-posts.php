<?php
/**
 * Custom API endpoint to import posts.
 *
 * @package Adhyathmika_Bhikshun
 */

require_once __DIR__ . '/../includes/post-managements/export-import/import_single_post_to_json.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
} 

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
    $data = $request->get_json_params();

    if (!is_array($data)) {
        return new WP_REST_Response(['error' => 'Invalid JSON array'], 400);
    }

    try {
        
        if (isset($data[0]) && is_array($data[0])) {
            import_all_posts_from_data($data);
        } else {
            import_custom_posts_from_data($data);
        }


        return new WP_REST_Response([
            'message' => 'Posts imported successfully',
            'count'   => count($data)
        ], 200);
    } catch (Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}


?>