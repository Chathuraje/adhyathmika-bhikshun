<?php
    $is_testing_enabled = get_option('ab_testing_enabled', true);
    $is_single_airtable_sync_enabled = get_option('ab_single_airtable_sync_enabled', true);
    $is_new_post_creation_enabled = get_option('ab_new_post_creation_enabled', true);
?>

<div class="ab-wrap">
    <h2>Post Management Settings
        <span class="ab-mode-badge <?php echo $is_testing_enabled ? 'test' : 'live'; ?>">
            <?php echo $is_testing_enabled ? '🧪 Test Mode' : '✅ Live Mode'; ?>
        </span>
    </h2>
    <p>Configure the settings for post management features below:</p>
</h2>

    <hr/>

    <form method="post">
    <?php wp_nonce_field('ab_post_save_settings', 'ab_post_settings_nonce'); ?>
        <!-- FEATURE TOGGLES -->
        <section class="ab-section">
            <h3>Enable / Disable Features</h3>

            <div class="ab-feature">
                <label class="ab-toggle">
                <input type="checkbox" name="ab_testing_enabled" value="1" <?php checked($is_testing_enabled); ?> />
                <span class="ab-slider"></span>
                <span class="ab-label">Enable Testing Mode</span>
                </label>

                <div class="ab-desc" style="margin-top:8px;">
                    When enabled, all post syncs will be sent to the testing webhook URL instead of the production one.
                    This is useful for testing without affecting live data.
                </div>
            </div>

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

            <div class="ab-feature">
                <label class="ab-toggle">
                <input type="checkbox" name="ab_new_post_creation_enabled" value="1" <?php checked($is_new_post_creation_enabled); ?> />
                <span class="ab-slider"></span>
                <span class="ab-label">Enable New Post Creation Feature</span>
                </label>
                <div class="ab-desc" style="margin-top:8px;">
                    When enabled, a new post creation feature will be available in the admin panel.
                    This allows you to create posts directly from the admin posts interface.
                </div>
            </div>
        </section>

        <p>
            <button type="submit" class="button button-primary">Save Settings</button>
        </p>
    </div>
