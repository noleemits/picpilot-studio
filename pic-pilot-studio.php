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

// Ensure the Plugin class exists before using it
if (class_exists('PicPilotStudio\Plugin')) {
    \PicPilotStudio\Plugin::init();
} else {
    error_log('PicPilotStudio\Plugin class not found. Please check autoloading and class definition.');
}

define('PIC_PILOT_STUDIO_URL', plugin_dir_url(__FILE__));

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'pic-pilot-studio-global',
        plugin_dir_url(__FILE__) . 'assets/css/pic-pilot-studio.css',
        [],
        null
    );
});

add_action('load-post.php', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'attachment') {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(
                'pic-pilot-editor-trim',
                PIC_PILOT_STUDIO_URL . 'assets/js/editor-trim.js',
                [],
                null,
                true
            );

            wp_localize_script('pic-pilot-editor-trim', 'PicPilotStudio', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('pic_pilot_trim_image'),
            ]);
        });
    }
});


add_filter('big_image_size_threshold', '__return_false');
