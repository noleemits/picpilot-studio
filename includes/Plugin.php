<?php

namespace PicPilotStudio;

use PicPilotStudio\Admin\AjaxController;
use PicPilotStudio\Admin\MediaList;
use PicPilotStudio\Admin\Settings;

defined('ABSPATH') || exit;

define('PIC_PILOT_STUDIO_LANG_PATH', plugin_dir_path(__FILE__) . 'languages');

class Plugin {
    public static function init() {
        // Load plugin text domain
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);

        // Register admin menu
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);

        // Initialize plugin modules
        if (is_admin()) {
            AjaxController::init();
            MediaList::init();
            Settings::init(); // This will handle admin_init and settings logic
        }
    }

    public static function load_textdomain() {
        load_plugin_textdomain('pic-pilot-studio', false, basename(PIC_PILOT_STUDIO_LANG_PATH));
    }

    public static function register_admin_page() {
        add_menu_page(
            'Pic Pilot Studio',
            'Pic Pilot Studio',
            'manage_options',
            'pic-pilot-studio',
            [Settings::class, 'render_settings_page'],
            'dashicons-format-image'
        );
    }
}
