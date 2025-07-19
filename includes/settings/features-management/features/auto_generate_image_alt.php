<?php
/**
 * Automatically generates and sets a clean ALT text for an image attachment if none exists.
 *
 * This function hooks into the metadata generation process for attachments and ensures
 * that the ALT text for an image is set based on the image's title. If an ALT text already
 * exists, it will not be overwritten.
 *
 * @param array $metadata The metadata for the attachment.
 * @param int $attachment_id The ID of the attachment being processed.
 * @return array The original metadata, unchanged.
 *
 * @uses get_post() Retrieves the post object for the attachment.
 * @uses get_the_title() Retrieves the title of the attachment.
 * @uses html_entity_decode() Decodes HTML entities in the title.
 * @uses get_post_meta() Checks if an ALT text already exists for the attachment.
 * @uses update_post_meta() Updates the ALT text for the attachment if none exists.
 */

function ab_auto_generate_image_alt_from_the_name($metadata, $attachment_id) {
    // Get the post object for the attachment
    $attachment = get_post($attachment_id);
    
    // Bail early if attachment is not valid
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return $metadata;
    }

    // Get the image title (usually filename or entered title)
    $raw_title = get_the_title($attachment_id);

    // Decode any HTML entities and clean up spacing/capitalization
    $clean_title = html_entity_decode($raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean_title = ucfirst(trim($clean_title));

    // Check if an ALT text already exists; if not, set it
    if (empty(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $clean_title);
    }

    // Return original metadata unchanged
    return $metadata;
}
