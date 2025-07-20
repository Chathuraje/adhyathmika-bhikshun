<?php

add_action('admin_init', function () {
    // Handle export
    if (isset($_POST['export_site_contents']) && check_admin_referer('export_site_contents_nonce')) {
        if (!current_user_can('manage_options')) return;

        $export_data = export_site_contents();
        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Get the host from site URL
        $site_url = parse_url(home_url());
        $host = $site_url['host']; // e.g. adyathmikabhikshun.lk
        $host_parts = explode('.', $host);

        // Sanitize and join domain parts with underscore, e.g. adyathmikabhikshun_lk
        $filename_base = implode('_', $host_parts);

        // Add timestamp
        $timestamp = date('Y-m-d_H-i-s');

        $filename = $filename_base . '_' . $timestamp . '.json';

        if (!empty($_POST['export_filename'])) {
            $name = sanitize_file_name(trim($_POST['export_filename']));
            if (strtolower(substr($name, -5)) !== '.json') {
                $name .= '.json';
            }
            if ($name !== '') {
                $filename = $name;
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    // Handle import
    if (isset($_POST['import_site_contents']) && check_admin_referer('import_site_contents_nonce')) {

        // Check user permissions
        if (!current_user_can('manage_options')) return;

        // Check if file is uploaded
        if (!empty($_FILES['import_site_contents_file']['tmp_name'])) {
            $file = $_FILES['import_site_contents_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (is_array($data)) {
                import_site_contents($data);
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success"><p>Site contents imported successfully!</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in import file.</p></div>';
                });
            }
        }
    }
});

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


