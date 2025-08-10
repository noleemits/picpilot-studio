<?php

/**
 * Uninstall script for Pic Pilot Studio
 * 
 * This file is executed when the plugin is deleted from WordPress
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user chose to remove settings during uninstall
$settings = get_option('picpilot_studio_settings', []);
$remove_settings = isset($settings['remove_settings_on_uninstall']) && $settings['remove_settings_on_uninstall'];

if ($remove_settings) {
    // Remove all plugin options
    delete_option('picpilot_studio_settings');
    
    // Remove any transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'picpilot_%'");
    
    // Remove dashboard database tables if they exist
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}picpilot_scan_results");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}picpilot_scan_history");
    
    // Remove any custom capabilities or user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'picpilot_%'");
    
    // Clear any cached data
    wp_cache_delete('picpilot_studio_settings', 'options');
}