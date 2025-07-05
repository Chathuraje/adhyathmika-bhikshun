<?php
/**
 * Unified Export / Import for All (Website Contents + Custom Post)
 */

add_action('admin_init', function () {
    global $allowed_post_types; // from CPT include

    // EXPORT ALL
    if (isset($_POST['abh_export_all']) && check_admin_referer('abh_export_all_nonce')) {
        if (!current_user_can('manage_options')) return;

        require_once __DIR__ . '/export-import-content.php';
        require_once __DIR__ . '/export-import-cpt.php';

        $acf_data = export_acf_options();

        $cpt_data = [];
        foreach ($allowed_post_types as $post_type) {
            $posts = export_cpt_posts($post_type);
            if ($posts !== null) {
                $cpt_data = array_merge($cpt_data, $posts);
            }
        }

        $export_data = [
            'acf_options' => $acf_data,
            'cpt_posts'   => $cpt_data,
        ];

        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'all-export.json';

        if (!empty($_POST['export_all_filename'])) {
            $name = sanitize_file_name(trim($_POST['export_all_filename']));
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

    // IMPORT ALL
    if (isset($_POST['abh_import_all']) && check_admin_referer('abh_import_all_nonce')) {
        if (!current_user_can('manage_options')) return;

        require_once __DIR__ . '/export-import-content.php';
        require_once __DIR__ . '/export-import-cpt.php';

        if (!empty($_FILES['import_all_file']['tmp_name'])) {
            $file = $_FILES['import_all_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (!is_array($data)) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in All import file.</p></div>';
                });
                return;
            }

            if (!empty($data['acf_options'])) {
                import_acf_options_from_data($data['acf_options']);
            }

            if (!empty($data['cpt_posts'])) {
                import_cpt_posts_from_data($data['cpt_posts']);
            }

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>All content imported successfully! Existing CPT posts replaced.</p></div>';
            });
        }
    }
});
