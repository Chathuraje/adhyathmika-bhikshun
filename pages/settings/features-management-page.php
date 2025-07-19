<?php
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'adhyathmika-bhikshun'));
}

$settings = [
    'ab_global_post_position_enabled'         => get_option('ab_global_post_position_enabled', true),
    'ab_language_switcher_enabled'            => get_option('ab_language_switcher_enabled', true),
    'ab_reading_time_enabled'                 => get_option('ab_reading_time_enabled', true),
    'ab_render_audio_note_enabled'            => get_option('ab_render_audio_note_enabled', true),

    'ab_auto_generate_image_alt_enabled'      => get_option('ab_auto_generate_image_alt_enabled', true),
    'ab_admin_bar_dynamic_site_link_enabled'  => get_option('ab_admin_bar_dynamic_site_link_enabled', false),
    'ab_cdn_url_rewrite_enabled'              => get_option('ab_cdn_url_rewrite_enabled', true),
    'ab_cdn_url_rewrite_enabled'              => get_option('ab_cdn_url_rewrite_enabled', false),
    'ab_cdn_url'                              => get_option('ab_cdn_url', ''),
];
?>

<div class="ab-wrap">
  <h2><?php _e('Available Shortcodes', 'adhyathmika-bhikshun'); ?></h2>
  <p><?php _e('Use the following shortcodes in your posts or pages:', 'adhyathmika-bhikshun'); ?></p>
  <hr/>

  <section class="ab-section">
    <table class="ab-shortcode-table">
      <?php
      $shortcodes = [
          'ab_global_post_position_enabled'       => ['[post_order]', 'Displays the post\'s position in chronological order.'],
          'ab_language_switcher_enabled'          => ['[language_switcher]', 'Shows a language toggle with English and Sinhala options.'],
          'ab_reading_time_enabled'               => ['[reading_time]', 'Displays the estimated reading time for the current post.'],
          'ab_render_audio_note_enabled'          => ['[language_audio_note]', 'Plays background audio in Sinhala (.lk) or English (.org).'],
      ];

      foreach ($shortcodes as $setting_key => [$tag, $desc]) {
          if (empty($settings[$setting_key])) continue;
          ?>
          <tr>
            <td>
              <span class="ab-shortcode" data-shortcode="<?php echo esc_attr($tag); ?>" tabindex="0" role="button">
                <?php echo esc_html($tag); ?>
                <span class="ab-tooltip"><?php _e('Copied!', 'adhyathmika-bhikshun'); ?></span>
              </span>
            </td>
            <td><?php echo esc_html($desc); ?></td>
          </tr>
          <?php
      }
      ?>
    </table>
  </section>
</div>

<div class="ab-wrap">
  <h2><?php _e('Feature Settings', 'adhyathmika-bhikshun'); ?></h2>
  <p><?php _e('Configure the settings for shortcodes and features below:', 'adhyathmika-bhikshun'); ?></p>
  <hr/>

  <form method="POST" action="">
    <?php wp_nonce_field('ab_save_settings', 'ab_settings_nonce'); ?>

    <?php if (!empty($_POST) && check_admin_referer('ab_save_settings', 'ab_settings_nonce')): ?>
      <div class="notice notice-success is-dismissible">
        <p><?php _e('Settings saved successfully.', 'adhyathmika-bhikshun'); ?></p>
      </div>
    <?php endif; ?>

    <!-- Shortcode Toggles -->
    <section class="ab-section">
      <h3><?php _e('Enable / Disable Shortcodes', 'adhyathmika-bhikshun'); ?></h3>
      <?php
      foreach (['ab_global_post_position_enabled', 'ab_language_switcher_enabled', 'ab_reading_time_enabled', 'ab_render_audio_note_enabled'] as $key) {
          ?>
          <div class="ab-feature">
            <label class="ab-toggle">
              <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key]); ?> />
              <span class="ab-slider"></span>
              <span class="ab-label"><?php echo esc_html(ucwords(str_replace(['ab_', '_enabled'], ['',''], $key))); ?> Shortcode</span>
            </label>
          </div>
          <?php
      }
      ?>
    </section>

    <hr/>

    <!-- Feature Toggles -->
    <section class="ab-section">
      <h3><?php _e('Enable / Disable Features', 'adhyathmika-bhikshun'); ?></h3>
      <?php
      $features = [
          'ab_auto_generate_image_alt_enabled' => [
              'label' => __('Enable Image Alt Text', 'adhyathmika-bhikshun'),
              'desc'  => __('Automatically adds alt text to images based on their filename.', 'adhyathmika-bhikshun')
          ],
          'ab_admin_bar_dynamic_site_link_enabled' => [
              'label' => __('Enable Cross-Site Links', 'adhyathmika-bhikshun'),
              'desc'  => __('Allows linking to external sites with proper tracking.', 'adhyathmika-bhikshun')
          ],
          'ab_register_custom_rest_fields_enabled' => [
              'label' => __('Enable REST API Extras', 'adhyathmika-bhikshun'),
              'desc'  => __('Adds additional fields to the REST API responses. (raw_contents and rank_math_meta)', 'adhyathmika-bhikshun')
          ],
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
                <?php echo esc_html($label['desc']); ?>
            </div>
          </div>
          <?php
      }
      ?>
    </section>

    <hr/>

    <!-- CDN Settings -->
    <section class="ab-section">
      <h3><?php _e('CDN URL Settings', 'adhyathmika-bhikshun'); ?></h3>
      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_cdn_url_rewrite_enabled" value="1" <?php checked($settings['ab_cdn_url_rewrite_enabled']); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label"><?php _e('Enable CDN URL rewriting', 'adhyathmika-bhikshun'); ?></span>
        </label>
        <div class="ab-desc"><?php _e('Rewrite media URLs using the CDN base URL below.', 'adhyathmika-bhikshun'); ?></div>
      </div>

      <label for="ab_cdn_url" style="display:block; margin-top:12px; font-weight:600;">
        <?php _e('CDN Base URL (include https://)', 'adhyathmika-bhikshun'); ?>
      </label>
      <input
        type="url"
        name="ab_cdn_url"
        id="ab_cdn_url"
        value="<?php echo esc_attr($settings['ab_cdn_url']); ?>"
        placeholder="https://cdn.example.com"
        style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;"
      />
    </section>

    <br/><br/>
    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'adhyathmika-bhikshun'); ?>" />
  </form>
</div>
