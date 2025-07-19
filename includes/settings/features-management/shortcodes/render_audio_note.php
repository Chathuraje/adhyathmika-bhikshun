<?php
/**
 * Renders a shortcode for an audio note player.
 *
 * This function generates the HTML structure for an audio note player
 * and allows users to embed it using a shortcode. The audio note player
 * includes a play button and an audio element with a dynamic source URL.
 *
 * Shortcode Attributes:
 * - `url` (string): The URL of the audio file to be played. Defaults to an empty string.
 *
 * Example Usage:
 * [ab_render_audio_note url="https://example.com/audio.mp3"]
 *
 * @param array $atts Shortcode attributes.
 * @return string The HTML output for the audio note player.
 */

/**
 * Enqueues the necessary CSS and JavaScript assets for the audio note player.
 *
 * This function ensures that the required styles and scripts are loaded
 * on the frontend to enable the functionality and styling of the audio note player.
 *
 * Enqueued Assets:
 * - CSS: ab-render_audio_note-sc-style (located in the `css` directory of the plugin)
 * - JS: ab-render-audio-note-sc-script (located in the `js` directory of the plugin)
 *
 * @return void
 */


// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Check if the function already exists to avoid redeclaration conflicts.
if ( ! function_exists( 'ab_render_audio_note_sc' ) ) {
    function ab_render_audio_note_sc($atts) {
        // Extract shortcode attributes with a default fallback
        $atts = shortcode_atts([
            'url' => '' // Default is empty; fallback handled in JS
        ], $atts);
    
        ob_start();
        ?>
        <div id="ab-audio-note" data-url="<?php echo esc_url($atts['url']); ?>">
            <audio id="ab-audio" preload="auto">
                <source id="ab-audio-source" src="" type="audio/mpeg">
            </audio>
            <div id="ab-audio-btn">▶️</div>
        </div>
        <?php
        return ob_get_clean();
    }
}

?>