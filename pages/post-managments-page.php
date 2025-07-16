<?php
    $is_single_airtable_sync_enabled = get_option('ab_single_airtable_sync_enabled', true);
?>

<div class="ab-wrap">
    <h2>Post Management Settings</h2>
    <p>Configure the settings for post management features below:</p>
    <hr/>

    <form method="post">
    <?php wp_nonce_field('ab_post_save_settings', 'ab_post_settings_nonce'); ?>
        <!-- FEATURE TOGGLES -->
        <section class="ab-section">
            <h3>Enable / Disable Features</h3>

            <div class="ab-feature">
                <label class="ab-toggle">
                <input type="checkbox" name="ab_single_airtable_sync_enabled" value="1" <?php checked($is_single_airtable_sync_enabled); ?> />
                <span class="ab-slider"></span>
                <span class="ab-label">Enable "Sync with Airtable" Feature</span>
                </label>
                <div class="ab-desc" style="margin-top:8px;">
                    Adds a button to manually sync posts with Airtable via webhook, including YouTube ID.
                </div>
            </div>
        </section>

        <p>
            <button type="submit" class="button button-primary">Save Settings</button>
        </p>
    </div>
