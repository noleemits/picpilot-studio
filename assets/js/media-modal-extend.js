(function (wp) {
  const media = wp.media;
  if (!media || !media.view || !media.view.Attachment) return;

  const originalDetails = media.view.Attachment.Details;

  media.view.Attachment.Details = originalDetails.extend({
    render: function () {
      originalDetails.prototype.render.apply(this, arguments);

      const duplicateQuick = document.createElement('button');
      duplicateQuick.textContent = 'Duplicate (Smart)';
      duplicateQuick.className = 'button button-secondary';
      duplicateQuick.style.marginTop = '10px';

      const group = document.createElement('div');
      group.className = 'pic-pilot-duplicate-group';
      group.appendChild(duplicateQuick);

      const container = this.$el.find('.attachment-info')[0];
      if (container) {
        container.appendChild(group);

        const helper = document.createElement('p');
        helper.textContent = "If the duplicated image doesn't appear right away, try closing and reopening the modal.";
        helper.style.fontSize = '12px';
        helper.style.marginTop = '8px';
        helper.style.opacity = '0.8';
        container.appendChild(helper);

        duplicateQuick.addEventListener('click', () => {
          const id = this.model.get('id');

          if (PicPilotStudio.enable_filename_generation || PicPilotStudio.enable_title_generation_on_duplicate || PicPilotStudio.enable_alt_generation_on_duplicate) {
            window.PicPilotFilenameModal?.open(id, (data) => {
              sendDuplicateRequest(id, data.title, data.filename, data.alt);
            });
          } else {
            sendDuplicateRequest(id, null, null, null);
          }
        });
      }

      return this;
    }
  });

  function sendDuplicateRequest(id, title, filename, alt) {
    const formData = new FormData();
    formData.append('action', 'pic_pilot_duplicate_image');
    formData.append('attachment_id', id);
    if (title) formData.append('new_title', title);
    if (filename) formData.append('new_filename', filename);
    if (alt) formData.append('new_alt', alt);

    fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('✅ Image duplicated successfully!');
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
