<?php

namespace PicPilot\Studio\Admin;

defined('ABSPATH') || exit;

class MediaList {

    public static function init() {
        add_filter('media_row_actions', [__CLASS__, 'add_duplicate_action'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', ['PicPilot\\Studio\\Admin\\MediaList', 'enqueue_modal_script']);
    }

    public static function add_duplicate_action($actions, $post) {
        if ($post->post_type === 'attachment' && current_user_can('upload_files')) {
            $buttons = '<div class="pic-pilot-duplicate-group" data-id="' . esc_attr($post->ID) . '">';
            $buttons .= '<a href="#" class="pic-pilot-duplicate-image button button-small">' . esc_html__('Duplicate (Quick)', 'pic-pilot-studio') . '</a> ';
            $buttons .= '<a href="#" class="pic-pilot-duplicate-image-prompt button button-small">' . esc_html__('Duplicate + Title', 'pic-pilot-studio') . '</a> ';
            $buttons .= '<a href="#" class="pic-pilot-trim-image button button-small">' . esc_html__('Trim Image', 'pic-pilot-studio') . '</a>';
            $buttons .= '</div>';

            // Inject the container as a single custom action
            $actions['pic_pilot_tools'] = $buttons;
        }

        return $actions;
    }


    public static function enqueue_scripts($hook) {
        if ('upload.php' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'pic-pilot-studio-duplicate',
            PIC_PILOT_STUDIO_URL . 'assets/js/duplicate-image.js',
            ['jquery'],
            null,
            true
        );


        wp_localize_script('pic-pilot-studio-duplicate', 'PicPilotStudio', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pic_pilot_duplicate_image')
        ]);
    }

    public static function enqueue_modal_script($hook) {
        // Only enqueue on media upload modal views
        if (in_array($hook, ['media-upload-popup', 'upload.php', 'post.php', 'post-new.php'])) {
            wp_enqueue_script(
                'pic-pilot-studio-media-modal',
                PIC_PILOT_STUDIO_URL . 'assets/js/media-list.js',
                ['media-views', 'jquery'],
                null,
                true
            );
        }
    }
}
