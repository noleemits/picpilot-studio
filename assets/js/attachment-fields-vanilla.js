/**
 * Attachment Fields AI Tools JavaScript - Vanilla JS Version
 * Handles AI generation in WordPress image edit screens without jQuery dependency
 */

// Utility functions
function $(selector, context = document) {
    return context.querySelectorAll(selector);
}

function $1(selector, context = document) {
    return context.querySelector(selector);
}

(function() {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function ajax(options) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(options.method || 'POST', options.url);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    reject(new Error('HTTP ' + xhr.status));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network error'));
            };
            
            // Convert data object to URL encoded string
            const data = new URLSearchParams(options.data).toString();
            xhr.send(data);
        });
    }

    // Initialize on DOM ready
    ready(function() {
        initAITools();
    });

    // Initialize when DOM changes (for dynamic content)
    let observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (
                        node.querySelector && node.querySelector('.pic-pilot-attachment-ai-tools') ||
                        node.classList && node.classList.contains('pic-pilot-attachment-ai-tools')
                    )) {
                        setTimeout(initAITools, 100);
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Global event delegation for all clicks
    document.addEventListener('click', function(e) {
        console.log('PicPilot: Click detected on:', e.target.tagName, e.target.className);
        
        // Media modal opening
        if (e.target.matches('.add_media, .elementor-control-media__file__edit, .attachment, .media-modal .attachment')) {
            setTimeout(initAITools, 300);
        }
        
        // AI Tools button clicks
        if (e.target.matches('.pic-pilot-launch-modal-btn') || e.target.closest('.pic-pilot-launch-modal-btn')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PicPilot: AI Tools launch button clicked');
            
            const button = e.target.matches('.pic-pilot-launch-modal-btn') ? e.target : e.target.closest('.pic-pilot-launch-modal-btn');
            const attachmentId = button.dataset.attachmentId;
            
            if (!attachmentId) {
                console.error('PicPilot: No attachment ID found');
                return;
            }
            
            openAIToolsModal(attachmentId);
            return;
        }
        
        // Modal close events
        if (e.target.matches('#pic-pilot-ai-modal, .pic-pilot-modal-close')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PicPilot: Modal close clicked');
            closeAIToolsModal();
            return;
        }
        
        // Generate buttons
        if (e.target.matches('.pic-pilot-modal-generate')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PicPilot: Generate button clicked');
            const button = e.target;
            const type = button.dataset.type;
            const attachmentId = button.dataset.attachmentId;
            generateModalMetadata(button, type, attachmentId);
            return;
        }
        
        // Duplicate button
        if (e.target.matches('.pic-pilot-modal-duplicate')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PicPilot: Duplicate button clicked');
            const button = e.target;
            const attachmentId = button.dataset.attachmentId;
            const mode = button.dataset.mode || 'ai';
            duplicateModalImage(button, attachmentId, mode);
            return;
        }
    });

    function initAITools() {
        try {
            const buttons = $('.pic-pilot-launch-modal-btn:not(.bound)');
            
            // Mark buttons as bound
            buttons.forEach(button => {
                button.classList.add('bound');
            });
            
        } catch (error) {
            console.error('PicPilot: Error in initAITools:', error);
        }
    }

    function openAIToolsModal(attachmentId) {
        // Remove existing modal if any
        const existingModal = $1('#pic-pilot-ai-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Remove existing styles
        const existingStyles = $1('#pic-pilot-modal-styles');
        if (existingStyles) {
            existingStyles.remove();
        }

        // Get current image data
        const currentTitle = getImageTitle(attachmentId);
        const currentAlt = getImageAlt(attachmentId);
        const imageUrl = getImageUrl(attachmentId);

        // Create modal HTML
        const modalHtml = `
            <div id="pic-pilot-ai-modal" class="pic-pilot-modal-overlay">
                <div class="pic-pilot-modal-content">
                    <div class="pic-pilot-modal-header">
                        <h2>🤖 AI Tools & Metadata</h2>
                        <button type="button" class="pic-pilot-modal-close">×</button>
                    </div>
                    
                    <div class="pic-pilot-modal-body">
                        <div class="pic-pilot-image-preview">
                            <img src="${imageUrl || ''}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                            <div class="pic-pilot-image-info">
                                <strong>Current Title:</strong> ${currentTitle || 'No title'}<br>
                                <strong>Current Alt Text:</strong> ${currentAlt || 'No alt text'}
                            </div>
                        </div>

                        <div class="pic-pilot-keywords-section">
                            <label for="pic-pilot-modal-keywords">🎯 Keywords (optional):</label>
                            <input type="text" id="pic-pilot-modal-keywords" placeholder="e.g., business person, outdoor scene, product photo">
                            <p class="pic-pilot-help-text">Provide context for better AI results</p>
                        </div>

                        <div class="pic-pilot-tools-grid">
                            <div class="pic-pilot-tool-card">
                                <h3>📝 Generate Title</h3>
                                <p>Create an SEO-friendly title</p>
                                <button type="button" class="button button-primary pic-pilot-modal-generate" data-type="title" data-attachment-id="${attachmentId}">
                                    Generate Title
                                </button>
                                <div class="pic-pilot-modal-status pic-pilot-title-status"></div>
                            </div>

                            <div class="pic-pilot-tool-card">
                                <h3>🏷️ Generate Alt Text</h3>
                                <p>Create accessible descriptions</p>
                                <button type="button" class="button button-primary pic-pilot-modal-generate" data-type="alt" data-attachment-id="${attachmentId}">
                                    ${currentAlt ? 'Regenerate Alt Text' : 'Generate Alt Text'}
                                </button>
                                <div class="pic-pilot-modal-status pic-pilot-alt-status"></div>
                            </div>

                            <div class="pic-pilot-tool-card pic-pilot-duplicate-card">
                                <h3>🔄 Duplicate Image</h3>
                                <p>Create a copy of this image</p>
                                <div class="pic-pilot-duplicate-options">
                                    <button type="button" class="button button-primary pic-pilot-modal-duplicate" data-attachment-id="${attachmentId}" data-mode="ai">
                                        🤖 Duplicate with AI
                                    </button>
                                    <button type="button" class="button button-secondary pic-pilot-modal-duplicate" data-attachment-id="${attachmentId}" data-mode="manual">
                                        ✏️ Duplicate (Manual)
                                    </button>
                                    <button type="button" class="button button-secondary pic-pilot-modal-duplicate" data-attachment-id="${attachmentId}" data-mode="default">
                                        📋 WordPress Default
                                    </button>
                                </div>
                                <div class="pic-pilot-modal-status pic-pilot-duplicate-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Add modal styles
        addModalStyles();

        // Focus keywords input
        const keywordsInput = $1('#pic-pilot-modal-keywords');
        if (keywordsInput) {
            keywordsInput.focus();
        }
        
        console.log('PicPilot: Modal created and added to DOM');
    }

    function addModalStyles() {
        if ($1('#pic-pilot-modal-styles')) return;

        const styles = `
            <style id="pic-pilot-modal-styles">
                .pic-pilot-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 999999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    box-sizing: border-box;
                }

                .pic-pilot-modal-content {
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    max-width: 700px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                }

                .pic-pilot-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #ddd;
                    background: #2271b1;
                    color: #fff;
                    border-radius: 8px 8px 0 0;
                }

                .pic-pilot-modal-header h2 {
                    margin: 0;
                    font-size: 18px;
                }

                .pic-pilot-modal-close {
                    background: none;
                    border: none;
                    color: #fff;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .pic-pilot-modal-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                }

                .pic-pilot-modal-body {
                    padding: 20px;
                }

                .pic-pilot-image-preview {
                    text-align: center;
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #f9f9f9;
                    border-radius: 6px;
                }

                .pic-pilot-image-info {
                    margin-top: 10px;
                    font-size: 13px;
                    color: #666;
                }

                .pic-pilot-keywords-section {
                    margin-bottom: 25px;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 6px;
                }

                .pic-pilot-keywords-section label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                }

                .pic-pilot-keywords-section input {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                    box-sizing: border-box;
                }

                .pic-pilot-help-text {
                    margin: 8px 0 0 0;
                    font-size: 12px;
                    color: #666;
                }

                .pic-pilot-tools-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }

                .pic-pilot-tool-card {
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    padding: 15px;
                    text-align: center;
                }

                .pic-pilot-duplicate-card {
                    grid-column: 1 / -1;
                    background: #f0f8f0;
                    border-color: #00a32a;
                }

                .pic-pilot-duplicate-options {
                    display: flex;
                    gap: 8px;
                    flex-wrap: wrap;
                    justify-content: center;
                }

                .pic-pilot-duplicate-options .button {
                    flex: 1;
                    min-width: 120px;
                    font-size: 12px;
                    padding: 6px 10px;
                }

                .pic-pilot-tool-card h3 {
                    margin: 0 0 8px 0;
                    font-size: 16px;
                    color: #333;
                }

                .pic-pilot-tool-card p {
                    margin: 0 0 15px 0;
                    font-size: 13px;
                    color: #666;
                }

                .pic-pilot-modal-status {
                    margin-top: 10px;
                    padding: 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    display: none;
                }

                .pic-pilot-modal-status.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                    display: block;
                }

                .pic-pilot-modal-status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                    display: block;
                }

                .pic-pilot-modal-status.info {
                    background: #cce7ff;
                    color: #055160;
                    border: 1px solid #b3d7ff;
                    display: block;
                }

                @media (max-width: 768px) {
                    .pic-pilot-tools-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .pic-pilot-duplicate-card {
                        grid-column: 1;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    // Escape key handling
    document.addEventListener('keyup', function(e) {
        if (e.keyCode === 27 && $1('#pic-pilot-ai-modal')) { // Escape key
            console.log('PicPilot: Escape key pressed');
            closeAIToolsModal();
        }
    });

    function closeAIToolsModal() {
        const modal = $1('#pic-pilot-ai-modal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 200);
        }
    }

    // Helper functions for getting image data
    function getImageTitle(attachmentId) {
        const titleSelectors = [
            `#attachment_${attachmentId}_title`,
            'input[name*="[post_title]"]',
            '#attachment-details-title',
            'input[data-setting="title"]',
            '.setting[data-setting="title"] input',
            'input.attachment-title',
            '#title'
        ];

        for (const selector of titleSelectors) {
            const field = $1(selector);
            if (field) {
                return field.value || '';
            }
        }

        const titleDisplay = $1('.attachment-details .title');
        if (titleDisplay) {
            return titleDisplay.textContent.trim();
        }

        return '';
    }

    function getImageAlt(attachmentId) {
        const altSelectors = [
            `#attachment_${attachmentId}_alt`,
            'input[name*="[image_alt]"]',
            '#attachment-details-alt-text',
            'input[data-setting="alt"]',
            '.setting[data-setting="alt"] input',
            'input.attachment-alt'
        ];

        for (const selector of altSelectors) {
            const field = $1(selector);
            if (field) {
                return field.value || '';
            }
        }

        return '';
    }

    function getImageUrl(attachmentId) {
        const img = $1(`.attachment-preview img, .details-image img, .media-modal img[data-attachment-id="${attachmentId}"]`);
        if (img) {
            return img.src || img.dataset.fullSrc || '';
        }

        const urlField = $1('input[name*="[url]"], #attachment-details-copy-link');
        if (urlField) {
            const url = urlField.value;
            if (url && url.includes('/uploads/')) {
                return url;
            }
        }

        return '';
    }

    function getImageFilename(attachmentId) {
        // Try to get filename from various sources
        const filenameSelectors = [
            `#attachment_${attachmentId}_filename`,
            'input[name*="[filename]"]',
            '.filename',
            '.attachment-details .filename'
        ];

        for (const selector of filenameSelectors) {
            const field = $1(selector);
            if (field) {
                return field.value || field.textContent || '';
            }
        }

        // Fallback: extract from image URL
        const imageUrl = getImageUrl(attachmentId);
        if (imageUrl) {
            const urlParts = imageUrl.split('/');
            const filename = urlParts[urlParts.length - 1];
            // Remove query parameters if any
            return filename.split('?')[0];
        }

        return '';
    }

    async function generateModalMetadata(button, type, attachmentId) {
        const keywords = $1('#pic-pilot-modal-keywords').value.trim();
        const statusEl = $1(`.pic-pilot-${type}-status`);
        
        const originalText = button.textContent;

        // Update UI
        button.disabled = true;
        button.textContent = 'Generating...';
        showModalStatus(statusEl, `Generating ${type}...`, 'info');

        try {
            const response = await ajax({
                url: window.picPilotAttachment?.ajax_url || '/wp-admin/admin-ajax.php',
                method: 'POST',
                data: {
                    action: 'picpilot_generate_metadata',
                    nonce: window.picPilotAttachment?.nonce || '',
                    attachment_id: attachmentId,
                    type: type,
                    keywords: keywords
                }
            });

            if (response.success) {
                // Update the corresponding WordPress field
                updateWordPressField(type, response.data.result, attachmentId);
                
                // Update the modal preview
                updateModalPreview(type, response.data.result);
                
                showModalStatus(statusEl, `✅ ${capitalizeFirst(type)} generated successfully!`, 'success');
                
                // Show fallback message if applicable
                if (response.data.used_fallback && response.data.fallback_message) {
                    setTimeout(() => {
                        showModalStatus(statusEl, `⚠️ ${response.data.fallback_message}`, 'info');
                    }, 2000);
                }

                // Update button text for alt text
                if (type === 'alt') {
                    button.textContent = 'Regenerate Alt Text';
                }
            } else {
                showModalStatus(statusEl, `❌ Failed to generate ${type}: ${response.data}`, 'error');
            }
        } catch (error) {
            console.error('Generation error:', error);
            showModalStatus(statusEl, `❌ Generation failed: ${error.message || 'Unknown error'}`, 'error');
        } finally {
            button.disabled = false;
            if (button.textContent === 'Generating...') {
                button.textContent = originalText;
            }
        }
    }

    async function duplicateModalImage(button, attachmentId, mode = 'ai') {
        const keywords = $1('#pic-pilot-modal-keywords').value.trim();
        const statusEl = $1('.pic-pilot-duplicate-status');
        
        const originalText = button.textContent;

        // Handle manual mode - ask for filename
        if (mode === 'manual') {
            const currentFilename = getImageFilename(attachmentId);
            const newFilename = prompt(
                'Enter a new filename for the duplicate (without extension):', 
                currentFilename ? currentFilename.replace(/\.[^/.]+$/, '') + '-copy' : 'image-copy'
            );
            
            if (!newFilename || newFilename.trim() === '') {
                showModalStatus(statusEl, '❌ Duplication cancelled - filename required', 'error');
                return;
            }
            
            // Store the custom filename for use below
            button._customFilename = newFilename.trim();
        }

        // Update UI
        button.disabled = true;
        button.textContent = 'Duplicating...';
        
        let statusMessage = 'Creating duplicate image...';
        if (mode === 'ai') {
            statusMessage = 'Creating duplicate with AI metadata...';
        } else if (mode === 'manual') {
            statusMessage = 'Creating duplicate with custom filename...';
        } else if (mode === 'default') {
            statusMessage = 'Creating WordPress default duplicate...';
        }
        
        showModalStatus(statusEl, statusMessage, 'info');

        try {
            let ajaxData = {
                action: 'pic_pilot_duplicate_image',
                nonce: window.picPilotAttachment?.nonce || '',
                attachment_id: attachmentId,
                keywords: keywords
            };

            // Set metadata generation based on mode
            if (mode === 'ai') {
                ajaxData.new_title = 'generate';
                ajaxData.new_alt = 'generate';
                // Keywords only used for AI mode
            } else if (mode === 'manual') {
                // Keep original metadata, use custom filename
                ajaxData.new_title = '';
                ajaxData.new_alt = '';
                ajaxData.new_filename = button._customFilename || '';
                ajaxData.keywords = ''; // Don't use keywords for manual
            } else if (mode === 'default') {
                // WordPress default behavior (copy suffix)
                ajaxData.new_title = '';
                ajaxData.new_alt = '';
                ajaxData.new_filename = '';
                ajaxData.keywords = ''; // Don't use keywords for default
            }

            const response = await ajax({
                url: window.picPilotAttachment?.ajax_url || '/wp-admin/admin-ajax.php',
                method: 'POST',
                data: ajaxData
            });

            if (response.success) {
                let successMessage = `✅ Image duplicated successfully! New image ID: ${response.data.id}`;
                
                if (mode === 'ai') {
                    successMessage = `🤖 AI duplicate created! ID: ${response.data.id}`;
                } else if (mode === 'manual') {
                    successMessage = `✏️ Manual duplicate created! ID: ${response.data.id}`;
                } else if (mode === 'default') {
                    successMessage = `📋 Default duplicate created! ID: ${response.data.id}`;
                }
                
                showModalStatus(statusEl, successMessage, 'success');
                
                // Show a link to the new image and reload advice
                setTimeout(() => {
                    const editUrl = `post.php?post=${response.data.id}&action=edit`;
                    const reloadAdvice = mode !== 'default' ? 
                        '<br><small>💡 You may need to reload the page to see the new image in your media library.</small>' : 
                        '<br><small>💡 You may need to reload the page to see the new image.</small>';
                    
                    showModalStatus(statusEl, 
                        `✅ Duplicate created! <a href="${editUrl}" target="_blank">View new image →</a>${reloadAdvice}`, 
                        'success'
                    );
                }, 1000);
            } else {
                showModalStatus(statusEl, `❌ Duplication failed: ${response.data?.message || 'Unknown error'}`, 'error');
            }
        } catch (error) {
            console.error('Duplication error:', error);
            showModalStatus(statusEl, `❌ Duplication failed: ${error.message || 'Unknown error'}`, 'error');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
            // Clean up custom filename
            if (button._customFilename) {
                delete button._customFilename;
            }
        }
    }

    function updateWordPressField(type, value, attachmentId) {
        if (type === 'alt') {
            const altSelectors = [
                `#attachment_${attachmentId}_alt`,
                'input[name*="[image_alt]"]',
                '#attachment-details-alt-text',
                'input[data-setting="alt"]',
                '.setting[data-setting="alt"] input',
                'input.attachment-alt'
            ];

            for (const selector of altSelectors) {
                const field = $1(selector);
                if (field) {
                    field.value = value;
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    break;
                }
            }

        } else if (type === 'title') {
            const titleSelectors = [
                `#attachment_${attachmentId}_title`,
                'input[name*="[post_title]"]',
                '#attachment-details-title',
                'input[data-setting="title"]',
                '.setting[data-setting="title"] input',
                'input.attachment-title',
                '#title'
            ];

            for (const selector of titleSelectors) {
                const field = $1(selector);
                if (field) {
                    field.value = value;
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    break;
                }
            }
        }
    }

    function updateModalPreview(type, value) {
        const infoEl = $1('.pic-pilot-image-info');
        if (!infoEl) return;
        
        if (type === 'title') {
            infoEl.innerHTML = infoEl.innerHTML.replace(/(<strong>Current Title:<\/strong>)[^<]*(<br>)/, `$1 ${value}$2`);
        } else if (type === 'alt') {
            infoEl.innerHTML = infoEl.innerHTML.replace(/(<strong>Current Alt Text:<\/strong>)[^<]*$/, `$1 ${value}`);
        }
    }

    function showModalStatus(element, message, type) {
        if (!element) return;
        
        element.innerHTML = message;
        element.className = `pic-pilot-modal-status ${type}`;
        element.style.display = 'block';
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

})();