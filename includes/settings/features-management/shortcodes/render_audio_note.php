<?php
/**
 * Renders the audio note shortcode.
 *
 * This function generates the HTML structure for an audio note player
 * that includes an audio element and a play button. The audio element
 * is preloaded and includes a source placeholder for an MP3 file.
 *
 * @return string The HTML content for the audio note player.
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Check if the function already exists to avoid redeclaration conflicts.
if ( ! function_exists( 'ab_render_audio_note_sc' ) ) {
    function ab_render_audio_note_sc() {
        ob_start();
        ?>
        <div id="ab-audio-note">
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