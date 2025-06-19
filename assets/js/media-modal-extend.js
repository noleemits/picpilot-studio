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

      const duplicatePrompt = document.createElement('button');
      duplicatePrompt.textContent = 'Duplicate + Title';
      duplicatePrompt.className = 'button button-secondary';
      duplicatePrompt.style.marginTop = '10px';
      duplicatePrompt.style.marginLeft = '5px';

      const group = document.createElement('div');
      group.className = 'pic-pilot-duplicate-group';
      group.appendChild(duplicateQuick);
      group.appendChild(duplicatePrompt);

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
          sendDuplicateRequest(this.model.get('id'), null, null);
        });

        duplicatePrompt.addEventListener('click', () => {
          const newTitle = prompt('Enter a new title for the duplicate (optional):');
          const newFilename = prompt('Enter a new file name (without extension, optional):');
          sendDuplicateRequest(this.model.get('id'), newTitle, newFilename);
        });
      }

      return this;
    }
  });

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
