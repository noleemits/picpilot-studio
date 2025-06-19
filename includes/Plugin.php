<?php

namespace PicPilot\Studio;

defined('ABSPATH') || exit;

class Plugin {
    public static function init() {
        // Initialization logic here
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
    }

    public static function load_textdomain() {
        load_plugin_textdomain('pic-pilot-studio', false, dirname(plugin_basename(__FILE__)) . '/../languages');
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
