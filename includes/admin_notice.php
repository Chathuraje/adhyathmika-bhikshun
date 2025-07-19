<?php

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Notices {

    private static $notices = [];

    /**
     * Add a notice to the internal queue.
     *
     * @param string $message
     * @param string $type success|error|warning|info
     */
    public static function add_notice(string $message, string $type = 'info'): void {
        if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $type = 'info';
        }

        self::$notices[] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    /**
     * Add a persistent notice (shown once).
     *
     * @param string $message
     * @param string $type
     */
    public static function add_persistent_notice(string $message, string $type = 'info'): void {
        set_transient('ab_admin_notice', [
            'message' => $message,
            'type'    => $type,
        ], 30); // 30 seconds
    }

    /**
     * Display and clear all queued notices.
     */
    public static function display_notices(): void {
        // Check for transient-based notice
        $transient_notice = get_transient('ab_admin_notice');
        if (!empty($transient_notice['message'])) {
            self::add_notice($transient_notice['message'], $transient_notice['type'] ?? 'info');
            delete_transient('ab_admin_notice');
        }

        foreach (self::$notices as $notice) {
            self::render_notice($notice['message'], $notice['type']);
        }

        self::$notices = [];
    }

    /**
     * Render a single admin notice.
     *
     * @param string $message
     * @param string $type
     */
    private static function render_notice(string $message, string $type = 'info'): void {
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }

    /**
     * Display notice from $_GET if nonce is valid.
     * Example URL: ?notice_status=success&notice_message=Post+created&_ab_notice=123xyz
     */
    public static function maybe_display_get_notice(): void {
        if (!isset($_GET['notice_status'], $_GET['notice_message'], $_GET['_ab_notice'])) {
            return;
        }

        if (!wp_verify_nonce($_GET['_ab_notice'], 'ab_display_notice')) {
            return;
        }

        $type    = sanitize_text_field($_GET['notice_status']);
        $message = sanitize_text_field(urldecode($_GET['notice_message']));

        self::add_notice($message, $type);
    }

    /**
     * Output the nonce to use in notice URLs.
     */
    public static function get_notice_nonce(): string {
        return wp_create_nonce('ab_display_notice');
    }
}

// Hook notices display in both admin and network admin
add_action('admin_notices', ['Admin_Notices', 'display_notices']);
add_action('network_admin_notices', ['Admin_Notices', 'display_notices']);

// Show GET-based notice
add_action('admin_init', ['Admin_Notices', 'maybe_display_get_notice']);
