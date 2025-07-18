<?php

/**
 * Auto-generate clean image alt text
 */
function auto_generate_clean_image_alt($metadata, $attachment_id) {
    $attachment = get_post($attachment_id);
    $raw_title = get_the_title($attachment_id);

    $clean_title = html_entity_decode($raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean_title = ucfirst(trim($clean_title));

    if (empty(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $clean_title);
    }

    return $metadata;
}

?>