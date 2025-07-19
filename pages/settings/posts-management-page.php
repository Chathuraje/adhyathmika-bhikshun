<?php
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'adhyathmika-bhikshun'));
}

$settings = [
    'ab_testing_enabled'               => get_option('ab_testing_enabled', true),
    'ab_create_a_new_post_enabled'     => get_option('ab_create_a_new_post_enabled', true),
    'ab_sync_single_post_with_airtable_enabled'  => get_option('ab_sync_single_post_with_airtable_enabled', true),
    // 'ab_import_posts_to_site_enabled' => get_option('ab_import_posts_to_site_enabled', true),
];
?>

<div class="ab-wrap">
  <h2>
    <?php _e('Post Management Settings', 'adhyathmika-bhikshun'); ?>
    <span class="ab-mode-badge <?php echo $settings['ab_testing_enabled'] ? 'test' : 'live'; ?>">
      <?php echo $settings['ab_testing_enabled'] ? '🧪 Test Mode' : '✅ Live Mode'; ?>
    </span>
  </h2>
  <p><?php _e('Configure the settings for post management features below:', 'adhyathmika-bhikshun'); ?></p>
  <hr/>

  <form method="post" action="">
    <?php wp_nonce_field('ab_post_save_settings', 'ab_post_settings_nonce'); ?>

    <?php if (!empty($_POST) && check_admin_referer('ab_post_save_settings', 'ab_post_settings_nonce')): ?>
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
              'desc'  => __('When enabled, all post syncs will be sent to the testing webhook URL instead of the production one. Useful for testing without affecting live data.', 'adhyathmika-bhikshun')
          ],
          'ab_create_a_new_post_enabled' => [
                'label' => __('Enable New Post Creation', 'adhyathmika-bhikshun'),
                'desc'  => __('Allows the creation of new posts through the plugin interface.', 'adhyathmika-bhikshun')
          ],
          'ab_sync_single_post_with_airtable_enabled' => [
              'label' => __('Enable Single Post Sync with Airtable', 'adhyathmika-bhikshun'),
              'desc'  => __('Enables the synchronization of individual posts with Airtable. This is useful for keeping your posts in sync with your Airtable base.', 'adhyathmika-bhikshun')
          ],
          // 'ab_import_posts_to_site_enabled' => [
          //     'label' => __('Enable Import Posts to Site API', 'adhyathmika-bhikshun'),
          //     'desc'  => __('
          //                   Enables the API endpoint for importing posts to the site. This is useful for bulk importing posts from external sources. Ensure you have the necessary permissions to access this endpoint.<br/> <br/> 
          //                   API Endpoint: <code>/wp-json/ab-custom-apis/v2/import-post</code><br/> 
          //                   Method: <code>POST</code> <br/>
          //                   Payload: <code>[{"post_title": "Title", "post_content": "Content", ...}]</code><br/> ', 'adhyathmika-bhikshun')
          // ],
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
