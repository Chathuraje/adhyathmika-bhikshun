<?php
function ab_language_audio_note_shortcode() {
    ob_start();
    ?>
    <div id="custom-audio-note" style="text-align:center;">
        <audio id="my-audio" preload="auto">
            <source id="audio-source" src="" type="audio/mpeg">
        </audio>

        <div id="icon-btn" style="
            color: #3661EE;
            border-radius: 50%;
            font-size: 32px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 10;
        ">▶️</div>
    </div>

    <script>
        const audio = document.getElementById('my-audio');
        const iconBtn = document.getElementById('icon-btn');
        const source = document.getElementById('audio-source');
        let hasPlayed = false;

        const hostname = window.location.hostname;
        if (hostname.endsWith('.lk')) {
            source.src = "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-Sinhala.mp3";
        } else {
            source.src = "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-English.mp3";
        }

        audio.load();

        const tryAutoplay = () => {
            if (!hasPlayed) {
                audio.play().then(() => {
                    hasPlayed = true;
                    removeListeners();
                }).catch(err => {
                    console.log("Autoplay blocked:", err);
                });
            }
        };

        const addListeners = () => {
            document.addEventListener('click', tryAutoplay);
            document.addEventListener('scroll', tryAutoplay);
            document.addEventListener('mousemove', tryAutoplay);
            document.addEventListener('touchstart', tryAutoplay);
        };

        const removeListeners = () => {
            document.removeEventListener('click', tryAutoplay);
            document.removeEventListener('scroll', tryAutoplay);
            document.removeEventListener('mousemove', tryAutoplay);
            document.removeEventListener('touchstart', tryAutoplay);
        };

        iconBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        });

        audio.addEventListener('play', () => iconBtn.textContent = '⏸');
        audio.addEventListener('pause', () => iconBtn.textContent = '▶️');
        audio.addEventListener('ended', () => iconBtn.textContent = '▶️');

        window.addEventListener('load', () => {
            addListeners();
        });
    </script>
    <?php
    return ob_get_clean();
}
