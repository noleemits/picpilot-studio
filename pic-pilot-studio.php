<?php

/**
 * Plugin Name: Pic Pilot: Studio
 * Description: Smart image assistant for metadata generation, background removal, and cleanup.
 * Version: 0.1.0
 * Author: Stephen Lee Hernandez
 * Author URI: 
 * Text Domain: pic-pilot-studio
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

use PicPilot\Studio\Plugin;

Plugin::init();
define('PIC_PILOT_STUDIO_URL', plugin_dir_url(__FILE__));

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'pic-pilot-studio-global',
        plugin_dir_url(__FILE__) . 'assets/css/pic-pilot-studio.css',
        [],
        null
    );
});
