<?php

// 🔁 Track post views on single post page
function adb_track_post_views($post_id) {
    if (!is_single() || empty($post_id)) return;
    if (is_admin() || wp_doing_ajax()) return;

    $views = (int) get_post_meta($post_id, 'post_views_count', true);
    update_post_meta($post_id, 'post_views_count', $views + 1);
}

// ✅ Hook into correct lifecycle for post global
function adb_trigger_view_counter() {
    if (is_single()) {
        global $post;
        if ($post && isset($post->ID)) {
            adb_track_post_views($post->ID);
        }
    }
}
add_action('wp', 'adb_trigger_view_counter');


// 📊 Add "Views" column in Posts admin
function adb_add_views_column($columns) {
    $columns['post_views'] = 'Views';
    return $columns;
}
add_filter('manage_posts_columns', 'adb_add_views_column');

// 🔢 Display view count
function adb_show_views_column($column, $post_id) {
    if ($column === 'post_views') {
        $views = get_post_meta($post_id, 'post_views_count', true);
        echo $views ? intval($views) : '0';
    }
}
add_action('manage_posts_custom_column', 'adb_show_views_column', 10, 2);

// 🔃 Make it sortable
function adb_make_views_column_sortable($columns) {
    $columns['post_views'] = 'post_views_count';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'adb_make_views_column_sortable');

// 🧮 Allow ordering by view count in admin query
function adb_sort_by_views_column($query) {
    if (!is_admin()) return;

    $orderby = $query->get('orderby');
    if ($orderby === 'post_views_count') {
        $query->set('meta_key', 'post_views_count');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'adb_sort_by_views_column');
?>