<?php

/**
 * Plugin Name: Pic Pilot: Studio
 * Description: Smart image assistant for metadata generation, background removal, and cleanup.
 * Version: 2.2.0
 * Author: Stephen Lee Hernandez
 * Author URI: 
 * Text Domain: pic-pilot-studio
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

// Ensure the Plugin class exists before using it
if (class_exists('PicPilotStudio\Plugin')) {
    \PicPilotStudio\Plugin::init();
} else {
    error_log('PicPilotStudio\Plugin class not found. Please check autoloading and class definition.');
}

define('PIC_PILOT_STUDIO_URL', plugin_dir_url(__FILE__));
define('PIC_PILOT_STUDIO_PATH', plugin_dir_path(__FILE__));
define('PIC_PILOT_STUDIO_VERSION', '2.2.0');

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'pic-pilot-studio-global',
        plugin_dir_url(__FILE__) . 'assets/css/pic-pilot-studio.css',
        [],
        PIC_PILOT_STUDIO_VERSION
    );
});

// Add pic-pilot-studio class to WordPress admin body for CSS scoping
add_action('admin_body_class', function($classes) {
    return $classes . ' pic-pilot-studio';
});



add_filter('big_image_size_threshold', '__return_false');
