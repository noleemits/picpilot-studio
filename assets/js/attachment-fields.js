/**
 * Attachment Fields AI Tools JavaScript
 * Handles AI generation in WordPress image edit screens
 */

// Immediate test to verify script loading
console.log('🖼️ PicPilot attachment-fields.js is loading...');
console.log('🖼️ Location:', window.location.href);
console.log('🖼️ jQuery available:', typeof jQuery !== 'undefined');

(function($) {
    'use strict';

    // Add error handling
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        if (msg.includes('pic-pilot') || msg.includes('🖼️')) {
            console.error('🖼️ PicPilot Error:', msg, 'at', url, ':', lineNo);
        }
        return false;
    };

    $(document).ready(function() {
        try {
            console.log('🖼️ Pic Pilot Attachment Fields initialized');
            console.log('🖼️ jQuery version:', $.fn.jquery);
            console.log('🖼️ Current URL:', window.location.href);
            initAITools();
        } catch (error) {
            console.error('🖼️ Error during initialization:', error);
        }
    });

    // Initialize when media modal opens or content changes
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).find('.pic-pilot-attachment-ai-tools').length) {
            console.log('🖼️ AI Tools detected in new content, initializing...');
            setTimeout(initAITools, 100); // Small delay to ensure DOM is ready
        }
    });
    
    // More aggressive detection for when attachment details show
    $(document).on('click', '.attachment, .media-modal .attachment', function() {
        setTimeout(function() {
            console.log('🖼️ Attachment clicked, checking for AI Tools...');
            initAITools();
        }, 300);
    });

    // Also listen for WordPress media events
    $(document).on('click', '.media-modal', function() {
        setTimeout(initAITools, 200);
    });

    // More comprehensive WordPress media events
    if (typeof wp !== 'undefined' && wp.media) {
        // Hook into media modal opening
        $(document).on('click', '.add_media, .elementor-control-media__file__edit', function() {
            setTimeout(function() {
                console.log('🖼️ Media modal opened via button click, initializing...');
                initAITools();
            }, 500);
        });

        // Hook into media frame events if available
        wp.media.view.Modal && (function() {
            const originalOpen = wp.media.view.Modal.prototype.open;
            wp.media.view.Modal.prototype.open = function() {
                const result = originalOpen.apply(this, arguments);
                setTimeout(initAITools, 300);
                return result;
            };
        })();

        // Hook into attachment details rendering
        wp.media.view.Attachment && wp.media.view.Attachment.Details && (function() {
            const originalRender = wp.media.view.Attachment.Details.prototype.render;
            wp.media.view.Attachment.Details.prototype.render = function() {
                const result = originalRender.apply(this, arguments);
                setTimeout(initAITools, 100);
                return result;
            };
        })();
    }

    function initAITools() {
        try {
            const $buttons = $('.pic-pilot-launch-modal-btn:not(.bound)');
            console.log('🖼️ Found', $buttons.length, 'unbound AI Tools buttons');
            
            if ($buttons.length === 0) {
                console.log('🖼️ No AI Tools buttons found. Looking for containers...');
                console.log('🖼️ AI Tools containers:', $('.pic-pilot-attachment-ai-tools').length);
                
                // Debug: Check what's in the media sidebar
                console.log('🖼️ Media sidebar elements:', $('.media-sidebar').length);
                console.log('🖼️ Attachment details elements:', $('.attachment-details').length);
                console.log('🖼️ All buttons in sidebar:', $('.media-sidebar button, .attachment-details button').length);
            }
            
            // Bind click events to modal launch buttons
            $buttons.addClass('bound').on('click', function(e) {
                try {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖼️ AI Tools button clicked!');
                    const $button = $(this);
                    const attachmentId = $button.data('attachment-id');
                    console.log('🖼️ Attachment ID:', attachmentId);
                    
                    if (!attachmentId) {
                        console.error('🖼️ No attachment ID found on button');
                        return;
                    }
                    
                    openAIToolsModal(attachmentId);
                } catch (clickError) {
                    console.error('🖼️ Error in button click handler:', clickError);
                }
            });
        } catch (error) {
            console.error('🖼️ Error in initAITools:', error);
        }
    }

    function openAIToolsModal(attachmentId) {
        console.log('🖼️ Opening AI Tools modal for attachment:', attachmentId);
        
        // Remove existing modal if any
        $('#pic-pilot-ai-modal').remove();
        
        // Remove any existing modal styles to prevent conflicts
        $('#pic-pilot-modal-styles').remove();

        // Get current image data
        const currentTitle = getImageTitle(attachmentId);
        const currentAlt = getImageAlt(attachmentId);
        const imageUrl = getImageUrl(attachmentId);
        
        console.log('🖼️ Modal data:', { currentTitle, currentAlt, imageUrl });
        
        // If we can't get image data, show a warning but continue
        if (!imageUrl) {
            console.warn('🖼️ Could not retrieve image URL, using placeholder');
        }

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
                            <img src="${imageUrl}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
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
                                <p>Create a copy with AI metadata</p>
                                <button type="button" class="button button-secondary pic-pilot-modal-duplicate" data-attachment-id="${attachmentId}">
                                    🔄 Duplicate with AI
                                </button>
                                <div class="pic-pilot-modal-status pic-pilot-duplicate-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to page
        $('body').append(modalHtml);

        // Add modal styles
        addModalStyles();

        // Bind modal events
        bindModalEvents();

        // Focus keywords input
        $('#pic-pilot-modal-keywords').focus();
    }

    function bindModalEvents() {
        // Close modal events
        $('.pic-pilot-modal-close, .pic-pilot-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                closeAIToolsModal();
            }
        });

        // Prevent modal content clicks from closing modal
        $('.pic-pilot-modal-content').on('click', function(e) {
            e.stopPropagation();
        });

        // Generate buttons
        $('.pic-pilot-modal-generate').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const type = $button.data('type');
            const attachmentId = $button.data('attachment-id');
            generateModalMetadata($button, type, attachmentId);
        });

        // Duplicate button
        $('.pic-pilot-modal-duplicate').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const attachmentId = $button.data('attachment-id');
            duplicateModalImage($button, attachmentId);
        });

        // Escape key to close
        $(document).on('keyup.pic-pilot-modal', function(e) {
            if (e.keyCode === 27) { // Escape key
                closeAIToolsModal();
            }
        });
    }

    function closeAIToolsModal() {
        $('#pic-pilot-ai-modal').fadeOut(200, function() {
            $(this).remove();
        });
        $(document).off('keyup.pic-pilot-modal');
    }

    function addModalStyles() {
        if ($('#pic-pilot-modal-styles').length) return;

        $('head').append(`
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
                }

                .pic-pilot-modal-status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .pic-pilot-modal-status.info {
                    background: #cce7ff;
                    color: #055160;
                    border: 1px solid #b3d7ff;
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
        `);
    }

    async function generateMetadata($button, type, attachmentId) {
        const $container = $button.closest('.pic-pilot-attachment-ai-tools');
        const $keywordsInput = $container.find('.pic-pilot-keywords-input');
        const $status = $container.find(`.pic-pilot-${type}-status`);
        const keywords = $keywordsInput.val().trim();
        
        const originalText = $button.text();

        // Update UI
        $button.prop('disabled', true).text('Generating...');
        showStatus($status, `Generating ${type}...`, 'info');

        try {
            const response = await $.ajax({
                url: picPilotAttachment.ajax_url,
                method: 'POST',
                data: {
                    action: 'picpilot_generate_metadata',
                    nonce: picPilotAttachment.nonce,
                    attachment_id: attachmentId,
                    type: type,
                    keywords: keywords
                }
            });

            if (response.success) {
                // Update the corresponding WordPress field
                updateWordPressField(type, response.data.result, attachmentId);
                
                showStatus($status, `✅ ${capitalizeFirst(type)} generated successfully!`, 'success');
                
                // Show fallback message if applicable
                if (response.data.used_fallback && response.data.fallback_message) {
                    setTimeout(() => {
                        showStatus($status, `⚠️ ${response.data.fallback_message}`, 'info');
                    }, 2000);
                }

                // Update button text for alt text
                if (type === 'alt') {
                    $button.text('Regenerate Alt Text');
                }
            } else {
                showStatus($status, `❌ Failed to generate ${type}: ${response.data}`, 'error');
            }
        } catch (error) {
            console.error('Generation error:', error);
            showStatus($status, `❌ Generation failed: ${error.statusText || 'Unknown error'}`, 'error');
        } finally {
            $button.prop('disabled', false);
            if ($button.text() === 'Generating...') {
                $button.text(originalText);
            }
        }
    }

    async function duplicateImage($button, attachmentId) {
        const $container = $button.closest('.pic-pilot-attachment-ai-tools');
        const $keywordsInput = $container.find('.pic-pilot-keywords-input');
        const $status = $container.find('.pic-pilot-duplicate-status');
        const keywords = $keywordsInput.val().trim();
        
        const originalText = $button.text();

        // Update UI
        $button.prop('disabled', true).text('Duplicating...');
        showStatus($status, 'Creating duplicate image...', 'info');

        try {
            const response = await $.ajax({
                url: picPilotAttachment.ajax_url,
                method: 'POST',
                data: {
                    action: 'pic_pilot_duplicate_image',
                    nonce: picPilotAttachment.nonce,
                    attachment_id: attachmentId,
                    keywords: keywords
                }
            });

            if (response.success) {
                showStatus($status, `✅ Image duplicated successfully! New image ID: ${response.data.id}`, 'success');
                
                // Show a link to the new image if we can
                setTimeout(() => {
                    const editUrl = `post.php?post=${response.data.id}&action=edit`;
                    showStatus($status, `✅ Duplicate created! <a href="${editUrl}" target="_blank">View new image →</a>`, 'success');
                }, 1000);
            } else {
                showStatus($status, `❌ Duplication failed: ${response.data?.message || 'Unknown error'}`, 'error');
            }
        } catch (error) {
            console.error('Duplication error:', error);
            showStatus($status, `❌ Duplication failed: ${error.statusText || 'Unknown error'}`, 'error');
        } finally {
            $button.prop('disabled', false).text(originalText);
        }
    }

    function updateWordPressField(type, value, attachmentId) {
        if (type === 'alt') {
            // Look for alt text field - try multiple selectors for different contexts
            const altSelectors = [
                `#attachment_${attachmentId}_alt`,
                'input[name="attachments[' + attachmentId + '][image_alt]"]',
                'input[name="attachments[' + attachmentId + '][post_excerpt]"]', // Some contexts use excerpt
                '#attachment-details-alt-text',
                'input[data-setting="alt"]',
                '.setting[data-setting="alt"] input',
                'input.attachment-alt'
            ];

            let $altField = null;
            for (const selector of altSelectors) {
                $altField = $(selector);
                if ($altField.length) {
                    console.log(`🖼️ Found alt field with selector: ${selector}`);
                    break;
                }
            }

            if ($altField && $altField.length) {
                $altField.val(value).trigger('change');
                console.log(`🖼️ Updated alt text: ${value}`);
            } else {
                console.warn('🖼️ Could not find alt text field to update');
            }

        } else if (type === 'title') {
            // Look for title field - try multiple selectors
            const titleSelectors = [
                `#attachment_${attachmentId}_title`,
                'input[name="attachments[' + attachmentId + '][post_title]"]',
                '#attachment-details-title',
                'input[data-setting="title"]',
                '.setting[data-setting="title"] input',
                'input.attachment-title',
                '#title' // Full page edit
            ];

            let $titleField = null;
            for (const selector of titleSelectors) {
                $titleField = $(selector);
                if ($titleField.length) {
                    console.log(`🖼️ Found title field with selector: ${selector}`);
                    break;
                }
            }

            if ($titleField && $titleField.length) {
                $titleField.val(value).trigger('change');
                console.log(`🖼️ Updated title: ${value}`);
            } else {
                console.warn('🖼️ Could not find title field to update');
            }
        }
    }

    function showStatus($element, message, type) {
        $element.html(message)
                .removeClass('success error info')
                .addClass(type)
                .show();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                $element.fadeOut();
            }, 5000);
        }
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Helper functions for modal
    function getImageTitle(attachmentId) {
        // Try multiple selectors to get current title
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
            const $field = $(selector);
            if ($field.length) {
                return $field.val() || '';
            }
        }

        // Try to get from attachment details display
        const $titleDisplay = $('.attachment-details .title');
        if ($titleDisplay.length) {
            return $titleDisplay.text().trim();
        }

        return '';
    }

    function getImageAlt(attachmentId) {
        // Try multiple selectors to get current alt text
        const altSelectors = [
            `#attachment_${attachmentId}_alt`,
            'input[name*="[image_alt]"]',
            '#attachment-details-alt-text',
            'input[data-setting="alt"]',
            '.setting[data-setting="alt"] input',
            'input.attachment-alt'
        ];

        for (const selector of altSelectors) {
            const $field = $(selector);
            if ($field.length) {
                return $field.val() || '';
            }
        }

        return '';
    }

    function getImageUrl(attachmentId) {
        // Try to get image URL from various sources
        const $img = $(`.attachment-preview img, .details-image img, .media-modal img[data-attachment-id="${attachmentId}"]`).first();
        if ($img.length) {
            return $img.attr('src') || $img.data('full-src') || '';
        }

        // Fallback: try to construct URL if we have attachment data
        const $urlField = $('input[name*="[url]"], #attachment-details-copy-link');
        if ($urlField.length) {
            const url = $urlField.val();
            if (url && url.includes('/uploads/')) {
                return url;
            }
        }

        return '';
    }

    async function generateModalMetadata($button, type, attachmentId) {
        const keywords = $('#pic-pilot-modal-keywords').val().trim();
        const $status = $(`.pic-pilot-${type}-status`);
        
        const originalText = $button.text();

        // Update UI
        $button.prop('disabled', true).text('Generating...');
        showModalStatus($status, `Generating ${type}...`, 'info');

        try {
            const response = await $.ajax({
                url: picPilotAttachment.ajax_url,
                method: 'POST',
                data: {
                    action: 'picpilot_generate_metadata',
                    nonce: picPilotAttachment.nonce,
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
                
                showModalStatus($status, `✅ ${capitalizeFirst(type)} generated successfully!`, 'success');
                
                // Show fallback message if applicable
                if (response.data.used_fallback && response.data.fallback_message) {
                    setTimeout(() => {
                        showModalStatus($status, `⚠️ ${response.data.fallback_message}`, 'info');
                    }, 2000);
                }

                // Update button text for alt text
                if (type === 'alt') {
                    $button.text('Regenerate Alt Text');
                }
            } else {
                showModalStatus($status, `❌ Failed to generate ${type}: ${response.data}`, 'error');
            }
        } catch (error) {
            console.error('Generation error:', error);
            showModalStatus($status, `❌ Generation failed: ${error.statusText || 'Unknown error'}`, 'error');
        } finally {
            $button.prop('disabled', false);
            if ($button.text() === 'Generating...') {
                $button.text(originalText);
            }
        }
    }

    async function duplicateModalImage($button, attachmentId) {
        const keywords = $('#pic-pilot-modal-keywords').val().trim();
        const $status = $('.pic-pilot-duplicate-status');
        
        const originalText = $button.text();

        // Update UI
        $button.prop('disabled', true).text('Duplicating...');
        showModalStatus($status, 'Creating duplicate image...', 'info');

        try {
            const response = await $.ajax({
                url: picPilotAttachment.ajax_url,
                method: 'POST',
                data: {
                    action: 'pic_pilot_duplicate_image',
                    nonce: picPilotAttachment.nonce,
                    attachment_id: attachmentId,
                    new_title: 'generate',
                    new_alt: 'generate',
                    keywords: keywords
                }
            });

            if (response.success) {
                showModalStatus($status, `✅ Image duplicated successfully! New image ID: ${response.data.id}`, 'success');
                
                // Show a link to the new image if we can
                setTimeout(() => {
                    const editUrl = `post.php?post=${response.data.id}&action=edit`;
                    showModalStatus($status, `✅ Duplicate created! <a href="${editUrl}" target="_blank">View new image →</a>`, 'success');
                }, 1000);
            } else {
                showModalStatus($status, `❌ Duplication failed: ${response.data?.message || 'Unknown error'}`, 'error');
            }
        } catch (error) {
            console.error('Duplication error:', error);
            showModalStatus($status, `❌ Duplication failed: ${error.statusText || 'Unknown error'}`, 'error');
        } finally {
            $button.prop('disabled', false).text(originalText);
        }
    }

    function updateModalPreview(type, value) {
        if (type === 'title') {
            $('.pic-pilot-image-info').html(function(i, html) {
                return html.replace(/(<strong>Current Title:<\/strong>)[^<]*(<br>)/, `$1 ${value}$2`);
            });
        } else if (type === 'alt') {
            $('.pic-pilot-image-info').html(function(i, html) {
                return html.replace(/(<strong>Current Alt Text:<\/strong>)[^<]*$/, `$1 ${value}`);
            });
        }
    }

    function showModalStatus($element, message, type) {
        $element.html(message)
                .removeClass('success error info')
                .addClass(type)
                .show();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                $element.fadeOut();
            }, 5000);
        }
    }

    // Global functions for debugging
    window.picPilotDebug = {
        initAITools: initAITools,
        openModal: openAIToolsModal,
        checkElements: function() {
            console.log('🖼️ === DEBUG INFO ===');
            console.log('🖼️ AI Tools buttons:', $('.pic-pilot-launch-modal-btn').length);
            console.log('🖼️ AI Tools containers:', $('.pic-pilot-attachment-ai-tools').length);
            console.log('🖼️ Media sidebar:', $('.media-sidebar').length);
            console.log('🖼️ Attachment details:', $('.attachment-details').length);
            console.log('🖼️ All forms:', $('form').length);
            
            // Show all attachment IDs found
            $('.pic-pilot-launch-modal-btn').each(function(i, btn) {
                console.log('🖼️ Button', i, 'has attachment ID:', $(btn).data('attachment-id'));
            });
        }
    };

})(jQuery);