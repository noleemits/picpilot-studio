<?php
defined('ABSPATH') || exit;

use PicPilotMeta\Admin\DashboardController;
?>

<div class="wrap pic-pilot-broken-images">
    <h1><?php _e('Broken Images Scanner', 'pic-pilot-meta'); ?></h1>

    <!-- Scanner Header -->
    <div class="scanner-header">
        <div class="scanner-intro">
            <p><?php _e('Find and fix broken images, missing attachments, and dead image links across your WordPress site.', 'pic-pilot-meta'); ?></p>
        </div>

        <!-- Quick Stats -->
        <div class="broken-stats" id="broken-stats" style="display: none;">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="total-checked">0</div>
                    <div class="stat-label"><?php _e('Images Checked', 'pic-pilot-meta'); ?></div>
                </div>

                <div class="stat-card stat-broken">
                    <div class="stat-number" id="broken-count">0</div>
                    <div class="stat-label"><?php _e('Broken Images', 'pic-pilot-meta'); ?></div>
                </div>

                <div class="stat-card stat-missing">
                    <div class="stat-number" id="missing-count">0</div>
                    <div class="stat-label"><?php _e('Missing Files', 'pic-pilot-meta'); ?></div>
                </div>

                <div class="stat-card stat-external">
                    <div class="stat-number" id="external-count">0</div>
                    <div class="stat-label"><?php _e('External Links', 'pic-pilot-meta'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Options -->
    <div class="scanner-options">
        <h3><?php _e('Scan Options', 'pic-pilot-meta'); ?></h3>

        <div class="options-grid">
            <div class="option-group">
                <label>
                    <input type="checkbox" id="check-media-library" checked>
                    <?php _e('Check Media Library attachments', 'pic-pilot-meta'); ?>
                </label>
                <small><?php _e('Verify that attachment files exist on disk', 'pic-pilot-meta'); ?></small>
            </div>

            <div class="option-group">
                <label>
                    <input type="checkbox" id="check-content-images" checked>
                    <?php _e('Check images in post content', 'pic-pilot-meta'); ?>
                </label>
                <small><?php _e('Scan for broken image links in pages and posts', 'pic-pilot-meta'); ?></small>
            </div>

            <div class="option-group">
                <label>
                    <input type="checkbox" id="check-featured-images" checked>
                    <?php _e('Check featured images', 'pic-pilot-meta'); ?>
                </label>
                <small><?php _e('Verify featured image attachments exist', 'pic-pilot-meta'); ?></small>
            </div>

            <div class="option-group">
                <label>
                    <input type="checkbox" id="check-external-images">
                    <?php _e('Check external images', 'pic-pilot-meta'); ?>
                </label>
                <small><?php _e('Test external image URLs (slower)', 'pic-pilot-meta'); ?></small>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="scanner-actions">
        <button type="button" class="button button-primary button-hero" id="start-broken-scan">
            <span class="dashicons dashicons-search"></span>
            <?php _e('Start Broken Images Scan', 'pic-pilot-meta'); ?>
        </button>

        <button type="button" class="button button-secondary" id="clear-broken-results" style="display: none;">
            <span class="dashicons dashicons-trash"></span>
            <?php _e('Clear Results', 'pic-pilot-meta'); ?>
        </button>
    </div>

    <!-- Scan Progress -->
    <div class="scan-progress" id="broken-scan-progress" style="display: none;">
        <div class="scan-status">
            <div class="scan-icon">
                <span class="dashicons dashicons-update spin"></span>
            </div>
            <div class="scan-text">
                <div class="scan-message"><?php _e('Scanning for broken images...', 'pic-pilot-meta'); ?></div>
                <div class="scan-details"></div>
            </div>
        </div>
        <div class="scan-progress-bar">
            <div class="scan-progress-fill" style="width: 0%"></div>
        </div>
        <button type="button" class="button" id="cancel-broken-scan">
            <?php _e('Cancel', 'pic-pilot-meta'); ?>
        </button>
    </div>

    <!-- Results Section -->
    <div class="broken-results-section" id="broken-results-section" style="display: none;">
        <div class="results-header">
            <h2><?php _e('Broken Images Found', 'pic-pilot-meta'); ?></h2>

            <!-- Filters -->
            <div class="results-filters">
                <select id="filter-issue-type">
                    <option value=""><?php _e('All Issues', 'pic-pilot-meta'); ?></option>
                    <option value="missing-file"><?php _e('Missing Files', 'pic-pilot-meta'); ?></option>
                    <option value="broken-link"><?php _e('Broken Links', 'pic-pilot-meta'); ?></option>
                    <option value="external-link"><?php _e('External Links', 'pic-pilot-meta'); ?></option>
                    <option value="orphaned-attachment"><?php _e('Orphaned Attachments', 'pic-pilot-meta'); ?></option>
                </select>

                <input type="search" id="search-broken" placeholder="<?php _e('Search images...', 'pic-pilot-meta'); ?>">

                <button type="button" class="button" id="apply-broken-filters">
                    <?php _e('Apply Filters', 'pic-pilot-meta'); ?>
                </button>
            </div>
        </div>

        <!-- Results Table -->
        <div class="results-table-container">
            <table class="wp-list-table widefat fixed striped" id="broken-results-table">
                <thead>
                    <tr>
                        <th class="column-image"><?php _e('Image', 'pic-pilot-meta'); ?></th>
                        <th class="column-location"><?php _e('Location', 'pic-pilot-meta'); ?></th>
                        <th class="column-issue"><?php _e('Issue Type', 'pic-pilot-meta'); ?></th>
                        <th class="column-details"><?php _e('Details', 'pic-pilot-meta'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'pic-pilot-meta'); ?></th>
                    </tr>
                </thead>
                <tbody id="broken-results-body">
                    <tr>
                        <td colspan="5" class="loading-row">
                            <?php _e('No broken images found.', 'pic-pilot-meta'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Export Actions -->
        <div class="export-actions-section" id="export-actions-section" style="display: none;">
            <div class="export-actions">
                <h3><?php _e('Export Report', 'pic-pilot-meta'); ?></h3>
                <p><?php _e('Export the broken images findings to a CSV file for further analysis or sharing with developers.', 'pic-pilot-meta'); ?></p>
                <div class="export-options">
                    <button type="button" class="button button-primary" id="export-broken-report">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV Report', 'pic-pilot-meta'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="help-section">
        <h3><?php _e('Understanding Broken Images', 'pic-pilot-meta'); ?></h3>

        <div class="help-content">
            <div class="help-definitions">
                <div class="help-definition">
                    <h4><?php _e('Missing Files', 'pic-pilot-meta'); ?></h4>
                    <p><?php _e('WordPress attachments that exist in the database but their files are missing from the server.', 'pic-pilot-meta'); ?></p>
                </div>

                <div class="help-definition">
                    <h4><?php _e('Broken Links', 'pic-pilot-meta'); ?></h4>
                    <p><?php _e('Image URLs in content that return 404 errors or are inaccessible.', 'pic-pilot-meta'); ?></p>
                </div>

                <div class="help-definition">
                    <h4><?php _e('External Links', 'pic-pilot-meta'); ?></h4>
                    <p><?php _e('Images hosted on external domains that may become unavailable.', 'pic-pilot-meta'); ?></p>
                </div>

                <div class="help-definition">
                    <h4><?php _e('Orphaned Attachments', 'pic-pilot-meta'); ?></h4>
                    <p><?php _e('Attachment records without corresponding files, or files without database records.', 'pic-pilot-meta'); ?></p>
                </div>
            </div>

            <div class="help-tips">
                <h4><?php _e('Tips for Fixing Broken Images', 'pic-pilot-meta'); ?></h4>
                <ul>
                    <li><?php _e('Always backup your site before performing bulk operations', 'pic-pilot-meta'); ?></li>
                    <li><?php _e('External images should be downloaded and hosted locally when possible', 'pic-pilot-meta'); ?></li>
                    <li><?php _e('Missing files may indicate server issues or incomplete migrations', 'pic-pilot-meta'); ?></li>
                    <li><?php _e('Use the export feature to create reports for developers or hosting providers', 'pic-pilot-meta'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Fix Broken Image Modal -->
<div class="pic-pilot-modal" id="fix-broken-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Fix Broken Image', 'pic-pilot-meta'); ?></h3>
            <button type="button" class="modal-close" id="close-broken-modal">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="modal-body">
            <div class="fix-broken-content"></div>
        </div>
    </div>
</div>