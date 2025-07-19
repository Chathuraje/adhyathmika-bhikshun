<?php
// Enqueue plugin CSS and JS for Admin Features Management Page
add_action('admin_enqueue_scripts', 'ab_enqueue_admin_assets');
function ab_enqueue_admin_assets($hook) {
    if ($hook === 'adhyathmika-bhikshun_page_abh-features-management') {
        // Load custom admin CSS
        wp_enqueue_style('ab-features-management-page-style', plugin_dir_url(__FILE__) . 'css/ab-features-management-page.css');

        // Load custom admin JS
        wp_enqueue_script('ab-features-management-page-script', plugin_dir_url(__FILE__) . 'js/ab-features-management-page.js', [], null, true);
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





// // Load progress bar script conditionally
    // if (get_option('ab_import_posts_to_site_enabled', true)) {
    //     $screen = get_current_screen();
    //     if (in_array($screen->post_type, allowed_post_types_for_import_button(), true)) {
    //         wp_enqueue_script(
    //             'ab-import-progress-bar',
    //             $plugin_url . 'js/import-progress.js',
    //             ['jquery'],
    //             '1.0.0',
    //             true
    //         );

    //         wp_localize_script('ab-import-progress-bar', 'abImport', [
    //             'postType' => $screen->post_type,
    //             'postTypeCapitalized' => ucfirst($screen->post_type),
    //             'importUrl' => esc_url(wp_nonce_url(
    //                 admin_url('admin-ajax.php?action=import_all_custom_posts&type=' . $screen->post_type),
    //                 'import_all_custom_posts'
    //             )),
    //         ]);
    //     }
    // }
?>
