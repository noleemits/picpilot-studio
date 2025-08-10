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
$settings = get_option('picpilot_meta_settings', []);
$remove_settings = isset($settings['remove_settings_on_uninstall']) && $settings['remove_settings_on_uninstall'];

if ($remove_settings) {
    // Remove all plugin options
    delete_option('picpilot_meta_settings');
    
    // Remove any transients using prepared statements
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'picpilot_%'));
    
    // Remove dashboard database tables if they exist
    $scan_results_table = $wpdb->prefix . 'picpilot_scan_results';
    $scan_history_table = $wpdb->prefix . 'picpilot_scan_history';
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $scan_results_table));
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $scan_history_table));
    
    // Remove any custom capabilities or user meta
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'picpilot_%'));
    
    // Clear any cached data
    wp_cache_delete('picpilot_meta_settings', 'options');
}