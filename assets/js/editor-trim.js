document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.imgedit-wrap') || document.querySelector('.wp_attachment_details');
    if (!container) return;

    const imageId = new URLSearchParams(window.location.search).get('post');
    if (!imageId) return;

    const btn = document.createElement('button');
    btn.textContent = 'Trim Empty Space';
    btn.className = 'button button-secondary';
    btn.style.marginTop = '10px';

    btn.addEventListener('click', function () {
        btn.textContent = 'Trimming...';

        fetch(PicPilotStudio.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'pic_pilot_trim_image',
                attachment_id: imageId
            })
        })
            .then(res => res.json())
            .then(data => {
                btn.textContent = data.success ? 'Trimmed!' : 'Trim Failed';
                showToast(data.message || 'Unknown response', !data.success);

                setTimeout(() => {
                    window.location.href = window.location.href.split('?')[0] + '?trim_success=1';
                }, 800)

                // Force refresh for all uploaded image previews
                document.querySelectorAll('img[src*="/uploads/"]').forEach(img => {
                    const clean = img.src.split('?')[0];
                    img.src = `${clean}?trimmed=${Date.now()}`;
                });
            })
            .catch(() => {
                btn.textContent = 'Trim Failed';
                showToast('Trim failed — check your connection.', true);
            });
    });

    container.appendChild(btn);

    function showToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.className = 'pic-pilot-toast';
        toast.textContent = message;
        if (isError) toast.classList.add('error');
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => toast.remove(), 4000);
    }
});
