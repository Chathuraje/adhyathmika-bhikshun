<?php
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'adhyathmika-bhikshun'));
}

$settings = [
    'ab_testing_enabled'               => get_option('ab_testing_enabled', true),
    'ab_sync_site_contetns_enabled'     => get_option('ab_sync_site_contetns_enabled', true),
];
?>

<div class="ab-wrap">
  <h2>
    <?php _e('Site Management Settings', 'adhyathmika-bhikshun'); ?>
    <span class="ab-mode-badge <?php echo $settings['ab_testing_enabled'] ? 'test' : 'live'; ?>">
      <?php echo $settings['ab_testing_enabled'] ? '🧪 Test Mode' : '✅ Live Mode'; ?>
    </span>
  </h2>
  <p><?php _e('Configure the settings for site management features below:', 'adhyathmika-bhikshun'); ?></p>
  <hr/>

  <form method="post" action="">
    <?php wp_nonce_field('ab_site_save_settings', 'ab_site_settings_nonce'); ?>

    <?php if (!empty($_POST) && check_admin_referer('ab_site_save_settings', 'ab_site_settings_nonce')): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php _e('Settings saved successfully.', 'adhyathmika-bhikshun'); ?></p>
      </div>
    <?php endif; ?>

    <!-- Feature Toggles -->
    <section class="ab-section">
      <h3><?php _e('Enable / Disable Features', 'adhyathmika-bhikshun'); ?></h3>
      <?php
      $features = [
          'ab_testing_enabled' => [
              'label' => __('Enable Testing Mode', 'adhyathmika-bhikshun'),
              'desc'  => __('When enabled, all site syncs will be sent to the testing webhook URL instead of the production one. Useful for testing without affecting live data.', 'adhyathmika-bhikshun')
          ],
          'ab_sync_site_contetns_enabled' => [
                'label' => __('Enable Site Content Sync', 'adhyathmika-bhikshun'),
                'desc'  => __('Allows the export and import of site contents through the plugin interface. This is useful for backing up or migrating site data.', 'adhyathmika-bhikshun')
          ]
      ];

      foreach ($features as $key => $label) {
          ?>
          <div class="ab-feature">
            <label class="ab-toggle">
                <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked(get_option($key, true), 1); ?> />
                <span class="ab-slider"></span>
                <span class="ab-label"><?php echo esc_html($label['label']); ?></span>
            </label>
            <div class="ab-desc" style="margin-top:8px;">
                <?php echo wp_kses_post($label['desc']); ?>
            </div>
          </div>
          <?php
      }
      ?>
    </section>

    <br/>
    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'adhyathmika-bhikshun'); ?>" />
  </form>
</div>
