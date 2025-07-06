<?php
// Imports Site Contents values into 'acf-options'

if (!function_exists('import_site_contents')) {
    function import_site_contents(array $data)
    {
        foreach ($data as $field_name => $value) {
            // Log field being updated for debugging (remove in production)
            error_log("Importing field: $field_name");

            // Update field, return success or failure for better error handling
            $updated = update_field($field_name, $value, 'option');
            if (!$updated) {
                error_log("Failed to update field: $field_name");
            }
        }
    }
}
