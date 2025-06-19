document.addEventListener('DOMContentLoaded', function () {
  function sendDuplicateRequest(attachmentId, newTitle = null, newFilename = null, button) {
    const formData = new FormData();
    formData.append('action', 'pic_pilot_duplicate_image');
    formData.append('attachment_id', attachmentId);
    if (newTitle) {
      formData.append('new_title', newTitle);
    }
    if (newFilename) {
      formData.append('new_filename', newFilename);
    }

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

  document.querySelectorAll('.pic-pilot-duplicate-image').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const id = button.dataset.id;
      if (!id) return;
      sendDuplicateRequest(id, null, null, button);
    });
  });

  document.querySelectorAll('.pic-pilot-duplicate-image-prompt').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const id = button.dataset.id;
      if (!id) return;
      const newTitle = prompt('Enter a new title for the duplicate (optional):');
      const newFilename = prompt('Enter a new file name (without extension, optional):');
      sendDuplicateRequest(id, newTitle, newFilename, button);
    });
  });

  function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.className = 'pic-pilot-toast';
    toast.textContent = message;
    if (isError) toast.classList.add('error');
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10); // trigger transition
    setTimeout(() => toast.remove(), 5000);;
  }

});
