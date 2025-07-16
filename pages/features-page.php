<?php
    $is_post_order_enabled = get_option('ab_post_order_enabled', true);
    $is_language_switch_enabled = get_option('ab_language_switch_enabled', true);
    $is_reading_time_enabled = get_option('ab_reading_time_enabled', true);
    $is_image_alt_enabled = get_option('ab_image_alt_enabled', true);
    $is_cross_site_link_enabled = get_option('ab_cross_site_link_enabled', true);   
    $is_use_cdn_urls_enabled = get_option('ab_use_cdn_urls_enabled', false);
    $is_language_note_enabled = get_option('ab_language_audio_note_enabled', true);
    $is_rest_api_extras_enabled = get_option('ab_rest_api_extras_enabled', false);
?>

<div class="ab-wrap">
<!-- SHORTCODES REFERENCE -->
<h2>Available Shortcodes</h2>
  <p>Use the following shortcodes in your posts or pages:</p>

  <hr/>

<section class="ab-section">
  <table class="ab-shortcode-table">

    <tr id="shortcode-post-order" <?php if (!$is_post_order_enabled) echo 'style="display:none;"'; ?>>
      <td>
        <span class="ab-shortcode" data-shortcode="[post_order]" tabindex="0" role="button" aria-label="Copy [post_order]">[post_order]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>Displays the post's position in chronological order.</td>
    </tr>

    <tr id="shortcode-language-switch" <?php if (!$is_language_switch_enabled) echo 'style="display:none;"'; ?>>
      <td>
        <span class="ab-shortcode" data-shortcode="[language_switch]" tabindex="0" role="button" aria-label="Copy [language_switch]">[language_switch]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>Shows a language toggle with English and Sinhala options.</td>
    </tr>

    <tr id="shortcode-reading-time" <?php if (!$is_reading_time_enabled) echo 'style="display:none;"'; ?>>
      <td>
        <span class="ab-shortcode" data-shortcode="[reading_time]" tabindex="0" role="button" aria-label="Copy [reading_time]">[reading_time]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>Displays the estimated reading time for the current post.</td>
    </tr>

    <tr id="shortcode-language-note" <?php if (!$is_language_note_enabled) echo 'style="display:none;"'; ?>>
      <td>
        <span class="ab-shortcode" data-shortcode="[language_audio_note]" tabindex="0" role="button" aria-label="Copy [language_audio_note]">[language_audio_note]
            <span class="ab-tooltip">Copied!</span>
        </span>
      </td>
      <td>Plays background audio in Sinhala (.lk) or English (.org).</td>
    </tr>

  </table>
</section>

</div>


<div class="ab-wrap">
  <h2>Feature Settings</h2>
  <p>Configure the settings for the shortcodes and features below:</p>

  <hr/>

  <form method="POST">
    <?php wp_nonce_field('ab_save_settings', 'ab_settings_nonce'); ?>

    <!--  SHORTCODES TOGGLE SETTINGS -->
    <section class="ab-section">
      <h3>Enable / Disable Shortcodes</h3>

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
          <input type="checkbox" name="ab_language_audio_note_enabled" value="1" <?php checked($is_language_note_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Language Audio Note shortcode</span>
        </label>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_reading_time_enabled" value="1" <?php checked($is_reading_time_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Reading Time shortcode</span>
        </label>
      </div>
    </section>

    <hr/>

    <!-- FEATURE TOGGLES -->
    <section class="ab-section">
      <h3>Enable / Disable Features</h3>
      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_image_alt_enabled" value="1" <?php checked($is_image_alt_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Auto-Generate Image ALT Text</span>
        </label>
        <div class="ab-desc" style="margin-top:8px;">
            Automatically generate ALT text for images based on their file names.
          </div>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_cross_site_link_enabled" value="1" <?php checked($is_cross_site_link_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Enable Dynamic .org/.lk Site Link in Admin Bar</span>
        </label>
        <div class="ab-desc" style="margin-top:8px;">
            Adds a dynamic link to the admin bar that points to the .org or .lk version of your site based on the current language.
          </div>
      </div>

      <div class="ab-feature">
        <label class="ab-toggle">
          <input type="checkbox" name="ab_rest_api_extras_enabled" value="1" <?php checked($is_rest_api_extras_enabled); ?> />
          <span class="ab-slider"></span>
          <span class="ab-label">Expose Extra Data to REST API</span>
        </label>
        <div class="ab-desc" style="margin-top:8px;">
          Enable this to expose raw post content and Rank Math focus keyword in the REST API.
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

  <hr/>
</div>
