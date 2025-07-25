// Defines window.PicPilotFilenameModal.open(id, callback)

window.PicPilotFilenameModal = {
    open(id, callback) {
        if (document.getElementById('picpilot-duplication-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'picpilot-duplication-modal';
        modal.style.cssText = `
      position: fixed; bottom: 100px; right: 20px; background: #fff;
      border: 1px solid #ccc; padding: 15px; z-index: 9999; width: 340px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3); border-radius: 6px; font-family: sans-serif;
    `;

        modal.innerHTML = `
      <h3 style="margin-top: 0; font-size: 16px;">Customize duplication metadata</h3>

      <div style="margin-bottom: 15px;">
        <strong>Title</strong><br>
        <label><input type="radio" name="dup-title" value="auto" checked> Auto</label><br>
        <label><input type="radio" name="dup-title" value="generate"> Generate with AI</label><br>
        <label><input type="radio" name="dup-title" value="manual"> Enter manually:</label><br>
        <input type="text" id="dup-title-manual" style="width: 100%; display: none; margin-top: 5px;" />
      </div>

      <div style="margin-bottom: 15px;">
        <strong>Alt Text</strong><br>
        <label><input type="radio" name="dup-alt" value="auto" checked> Auto</label><br>
        <label><input type="radio" name="dup-alt" value="generate"> Generate with AI</label><br>
        <label><input type="radio" name="dup-alt" value="manual"> Enter manually:</label><br>
        <input type="text" id="dup-alt-manual" style="width: 100%; display: none; margin-top: 5px;" />
      </div>

      <div style="margin-bottom: 15px;">
        <strong>Filename</strong><br>
        <label><input type="radio" name="dup-filename" value="auto" checked> Auto</label><br>
        <label><input type="radio" name="dup-filename" value="generate"> Generate with AI</label><br>
        <label><input type="radio" name="dup-filename" value="manual"> Enter manually:</label><br>
        <input type="text" id="dup-filename-manual" style="width: 100%; display: none; margin-top: 5px;" />
      </div>

      <button id="picpilot-dup-confirm" style="width: 100%;">✅ Duplicate Image</button>
    `;

        document.body.appendChild(modal);

        const updateFieldVisibility = (groupName, inputId) => {
            document.querySelectorAll(`input[name='${groupName}']`).forEach(input => {
                input.addEventListener('change', () => {
                    const field = document.getElementById(inputId);
                    if (!field) return;
                    field.style.display = input.value === 'manual' && input.checked ? 'block' : 'none';
                });
            });
        };

        updateFieldVisibility('dup-title', 'dup-title-manual');
        updateFieldVisibility('dup-alt', 'dup-alt-manual');
        updateFieldVisibility('dup-filename', 'dup-filename-manual');

        document.getElementById('picpilot-dup-confirm').onclick = () => {
            const titleVal = getValueFromRadio('dup-title', 'dup-title-manual');
            const altVal = getValueFromRadio('dup-alt', 'dup-alt-manual');
            const fileVal = getValueFromRadio('dup-filename', 'dup-filename-manual');

            modal.remove();
            callback({ title: titleVal, alt: altVal, filename: fileVal });
        };

        function getValueFromRadio(groupName, manualId) {
            const selected = document.querySelector(`input[name='${groupName}']:checked`).value;
            if (selected === 'manual') {
                const input = document.getElementById(manualId).value.trim();
                return input || null;
            }
            return selected === 'generate' ? 'generate' : null; // 'auto' => null
        }
    }
};
