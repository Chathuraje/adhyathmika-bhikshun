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
  
  
// Toggle visibility of shortcode rows based on checkbox state
  document.addEventListener('DOMContentLoaded', function () {
    const toggleMap = {
      'ab_post_order_enabled': 'shortcode-post-order',
      'ab_language_switch_enabled': 'shortcode-language-switch',
      'ab_reading_time_enabled': 'shortcode-reading-time',
      'ab_language_audio_note_enabled': 'shortcode-language-note',
    };

    Object.entries(toggleMap).forEach(([checkboxName, rowId]) => {
      const checkbox = document.querySelector(`input[name="${checkboxName}"]`);
      const row = document.getElementById(rowId);

      if (checkbox && row) {
        checkbox.addEventListener('change', () => {
          row.style.display = checkbox.checked ? '' : 'none';
        });
      }
    });
  });
