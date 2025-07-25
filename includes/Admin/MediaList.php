<?php

namespace PicPilotStudio\Admin;

defined('ABSPATH') || exit;

class MediaList {

    public static function init() {
        add_filter('media_row_actions', [__CLASS__, 'add_duplicate_action'], 10, 2);
        // Enqueue styles/scripts for list view and modal
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_modal_script']);
        add_filter('media_row_actions', [__CLASS__, 'add_generate_button'], 10, 2);
    }


    public static function add_generate_button($actions, $post) {
        if ($post->post_type !== 'attachment' || !wp_attachment_is_image($post->ID)) return $actions;

        $settings = get_option('picpilot_studio_settings', []);
        $show_keywords = !empty($settings['show_keywords_field']);

        // Only output keyword input once
        $keywords_input = '';
        if ($show_keywords) {
            $keywords_input = '<input type="text" class="picpilot-keywords" placeholder="' . esc_attr__('Optional keywords/context', 'pic-pilot-studio') . '" data-id="' . esc_attr($post->ID) . '" style="margin-right:6px;max-width:160px;" />';
        }

        $actions['generate_meta'] = $keywords_input
            . sprintf(
                '<button type="button" class="picpilot-generate-meta" data-id="%d" data-type="alt">%s</button> ',
                esc_attr($post->ID),
                esc_html__('Generate Alt Text', 'pic-pilot-studio')
            )
            . sprintf(
                '<button type="button" class="picpilot-generate-meta" data-id="%d" data-type="title">%s</button>',
                esc_attr($post->ID),
                esc_html__('Generate Title', 'pic-pilot-studio')
            );

        return $actions;
    }



    public static function add_duplicate_action($actions, $post) {
        if ($post->post_type === 'attachment' && current_user_can('upload_files')) {
            $actions['duplicate_image_quick'] = '<a href="#" class="pic-pilot-duplicate-image" data-id="' . esc_attr($post->ID) . '">' . esc_html__('Duplicate', 'pic-pilot-studio') . '</a>';

            // $actions['duplicate_image_prompt'] = '<a href="#" class="pic-pilot-duplicate-image-prompt" data-id="' . esc_attr($post->ID) . '">' . esc_html__('Duplicate + Title', 'pic-pilot-studio') . '</a>';

            $actions['trim_image'] = '<a href="#" class="pic-pilot-trim-image" data-id="' . esc_attr($post->ID) . '">' . esc_html__('Trim Image', 'pic-pilot-studio') . '</a>';
        }

        return $actions;
    }



    public static function enqueue_scripts($hook) {
        if ('upload.php' !== $hook) {
            return;
        }

        // Enqueue for duplicate logic (still uses jQuery)
        wp_enqueue_script(
            'pic-pilot-studio-duplicate',
            PIC_PILOT_STUDIO_URL . 'assets/js/duplicate-image.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('pic-pilot-studio-duplicate', 'PicPilotStudio', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('picpilot_studio_generate'),
            'enable_filename_generation' => get_option('picpilot_studio_settings')['enable_filename_generation'] ?? false,
        ]);


        // Enqueue media list script (vanilla JS)
        wp_enqueue_script(
            'pic-pilot-media-list',
            PIC_PILOT_STUDIO_URL . 'assets/js/media-list.js',
            [],
            '1.0.0',
            true
        );
        //Enqueue smart duplication modal
        wp_enqueue_script(
            'picpilot-smart-duplication',
            PIC_PILOT_STUDIO_URL . 'assets/js/smart-duplication-modal.js',
            [],
            null,
            true
        );


        // Attach config BEFORE script load
        wp_add_inline_script('pic-pilot-media-list', 'window.picPilotStudio = ' . json_encode([
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('picpilot_studio_generate'),
        ]) . ';', 'before');
    }



    public static function enqueue_modal_script($hook) {
        // Only enqueue on media upload modal views
        if (in_array($hook, ['media-upload-popup', 'upload.php', 'post.php', 'post-new.php'])) {
            wp_enqueue_script(
                'pic-pilot-studio-media-modal',
                PIC_PILOT_STUDIO_URL . 'assets/js/media-modal-extend.js',
                ['media-views', 'jquery'],
                null,
                true
            );
        }
    }

    public static function add_row_actions($actions, $post) {
        if ($post->post_type !== 'attachment' || !wp_attachment_is_image($post)) return $actions;

        $actions['picpilot_generate'] = sprintf(
            '<a href="#" class="pic-pilot-generate-metadata" data-id="%d">%s</a>',
            esc_attr($post->ID),
            esc_html__('Generate Metadata', 'pic-pilot-studio')
        );

        return $actions;
    }
}
