<?php

/**
 * Unified Export / Import for All (Website Contents + Custom Posts)
 */

add_action('admin_init', function () {
    global $allowed_post_types; // from CPT include

    // EXPORT ALL
    if (isset($_POST['export_all']) && check_admin_referer('export_all_nonce')) {
        if (!current_user_can('manage_options')) return;

        require_once __DIR__ . '/../site_contents/export.php';
        require_once __DIR__ . '/../custom_posts/export.php';

        $site_contents = export_site_contents();

        $custom_posts = [];
        foreach ($allowed_post_types as $post_type) {
            $posts = export_custom_posts($post_type);
            if ($posts !== null) {
                $custom_posts = array_merge($custom_posts, $posts);
            }
        }

        // Export all taxonomies and their terms with meta
        $all_taxonomies = [];
        foreach ($allowed_post_types as $post_type) {
            $terms = export_all_taxonomies_for_post_type($post_type);
            if (!empty($terms)) {
                foreach ($terms as $taxonomy => $terms_data) {
                    if (!isset($all_taxonomies[$taxonomy])) {
                        $all_taxonomies[$taxonomy] = [];
                    }
                    $all_taxonomies[$taxonomy] = array_merge($all_taxonomies[$taxonomy], $terms_data);
                }
            }
        }

        $export_data = [
            'site_contents' => $site_contents,
            'custom_posts'  => $custom_posts,
            'all_taxonomies' => $all_taxonomies
        ];

        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Generate default filename: siteurl_lk-YYYY-MM-DD_HH-MM-SS.json
        $host = parse_url(home_url(), PHP_URL_HOST);
        $site_slug = sanitize_title($host);
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$site_slug}_lk-{$timestamp}.json";

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
    if (isset($_POST['import_all']) && check_admin_referer('import_all_nonce')) {
        if (!current_user_can('manage_options')) return;

        require_once __DIR__ . '/../site_contents/import.php';
        require_once __DIR__ . '/../custom_posts/import.php';

        if (!empty($_FILES['import_all_file']['tmp_name'])) {
            $file = $_FILES['import_all_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (!is_array($data)) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in All import file.</p></div>';
                });
                return;
            }

            if (!empty($data['site_contents'])) {
                import_site_contents($data['site_contents']);
            }

            if (!empty($data['custom_posts'])) {
                import_custom_posts_from_data($data['custom_posts']);
            }

            if (!empty($data['all_taxonomies'])) {
                import_all_taxonomies_from_data($data['all_taxonomies']);
            }

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>All content imported successfully.</p></div>';
            });
        }
    }
});
