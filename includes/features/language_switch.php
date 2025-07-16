<?php

// Add the [language_switch] shortcode only if enabled
add_action('init', function () {
    if (get_option('ab_language_switch_enabled', true)) {
        add_shortcode('language_switch', 'render_language_switch_shortcode');
    }
});


function render_language_switch_shortcode()
{
    ob_start();
?>
    <div class="lang-toggle-wrap" style="text-align: center; margin: 20px 0;">
        <div class="lang-toggle" style="margin: auto;">
            <a id="lang-en" class="lang-option" href="https://adhyathmikabhikshun.org">English</a>
            <a id="lang-si" class="lang-option" href="https://adhyathmikabhikshun.lk">සිංහල</a>
        </div>
    </div>

    <style>
        .lang-toggle {
            background: #fff;
            border-radius: 30px;
            padding: 5px;
            display: inline-flex;
            gap: 5px;
            font-family: sans-serif;
            font-size: 12px;
            border: 1px solid #ddd;
            align-items: center;
        }

        .lang-option,
        .lang-option:visited {
            padding: 5px 10px;
            border-radius: 20px;
            color: #999;
            text-decoration: none;
            transition: color 0.4s ease, background-color 0.4s ease, font-weight 0.3s ease, transform 0.3s ease;
        }

        .lang-option.active {
            color: #0061cb !important;
            background-color: #e6f0ff;
            font-weight: 500;
            pointer-events: none;
            cursor: default;
        }

        .lang-option:not(.active) {
            cursor: pointer;
        }

        .lang-option:not(.active):hover {
            color: #0061cb !important;
            background-color: #e6f0ff;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const hostname = window.location.hostname;
            const currentPath = window.location.pathname + window.location.search + window.location.hash;

            if (hostname.includes('adhyathmikabhikshun.lk')) {
                const siLink = document.getElementById('lang-si');
                siLink.classList.add('active');
                siLink.removeAttribute('href');
            } else if (hostname.includes('adhyathmikabhikshun.org') || hostname === 'localhost') {
                const enLink = document.getElementById('lang-en');
                enLink.classList.add('active');
                enLink.removeAttribute('href');
            }

            if (!document.getElementById('lang-en').classList.contains('active')) {
                document.getElementById('lang-en').href = 'https://adhyathmikabhikshun.org' + currentPath;
            }

            if (!document.getElementById('lang-si').classList.contains('active')) {
                document.getElementById('lang-si').href = 'https://adhyathmikabhikshun.lk' + currentPath;
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('language_switch', 'render_language_switch_shortcode');
