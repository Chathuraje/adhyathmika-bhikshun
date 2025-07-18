jQuery(document).ready(function($) {
    // Variables passed from PHP will be set later via wp_localize_script or similar
    var postType = window.abImportPostType || 'post';
    var importUrl = window.abImportUrl || '';

    // Add the "Import All" button
    var button = '<a href="' + importUrl + '" id="trigger-import" class="page-title-action">Import All ' + postType.charAt(0).toUpperCase() + postType.slice(1) + '</a>';
    $(".wrap .page-title-action").first().after(button);

    // Inject the popup HTML
    $("body").append(`
        <div id="import-progress-modal" style="position:fixed; top:20%; left:50%; transform:translateX(-50%); width:400px; background:#fff; border:1px solid #ccc; z-index:9999; padding:20px; display:none; box-shadow:0 0 10px rgba(0,0,0,0.3); border-radius:8px;">
            <h2 style="margin-top:0;">Importing Posts...</h2>
            <div style="background:#e5e5e5; border-radius:4px; overflow:hidden; margin:15px 0;">
                <div id="progress-bar" style="height:20px; width:0%; background:#0073aa; color:#fff; text-align:center; line-height:20px;">0%</div>
            </div>
            <p id="progress-status">Starting import...</p>
            <button onclick="jQuery('#import-progress-modal').hide();" style="float:right;" class="button">Close</button>
            <div style="clear:both;"></div>
        </div>
    `);

    // When "Import All" button clicked, show the popup
    $("#trigger-import").on("click", function() {
        $("#import-progress-modal").show();
        setTimeout(function() {
            pollProgress(postType);
        }, 2000); // Start polling after slight delay
    });

    // Polling function to update the progress bar
    window.pollProgress = function(postType) {
        const endpoint = `/wp-json/ab-custom-apis/v2/import-progress?post_type=` + postType;
        const bar = document.getElementById("progress-bar");
        const status = document.getElementById("progress-status");

        if (!bar || !status) return;

        const interval = setInterval(() => {
            fetch(endpoint)
                .then(r => r.json())
                .then(data => {
                    let percent = Math.round(data.percent || 0);
                    bar.style.width = percent + "%";
                    bar.textContent = percent + "%";
                    status.textContent = "Imported " + (data.completed || 0) + " of " + (data.total || "?") + " posts.";

                    if (percent >= 100) {
                        clearInterval(interval);
                        status.textContent = "✅ Import Complete!";
                    }
                })
                .catch(e => {
                    status.textContent = "⚠️ Error polling progress.";
                    clearInterval(interval);
                });
        }, 3000);
    };
});
