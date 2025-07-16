<?php
    $is_post_order_enabled = get_option('ab_post_order_enabled', true);
    $is_language_switch_enabled = get_option('ab_language_switch_enabled', true);
    $is_reading_time_enabled = get_option('ab_reading_time_enabled', true);
    $is_image_alt_enabled = get_option('ab_image_alt_enabled', true);
    $is_cross_site_link_enabled = get_option('ab_cross_site_link_enabled', true);
?>

<div class="ab-wrap">
  <h2>Feature Settings</h2>
  <p>Configure the settings for the shortcodes below:</p>

  <table class="ab-shortcode-table">
    <tr>
      <td>
        <span class="ab-shortcode" data-shortcode="[post_order]" tabindex="0" role="button" aria-label="Copy [post_order]">[post_order]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>
        Displays the post's position in chronological order.
    </td>
    </tr>
    <tr>
        <td>
            <span class="ab-shortcode" data-shortcode="[language_switch]" tabindex="0" role="button" aria-label="Copy [language_switch]">[language_switch]
                <span class="ab-tooltip">Copied!</span>
            </span>
        </td>
      <td>
        Shows a language toggle with English and Sinhala options.
    </td>
    </tr>
    <tr>
      <td>
        <span class="ab-shortcode" data-shortcode="[reading_time]" tabindex="0" role="button" aria-label="Copy [reading_time]">[reading_time]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>
        Displays the estimated reading time for the current post.</td>
      </td>
    </tr>
  </table>

  <hr/>

  <form method="POST">
    <?php wp_nonce_field('ab_save_settings', 'ab_settings_nonce'); ?>

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
    

    <input type="submit" class="button button-primary" value="Save Settings" />
  </form>
</div>
