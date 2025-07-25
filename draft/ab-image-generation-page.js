document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ab-generate-image-form');
    const resultContainer = document.getElementById('ab-image-result');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const prompt = document.getElementById('ab-prompt').value;
        const nonce = document.getElementById('ab_generate_image_nonce').value;

        resultContainer.innerHTML = 'Generating image...';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'ab_generate_image',
                nonce: nonce,
                prompt: prompt,
                settings: JSON.stringify({}) // Replace or add additional settings as needed
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Image generated successfully:', data);
                resultContainer.innerHTML = `<img src="${data.image_url}" alt="Generated Image" style="max-width:100%; height:auto;">`;
            } else {
                const message = data?.data?.message || 'Unknown error occurred.';
                resultContainer.innerHTML = `<p style="color:red;">Error: ${message}</p>`;
            }
        })
        .catch(error => {
            resultContainer.innerHTML = `<p style="color:red;">Request failed: ${error.message}</p>`;
        });
    });
});