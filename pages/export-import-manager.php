<div class="wrap" style="max-width: 800px; margin: auto;">
    <h1 style="margin-bottom: 30px;">Export / Import Manager</h1>


    <!-- SECTION: Export / Import All -->
    <section style="background: #f9f9f9; padding: 20px 25px; margin-bottom: 40px; border-left: 6px solid #0073aa; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🔁 Export / Import All (Website Contents + All Posts)</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('export_all_nonce'); ?>
            <label>
                Export Filename:
                <input type="text" name="export_all_file" placeholder="all-export.json"
                    pattern="^[a-zA-Z0-9_\-\.]+$"
                    style="width: 100%; margin: 10px 0; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="export_all" class="button button-primary" value="Export All">
        </form>
        <hr style="margin: 20px 0;">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('import_all_nonce'); ?>
            <input type="file" name="import_all_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="import_all" class="button button-secondary" value="Import All">
        </form>
    </section>

    <!-- SECTION: Export / Import Site Content -->
    <section style="background: #fefefe; padding: 20px 25px; margin-bottom: 40px; border-left: 6px solid #46b450; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🧩 Export / Import Website Contents</h2>

        <!-- Export Form -->
        <form method="post">
            <?php wp_nonce_field('export_site_contents_nonce'); ?>
            <label>
                Filename:
                <input type="text" name="export_filename" placeholder="my-demo-site-lk_2025-07-05_18-30-15.json.json"
                    pattern="^[a-zA-Z0-9_\-\.]+$"
                    style="width: 100%; margin: 10px 0; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="export_site_contents" class="button button-primary" value="Export Site Content">
        </form>
        <hr style="margin: 20px 0;">

        <!-- Import Form -->
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('import_site_contents_nonce'); ?>
            <input type="file" name="import_site_contents_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="import_site_contents" class="button button-secondary" value="Import Site Content">
        </form>
    </section>
    <!-- END OF SECTION: Export / Import Site Content -->


    <!-- SECTION: Export / Import Custom Posts -->
    <section style="background: #fff; padding: 20px 25px; border-left: 6px solid #d54e21; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🗂️ Export / Import Custom Posts</h2>

        <!-- Export Form -->
        <form method="post">
            <?php wp_nonce_field('export_custom_posts_nonce'); ?>
            <label>
                Select Post Type:
                <select name="export_custom_posts_type" required style="width: 100%; padding: 6px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">-- Select Post Type --</option>
                    <option value="post">Posts</option>
                    <option value="daily-spiritual-offe">Daily Spiritual Offerings</option>
                    <option value="testimonial">Testimonials</option>
                    <option value="small-quote">Small Quotes</option>
                </select>
            </label>
            <br><br>
            <label>
                Filename:
                <input type="text" name="export_custom_posts_filename" placeholder="custom-posts-export.json"
                    pattern="^[a-zA-Z0-9_\-\.]+$"
                    style="width: 100%; margin-top: 10px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="export_custom_posts" class="button button-primary" value="Export Custom Posts" style="margin-top: 10px;">
        </form>

        <hr style="margin: 20px 0;">

        <!-- Import Form -->
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('import_custom_posts_nonce'); ?>
            <input type="file" name="custom_posts_import_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="import_custom_posts" class="button button-secondary" value="Import Custom Posts">
        </form>
    </section>
    <!-- END OF SECTION: Export / Import Custom Posts -->


</div>