<?php

namespace PicPilotMeta\Admin;

use PicPilotMeta\Helpers\Logger;

defined('ABSPATH') || exit;

class DashboardController {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_dashboard_submenu'], 20);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_dashboard_assets']);
        
        // Initialize database manager
        DatabaseManager::init();
    }
    
    public static function add_dashboard_submenu() {
        add_submenu_page(
            'pic-pilot-meta',
            __('Dashboard', 'pic-pilot-meta'),
            __('Dashboard', 'pic-pilot-meta'),
            'manage_options',
            'pic-pilot-dashboard',
            [__CLASS__, 'render_dashboard_page']
        );
    }
    
    public static function enqueue_dashboard_assets($hook) {
        if ($hook !== 'pic-pilot-meta_page_pic-pilot-dashboard') {
            return;
        }
        
        wp_enqueue_script(
            'pic-pilot-dashboard',
            PIC_PILOT_META_URL . 'assets/js/dashboard.js',
            ['jquery'],
            PIC_PILOT_META_VERSION,
            true
        );
        
        wp_enqueue_style(
            'pic-pilot-dashboard',
            PIC_PILOT_META_URL . 'assets/css/dashboard.css',
            [],
            PIC_PILOT_META_VERSION
        );
        
        $settings = get_option('picpilot_meta_settings', []);
        
        wp_localize_script('pic-pilot-dashboard', 'picPilotDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pic_pilot_dashboard'),
            'strings' => [
                'scan_starting' => __('Starting scan...', 'pic-pilot-meta'),
                'scan_progress' => __('Scanning page {current} of {total}...', 'pic-pilot-meta'),
                'scan_completed' => __('Scan completed successfully!', 'pic-pilot-meta'),
                'scan_failed' => __('Scan failed. Please try again.', 'pic-pilot-meta'),
                'confirm_new_scan' => __('This will start a new scan. Continue?', 'pic-pilot-meta'),
                'no_issues_found' => __('Great! No accessibility issues found.', 'pic-pilot-meta'),
                'loading' => __('Loading...', 'pic-pilot-meta')
            ]
        ]);
        
        // Pass settings to JavaScript for feature detection
        wp_localize_script('pic-pilot-dashboard', 'picPilotSettings', [
            'ai_features_enabled' => !empty($settings['openai_api_key']) || !empty($settings['gemini_api_key']),
            'auto_generate_both_enabled' => !empty($settings['enable_auto_generate_both']),
            'dangerous_rename_enabled' => !empty($settings['enable_dangerous_filename_rename']),
            // Smart generation features are now always enabled
            'alt_generation_enabled' => true,
            'title_generation_enabled' => true,
            'filename_generation_enabled' => true,
            'generate_nonce' => wp_create_nonce('picpilot_studio_generate')
        ]);
    }
    
    public static function render_dashboard_page() {
        $latest_scan = DatabaseManager::get_latest_scan();
        $stats = $latest_scan ? DatabaseManager::get_scan_stats($latest_scan['scan_id']) : null;
        
        include __DIR__ . '/templates/dashboard.php';
    }
    
    public static function get_dashboard_stats() {
        $latest_scan = DatabaseManager::get_latest_scan();
        
        if (!$latest_scan) {
            return [
                'has_scan' => false,
                'message' => __('No scans found. Click "Scan Now" to get started.', 'pic-pilot-meta')
            ];
        }
        
        $stats = DatabaseManager::get_scan_stats($latest_scan['scan_id']);
        $total_issues = $stats['missing_alt'] + $stats['missing_title'] - $stats['missing_both'];
        
        return [
            'has_scan' => true,
            'scan_date' => $latest_scan['completed_at'],
            'total_images' => (int)$stats['total_images'],
            'total_issues' => $total_issues,
            'missing_alt' => (int)$stats['missing_alt'],
            'missing_title' => (int)$stats['missing_title'],
            'missing_both' => (int)$stats['missing_both'],
            'critical_issues' => (int)$stats['critical_issues'],
            'high_issues' => (int)$stats['high_issues'],
            'medium_issues' => (int)$stats['medium_issues'],
            'pages_with_issues' => (int)$stats['pages_with_issues'],
            'completion_percentage' => $stats['total_images'] > 0 ? 
                round((($stats['total_images'] - $total_issues) / $stats['total_images']) * 100, 1) : 100
        ];
    }
    
    public static function get_recent_scans($limit = 5) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'picpilot_scan_history';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                scan_id,
                scan_type,
                status,
                pages_scanned,
                issues_found,
                started_at,
                completed_at,
                error_message
            FROM $table 
            ORDER BY started_at DESC 
            LIMIT %d
        ", $limit), ARRAY_A);
    }
    
    public static function get_priority_breakdown($scan_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'picpilot_scan_results';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                CASE 
                    WHEN priority_score >= 8 THEN 'critical'
                    WHEN priority_score >= 6 THEN 'high'
                    WHEN priority_score >= 4 THEN 'medium'
                    ELSE 'low'
                END as priority_level,
                COUNT(*) as count,
                GROUP_CONCAT(DISTINCT page_type) as page_types
            FROM $table 
            WHERE scan_id = %s
            GROUP BY 
                CASE 
                    WHEN priority_score >= 8 THEN 'critical'
                    WHEN priority_score >= 6 THEN 'high'
                    WHEN priority_score >= 4 THEN 'medium'
                    ELSE 'low'
                END
            ORDER BY priority_score DESC
        ", $scan_id), ARRAY_A);
    }
    
    public static function get_page_types_summary($scan_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'picpilot_scan_results';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                page_type,
                COUNT(DISTINCT page_id) as pages_count,
                COUNT(*) as images_count,
                SUM(CASE WHEN alt_text_status IN ('missing', 'empty') THEN 1 ELSE 0 END) as missing_alt_count,
                SUM(CASE WHEN title_attr_status IN ('missing', 'empty') THEN 1 ELSE 0 END) as missing_title_count
            FROM $table 
            WHERE scan_id = %s
            GROUP BY page_type
            ORDER BY images_count DESC
        ", $scan_id), ARRAY_A);
    }
}