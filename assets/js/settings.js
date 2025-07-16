document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ab-shortcode').forEach(shortcode => {
      const copy = () => {
        const text = shortcode.dataset.shortcode;
        navigator.clipboard.writeText(text).then(() => {
          shortcode.classList.add('active');
          setTimeout(() => shortcode.classList.remove('active'), 1500);
        });
      };
  
      shortcode.addEventListener('click', copy);
  
      shortcode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          copy();
        }
      });
    });
  });
  