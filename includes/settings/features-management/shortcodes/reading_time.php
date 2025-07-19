<?php

/**
 * Shortcode: [ab_reading_time]
 * 
 * Displays the estimated reading time for the current post content based on the word count
 * and a specified reading speed (words per minute).
 * 
 * Attributes:
 * - `wpm` (int): Words per minute for reading speed. Default is 200.
 * - `minute` (string): Singular form of "minute". Default is "minute".
 * - `minutes` (string): Plural form of "minutes". Default is "minutes".
 * - `label_suffix` (string): Text suffix to display after the reading time. Default is "read".
 * - `zero_label` (string): Text to display if the reading time is less than 1 minute or no content is available. Default is "Less than a minute".
 * 
 * Example Usage:
 * [reading_time wpm="250" minute="min" minutes="mins" label_suffix="to read" zero_label="Quick read"]
 * 
 * Returns:
 * - A formatted string wrapped in a `<span>` element with the class `reading-time`.
 * - If no content or reading time is less than 1 minute, it displays the `zero_label` text.
 * 
 * Notes:
 * - This shortcode relies on the global `$post` object to retrieve the post content.
 * - HTML tags are stripped from the content before calculating the word count.
 * - The function ensures a minimum reading speed of 1 word per minute to avoid division by zero.
 * 
 * Prevents direct access to the file by checking if `ABSPATH` is defined.
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

if ( ! function_exists( 'ab_reading_time_sc' ) ) {
    function ab_reading_time_sc($atts) {
        global $post;

        // Shortcode attributes with default values
        $atts = shortcode_atts([
            'wpm'           => 200,                     // Words per minute
            'minute'        => 'minute',               // Singular form
            'minutes'       => 'minutes',              // Plural form
            'label_suffix'  => 'read',                 // Text suffix
            'zero_label'    => 'Less than a minute'    // Shown if no text or <1 minute
        ], $atts, 'reading_time');

        // If post is not available (edge case)
        if (empty($post) || !($post instanceof WP_Post)) {
            return '<span class="reading-time"><em>' . esc_html($atts['zero_label']) . ' ' . esc_html($atts['label_suffix']) . '</em></span>';
        }

        // Strip HTML tags and count words
        $content = strip_tags($post->post_content);
        $word_count = str_word_count($content);

        // Validate and sanitize reading speed
        $reading_speed = max(1, intval($atts['wpm'])); // Prevent divide by zero
        $reading_time = ceil($word_count / $reading_speed);

        // If no content or reading time rounds to 0
        if ($word_count === 0 || $reading_time === 0) {
            return '<span class="reading-time"><em>' . esc_html($atts['zero_label']) . ' ' . esc_html($atts['label_suffix']) . '</em></span>';
        }

        // Choose singular/plural label
        $minute_label = $reading_time === 1 ? esc_html($atts['minute']) : esc_html($atts['minutes']);
        $suffix = esc_html($atts['label_suffix']);

        // Return formatted reading time
        return "<span class='reading-time'><em>{$reading_time} {$minute_label} {$suffix}</em></span>";
    }
}
?>
