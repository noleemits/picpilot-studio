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
                per_page: 25,
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
                            <div class="image-meta">${issue.image_width}×${issue.image_height} • ${issue.image_size_formatted}</div>
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
                
                for (let i = 1; i <= data.total_pages; i++) {
                    const activeClass = i === data.current_page ? 'current' : '';
                    paginationHtml += `<a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a>`;
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
            
            let fixContent;
            if (isVirtual) {
                fixContent = `
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
            } else {
                fixContent = `
                    <div class="fix-issue-form">
                        <h4>Fix Accessibility Issue</h4>
                        <p><strong>Image ID:</strong> ${imageId}</p>
                        <p>This image is managed through WordPress Media Library.</p>
                        <div class="fix-actions">
                            <a href="/wp-admin/post.php?post=${imageId}&action=edit" target="_blank" class="button button-primary">
                                Edit Image in Media Library
                            </a>
                            <button type="button" class="button button-secondary" id="close-fix-modal">
                                Close
                            </button>
                        </div>
                    </div>
                `;
            }
            
            modalContent.html(fixContent);
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
        }
    };
    
    // Initialize dashboard when document is ready
    $(document).ready(() => {
        Dashboard.init();
    });
    
    // Expose Dashboard object globally for debugging
    window.PicPilotDashboard = Dashboard;
    
})(jQuery);