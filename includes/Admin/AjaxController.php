<?php

namespace PicPilotStudio\Admin;

use PicPilotStudio\Services\ImageDuplicator;
use PicPilotStudio\Services\ImageTrimmer;
use PicPilotStudio\Helpers\Logger;

defined('ABSPATH') || exit;

class AjaxController {

    public static function init() {
        add_action('wp_ajax_pic_pilot_duplicate_image', [__CLASS__, 'handle_image_duplication']);
        add_action('wp_ajax_pic_pilot_trim_image', [__CLASS__, 'wp_ajax_pic_pilot_trim_image']);
        add_action('wp_ajax_picpilot_generate_metadata', [__CLASS__, 'generate_metadata']);
    }


    public static function generate_metadata() {
        ob_clean();
        check_ajax_referer('picpilot_studio_generate', 'nonce');

        $id       = absint($_POST['attachment_id'] ?? 0);
        $type     = sanitize_text_field($_POST['type'] ?? 'alt');
        $keywords = sanitize_text_field($_POST['keywords'] ?? '');

        if (!$id || !wp_attachment_is_image($id)) {
            return self::log_and_fail($id, 'Invalid image ID');
        }

        $settings = get_option('picpilot_studio_settings', []);
        $api_key  = $settings['openai_api_key'] ?? '';
        if (!$api_key) {
            return self::log_and_fail($id, 'Missing OpenAI API key.');
        }

        $prompt = $type === 'title'
            ? ($settings['default_prompt_title'] ?? 'Suggest a short SEO-friendly title for this image.')
            : ($settings['default_prompt_alt'] ?? 'Describe this image for alt text in one short sentence.');
        if ($keywords) {
            $prompt .= "\nContext: $keywords";
        }

        $file_path = get_attached_file($id);
        if (!file_exists($file_path)) {
            return self::log_and_fail($id, 'Image file not found.');
        }

        $image_data = file_get_contents($file_path);
        if (!$image_data) {
            return self::log_and_fail($id, 'Unable to read image.');
        }

        $mime_type = mime_content_type($file_path);
        $base64    = base64_encode($image_data);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'    => 'gpt-4o',
                'messages' => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => [
                            'url' => "data:$mime_type;base64,$base64"
                        ]],
                    ],
                ]],
                'max_tokens' => 150,
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return self::log_and_fail($id, 'OpenAI request failed: ' . $response->get_error_message());
        }

        $body   = json_decode(wp_remote_retrieve_body($response), true);
        $result = trim($body['choices'][0]['message']['content'] ?? '');

        // Remove wrapping quotes if present
        if (str_starts_with($result, '"') && str_ends_with($result, '"')) {
            $result = trim($result, '"');
        } elseif (str_starts_with($result, '“') && str_ends_with($result, '”')) {
            $result = trim($result, '“”');
        }


        if ($type === 'alt') {
            update_post_meta($id, '_wp_attachment_image_alt', $result);
        } elseif ($type === 'title') {
            wp_update_post(['ID' => $id, 'post_title' => $result]);
        }

        Logger::log("[SUCCESS] [$type] Image ID: $id, Snippet: " . substr($result, 0, 100));
        wp_send_json_success([
            'type'   => $type,
            'result' => $result,
        ]);
        if (ob_get_length()) {
            ob_clean();
        }
    }

    protected static function log_and_fail($id, $message) {
        Logger::log("[ERROR] Image ID: $id, Reason: $message");
        wp_send_json_error($message);
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

    //Ajax trimmer
    public static function wp_ajax_pic_pilot_trim_image() {
        error_log('🔧 Trimming triggered');

        $id = absint($_POST['attachment_id'] ?? 0);
        error_log("🧾 Attachment ID: $id");

        if (!$id || !current_user_can('edit_post', $id)) {
            error_log("🚫 Unauthorized or invalid ID");
            wp_send_json_error(['message' => __('Unauthorized or missing image.', 'pic-pilot-studio')]);
        }

        $path = get_attached_file($id);
        if (!$path) {
            error_log("📂 Failed to resolve file path");
            wp_send_json_error(['message' => __('Image not found.', 'pic-pilot-studio')]);
        }

        error_log("📍 File path: $path");

        $result = ImageTrimmer::trim($path, $id);
        wp_send_json($result);

        error_log("🔧 Trimming result: " . json_encode($result));

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}
