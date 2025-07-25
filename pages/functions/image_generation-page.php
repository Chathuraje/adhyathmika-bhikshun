<?php
if (!defined('ABSPATH')) {
    exit;
}

$data = include 'image-generation-logic.php';

$image_url = $data['image_url'];
$message = $data['message'];
?>

<div class="wrap">
    <h1>Image Generation</h1>

    <?php if (!empty($message)): ?>
        <div style="margin: 15px 0; padding: 10px; background: #fff; border-left: 4px solid #0073aa;">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="prompt">Prompt</label></th>
                <td><textarea name="prompt" id="prompt" class="regular-text" required rows="10"></textarea></td>
            </tr>
        </table>
        <?php submit_button('Generate Image'); ?>
    </form>

    <?php if ($image_url): ?>
        <h2>Generated Image:</h2>
        <img src="<?php echo $image_url; ?>" alt="Generated" style="max-width: 100%; height: auto; border: 1px solid #ccc; margin: 10px 0;">

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="handle_image_decision">
            <input type="hidden" name="image_url" value="<?php echo esc_attr($image_url); ?>">
            <input type="hidden" name="decision" value="approve">
            <?php submit_button('Approve', 'primary', 'approve', false); ?>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
            <input type="hidden" name="action" value="handle_image_decision">
            <input type="hidden" name="image_url" value="<?php echo esc_attr($image_url); ?>">
            <input type="hidden" name="decision" value="disapprove">
            <?php submit_button('Disapprove', 'secondary', 'disapprove', false); ?>
        </form>
    <?php endif; ?>
</div>
