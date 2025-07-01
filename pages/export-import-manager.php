<div class="wrap" style="max-width: 800px; margin: auto;">
    <h1 style="margin-bottom: 30px;">Export / Import Manager</h1>

    <!-- SECTION: Export / Import All -->
    <section style="background: #f9f9f9; padding: 20px 25px; margin-bottom: 40px; border-left: 6px solid #0073aa; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🔁 Export / Import All (Website Contents + All Posts)</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('abh_export_all_nonce'); ?>
            <label>
                Export Filename:
                <input type="text" name="export_all_filename" placeholder="all-export.json"
                       pattern="^[a-zA-Z0-9_\-\.]+$"
                       style="width: 100%; margin: 10px 0; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="abh_export_all" class="button button-primary" value="Export All">
        </form>
        <hr style="margin: 20px 0;">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('abh_import_all_nonce'); ?>
            <input type="file" name="import_all_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="abh_import_all" class="button button-secondary" value="Import All">
        </form>
    </section>

    <!-- SECTION: Export / Import ACF Content -->
    <section style="background: #fefefe; padding: 20px 25px; margin-bottom: 40px; border-left: 6px solid #46b450; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🧩 Export / Import Website Contents</h2>
        <form method="post">
            <?php wp_nonce_field('abh_export_acf_nonce'); ?>
            <label>
                Filename:
                <input type="text" name="export_filename" placeholder="contents_export.json"
                       pattern="^[a-zA-Z0-9_\-\.]+$"
                       style="width: 100%; margin: 10px 0; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="abh_export_acf" class="button button-primary" value="Export ACF Content">
        </form>
        <hr style="margin: 20px 0;">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('abh_import_acf_nonce'); ?>
            <input type="file" name="acf_import_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="abh_import_acf" class="button button-secondary" value="Import ACF Content">
        </form>
    </section>

    <!-- SECTION: Export / Import CPT Posts -->
    <section style="background: #fff; padding: 20px 25px; border-left: 6px solid #d54e21; border-radius: 4px;">
        <h2 style="font-size: 1.4em;">🗂️ Export / Import Post Types</h2>
        <form method="post">
            <?php wp_nonce_field('abh_export_cpt_nonce'); ?>
            <label>
                Select Post Type:
                <select name="export_cpt" required style="width: 100%; padding: 6px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">-- Select CPT --</option>
					<option value="post">Posts</option>
                    <option value="daily-spiritual-offe">Daily Spiritual Offerings</option>
                    <option value="guidance-paths-items">Guidance Path Items</option>
                    <option value="testimonial">Testimonials</option>
					<option value="small-quote">Small Quotes</option>
                </select>
            </label>
            <br><br>
            <label>
                Filename:
                <input type="text" name="export_cpt_filename" placeholder="cpt-posts-export.json"
                       pattern="^[a-zA-Z0-9_\-\.]+$"
                       style="width: 100%; margin-top: 10px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </label><br>
            <input type="submit" name="abh_export_cpt" class="button button-primary" value="Export CPT Posts" style="margin-top: 10px;">
        </form>
        <hr style="margin: 20px 0;">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('abh_import_cpt_nonce'); ?>
            <input type="file" name="cpt_import_file" accept=".json" required style="margin-bottom: 10px;">
            <br>
            <input type="submit" name="abh_import_cpt" class="button button-secondary" value="Import CPT Posts">
        </form>
    </section>
</div>
