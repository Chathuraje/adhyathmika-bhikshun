// Wait until the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    const audio = document.getElementById('ab-audio');
    const iconBtn = document.getElementById('ab-audio-btn');
    const source = document.getElementById('ab-audio-source');
    let hasPlayed = false;

    // Set audio source based on domain extension (.lk = Sinhala)
    const hostname = window.location.hostname;
    if (hostname.endsWith('.lk')) {
        source.src = "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-Sinhala.mp3";
    } else {
        source.src = "https://cdn.adhyathmikabhikshun.org/wp-content/uploads/2025/07/Nature-Audio-English.mp3";
    }

    // Load the selected source into the audio element
    audio.load();

    // Try autoplaying the audio when user interacts (browsers block autoplay otherwise)
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

    // Add event listeners to detect user interaction
    const addListeners = () => {
        document.addEventListener('click', tryAutoplay);
        document.addEventListener('scroll', tryAutoplay);
        document.addEventListener('mousemove', tryAutoplay);
        document.addEventListener('touchstart', tryAutoplay);
    };

    // Remove listeners after audio successfully plays
    const removeListeners = () => {
        document.removeEventListener('click', tryAutoplay);
        document.removeEventListener('scroll', tryAutoplay);
        document.removeEventListener('mousemove', tryAutoplay);
        document.removeEventListener('touchstart', tryAutoplay);
    };

    // Toggle play/pause on button click
    iconBtn.addEventListener('click', () => {
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    });

    // Update icon based on audio state
    audio.addEventListener('play', () => iconBtn.textContent = '⏸');
    audio.addEventListener('pause', () => iconBtn.textContent = '▶️');
    audio.addEventListener('ended', () => iconBtn.textContent = '▶️');

    // Begin listening for interaction on page load
    addListeners();
});
