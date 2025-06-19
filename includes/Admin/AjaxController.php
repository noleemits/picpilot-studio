<?php

namespace PicPilot\Studio\Admin;

use PicPilot\Studio\Services\ImageDuplicator;

defined('ABSPATH') || exit;

class AjaxController {

    public static function init() {
        add_action('wp_ajax_pic_pilot_duplicate_image', [__CLASS__, 'handle_image_duplication']);
    }

    public static function handle_image_duplication() {
        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied', 'pic-pilot-studio')], 403);
        }

        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        $new_title = isset($_POST['new_title']) ? sanitize_text_field($_POST['new_title']) : null;
        $new_filename = isset($_POST['new_filename']) ? sanitize_file_name($_POST['new_filename']) : null;

        if (!$attachment_id || get_post_type($attachment_id) !== 'attachment') {
            wp_send_json_error(['message' => __('Invalid attachment ID', 'pic-pilot-studio')], 400);
        }

        $new_id = ImageDuplicator::duplicate($attachment_id, $new_title, $new_filename);

        if (!$new_id) {
            wp_send_json_error(['message' => __('Failed to duplicate image', 'pic-pilot-studio')], 500);
        }

        wp_send_json_success([
            'new_id' => $new_id,
            'message' => __('Image duplicated successfully', 'pic-pilot-studio')
        ]);
    }
}
