// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Get current hostname and full URL path
    const hostname = window.location.hostname;
    const currentPath = window.location.pathname + window.location.search + window.location.hash;

    // Get references to both language links
    const enLink = document.getElementById('lang-en');
    const siLink = document.getElementById('lang-si');

    // Set active language based on hostname
    if (hostname.includes('adhyathmikabhikshun.lk')) {
        siLink.classList.add('active');       // Highlight Sinhala
        siLink.removeAttribute('href');       // Disable link
    } else if (hostname.includes('adhyathmikabhikshun.org') || hostname === 'localhost') {
        enLink.classList.add('active');       // Highlight English
        enLink.removeAttribute('href');       // Disable link
    }

    // Update inactive link to include current path for better UX
    if (!enLink.classList.contains('active')) {
        enLink.href = 'https://adhyathmikabhikshun.org' + currentPath;
    }

    if (!siLink.classList.contains('active')) {
        siLink.href = 'https://adhyathmikabhikshun.lk' + currentPath;
    }
});
