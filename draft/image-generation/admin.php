<?php

/**
 * Admin functions for image generation settings.
 *
 * @package Adhyathmika_Bhikshun
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.

}


/**
 * Register settings for image generation.
 */

 add_action('init', function () {
    if (get_option('ab_auto_generate_media_files_enabled', true)) {
        require_once __DIR__ . '/image_generation.php';
    }
});
?>