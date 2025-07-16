<?php
    $is_post_order_enabled = get_option('ab_post_order_enabled', true);
    $is_language_switch_enabled = get_option('ab_language_switch_enabled', true);
    $is_reading_time_enabled = get_option('ab_reading_time_enabled', true);
    $is_image_alt_enabled = get_option('ab_image_alt_enabled', true);
    $is_cross_site_link_enabled = get_option('ab_cross_site_link_enabled', true);   
    $is_use_cdn_urls_enabled = get_option('ab_use_cdn_urls_enabled', false);
?>

<div class="ab-wrap">
  <h2>Feature Settings</h2>
  <p>Configure the settings for the shortcodes and features below:</p>

  <!-- SHORTCODES REFERENCE -->
  <section class="ab-section">
    <h3>Available Shortcodes</h3>
    <table class="ab-shortcode-table">
      <tr>
        <td>
          <span class="ab-shortcode" data-shortcode="[post_order]" tabindex="0" role="button" aria-label="Copy [post_order]">[post_order]
              <span class="ab-tooltip">Copied!</span>
          </span>
        </td>
        <td>Displays the post's position in chronological order.</td>
      </tr>
      <tr>
        <td>
          <span class="ab-shortcode" data-shortcode="[language_switch]" tabindex="0" role="button" aria-label="Copy [language_switch]">[language_switch]
              <span class="ab-tooltip">Copied!</span>
          </span>
        </td>
        <td>Shows a language toggle with English and Sinhala options.</td>
      </tr>
      <tr>
        <td>
          <span class="ab-shortcode" data-shortcode="[reading_time]" tabindex="0" role="button" aria-label="Copy [reading_time]">[reading_time]
              <span class="ab-tooltip">Copied!</span>
          </span>
        </td>
        <td>Displays the estimated reading time for the current post.</td>
      </tr>
      <tr>
      <td>
        <span class="ab-shortcode" data-shortcode="[language_audio_player]" tabindex="0" role="button" aria-label="Copy [language_audio_player]">[language_audio_player]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>Plays background audio in Sinhala (.lk) or English (.org).</td>
    </tr>

    </table>
  </section>

  <hr/>

  <form method="POST">
    <?php wp_nonce_field('ab_save_settings', 'ab_settings_nonce'); ?>

    <!-- FEATURE TOGGLES -->
    <section class="ab-section">
      <h3>Enable / Disable Features</h3>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_post_order_enabled" value="1" <?php checked($is_post_order_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Post Order shortcode</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_language_switch_enabled" value="1" <?php checked($is_language_switch_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Language Switcher shortcode</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_language_audio_player_enabled" value="1" <?php checked(get_option('ab_language_audio_player_enabled', true)); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Language Audio Player shortcode</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_reading_time_enabled" value="1" <?php checked($is_reading_time_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Reading Time shortcode</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_image_alt_enabled" value="1" <?php checked($is_image_alt_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Auto-Generate Image ALT Text</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_cross_site_link_enabled" value="1" <?php checked($is_cross_site_link_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Dynamic .org/.lk Site Link in Admin Bar</span>
        </label>
      </div>
    </section>

    <hr/>

    <!-- CDN SETTINGS -->
    <section class="ab-section">
      <h3>CDN URL Settings</h3>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_use_cdn_urls_enabled" value="1" <?php checked($is_use_cdn_urls_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable this to rewrite media attachment URLs to the CDN URL specified below.</span>
        </label>
        <div class="ab-desc" style="margin-top:8px;">
          Rewrite media attachment URLs from your site URL to the CDN URL you specify below for faster content delivery.
        </div>
      </div>

      <label for="ab_cdn_url" style="display:block; margin-top:12px; font-weight:600;">
        CDN Base URL (include https://)
      </label>
      <input
        type="url"
        name="ab_cdn_url"
        id="ab_cdn_url"
        value="<?php echo esc_attr(get_option('ab_cdn_url', '')); ?>"
        placeholder="https://cdn.example.com"
        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px;"
      />
    </section>

    <br/>
    <br/>
    <hr/>

    <input type="submit" class="button button-primary" value="Save Settings" />
  </form>
</div>
