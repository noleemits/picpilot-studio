<?php

namespace PicPilotStudio\Admin;

class Settings {

    public static function init() {
        register_setting('picpilot_studio_settings_group', 'picpilot_studio_settings');
        add_action('admin_menu', function () {
            add_options_page(
                __('Pic Pilot Studio Settings', 'pic-pilot-studio'),
                __('Pic Pilot Studio', 'pic-pilot-studio'),
                'manage_options',
                'picpilot-studio-settings',
                [self::class, 'render_settings_page']
            );
        });
    }


    public static function render_setting_row($setting) {
        $value = get_option('picpilot_studio_settings')[$setting['key']] ?? '';
        echo '<tr valign="top">';
        echo '<th scope="row">' . esc_html($setting['label']) . '</th>';
        echo '<td>';

        switch ($setting['type']) {
            case 'checkbox':
                echo '<label><input type="checkbox" name="picpilot_studio_settings[' . esc_attr($setting['key']) . ']" value="1" ' . checked($value, 1, false) . '> ' . esc_html($setting['description']) . '</label>';
                break;

            case 'text':
                echo '<input type="text" name="picpilot_studio_settings[' . esc_attr($setting['key']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                if (!empty($setting['description'])) {
                    echo '<p class="description">' . esc_html($setting['description']) . '</p>';
                }
                break;
            case 'password-toggle':
                echo '<div style="position:relative;">';
                echo '<input type="password" data-toggleable name="picpilot_studio_settings[' . esc_attr($setting['key']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                echo '</div>';
                if (!empty($setting['description'])) {
                    echo '<p class="description">' . esc_html($setting['description']) . '</p>';
                }
                break;
        }

        echo '</td></tr>';
    }

    public static function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Pic Pilot Studio Settings', 'pic-pilot-studio') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('picpilot_studio_settings_group');
        echo '<table class="form-table">';

        // Load modular sections
        include __DIR__ . '/templates/settings-section-ai.php';
        include __DIR__ . '/templates/settings-section-behavior.php';


        echo '</table>';
        submit_button();
        echo '</form></div>';
    }

    public static function get($key, $default = null) {
        $options = get_option('picpilot_studio_settings', []);
        return $options[$key] ?? $default;
    }
}
