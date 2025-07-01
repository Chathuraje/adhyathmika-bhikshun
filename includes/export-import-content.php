<?php
/**
 * Website Contents Export and Import Handler
 * Handles both exporting and importing ACF options for 'website-contents'
 */

if (!function_exists('export_acf_options')) {
    function export_acf_options() {
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

if (!function_exists('import_acf_options_from_data')) {
    function import_acf_options_from_data(array $data) {
        foreach ($data as $field_name => $value) {
            update_field($field_name, $value, 'option');
        }
    }
}

add_action('admin_init', function () {
    // Existing export/import handlers here (using above functions) ...
    if (isset($_POST['abh_export_acf']) && check_admin_referer('abh_export_acf_nonce')) {
        if (!current_user_can('manage_options')) return;

        $export_data = export_acf_options();

        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'contents_export.json';

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

    if (isset($_POST['abh_import_acf']) && check_admin_referer('abh_import_acf_nonce')) {
        if (!current_user_can('manage_options')) return;

        if (!empty($_FILES['acf_import_file']['tmp_name'])) {
            $file = $_FILES['acf_import_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (is_array($data)) {
                import_acf_options_from_data($data);
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success"><p>Content imported successfully!</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in import file.</p></div>';
                });
            }
        }
    }
});
