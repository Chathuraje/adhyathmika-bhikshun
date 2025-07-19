document.addEventListener('DOMContentLoaded', () => {
    const audio = document.getElementById('ab-audio');
    const iconBtn = document.getElementById('ab-audio-btn');
    const source = document.getElementById('ab-audio-source');
    const wrapper = document.getElementById('ab-audio-note');
    let hasPlayed = false;

    // Get custom URL from shortcode, if provided
    const customUrl = wrapper?.dataset?.url;

    // Set source to shortcode URL or fallback based on domain
    if (customUrl) {
        source.src = customUrl;
    } else {
        const hostname = window.location.hostname;
        source.src = hostname.endsWith('.lk')
            ? "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-Sinhala.mp3"
            : "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-English.mp3";
    }

    audio.load();

    // Autoplay fallback
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

    addListeners();
});
