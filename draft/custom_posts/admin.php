<?php
// admin.php
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/import.php';

add_action('admin_init', function () {
    global $allowed_post_types;

    if (isset($_POST['export_custom_posts']) && check_admin_referer('export_custom_posts_nonce')) {
        if (!current_user_can('manage_options')) return;

        $post_type = sanitize_text_field($_POST['export_custom_posts_type']);

        if (!in_array($post_type, $allowed_post_types)) {
            wp_die('Invalid post type selected.');
        }

        $categories_data = export_all_taxonomies_for_post_type($post_type);
        $posts_data = export_custom_posts($post_type);

        $export_data = [
            'categories' => $categories_data,
            'posts' => $posts_data,
        ];

        $json = json_encode($export_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


        $site_url = parse_url(home_url());
        $host = $site_url['host'];
        $host_parts = explode('.', $host);
        $tld = end($host_parts);
        $site_name = sanitize_title(get_bloginfo('name'));
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$site_name}_{$tld}-{$post_type}-{$timestamp}.json";

        if (!empty($_POST['export_custom_posts_filename'])) {
            $name = sanitize_file_name(trim($_POST['export_custom_posts_filename']));
            if (strtolower(substr($name, -5)) !== '.json') $name .= '.json';
            if ($name !== '') $filename = $name;
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $json;
        exit;
    }

    if (isset($_POST['import_custom_posts']) && check_admin_referer('import_custom_posts_nonce')) {
        if (!current_user_can('manage_options')) return;

        if (!empty($_FILES['custom_posts_import_file']['tmp_name'])) {
            $file = $_FILES['custom_posts_import_file']['tmp_name'];
            $data = json_decode(file_get_contents($file), true);

            if (!is_array($data)) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Invalid JSON format in custom posts import file.</p></div>';
                });
                return;
            }

            if (isset($data['categories']) && is_array($data['categories'])) {
                import_all_taxonomies_from_data($data['categories']);
            }

            if (!empty($data['posts'])) import_custom_posts_from_data($data['posts']);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>Custom taxonomies and posts imported successfully. Previous posts were replaced.</p></div>';
            });
        }
    }
});
