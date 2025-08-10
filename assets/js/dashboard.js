/**
 * Pic Pilot Studio Dashboard JavaScript
 */

(function($) {
    'use strict';
    
    const Dashboard = {
        currentScanId: null,
        scanInProgress: false,
        scanInterval: null,
        
        init: function() {
            this.bindEvents();
            this.checkForExistingData();
        },
        
        bindEvents: function() {
            $('#start-scan').on('click', this.startScan.bind(this));
            $('#cancel-scan').on('click', this.cancelScan.bind(this));
            $('#view-issues').on('click', this.showIssues.bind(this));
            $('#export-report').on('click', this.exportReport.bind(this));
            $('#apply-filters').on('click', this.applyFilters.bind(this));
            $('#close-fix-modal').on('click', this.closeFixModal.bind(this));
            
            // Search functionality
            $('#search-issues').on('input', this.debounce(this.applyFilters.bind(this), 500));
            
            // Filter change handlers
            $('#filter-priority, #filter-attribute, #filter-page-type').on('change', this.applyFilters.bind(this));
            
            // Fix button handler (delegated for dynamically added buttons)
            $(document).on('click', '.fix-issue-btn', this.showFixModal.bind(this));
        },
        
        checkForExistingData: function() {
            // If there's existing scan data, we might want to show the issues section
            if ($('.stat-card').length > 0 && $('.stat-card .stat-number').first().text() !== '0') {
                $('#view-issues').show();
                $('#export-report').show();
            }
        },
        
        startScan: function(e) {
            e.preventDefault();
            
            if (this.scanInProgress) {
                return;
            }
            
            // Show confirmation if there's existing data
            if ($('#issues-section').is(':visible')) {
                if (!confirm(picPilotDashboard.strings.confirm_new_scan)) {
                    return;
                }
            }
            
            this.scanInProgress = true;
            this.showScanProgress();
            
            // Hide issues section during scan
            $('#issues-section').hide();
            
            const scanData = {
                action: 'pic_pilot_start_scan',
                nonce: picPilotDashboard.nonce,
                scan_type: 'partial', // Default to partial scan
                filters: JSON.stringify({})
            };
            
            $.post(picPilotDashboard.ajax_url, scanData)
                .done(this.handleScanStart.bind(this))
                .fail(this.handleScanError.bind(this));
        },
        
        handleScanStart: function(response) {
            if (response.success) {
                this.currentScanId = response.data.scan_id;
                this.updateScanProgress(0, response.data.total_pages, 'Starting scan...');
                
                // Start batch processing
                this.processScanBatch(0, response.data.total_pages);
            } else {
                this.handleScanError(response);
            }
        },
        
        processScanBatch: function(batchStart, totalPages) {
            if (!this.scanInProgress || !this.currentScanId) {
                return;
            }
            
            const batchData = {
                action: 'pic_pilot_scan_batch',
                nonce: picPilotDashboard.nonce,
                scan_id: this.currentScanId,
                batch_start: batchStart,
                batch_size: 15
            };
            
            $.post(picPilotDashboard.ajax_url, batchData)
                .done((response) => {
                    if (response.success) {
                        const data = response.data;
                        
                        this.updateScanProgress(
                            data.pages_scanned, 
                            data.total_pages,
                            `Scanned ${data.pages_scanned} of ${data.total_pages} pages...`
                        );
                        
                        if (data.is_complete) {
                            this.completeScan(data);
                        } else {
                            // Continue with next batch
                            setTimeout(() => {
                                this.processScanBatch(data.pages_scanned, data.total_pages);
                            }, 500); // Small delay to prevent overwhelming the server
                        }
                    } else {
                        this.handleScanError(response);
                    }
                })
                .fail(this.handleScanError.bind(this));
        },
        
        completeScan: function(scanData) {
            this.scanInProgress = false;
            this.currentScanId = null;
            
            // Hide scan progress
            $('#scan-progress').fadeOut();
            
            // Show completion message
            this.showNotification(picPilotDashboard.strings.scan_completed, 'success');
            
            // Refresh the page to show new stats
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        },
        
        cancelScan: function(e) {
            e.preventDefault();
            
            if (!this.scanInProgress || !this.currentScanId) {
                return;
            }
            
            const cancelData = {
                action: 'pic_pilot_cancel_scan',
                nonce: picPilotDashboard.nonce,
                scan_id: this.currentScanId
            };
            
            $.post(picPilotDashboard.ajax_url, cancelData)
                .done(() => {
                    this.scanInProgress = false;
                    this.currentScanId = null;
                    $('#scan-progress').fadeOut();
                    this.showNotification('Scan cancelled', 'info');
                });
        },
        
        showScanProgress: function() {
            $('#scan-progress').show();
            $('#start-scan').prop('disabled', true);
        },
        
        updateScanProgress: function(current, total, message) {
            const percentage = total > 0 ? (current / total) * 100 : 0;
            
            $('#scan-progress .scan-message').text(message);
            $('#scan-progress .scan-details').text(`${current} of ${total} pages processed`);
            $('#scan-progress .scan-progress-fill').css('width', percentage + '%');
        },
        
        handleScanError: function(error) {
            this.scanInProgress = false;
            this.currentScanId = null;
            $('#scan-progress').fadeOut();
            $('#start-scan').prop('disabled', false);
            
            const message = error.data && error.data.message ? 
                error.data.message : picPilotDashboard.strings.scan_failed;
            
            this.showNotification(message, 'error');
        },
        
        showIssues: function(e) {
            e.preventDefault();
            
            $('#issues-section').show();
            this.loadIssues(1);
            
            // Scroll to issues section
            $('html, body').animate({
                scrollTop: $('#issues-section').offset().top - 50
            }, 500);
        },
        
        loadIssues: function(page = 1) {
            $('#issues-table-body').html('<tr><td colspan="6" class="loading-row">Loading issues...</td></tr>');
            
            const filters = this.getFilters();
            const issuesData = {
                action: 'pic_pilot_get_issues',
                nonce: picPilotDashboard.nonce,
                page: page,
                per_page: 10,
                filters: JSON.stringify(filters)
            };
            
            $.post(picPilotDashboard.ajax_url, issuesData)
                .done(this.renderIssues.bind(this))
                .fail(() => {
                    $('#issues-table-body').html('<tr><td colspan="6" class="error-row">Failed to load issues</td></tr>');
                });
        },
        
        renderIssues: function(response) {
            if (response.success) {
                const data = response.data;
                let tableHtml = '';
                
                if (data.results.length === 0) {
                    tableHtml = '<tr><td colspan="6" class="no-results">' + 
                        picPilotDashboard.strings.no_issues_found + '</td></tr>';
                } else {
                    data.results.forEach(issue => {
                        tableHtml += this.renderIssueRow(issue);
                    });
                }
                
                $('#issues-table-body').html(tableHtml);
                this.renderPagination(data);
            } else {
                $('#issues-table-body').html('<tr><td colspan="6" class="error-row">Error loading issues</td></tr>');
            }
        },
        
        renderIssueRow: function(issue) {
            const statusBadges = issue.status_labels.map(label => 
                `<span class="status-badge ${this.getStatusClass(label)}">${label}</span>`
            ).join(' ');
            
            const priorityClass = issue.priority_label.toLowerCase();
            
            return `
                <tr data-issue-id="${issue.id}">
                    <td class="column-page">
                        <strong><a href="${issue.page_url}" target="_blank">${issue.page_title}</a></strong>
                        <div class="page-meta">${issue.page_type} • ${issue.formatted_date}</div>
                    </td>
                    <td class="column-image">
                        <div class="image-info">
                            <strong>${issue.image_filename}</strong>
                            <div class="image-meta">${issue.image_width}x${issue.image_height} • ${issue.image_size_formatted}</div>
                        </div>
                    </td>
                    <td class="column-status">
                        ${statusBadges}
                    </td>
                    <td class="column-context">
                        ${issue.section_heading ? `<strong>${issue.section_heading}</strong><br>` : ''}
                        ${issue.context_before}${issue.context_after ? '...' + issue.context_after : ''}
                        ${issue.image_caption ? `<br><em>Caption: ${issue.image_caption}</em>` : ''}
                    </td>
                    <td class="column-priority">
                        <span class="priority-badge priority-${priorityClass}">${issue.priority_label}</span>
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button button-small fix-issue-btn" 
                                data-issue-id="${issue.id}" data-image-id="${issue.image_id}">
                            Fix Now
                        </button>
                        <a href="${issue.page_url}" target="_blank" class="button button-small">View Page</a>
                    </td>
                </tr>
            `;
        },
        
        renderPagination: function(data) {
            let paginationHtml = '';
            
            // Pagination info
            const start = ((data.current_page - 1) * data.per_page) + 1;
            const end = Math.min(data.current_page * data.per_page, data.total_items);
            
            $('#issues-pagination .pagination-info').html(
                `Showing ${start}-${end} of ${data.total_items} issues`
            );
            
            // Pagination controls
            if (data.total_pages > 1) {
                paginationHtml += '<div class="pagination-links">';
                
                // Previous button
                if (data.current_page > 1) {
                    paginationHtml += `<a href="#" class="page-link prev" data-page="${data.current_page - 1}">&laquo; Previous</a>`;
                }
                
                // Calculate visible page range (show max 10 pages)
                const maxVisible = 10;
                const halfVisible = Math.floor(maxVisible / 2);
                let startPage = Math.max(1, data.current_page - halfVisible);
                let endPage = Math.min(data.total_pages, startPage + maxVisible - 1);
                
                // Adjust start if we're near the end
                if (endPage - startPage < maxVisible - 1) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }
                
                // First page and ellipsis if needed
                if (startPage > 1) {
                    paginationHtml += `<a href="#" class="page-link" data-page="1">1</a>`;
                    if (startPage > 2) {
                        paginationHtml += '<span class="page-ellipsis">...</span>';
                    }
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === data.current_page ? 'current' : '';
                    paginationHtml += `<a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a>`;
                }
                
                // Last page and ellipsis if needed
                if (endPage < data.total_pages) {
                    if (endPage < data.total_pages - 1) {
                        paginationHtml += '<span class="page-ellipsis">...</span>';
                    }
                    paginationHtml += `<a href="#" class="page-link" data-page="${data.total_pages}">${data.total_pages}</a>`;
                }
                
                // Next button
                if (data.current_page < data.total_pages) {
                    paginationHtml += `<a href="#" class="page-link next" data-page="${data.current_page + 1}">Next &raquo;</a>`;
                }
                
                paginationHtml += '</div>';
            }
            
            $('#issues-pagination .pagination-controls').html(paginationHtml);
            
            // Bind pagination events
            $('.page-link').on('click', (e) => {
                e.preventDefault();
                const page = $(e.target).data('page');
                this.loadIssues(page);
            });
        },
        
        getFilters: function() {
            return {
                priority: $('#filter-priority').val(),
                attribute: $('#filter-attribute').val(),
                page_type: $('#filter-page-type').val(),
                search: $('#search-issues').val()
            };
        },
        
        applyFilters: function() {
            this.loadIssues(1);
        },
        
        getStatusClass: function(label) {
            const classes = {
                'Missing Alt Text': 'missing-alt',
                'Missing Title': 'missing-title',
                'Missing Both': 'missing-both',
                'Complete': 'complete'
            };
            return classes[label] || 'default';
        },
        
        exportReport: function(e) {
            e.preventDefault();
            
            const filters = this.getFilters();
            const exportData = {
                action: 'pic_pilot_export_csv',
                nonce: picPilotDashboard.nonce,
                filters: JSON.stringify(filters)
            };
            
            // Show loading state
            const $button = $(e.target);
            const originalText = $button.text();
            $button.text('Generating...').prop('disabled', true);
            
            $.post(picPilotDashboard.ajax_url, exportData)
                .done((response) => {
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        this.showNotification('CSV report downloaded successfully!', 'success');
                    } else {
                        if (response.data && response.data.pro_feature) {
                            this.showNotification(response.data.message + ' Upgrade to unlock advanced exports.', 'info');
                        } else {
                            this.showNotification(response.data?.message || 'Export failed', 'error');
                        }
                    }
                })
                .fail(() => {
                    this.showNotification('Export failed. Please try again.', 'error');
                })
                .always(() => {
                    $button.text(originalText).prop('disabled', false);
                });
        },
        
        showFixModal: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const issueId = $button.data('issue-id');
            const imageId = $button.data('image-id');
            
            if (!issueId || !imageId) {
                this.showNotification(
                    'Missing issue data - This may be a broken image reference from a page builder. Please check your page content and remove any empty image widgets.', 
                    'info'
                );
                return;
            }
            
            // Load issue details and show modal
            this.loadIssueForFix(issueId, imageId);
        },
        
        loadIssueForFix: function(issueId, imageId) {
            const modalContent = $('.fix-issue-content');
            modalContent.html('<div class="loading">Loading issue details...</div>');
            $('#fix-issue-modal').fadeIn();
            
            // Check if this is a virtual image (URL-based)
            const isVirtual = typeof imageId === 'string' && imageId.startsWith('url_');
            
            if (isVirtual) {
                // Virtual images - show instructions only
                const fixContent = `
                    <div class="fix-issue-form">
                        <h4>Fix Accessibility Issue</h4>
                        <p><strong>Image Type:</strong> Content Image (not in Media Library)</p>
                        <p>This image is embedded directly in the page content and is not managed through WordPress Media Library.</p>
                        <div class="fix-instructions">
                            <h5>To fix this issue:</h5>
                            <ol>
                                <li>Edit the page/post containing this image</li>
                                <li>Find the image and add alt text and/or title attributes</li>
                                <li>In Gutenberg: Select image → Alt text field in sidebar</li>
                                <li>In page builders: Look for accessibility or alt text options</li>
                            </ol>
                        </div>
                        <div class="fix-actions">
                            <button type="button" class="button button-secondary" id="close-fix-modal">
                                Close
                            </button>
                        </div>
                    </div>
                `;
                modalContent.html(fixContent);
            } else {
                // WordPress Media Library images - show advanced fix tools
                this.loadAdvancedFixModal(issueId, imageId);
            }
        },
        
        loadAdvancedFixModal: function(issueId, imageId) {
            const modalContent = $('.fix-issue-content');
            
            // Get current row data to determine what's missing
            const $row = $(`tr[data-issue-id="${issueId}"]`);
            const statusBadges = $row.find('.column-status').text();
            const isMissingAlt = statusBadges.includes('Missing Alt Text');
            const isMissingTitle = statusBadges.includes('Missing Title');
            const isMissingBoth = isMissingAlt && isMissingTitle;
            
            let fixContent = `
                <div class="fix-issue-form">
                    <h4>Fix Accessibility Issue</h4>
                    <p><strong>Image ID:</strong> ${imageId}</p>
                    <div class="issue-status">
                        <p><strong>Missing:</strong> 
                        ${isMissingAlt ? '<span class="missing-badge">Alt Text</span>' : ''}
                        ${isMissingTitle ? '<span class="missing-badge">Title</span>' : ''}
                        </p>
                    </div>
                    
                    <div class="fix-methods">
                        <h5>Choose your fix method:</h5>
                        
                        <div class="fix-method-group">
                            <h6>🔧 Manual Edit</h6>
                            <a href="/wp-admin/post.php?post=${imageId}&action=edit" target="_blank" class="button button-secondary">
                                Edit in Media Library
                            </a>
                        </div>
            `;
            
            // Add AI generation options if features are enabled
            if (window.picPilotSettings && window.picPilotSettings.ai_features_enabled) {
                fixContent += `
                    <div class="fix-method-group">
                        <h6>🧠 AI Generation</h6>
                        <div class="ai-generation-tools">
                            <div class="keywords-input-group" style="margin-bottom: 10px;">
                                <input type="text" id="fix-keywords" placeholder="Optional: Enter keywords for better AI results" class="regular-text">
                            </div>
                            <div class="generation-buttons">
                `;
                
                if (isMissingAlt) {
                    fixContent += `<button type="button" class="button button-secondary generate-single-btn" data-type="alt" data-image-id="${imageId}">Generate Alt Text</button>`;
                }
                
                if (isMissingTitle) {
                    fixContent += `<button type="button" class="button button-secondary generate-single-btn" data-type="title" data-image-id="${imageId}">Generate Title</button>`;
                }
                
                // Show "Generate Both" button if both are missing and feature is enabled
                if (isMissingBoth && window.picPilotSettings && window.picPilotSettings.auto_generate_both_enabled) {
                    fixContent += `<button type="button" class="button button-primary generate-both-btn" data-image-id="${imageId}">🪄 Generate Both</button>`;
                }
                
                fixContent += `
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Add filename renaming if enabled
            if (window.picPilotSettings && window.picPilotSettings.dangerous_rename_enabled) {
                fixContent += `
                    <div class="fix-method-group dangerous-section">
                        <h6>⚠️ Filename Renaming (Dangerous)</h6>
                        <p class="warning-notice">Changing the filename may break existing references to this image.</p>
                        <div class="filename-tools">
                            <button type="button" class="button button-secondary check-usage-btn" data-image-id="${imageId}">
                                Check Image Usage
                            </button>
                            <div class="filename-rename-section" style="display: none;">
                                <div class="filename-input-group" style="margin: 10px 0;">
                                    <input type="text" id="new-filename" placeholder="Enter new filename" class="regular-text">
                                    <button type="button" class="button button-secondary generate-filename-btn" data-image-id="${imageId}">Generate with AI</button>
                                </div>
                                <div class="rename-buttons">
                                    <button type="button" class="button button-secondary rename-filename-btn" data-image-id="${imageId}">
                                        Rename Filename
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            fixContent += `
                    </div>
                    
                    <div class="fix-actions">
                        <button type="button" class="button button-secondary" id="close-fix-modal">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            modalContent.html(fixContent);
            
            // Bind event handlers for the new buttons
            this.bindFixModalEvents();
        },
        
        bindFixModalEvents: function() {
            // Generate single metadata
            $(document).off('click', '.generate-single-btn').on('click', '.generate-single-btn', (e) => {
                const $btn = $(e.target);
                const type = $btn.data('type');
                const imageId = $btn.data('image-id');
                const keywords = $('#fix-keywords').val();
                
                this.generateSingleMetadata(imageId, type, keywords, $btn);
            });
            
            // Generate both metadata
            $(document).off('click', '.generate-both-btn').on('click', '.generate-both-btn', (e) => {
                const $btn = $(e.target);
                const imageId = $btn.data('image-id');
                const keywords = $('#fix-keywords').val();
                
                this.generateBothMetadata(imageId, keywords, $btn);
            });
            
            // Check image usage
            $(document).off('click', '.check-usage-btn').on('click', '.check-usage-btn', (e) => {
                const $btn = $(e.target);
                const imageId = $btn.data('image-id');
                
                this.checkImageUsage(imageId, $btn);
            });
            
            // Generate filename
            $(document).off('click', '.generate-filename-btn').on('click', '.generate-filename-btn', (e) => {
                const $btn = $(e.target);
                const imageId = $btn.data('image-id');
                const keywords = $('#fix-keywords').val();
                
                this.generateFilename(imageId, keywords, $btn);
            });
            
            // Rename filename
            $(document).off('click', '.rename-filename-btn').on('click', '.rename-filename-btn', (e) => {
                const $btn = $(e.target);
                const imageId = $btn.data('image-id');
                const newFilename = $('#new-filename').val();
                
                this.renameFilename(imageId, newFilename, $btn);
            });
        },
        
        closeFixModal: function(e) {
            e.preventDefault();
            $('#fix-issue-modal').fadeOut();
        },
        
        showNotification: function(message, type = 'info') {
            // Create notification element
            const notification = $(`
                <div class="pic-pilot-notification notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close">&times;</button>
                </div>
            `);
            
            // Add to page
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            // Manual close
            notification.find('.notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        },
        
        debounce: function(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        },
        
        // New methods for enhanced fix modal functionality
        generateSingleMetadata: function(imageId, type, keywords, $btn) {
            const originalText = $btn.text();
            $btn.text('Generating...').prop('disabled', true);
            
            const data = {
                action: 'picpilot_generate_metadata',
                nonce: picPilotSettings.generate_nonce,
                attachment_id: imageId,
                type: type,
                keywords: keywords
            };
            
            $.post(picPilotDashboard.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(`${type === 'alt' ? 'Alt text' : 'Title'} generated successfully!`, 'success');
                        // Refresh the issues table
                        setTimeout(() => {
                            this.loadIssues(1);
                            this.closeFixModal({preventDefault: () => {}});
                        }, 1500);
                    } else {
                        this.showNotification(response.data?.message || 'Generation failed', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Generation failed. Please try again.', 'error');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
        },
        
        generateBothMetadata: function(imageId, keywords, $btn) {
            const originalText = $btn.text();
            $btn.text('Generating Both...').prop('disabled', true);
            
            const data = {
                action: 'picpilot_generate_both',
                nonce: picPilotDashboard.nonce,
                attachment_id: imageId,
                keywords: keywords
            };
            
            $.post(picPilotDashboard.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification('Both alt text and title generated successfully!', 'success');
                        // Refresh the issues table
                        setTimeout(() => {
                            this.loadIssues(1);
                            this.closeFixModal({preventDefault: () => {}});
                        }, 1500);
                    } else {
                        this.showNotification(response.data?.message || 'Generation failed', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Generation failed. Please try again.', 'error');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
        },
        
        checkImageUsage: function(imageId, $btn) {
            const originalText = $btn.text();
            $btn.text('Checking...').prop('disabled', true);
            
            const data = {
                action: 'picpilot_check_image_usage',
                nonce: picPilotDashboard.nonce,
                attachment_id: imageId
            };
            
            $.post(picPilotDashboard.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.displayUsageResults(response.data, $btn);
                    } else {
                        this.showNotification(response.data?.message || 'Usage check failed', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Usage check failed. Please try again.', 'error');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
        },
        
        displayUsageResults: function(usageData, $btn) {
            const $renameSection = $('.filename-rename-section');
            
            let usageHtml = `
                <div class="usage-results" style="margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <h6>Image Usage Analysis</h6>
                    <p><strong>Current Filename:</strong> ${usageData.current_filename}</p>
            `;
            
            if (usageData.is_safe_to_rename) {
                usageHtml += `
                    <p style="color: #00a32a;"><strong>✅ Safe to rename</strong> - No usage found</p>
                `;
            } else {
                usageHtml += `
                    <p style="color: #d63638;"><strong>⚠️ Image is in use (${usageData.usage_count} location${usageData.usage_count !== 1 ? 's' : ''})</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                `;
                
                usageData.usage.forEach(usage => {
                    usageHtml += `
                        <li>
                            <strong>${usage.type}:</strong> 
                            <a href="${usage.edit_url}" target="_blank">${usage.post_title}</a> 
                            (${usage.post_type})
                        </li>
                    `;
                });
                
                usageHtml += `
                    </ul>
                    <p style="color: #d63638; font-size: 12px;">
                        <strong>Warning:</strong> Renaming will break these references!
                    </p>
                `;
            }
            
            usageHtml += '</div>';
            
            // Remove any existing usage results
            $('.usage-results').remove();
            
            // Add usage results before the rename section
            $renameSection.before(usageHtml);
            
            // Show the rename section
            $renameSection.show();
            
            // Store the usage data for later use
            $renameSection.data('usage-data', usageData);
        },
        
        generateFilename: function(imageId, keywords, $btn) {
            const originalText = $btn.text();
            $btn.text('Generating...').prop('disabled', true);
            
            const data = {
                action: 'picpilot_generate_filename',
                nonce: picPilotSettings.generate_nonce,
                attachment_id: imageId,
                keywords: keywords
            };
            
            $.post(picPilotDashboard.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        $('#new-filename').val(response.data.filename);
                        this.showNotification('Filename generated successfully!', 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Filename generation failed', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Filename generation failed. Please try again.', 'error');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
        },
        
        renameFilename: function(imageId, newFilename, $btn) {
            if (!newFilename.trim()) {
                this.showNotification('Please enter a new filename', 'error');
                return;
            }
            
            const usageData = $('.filename-rename-section').data('usage-data');
            const forceRename = !usageData?.is_safe_to_rename;
            
            // Show confirmation if image is in use
            if (forceRename) {
                const confirmMessage = `WARNING: This image is being used in ${usageData.usage_count} location(s). Renaming will break these references. Are you sure you want to continue?`;
                if (!confirm(confirmMessage)) {
                    return;
                }
            }
            
            const originalText = $btn.text();
            $btn.text('Renaming...').prop('disabled', true);
            
            const data = {
                action: 'picpilot_rename_filename',
                nonce: picPilotDashboard.nonce,
                attachment_id: imageId,
                new_filename: newFilename,
                force_rename: forceRename ? 'true' : 'false'
            };
            
            $.post(picPilotDashboard.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(`Filename renamed to "${response.data.new_filename}"`, 'success');
                        // Close modal after short delay
                        setTimeout(() => {
                            this.closeFixModal({preventDefault: () => {}});
                        }, 2000);
                    } else {
                        if (response.data?.requires_force) {
                            this.showNotification('Image is in use. Please check usage first.', 'error');
                        } else {
                            this.showNotification(response.data?.message || 'Rename failed', 'error');
                        }
                    }
                })
                .fail(() => {
                    this.showNotification('Rename failed. Please try again.', 'error');
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
        }
    };
    
    // Initialize dashboard when document is ready
    $(document).ready(() => {
        Dashboard.init();
    });
    
    // Expose Dashboard object globally for debugging
    window.PicPilotDashboard = Dashboard;
    
})(jQuery);