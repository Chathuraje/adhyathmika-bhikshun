<?php
function custom_reading_time_shortcode() {
    global $post;

    if ( ! isset($post) || ! ($post instanceof WP_Post) ) {
        return '<p><em>Reading time not available.</em></p>';
    }

    $content = $post->post_content;

    if ( empty($content) ) {
        return '<p><em>No content to calculate reading time.</em></p>';
    }

    $word_count = str_word_count(strip_tags($content));
    $reading_speed = 200;

    if ( $reading_speed <= 0 ) {
        return '<p><em>Invalid reading speed.</em></p>';
    }

    $reading_time = max(1, ceil($word_count / $reading_speed));
    return "<p><em>{$reading_time} minute" . ($reading_time > 1 ? 's' : '') . " read</em></p>";
}

?>