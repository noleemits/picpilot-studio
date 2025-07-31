<?php

namespace PicPilotStudio\Admin;

use PicPilotStudio\Services\ImageDuplicator;
use PicPilotStudio\Helpers\Logger;
use PicPilotStudio\Helpers\FilenameGenerator;
use PicPilotStudio\Helpers\MetadataGenerator;

// Import WordPress functions
use function add_action;
use function check_ajax_referer;
use function wp_send_json_error;
use function wp_send_json_success;
use function absint;
use function wp_attachment_is_image;
use function sanitize_text_field;
use function is_wp_error;
use function get_option;
use function wp_remote_post;
use function wp_remote_retrieve_body;
use function json_encode;
use function json_decode;
use function get_attached_file;
use function update_post_meta;
use function wp_update_post;
use function current_user_can;
use function error_log;
use function __;

// If this file is called directly, abort
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AjaxController
 * 
 * Handles all AJAX requests for the plugin.
 * This includes image duplication and metadata generation.
 * 
 * @package PicPilotStudio\Admin
 */

// WordPress functions are in global namespace
if (!defined('\ABSPATH')) {
    exit;
}

class AjaxController {

    public static function init(): void {
        add_action('wp_ajax_pic_pilot_duplicate_image', [__CLASS__, 'duplicate_image']);
        add_action('wp_ajax_picpilot_generate_metadata', [__CLASS__, 'generate_metadata']);
        add_action('wp_ajax_picpilot_generate_filename', [__CLASS__, 'wp_ajax_picpilot_generate_filename']);
        add_action('wp_ajax_picpilot_bulk_process', [__CLASS__, 'bulk_process']);
        add_action('wp_ajax_picpilot_get_images_without_alt', [__CLASS__, 'get_images_without_alt']);
        add_action('wp_ajax_picpilot_get_images_without_titles', [__CLASS__, 'get_images_without_titles']);
    }

    public static function duplicate_image() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        $id = \absint($_POST['attachment_id'] ?? 0);
        if (!$id || !\wp_attachment_is_image($id)) {
            \wp_send_json_error(['message' => 'Invalid image ID.']);
        }

        $new_title = \sanitize_text_field($_POST['new_title'] ?? '');
        $new_alt = \sanitize_text_field($_POST['new_alt'] ?? '');
        $new_filename = \sanitize_text_field($_POST['new_filename'] ?? '');
        $keywords = \sanitize_text_field($_POST['keywords'] ?? '');

