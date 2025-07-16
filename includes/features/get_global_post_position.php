<?php
if (!function_exists('get_global_post_position')) {
    function get_global_post_position($atts)
    {
        global $post;

        if (!isset($post) || ! $post instanceof WP_Post) return '';

        $all_posts = get_posts([
            'posts_per_page' => -1,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        if (!is_array($all_posts) || empty($all_posts)) return '';

        $position = array_search($post->ID, $all_posts, true);

        return $position !== false ? $position + 1 : '';
    }
}
