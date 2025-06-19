document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.pic-pilot-trim-image').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const attachmentId = this.closest('[data-id]').getAttribute('data-id');
            if (!attachmentId) return;

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'pic_pilot_trim_image',
                    attachment_id: attachmentId
                })
            })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                    const toast = document.createElement('div');
                    toast.textContent = data.message || "Done";
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
`;
                    document.body.appendChild(toast);
                    setTimeout(() => { toast.style.opacity = '1'; }, 10);
                    setTimeout(() => toast.remove(), 4000);

                });
        });
    });
});
