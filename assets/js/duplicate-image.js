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

      if (PicPilotStudio.enable_filename_generation) {
        createFilenameModal(id, button);
      } else {
        sendDuplicateRequest(id, null, null, button);
      }
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

  //Modal logic
  function createFilenameModal(id, button) {
    if (document.getElementById('picpilot-filename-modal')) return;

    const modal = document.createElement('div');
    modal.id = 'picpilot-filename-modal';
    modal.style.cssText = `
    position: fixed; bottom: 100px; right: 20px; background: #fff;
    border: 1px solid #ccc; padding: 15px; z-index: 9999; width: 300px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3); border-radius: 6px;
  `;
    modal.innerHTML = `
    <strong>Enter filename (no extension):</strong><br>
    <input type="text" id="picpilot-filename-input" style="width: 100%; margin-top: 5px;" /><br><br>
    <button id="picpilot-generate-filename">🧠 Generate with AI</button>
    <button id="picpilot-confirm-filename" style="float: right;">💾 Use filename</button>
  `;
    document.body.appendChild(modal);

    document.getElementById('picpilot-generate-filename').onclick = () => {
      generateFilenameWithAI(id);
    };

    document.getElementById('picpilot-confirm-filename').onclick = () => {
      const filename = document.getElementById('picpilot-filename-input').value.trim();
      document.getElementById('picpilot-filename-modal').remove();
      sendDuplicateRequest(id, null, filename, button);
    };
  }
  //Ajax for filename generation
  function generateFilenameWithAI(attachmentId) {
    const btn = document.getElementById('picpilot-filename-ai');
    btn.disabled = true;
    btn.textContent = "Generating...";

    fetch(PicPilotStudio.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'picpilot_generate_filename',
        attachment_id: attachmentId,
        nonce: PicPilotStudio.nonce
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('picpilot-filename-input').value = data.data.filename;
          showToast("✅ AI filename generated.");
        } else {
          showToast("⚠️ " + (data.data?.message || "Error generating filename"), true);
        }
      })
      .catch(() => {
        showToast("⚠️ Request failed", true);
      })
      .finally(() => {
        btn.disabled = false;
        btn.textContent = "🧠 AI Filename";
      });
  }



});
