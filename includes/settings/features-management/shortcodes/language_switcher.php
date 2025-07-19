<?php
/**
 * Registers a shortcode [ab_language_switcher] to display a language switcher.
 *
 * The shortcode outputs a language toggle HTML structure with links to 
 * English and Sinhala versions of the website.
 *
 * Functions:
 * - ab_language_switcher_sc(): Generates the HTML for the language switcher.
 * - ab_language_switcher_sc_assets(): Enqueues the necessary CSS and JS files 
 *   for the language switcher functionality.
 *
 * Shortcode:
 * - [ab_language_switcher]: Use this shortcode to display the language switcher 
 *   on any page or post.
 *
 * Enqueued Assets:
 * - CSS: ab_language_switcher_sc.css (located in the 'css' directory relative to this file)
 * - JS: ab_language_switcher_sc.js (located in the 'js' directory relative to this file)
 *
 * Hooks:
 * - add_action('wp_enqueue_scripts', 'ab_language_switcher_sc_assets'): Ensures 
 *   the CSS and JS files are loaded on the frontend.
 */

 // Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

if ( ! function_exists( 'ab_language_switcher_sc' ) ) {
    // Register shortcode [ab_language_switch]
    function ab_language_switcher_sc() {
        ob_start();
        ?>
        <!-- Language Switcher HTML -->
        <div class="lang-toggle-wrap">
            <div class="lang-toggle">
                <a id="lang-en" class="lang-option" href="https://adhyathmikabhikshun.org">English</a>
                <a id="lang-si" class="lang-option" href="https://adhyathmikabhikshun.lk">සිංහල</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>