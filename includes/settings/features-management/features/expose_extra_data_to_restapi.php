<?php
// Expose raw post content in REST API
function ab_expose_raw_post_content() {
    register_rest_field('post', 'content_raw', [
        'get_callback' => function ($post) {
            return get_post_field('post_content', $post['id']);
        },
        'schema' => null,
    ]);
}

// Expose Rank Math focus keyword in REST API
function ab_expose_focus_keyword() {
    register_post_meta('post', 'rank_math_focus_keyword', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}
