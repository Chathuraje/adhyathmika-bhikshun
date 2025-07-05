<?php
// Imports Site Contents values into 'acf-options'

if (!function_exists('import_site_contents')) {
    function import_site_contents(array $data)
    {
        foreach ($data as $field_name => $value) {
            update_field($field_name, $value, 'option');
        }
    }
}
