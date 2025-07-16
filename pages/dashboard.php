<?php
$is_post_order_enabled = get_option('ab_post_order_enabled', true);
$is_language_switch_enabled = get_option('ab_language_switch_enabled', true);
?>

<div class="wrap" style="max-width: 800px; margin: auto;">
    <h1 style="text-align: center;">Welcome to Adhyathmika Bhikshun</h1>
    <div style="text-align: center; margin-top: 20px;">
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/welcome-image.png'); ?>"
            alt="Spiritual Image"
            style="max-width: 100%; height: auto; border-radius: 10px;" />
    </div>
    <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-left: 5px solid #5a5a5a;">
        <p><strong>“Spiritual Monks for a New Era.”</strong></p>
    </div>
    <p style="margin-top: 20px;">
        This plugin aids in managing and enhancing spiritual content on <strong>adhyathmikabhikshun.org</strong>.
    </p>

    <!-- 🔘 Toggle Shortcode Feature -->
    <hr style="margin: 40px 0;">

    <form method="POST" style="margin-top: 30px;">
        <?php wp_nonce_field('ab_save_settings', 'ab_settings_nonce'); ?>

        <h2>Feature Settings</h2>

        <label>
            <input type="checkbox" name="ab_post_order_enabled" value="1" <?php checked($is_post_order_enabled); ?> />
            Enable [post_order] shortcode
        </label>
        <p>This displays the post's position in chronological order using the shortcode <code>[post_order]</code>.</p>

        <br />

        <label>
            <input type="checkbox" name="ab_language_switch_enabled" value="1" <?php checked($is_language_switch_enabled); ?> />
            Enable [language_switch] shortcode
        </label>
        <p>Display the language toggle switcher with English and Sinhala options.using the shortcode <code>[language_switch] </code>.</p>

        <input type="submit" class="button button-primary" value="Save Settings" />
    </form>
</div>