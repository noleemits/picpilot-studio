(function (wp) {
  const media = wp.media;
  if (!media || !media.view || !media.view.Attachment) return;

  const originalDetails = media.view.Attachment.Details;

  media.view.Attachment.Details = originalDetails.extend({
    render: function () {
      originalDetails.prototype.render.apply(this, arguments);

      const duplicateQuick = document.createElement('button');
      duplicateQuick.textContent = 'Duplicate (Quick)';
      duplicateQuick.className = 'button button-secondary';
      duplicateQuick.style.marginTop = '10px';


      const group = document.createElement('div');
      group.className = 'pic-pilot-duplicate-group';
      const container = this.$el.find('.attachment-info')[0];
      if (container) {
        container.appendChild(group);

        const helper = document.createElement('p');
        helper.textContent = "If the duplicated image doesn't appear right away, please try closing and reopening the media modal or reloading your page builder preview.";
        helper.style.fontSize = '12px';
        helper.style.marginTop = '8px';
        helper.style.opacity = '0.8';
        container.appendChild(helper);

        duplicateQuick.addEventListener('click', () => {
          const id = this.model.get('id');

          if (PicPilotStudio.enable_filename_generation) {
            createFilenameModal(id);
          } else {
            sendDuplicateRequest(id, null, null);
          }
        });
      }

      return this;
    }
  });


  //Modal logic
  function createFilenameModal(id, button) {
    if (document.getElementById('picpilot-filename-modal')) return;

    const modal = document.createElement('div');
    modal.id = 'picpilot-filename-modal';
    modal.style.cssText = `
    position: fixed; bottom: 100px; right: 20px; background: #fff;
    border: 1px solid #ccc; padding: 15px; z-index: 9999; width: 320px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3); border-radius: 6px;
    font-family: sans-serif;
  `;

    modal.innerHTML = `
    <h3 style="margin-top: 0; font-size: 16px;">Choose a name for the duplicated file</h3>
    <p style="font-size: 13px; opacity: 0.85; margin-bottom: 10px;">
      You may generate a name with AI, type it manually, or use WordPress’s default automatic naming.
    </p>
    <input type="text" id="picpilot-filename-input" placeholder="Optional custom filename..." style="width: 100%; margin-bottom: 12px; padding: 6px; font-size: 13px;" />
    <div style="display: flex; justify-content: space-between; gap: 6px;">
      <button id="picpilot-filename-ai" style="flex: 1;">🧠 AI Filename</button>
      <button id="picpilot-filename-manual" style="flex: 1;">💾 Use Manual</button>
      <button id="picpilot-filename-auto" style="flex: 1;">🔄 Use Automatic</button>
    </div>
  `;

    document.body.appendChild(modal);

    document.getElementById('picpilot-filename-ai').onclick = () => {
      generateFilenameWithAI(id);
    };

    document.getElementById('picpilot-filename-manual').onclick = () => {
      const filename = document.getElementById('picpilot-filename-input').value.trim();
      closeModal();
      sendDuplicateRequest(id, null, filename || null, button);
    };

    document.getElementById('picpilot-filename-auto').onclick = () => {
      closeModal();
      sendDuplicateRequest(id, null, null, button);
    };
  }

  function closeModal() {
    const modal = document.getElementById('picpilot-filename-modal');
    if (modal) modal.remove();
  }



  function sendDuplicateRequest(id, title, filename) {
    const formData = new FormData();
    formData.append('action', 'pic_pilot_duplicate_image');
    formData.append('attachment_id', id);
    if (title) formData.append('new_title', title);
    if (filename) formData.append('new_filename', filename);

    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('✅ Image duplicated successfully! You may need to reload.');
        } else {
          showToast('❌ ' + (res.data?.message || 'Duplication failed.'), true);
        }
      })
      .catch(() => {
        showToast('❌ Request failed. Check your connection.', true);
      });
  }

  function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.className = 'pic-pilot-toast';
    if (isError) toast.classList.add('error');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('show');
    }, 10);
    setTimeout(() => {
      toast.remove();
    }, 5000);
  }


})(window.wp);
