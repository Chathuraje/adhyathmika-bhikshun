<?php
if (!function_exists('export_site_contents_to_json')) {
    function export_site_contents_to_json()
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

        return [
            'json_data' => json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'exported_at' => current_time('mysql')
        ];
    }
}

