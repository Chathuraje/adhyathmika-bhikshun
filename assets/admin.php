<?php

add_action('admin_enqueue_scripts', 'ab_enqueue_admin_assets');
function ab_enqueue_admin_assets($hook) {
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue settings CSS/JS globally or for specific pages as needed
    wp_enqueue_style(
        'ab-settings-css',
        $plugin_url . 'css/settings.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'ab-settings-js',
        $plugin_url . 'js/settings.js',
        [],
        '1.0.0',
        true
    );

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

    // Load audio note shortcode script conditionally
    if (get_option('ab_render_audio_note_enabled', true)) {
        // Enqueue audio note CSS
        wp_enqueue_style(
            'ab-render-audio-note-style',
            plugin_dir_url(__FILE__) . 'css/ab-render-audio-note.css',
            [], // Dependencies
            '1.0' // Version
        );

        // Enqueue audio note JS
        wp_enqueue_script(
            'ab-render-audio-note-script',
            plugin_dir_url(__FILE__) . 'js/ab-render-audio-note.js',
            [], // Dependencies
            '1.0', // Version
            true // Load in footer
        );
    }
}

?>