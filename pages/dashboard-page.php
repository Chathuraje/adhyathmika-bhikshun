<?php
function render_airtable_sync_summary() {
    /** @var array $data */
    $data = get_airtable_sync_data();

    if (empty($data)) {
        return '<p>No post types found for Airtable sync.</p>';
    }

    $rows = '';

    foreach ($data as $item) {
        $detail_html = '';
        if (count($item['unique_times']) > 1) {
            $detail_html .= '<div style="margin-top:10px;"><em>Post sync times vary:</em><ul style="margin-left:15px;">';
            foreach ($item['details'] as $detail) {
                $color = $detail['status'] === 'success' ? 'green' : ($detail['status'] === 'failed' ? 'red' : 'gray');
                $icon = $detail['status'] === 'success' ? '✅' : ($detail['status'] === 'failed' ? '❌' : '⏺️');
                $detail_html .= sprintf(
                    '<li><span style="color:%s;">%s</span> %s — <code>%s</code></li>',
                    $color, $icon, esc_html($detail['title']), $detail['date']
                );
            }
            $detail_html .= '</ul></div>';
        }

        if ($item['synced'] > 0) {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td style="text-align:center;">%d</td>
                    <td style="text-align:center;">%d</td>
                    <td style="text-align:center; color:green;">%d</td>
                    <td style="text-align:center; color:red;">%d</td>
                    <td style="text-align:center;">%s%s</td>
                </tr>',
                esc_html(ucfirst($item['post_type'])),
                $item['total'],
                $item['synced'],
                $item['success'],
                $item['failed'],
                $item['latest_sync'] ?? '<em>—</em>',
                $detail_html
            );
        } else {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td style="text-align:center;">%d</td>
                    <td colspan="4" style="text-align:center; font-style: italic; color: #888;">No sync data</td>
                </tr>',
                esc_html(ucfirst($item['post_type'])),
                $item['total']
            );
        }
    }

    return '
    <div style="margin-top: 30px;">
        <h2>Airtable Sync Overview</h2>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Post Type</th>
                    <th style="text-align:center;">Total Posts</th>
                    <th style="text-align:center;">Synced</th>
                    <th style="text-align:center;">Success</th>
                    <th style="text-align:center;">Failed</th>
                    <th style="text-align:center;">Latest Sync</th>
                </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
        </table>
    </div>';
}

?>

<div class="wrap" style="max-width: 800px; margin: auto;">
    <h1 style="text-align: center;">Welcome to Adhyathmika Bhikshun</h1>
    <div style="text-align: center; margin-top: 20px;">
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/welcome-image.png'); ?>"
             alt="Spiritual Image"
             style="max-width: 100%; height: auto; border-radius: 10px;" />
    </div>

    <?php echo render_airtable_sync_summary(); ?>

    <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-left: 5px solid #5a5a5a;">
        <p><strong>“Spiritual Monks for a New Era.”</strong></p>
    </div>

    <p style="margin-top: 20px;">
        This plugin aids in managing and enhancing spiritual content on <strong>adhyathmikabhikshun.org</strong>.
    </p>
</div>
