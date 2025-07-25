<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'adhyathmika-bhikshun'));
}

$settings = [
    'ab_auto_generate_media_files_enabled' => get_option('ab_auto_generate_media_files_enabled', false),
];

// if not enabled, show a message
if (!$settings['ab_auto_generate_media_files_enabled']) {
    echo '<div class="notice notice-warning"><p>' . __('Auto-generate media files is disabled. Please enable it in the settings.', 'adhyathmika-bhikshun') . '</p></div>';
    return;
}

?>

<div class="wrap">
    <h2><?php _e('Generate Image', 'adhyathmika-bhikshun'); ?></h2>

    <form id="ab-generate-image-form">
        <?php wp_nonce_field('ab_generate_image_action', 'ab_generate_image_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ab-prompt"><?php _e('Image Prompt', 'adhyathmika-bhikshun'); ?></label>
                </th>
                <td>
                    <input type="text" id="ab-prompt" name="prompt" class="regular-text" required />
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Generate Image', 'adhyathmika-bhikshun'); ?></button>
        </p>

        <div id="ab-image-result"></div>
    </form>
</div>