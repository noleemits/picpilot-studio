document.addEventListener('click', function (e) {
    const btn = e.target.closest('.picpilot-generate-meta');
    if (!btn || !window.picPilotStudio) return;

    const id = btn.getAttribute('data-id');
    const type = btn.getAttribute('data-type');
    const keywords = btn.closest('tr').querySelector('.picpilot-keywords')?.value || '';

    btn.textContent = 'Generating...';
    btn.disabled = true;

    // Debug log
    console.log('Sending metadata request:', {
        id, type, keywords,
        keywordsElement: btn.closest('tr').querySelector('.picpilot-keywords')
    });

    fetch(window.picPilotStudio.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'picpilot_generate_metadata',
            nonce: window.picPilotStudio.nonce,
            attachment_id: id,
            type: type,
            keywords: keywords
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                let toastMessage = (type === 'alt' ? 'Alt' : 'Title') + ' generated ✔: ' + result.data.result;
                
                // Show fallback message if used
                if (result.data.used_fallback && result.data.fallback_message) {
                    toastMessage = result.data.fallback_message;
                }
                
                showToast(toastMessage);
                
                // Update button text for alt text (now it exists, so should show "Regenerate")
                if (type === 'alt') {
                    btn.textContent = 'Regenerate Alt Text';
                } else {
                    btn.textContent = 'Generate Title';
                }
            } else {
                showToast('⚠ Failed: ' + result.data, true);
                // Keep original button text on failure
                btn.textContent = btn.textContent.replace('Generating...', 
                    type === 'alt' ? 
                        (btn.textContent.includes('Regenerate') ? 'Regenerate Alt Text' : 'Generate Alt Text') : 
                        'Generate Title'
                );
            }
        })
        .catch(err => {
            showToast('AJAX error: ' + err, true);
            // Restore original button text on error
            btn.textContent = type === 'alt' ? 
                (btn.textContent.includes('Regenerate') ? 'Regenerate Alt Text' : 'Generate Alt Text') : 
                'Generate Title';
        })
        .finally(() => {
            btn.disabled = false;
        });
});



// Function to add the "Generate Metadata" button to media row actions
function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.textContent = message;

    styleToast(toast);
    if (isError) {
        toast.style.background = '#dc3232'; // WordPress error red
    }

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function styleToast(toast) {
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #0073aa;
        color: #fff;
        padding: 10px 15px;
        border-radius: 6px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-size: 14px;
        transition: opacity 0.3s ease;
        opacity: 0;
        pointer-events: none;
    `;
}
