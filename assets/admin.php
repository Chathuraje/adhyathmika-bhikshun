<?php
// Enqueue plugin CSS and JS for Admin Management Pages
add_action('admin_enqueue_scripts', 'ab_enqueue_admin_assets');
function ab_enqueue_admin_assets($hook) {
    if (($hook === 'adhyathmika-bhikshun_page_abh-features-management') || ($hook === 'adhyathmika-bhikshun_page_abh-post-management')) {
        // Load custom admin CSS
        wp_enqueue_style('ab-features-management-page-style', plugin_dir_url(__FILE__) . 'css/ab-management-pages.css');

        // Load custom admin JS
        wp_enqueue_script('ab-features-management-page-script', plugin_dir_url(__FILE__) . 'js/ab-management-pages.js', [], null, true);
    }
}

// Enqueue plugin CSS and JS for Audio Note Shortcode
add_action('wp_enqueue_scripts', 'ab_render_audio_note_sc_assets');
function ab_render_audio_note_sc_assets() {
    wp_enqueue_style('ab-render-audio-note-sc-style', plugin_dir_url(__FILE__) . 'css/ab-render-audio-note-sc.css');
    wp_enqueue_script('ab-render-audio-note-sc-script', plugin_dir_url(__FILE__) . 'js/ab-render-audio-note-sc.js', [], false, true);
}

// Enqueue plugin CSS and JS for Language Switcher Shortcode
add_action('wp_enqueue_scripts', 'ab_language_switcher_sc_assets');
function ab_language_switcher_sc_assets() {
    wp_enqueue_style('ab-language-switcher-sc-style', plugin_dir_url(__FILE__) . 'css/ab-language-switcher-sc.css');
    wp_enqueue_script('ab-language-switcher-sc-script', plugin_dir_url(__FILE__) . 'js/ab-language-switcher-sc.js', [], false, true);
}
?>
