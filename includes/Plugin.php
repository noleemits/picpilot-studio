<?php

namespace PicPilot\Studio;

use PicPilot\Studio\Admin\AjaxController;
use PicPilot\Studio\Admin\MediaList;

defined('ABSPATH') || exit;

define('PIC_PILOT_STUDIO_LANG_PATH', plugin_dir_path(__FILE__) . 'languages');


class Plugin {
    public static function init() {
        // Load plugin text domain
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);

        // Register settings page
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);

        // Initialize admin functionality
        AjaxController::init();
        MediaList::init();
    }

    public static function load_textdomain() {
        load_plugin_textdomain('pic-pilot-studio', false, basename(PIC_PILOT_STUDIO_LANG_PATH));
    }

    public static function register_admin_page() {
        add_menu_page(
            __('Pic Pilot Studio', 'pic-pilot-studio'),
            __('Pic Pilot Studio', 'pic-pilot-studio'),
            'manage_options',
            'pic-pilot-studio',
            function () {
                echo '<div class="wrap"><h1>' . esc_html__('Pic Pilot Studio Settings', 'pic-pilot-studio') . '</h1></div>';
            },
            'dashicons-format-image'
        );
    }
}
