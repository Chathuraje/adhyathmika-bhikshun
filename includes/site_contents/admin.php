<?php
require_once __DIR__ . '/includes/site_contents/export.php';
require_once __DIR__ . '/includes/site_contents/import.php';

add_action('admin_init', function () {
    // Handle export
    if (isset($_POST['export_site_contents']) && check_admin_referer('export_site_contents_nonce')) {
        if (!current_user_can('manage_options')) return;

        $export_data = export_site_contents();
        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'site-contents-export.json';

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
        if (!current_user_can('manage_options')) return;

        if (!empty($_FILES['site_contents_file']['tmp_name'])) {
            $file = $_FILES['site_contents_file']['tmp_name'];
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
