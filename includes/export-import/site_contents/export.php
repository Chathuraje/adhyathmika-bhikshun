<?php
// Exports Site Contents from 'acf-options'

if (!function_exists('export_site_contents')) {
    function export_site_contents()
    {
        $export_data = [];

        if (function_exists('acf_get_field_groups')) {
            $groups = acf_get_field_groups(['options_page' => 'website-contents']);
            foreach ($groups as $group) {
                $fields = acf_get_fields($group['key']);
                if (!$fields) continue;
                foreach ($fields as $field) {
                    $value = get_field($field['name'], 'option');
                    $export_data[$field['name']] = $value;
                }
            }
        }

        return $export_data;
    }
}
