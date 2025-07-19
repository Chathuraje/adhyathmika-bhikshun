// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Get the current hostname and full URL path (path + query string + hash)
    const hostname = window.location.hostname;
    const currentPath = window.location.pathname + window.location.search + window.location.hash;

    // Get references to both language switcher links
    const enLink = document.getElementById('lang-en');
    const siLink = document.getElementById('lang-si');

    // Optional: Log warnings if the expected language links are missing
    if (!enLink) console.warn('#lang-en not found in the DOM.');
    if (!siLink) console.warn('#lang-si not found in the DOM.');

    // Set the active language based on the hostname
    if (hostname.includes('adhyathmikabhikshun.lk')) {
        // We're on the Sinhala domain
        if (siLink) {
            siLink.classList.add('active');        // Highlight Sinhala link
            siLink.removeAttribute('href');        // Disable the active link
        }
    } else if (hostname.includes('adhyathmikabhikshun.org') || hostname === 'localhost') {
        // We're on the English domain or local dev
        if (enLink) {
            enLink.classList.add('active');        // Highlight English link
            enLink.removeAttribute('href');        // Disable the active link
        }
    }

    // Update inactive link to preserve the current path and improve user experience
    if (enLink && !enLink.classList.contains('active')) {
        enLink.href = 'https://adhyathmikabhikshun.org' + currentPath;
    }

    if (siLink && !siLink.classList.contains('active')) {
        siLink.href = 'https://adhyathmikabhikshun.lk' + currentPath;
    }
});
