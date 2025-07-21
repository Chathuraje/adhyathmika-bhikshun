<?php

/**
 * Adhyathmika Bhikshun Plugin
 * Site Management Functions
 *
 * @package Adhyathmika_Bhikshun
 */

require_once __DIR__ . '/requests/get_site_contentns_from_db.php';
require_once __DIR__ . '/requests/send_site_contents_to_db.php';

add_action('admin_footer', function () {
    // Only on your specific admin page
    if (!isset($_GET['page']) || $_GET['page'] !== 'website-contents') {
        return;
    }

    $url_send_site_contents_to_db = wp_nonce_url(admin_url('admin-post.php?action=ab_send_site_content_to_db'), 'admin_ab_send_site_content_to_db_action');
    $url_get_site_contents_from_db   = wp_nonce_url(admin_url('admin-post.php?action=ab_get_site_content_from_db'), 'admin_ab_get_site_content_from_db_action');
    ?>
    <style>
        #custom-sync-buttons {
            margin: 20px 0;
        }
        #custom-sync-buttons button {
            background-color: #0073aa;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            margin-right: 10px;
        }

        #custom-sync-buttons button:hover {
            background-color: #005177;
        }
    </style>
    <script type="text/javascript">
        jQuery(function($) {
            const buttonsHtml = `
                <div id="custom-sync-buttons">
                    <form method="post" action="<?php echo esc_url($url_send_site_contents_to_db); ?>" style="display:inline;">
                        <button type="submit">Send Site Contents to DB</button>
                    </form>
                    <form method="post" action="<?php echo esc_url($url_get_site_contents_from_db); ?>" style="display:inline;">
                        <button type="submit">Get Site Contents from DB</button>
                    </form>
                </div>
            `;
            // Append buttons below the page title or some container on that page
            $('.wrap h1').after(buttonsHtml);
        });
    </script>
    <?php
});


add_action('admin_post_ab_send_site_content_to_db', function() {
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'admin_ab_send_site_content_to_db_action')) {
        wp_die('Nonce verification failed');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }
    $result = send_site_contents_to_db();

    Admin_Notices::redirect_with_notice('✅ Site contents sent to database successfully!', 'success', wp_get_referer());
    exit;
});

add_action('admin_post_ab_get_site_content_from_db', function() {
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'admin_ab_get_site_content_from_db_action')) {
        wp_die('Nonce verification failed');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }
    $result = get_site_contents_from_db();

    Admin_Notices::redirect_with_notice('✅ Site contents synced successfully!', 'success', wp_get_referer());
    exit;
});



?>