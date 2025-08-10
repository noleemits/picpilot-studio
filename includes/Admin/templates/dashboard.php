<?php
defined('ABSPATH') || exit;

use PicPilotMeta\Admin\DashboardController;
use PicPilotMeta\Admin\ScanController;

$stats = DashboardController::get_dashboard_stats();
$recent_scans = DashboardController::get_recent_scans();
$scanned_post_types = ScanController::get_scanned_post_types();
?>

<div class="wrap pic-pilot-dashboard">
    <h1><?php _e('Pic Pilot Studio Dashboard', 'pic-pilot-meta'); ?></h1>
    
    <!-- Quick Stats Header -->
    <div class="dashboard-header">
        <?php if ($stats['has_scan']): ?>
            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-number"><?php echo number_format($stats['total_images']); ?></div>
                    <div class="stat-label"><?php _e('Total Images', 'pic-pilot-meta'); ?></div>
                </div>
                
                <div class="stat-card stat-issues">
                    <div class="stat-number"><?php echo number_format($stats['total_issues']); ?></div>
                    <div class="stat-label"><?php _e('Issues Found', 'pic-pilot-meta'); ?></div>
                </div>
                
                <div class="stat-card stat-progress">
                    <div class="stat-number"><?php echo $stats['completion_percentage']; ?>%</div>
                    <div class="stat-label"><?php _e('Complete', 'pic-pilot-meta'); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['completion_percentage']; ?>%"></div>
                    </div>
                </div>
                
                <div class="stat-card stat-pages">
                    <div class="stat-number"><?php echo number_format($stats['pages_with_issues']); ?></div>
                    <div class="stat-label"><?php _e('Pages Affected', 'pic-pilot-meta'); ?></div>
                </div>
            </div>
            
            <!-- Priority Breakdown -->
            <div class="priority-breakdown">
                <h3><?php _e('Issue Breakdown', 'pic-pilot-meta'); ?></h3>
                <div class="priority-grid">
                    <div class="priority-item critical">
                        <span class="priority-count"><?php echo $stats['critical_issues']; ?></span>
                        <span class="priority-label"><?php _e('Critical', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="priority-item high">
                        <span class="priority-count"><?php echo $stats['high_issues']; ?></span>
                        <span class="priority-label"><?php _e('High', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="priority-item medium">
                        <span class="priority-count"><?php echo $stats['medium_issues']; ?></span>
                        <span class="priority-label"><?php _e('Medium', 'pic-pilot-meta'); ?></span>
                    </div>
                </div>
                
                <!-- Priority Legend -->
                <div class="priority-explanations">
                    <div class="priority-explanation">
                        <span class="priority-badge priority-critical">Critical</span>
                        <span class="priority-description"><?php _e('Missing both attributes', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="priority-explanation">
                        <span class="priority-badge priority-high">High</span>
                        <span class="priority-description"><?php _e('Important content images', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="priority-explanation">
                        <span class="priority-badge priority-medium">Medium</span>
                        <span class="priority-description"><?php _e('Standard content images', 'pic-pilot-meta'); ?></span>
                    </div>
                </div>
                
                <!-- Scan Info -->
                <div class="scan-notes">
                    <ul>
                        <li><strong><?php _e('Missing Images', 'pic-pilot-meta'); ?></strong></li>
                        <li><strong><?php _e('External Images', 'pic-pilot-meta'); ?></strong></li>
                        <li><strong><?php _e('Page Builders', 'pic-pilot-meta'); ?></strong></li>
                    </ul>
                </div>
            </div>
            
            <!-- Missing Attributes Breakdown -->
            <div class="attributes-breakdown">
                <h3><?php _e('Missing Attributes', 'pic-pilot-meta'); ?></h3>
                <div class="attributes-grid">
                    <div class="attribute-item missing-both">
                        <span class="attribute-count"><?php echo $stats['missing_both']; ?></span>
                        <span class="attribute-label"><?php _e('Missing Both', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="attribute-item missing-alt">
                        <span class="attribute-count"><?php echo $stats['missing_alt']; ?></span>
                        <span class="attribute-label"><?php _e('Missing Alt Text', 'pic-pilot-meta'); ?></span>
                    </div>
                    <div class="attribute-item missing-title">
                        <span class="attribute-count"><?php echo $stats['missing_title']; ?></span>
                        <span class="attribute-label"><?php _e('Missing Title', 'pic-pilot-meta'); ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-scan-message">
                <div class="no-scan-icon">📊</div>
                <h2><?php _e('Welcome to Pic Pilot Dashboard', 'pic-pilot-meta'); ?></h2>
                <p><?php echo $stats['message']; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="dashboard-actions">
        <button type="button" class="button button-primary button-hero" id="start-scan">
            <span class="dashicons dashicons-search"></span>
            <?php _e('Scan Now', 'pic-pilot-meta'); ?>
        </button>
        
        <?php if ($stats['has_scan']): ?>
            <button type="button" class="button button-secondary" id="view-issues">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('View Issues', 'pic-pilot-meta'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="export-report">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Report', 'pic-pilot-meta'); ?>
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Scan Progress (Hidden by default) -->
    <div class="scan-progress" id="scan-progress" style="display: none;">
        <div class="scan-status">
            <div class="scan-icon">
                <span class="dashicons dashicons-update spin"></span>
            </div>
            <div class="scan-text">
                <div class="scan-message"><?php _e('Preparing scan...', 'pic-pilot-meta'); ?></div>
                <div class="scan-details"></div>
            </div>
        </div>
        <div class="scan-progress-bar">
            <div class="scan-progress-fill" style="width: 0%"></div>
        </div>
        <button type="button" class="button" id="cancel-scan">
            <?php _e('Cancel', 'pic-pilot-meta'); ?>
        </button>
    </div>
    
    <!-- Issues Table (Hidden by default) -->
    <div class="issues-section" id="issues-section" style="display: none;">
        <div class="issues-header">
            <h2><?php _e('Accessibility Issues', 'pic-pilot-meta'); ?></h2>
            
            <!-- Filters -->
            <div class="issues-filters">
                <select id="filter-priority">
                    <option value=""><?php _e('All Priorities', 'pic-pilot-meta'); ?></option>
                    <option value="critical"><?php _e('Critical Only', 'pic-pilot-meta'); ?></option>
                    <option value="high"><?php _e('High Priority', 'pic-pilot-meta'); ?></option>
                    <option value="medium"><?php _e('Medium Priority', 'pic-pilot-meta'); ?></option>
                </select>
                
                <select id="filter-attribute">
                    <option value=""><?php _e('All Issues', 'pic-pilot-meta'); ?></option>
                    <option value="missing-both"><?php _e('Missing Both', 'pic-pilot-meta'); ?></option>
                    <option value="missing-alt"><?php _e('Missing Alt Text', 'pic-pilot-meta'); ?></option>
                    <option value="missing-title"><?php _e('Missing Title', 'pic-pilot-meta'); ?></option>
                </select>
                
                <select id="filter-page-type">
                    <option value=""><?php _e('All Page Types', 'pic-pilot-meta'); ?></option>
                    <?php foreach ($scanned_post_types as $post_type => $label): ?>
                        <option value="<?php echo esc_attr($post_type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="search" id="search-issues" placeholder="<?php _e('Search pages or images...', 'pic-pilot-meta'); ?>">
                
                <button type="button" class="button" id="apply-filters">
                    <?php _e('Apply Filters', 'pic-pilot-meta'); ?>
                </button>
            </div>
        </div>
        
        <!-- Issues Table -->
        <div class="issues-table-container">
            <table class="wp-list-table widefat fixed striped" id="issues-table">
                <thead>
                    <tr>
                        <th class="column-page"><?php _e('Page', 'pic-pilot-meta'); ?></th>
                        <th class="column-image"><?php _e('Image', 'pic-pilot-meta'); ?></th>
                        <th class="column-status"><?php _e('Status', 'pic-pilot-meta'); ?></th>
                        <th class="column-context"><?php _e('Context', 'pic-pilot-meta'); ?></th>
                        <th class="column-priority"><?php _e('Priority', 'pic-pilot-meta'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'pic-pilot-meta'); ?></th>
                    </tr>
                </thead>
                <tbody id="issues-table-body">
                    <tr>
                        <td colspan="6" class="loading-row">
                            <?php _e('Loading issues...', 'pic-pilot-meta'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="issues-pagination" id="issues-pagination">
            <div class="pagination-info"></div>
            <div class="pagination-controls"></div>
        </div>
    </div>
    
    <!-- Recent Scans -->
    <?php if (!empty($recent_scans)): ?>
        <div class="recent-scans">
            <h3><?php _e('Recent Scans', 'pic-pilot-meta'); ?></h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'pic-pilot-meta'); ?></th>
                        <th><?php _e('Type', 'pic-pilot-meta'); ?></th>
                        <th><?php _e('Pages', 'pic-pilot-meta'); ?></th>
                        <th><?php _e('Issues', 'pic-pilot-meta'); ?></th>
                        <th><?php _e('Status', 'pic-pilot-meta'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_scans as $scan): ?>
                        <tr>
                            <td><?php echo esc_html(mysql2date('M j, Y g:i A', $scan['started_at'])); ?></td>
                            <td><?php echo esc_html(ucfirst($scan['scan_type'])); ?></td>
                            <td><?php echo esc_html($scan['pages_scanned']); ?></td>
                            <td><?php echo esc_html($scan['issues_found']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($scan['status']); ?>">
                                    <?php echo esc_html(ucfirst($scan['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Fix Issue Modal (Hidden by default) -->
<div class="pic-pilot-modal" id="fix-issue-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Fix Accessibility Issue', 'pic-pilot-meta'); ?></h3>
            <button type="button" class="modal-close" id="close-fix-modal">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="fix-issue-content"></div>
        </div>
    </div>
</div>