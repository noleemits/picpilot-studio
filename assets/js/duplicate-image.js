document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.pic-pilot-duplicate-image').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const id = button.dataset.id;
      if (!id) return;

      if (PicPilotStudio.enable_filename_generation || PicPilotStudio.enable_title_generation_on_duplicate || PicPilotStudio.enable_alt_generation_on_duplicate) {
        window.PicPilotFilenameModal?.open(id, (data) => {
          sendDuplicateRequest(id, data.title, data.filename, data.alt, button);
        });
      } else {
        sendDuplicateRequest(id, null, null, null, button);
      }
    });
  });

  function sendDuplicateRequest(attachmentId, newTitle = null, newFilename = null, newAlt = null, button) {
    const formData = new FormData();
    formData.append('action', 'pic_pilot_duplicate_image');
    formData.append('attachment_id', attachmentId);
    formData.append('nonce', PicPilotStudio.nonce);
    if (newTitle) formData.append('new_title', newTitle);
    if (newFilename) formData.append('new_filename', newFilename);
    if (newAlt) formData.append('new_alt', newAlt);

    button.textContent = 'Duplicating...';

    fetch(PicPilotStudio.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(res => res.json())
      .then(response => {
        if (response.success) {
          showToast('✅ Image duplicated successfully!');
          location.reload();
        } else {
          showToast('❌ ' + (response.data?.message || 'Duplication failed.'));
          button.textContent = 'Duplicate';
        }
      })
      .catch(() => {
        showToast('❌ Request failed. Check your connection.');
        button.textContent = 'Duplicate';
      });
  }

  function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.className = 'pic-pilot-toast';
    toast.textContent = message;
    if (isError) toast.classList.add('error');
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => toast.remove(), 5000);
  }
});