        try {
            $_POST['update_original'] = 'false'; // Prevent updating original image

            // Get settings to check if features are enabled
            $settings = \get_option('picpilot_studio_settings', []);
            $title_enabled = $settings['enable_title_generation_on_duplicate'] ?? false;
            $alt_enabled = $settings['enable_alt_generation_on_duplicate'] ?? false;
            $filename_enabled = $settings['enable_filename_generation'] ?? false;

            if ($new_title === 'generate' && $title_enabled) {
                $title_result = MetadataGenerator::generate($id, 'title', $keywords);
                if (\is_wp_error($title_result)) {
                    Logger::log('[DUPLICATE] Title generation failed: ' . $title_result->get_error_message());
                    throw new \Exception('Title generation failed: ' . $title_result->get_error_message());
                }

                // MetadataGenerator now returns string directly
                $new_title = $title_result;

                if (empty($new_title)) {
                    Logger::log('[DUPLICATE] Title generation returned empty result');
                    throw new \Exception('AI returned an empty title.');
                }
            } elseif ($new_title === 'generate' && !$title_enabled) {
                Logger::log('[DUPLICATE] Title generation requested but feature is disabled in settings');
                throw new \Exception('Title generation is disabled in plugin settings.');
            }

            if ($new_alt === 'generate' && $alt_enabled) {
                $alt_result = MetadataGenerator::generate($id, 'alt', $keywords);
                if (\is_wp_error($alt_result)) {
                    Logger::log('[DUPLICATE] Alt generation failed: ' . $alt_result->get_error_message());
                    throw new \Exception('Alt text generation failed: ' . $alt_result->get_error_message());
                }

                // MetadataGenerator now returns string directly
                $new_alt = $alt_result;

                if (empty($new_alt)) {
                    Logger::log('[DUPLICATE] Alt generation returned empty result');
                    throw new \Exception('AI returned an empty alt text.');
                }
            } elseif ($new_alt === 'generate' && !$alt_enabled) {
                Logger::log('[DUPLICATE] Alt generation requested but feature is disabled in settings');
                throw new \Exception('Alt text generation is disabled in plugin settings.');
            }

            if ($new_filename === 'generate' && $filename_enabled) {
                $new_filename = FilenameGenerator::generate($id, $keywords);
                if (\is_wp_error($new_filename)) {
                    Logger::log('[DUPLICATE] Filename generation failed: ' . $new_filename->get_error_message());
                    throw new \Exception('Filename generation failed: ' . $new_filename->get_error_message());
                }
                if (empty($new_filename)) {
                    Logger::log('[DUPLICATE] Filename generation returned empty result');
                    throw new \Exception('AI returned an empty filename.');
                }
            } elseif ($new_filename === 'generate' && !$filename_enabled) {
                Logger::log('[DUPLICATE] Filename generation requested but feature is disabled in settings');
                throw new \Exception('Filename generation is disabled in plugin settings.');
            }

            // Create duplicate with generated or provided metadata
            $new_id = ImageDuplicator::duplicate($id, $new_title ?: null, $new_filename ?: null, $new_alt ?: null);

            if (!$new_id) {
                throw new \Exception('Failed to duplicate image.');
            }

            Logger::log("[DUPLICATE] Created #$new_id from #$id | Title: $new_title | Alt: $new_alt | Filename: $new_filename | Keywords: $keywords");
            \wp_send_json_success(['id' => $new_id]);
        } catch (\Throwable $e) {
            Logger::log('[DUPLICATE] Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public static function wp_ajax_picpilot_generate_filename() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        $id = \absint($_POST['attachment_id'] ?? 0);
        $keywords = \sanitize_text_field($_POST['keywords'] ?? '');

        if (!$id || !\wp_attachment_is_image($id)) {
            \wp_send_json_error(['message' => \__('Invalid image ID.', 'pic-pilot-studio')]);
        }

        $result = FilenameGenerator::generate($id, $keywords);
        if (\is_wp_error($result)) {
            Logger::log('[FILENAME] Error: ' . $result->get_error_message());
            \wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (empty($result)) {
            Logger::log('[FILENAME] Empty result');
            \wp_send_json_error(['message' => 'Generated filename is empty']);
        }

        Logger::log("[FILENAME] ID $id - Keywords: '$keywords' - Generated: $result");
        \wp_send_json_success(['filename' => $result]);
    }

    public static function generate_metadata() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        $id = \absint($_POST['attachment_id'] ?? 0);
        $type = \sanitize_text_field($_POST['type'] ?? 'alt');
        $keywords = \sanitize_text_field($_POST['keywords'] ?? '');

        // Debug logging
        Logger::log("[AJAX] generate_metadata called - ID: $id, Type: $type, Keywords: '$keywords'");
        Logger::log("[AJAX] Raw POST data: " . json_encode($_POST));

        if (!$id || !\wp_attachment_is_image($id)) {
            return self::log_and_fail($id, 'Invalid image ID');
        }

        $settings = \get_option('picpilot_studio_settings', []);
        $provider = $settings['ai_provider'] ?? 'openai';
        
        if ($provider === 'gemini') {
            $api_key = $settings['gemini_api_key'] ?? '';
            if (empty($api_key)) {
                return self::log_and_fail($id, 'Missing Gemini API key.');
            }
        } else {
            $api_key = $settings['openai_api_key'] ?? '';
            if (empty($api_key)) {
                return self::log_and_fail($id, 'Missing OpenAI API key.');
            }
        }

        // Use the unified MetadataGenerator instead of duplicate logic
        Logger::log("[AJAX] Calling MetadataGenerator::generate with keywords: '$keywords'");
        $result = MetadataGenerator::generate($id, $type, $keywords);

        if (\is_wp_error($result)) {
            Logger::log("[{$type}] Generation failed: " . $result->get_error_message());
            return self::log_and_fail($id, $result->get_error_message());
        }

        // MetadataGenerator now returns string directly
        $content = $result;

        if (empty($content)) {
            Logger::log("[{$type}] Empty content from generator");
            return self::log_and_fail($id, 'Empty result from AI generation');
        }

        // Update the actual attachment with the generated metadata
        if ($type === 'alt') {
            \update_post_meta($id, '_wp_attachment_image_alt', $content);
            Logger::log("[{$type}] Updated alt text for image ID: $id");
        } elseif ($type === 'title') {
            \wp_update_post(['ID' => $id, 'post_title' => $content]);
            Logger::log("[{$type}] Updated title for image ID: $id");
        }

        // Prepare success response
        $response_data = [
            'type' => $type,
            'result' => $content,
            'shouldUpdate' => true,
        ];

        Logger::log("[SUCCESS] [$type] Image ID: $id, Keywords: '$keywords', Result: " . substr($content, 0, 100));

        \wp_send_json_success($response_data);
    }

    public static function log_and_fail($id, $message) {
        Logger::log("[ERROR] Image ID: $id, Reason: $message");
        \wp_send_json_error($message);
        exit;
    }

    /**
     * Handle bulk processing request
     */
    public static function bulk_process() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        $bulk_key = \sanitize_text_field($_POST['bulk_key'] ?? '');
        $generate_title = $_POST['generate_title'] === '1';
        $generate_alt = $_POST['generate_alt'] === '1';

        if (empty($bulk_key)) {
            \wp_send_json_error(['message' => 'Invalid bulk key']);
        }

        // Get image IDs from transient
        $image_ids = \get_transient($bulk_key);
        if (!$image_ids || !is_array($image_ids)) {
            \wp_send_json_error(['message' => 'Bulk operation expired or invalid']);
        }

        // Validate that user can edit these attachments
        if (!\current_user_can('upload_files')) {
            \wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Filter to ensure all IDs are valid images
        $valid_image_ids = [];
        foreach ($image_ids as $id) {
            if (\wp_attachment_is_image($id)) {
                $valid_image_ids[] = $id;
            }
        }

        if (empty($valid_image_ids)) {
            \wp_send_json_error(['message' => 'No valid image attachments found']);
        }

        Logger::log("[BULK] Starting bulk processing for " . count($valid_image_ids) . " images. Title: " . ($generate_title ? 'yes' : 'no') . ", Alt: " . ($generate_alt ? 'yes' : 'no'));

        // Delete the transient as it's no longer needed
        \delete_transient($bulk_key);

        \wp_send_json_success([
            'image_ids' => $valid_image_ids,
            'message' => 'Bulk processing initiated'
        ]);
    }

    /**
     * Get images without alt text
     */
    public static function get_images_without_alt() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        if (!\current_user_can('upload_files')) {
            \wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Query for image attachments without alt text
        $query = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '='
                ],
                [
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        $image_ids = $query->posts;

        Logger::log("[BULK] Found " . count($image_ids) . " images without alt text");

        \wp_send_json_success([
            'image_ids' => $image_ids,
            'count' => count($image_ids)
        ]);
    }

    /**
     * Get images without titles (or with default titles)
     */
    public static function get_images_without_titles() {
        \check_ajax_referer('picpilot_studio_generate', 'nonce');

        if (!\current_user_can('upload_files')) {
            \wp_send_json_error(['message' => 'Insufficient permissions']);
        }

        // Query for image attachments with missing or default titles
        $query = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        $image_ids = [];

        // Filter images with missing or default titles
        foreach ($query->posts as $attachment_id) {
            $title = \get_the_title($attachment_id);
            $filename = \get_post_meta($attachment_id, '_wp_attached_file', true);
            $basename = basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION));

            // Check if title is empty, matches filename, or is a generic pattern
            if (
                empty($title) ||
                $title === $basename ||
                $title === $filename ||
                preg_match('/^(img|image|photo|picture)[-_]?\d*$/i', $title) ||
                preg_match('/^untitled[-_]?\d*$/i', $title)
            ) {
                $image_ids[] = $attachment_id;
            }
        }

        Logger::log("[BULK] Found " . count($image_ids) . " images without proper titles");

        \wp_send_json_success([
            'image_ids' => $image_ids,
            'count' => count($image_ids)
        ]);
    }
}
