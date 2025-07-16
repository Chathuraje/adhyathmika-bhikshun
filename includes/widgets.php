<?php
// Store notes globally
global $ab_dashboard_notes;
$ab_dashboard_notes = [];

function ab_add_dashboard_note($note_html) {
    global $ab_dashboard_notes;
    $ab_dashboard_notes[] = $note_html;
}

// Setup dashboard widget
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget(
        'ab_feature_status_widget',
        'Adyathmika Bhikshun - Plugin Feature Status',
        function () {
            global $ab_dashboard_notes;

            if (empty($ab_dashboard_notes)) {
                echo '<p>No feature summaries available.</p>';
            } else {
                foreach ($ab_dashboard_notes as $note) {
                    echo "<p>{$note}</p>";
                }
            }
        }
    );
});

if (function_exists('ab_add_dashboard_note')) {
    ab_add_dashboard_note('📌 <strong>Post Order Shortcode</strong>: ' . (get_option('ab_post_order_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Displays the post’s position in chronological order.</small>');

    ab_add_dashboard_note('🌐 <strong>Language Switcher</strong>: ' . (get_option('ab_language_switch_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Adds a shortcode to toggle language between English and Sinhala.</small>');

    ab_add_dashboard_note('📖 <strong>Reading Time</strong>: ' . (get_option('ab_reading_time_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Shows estimated reading time based on post content length.</small>');

    ab_add_dashboard_note('🖼️ <strong>Auto Image ALT Text</strong>: ' . (get_option('ab_image_alt_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Automatically generates ALT tags for uploaded images.</small>');

    ab_add_dashboard_note('🔗 <strong>Cross-Site Link in Admin Bar</strong>: ' . (get_option('ab_cross_site_link_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Shows dynamic .org / .lk links in the WordPress admin bar.</small>');

    ab_add_dashboard_note('🎧 <strong>Language Audio Note</strong>: ' . (get_option('ab_language_audio_note_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Plays Sinhala or English audio based on domain.</small>');

    ab_add_dashboard_note('🚀 <strong>CDN URL Rewrite</strong>: ' . (get_option('ab_use_cdn_urls_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Rewrites media URLs to load from your CDN domain.</small>');

    ab_add_dashboard_note('🧩 <strong>REST API Extras</strong>: ' . (get_option('ab_rest_api_extras_enabled') ? '✅ Enabled' : '❌ Not Enabled') . '<br><small>Adds <code>content_raw</code> and <code>rank_math_focus_keyword</code> to REST API.</small>');
}
?>
