<?php

namespace PicPilotStudio\Helpers;

use PicPilot\Settings;
use WP_Error;

class FilenameGenerator {
    public static function generate($attachment_id) {
        $api_key = Settings::get('openai_api_key');
        $prompt = Settings::get('default_prompt_filename') ?: 'Give a short filename for this image.';
        $image_url = wp_get_attachment_url($attachment_id);

        if (!$api_key || !$image_url) {
            return new WP_Error('missing_data', 'Missing API key or image.');
        }

        // Fetch and encode image in base64
        $image_data = file_get_contents($image_url);
        if (!$image_data) {
            return new WP_Error('image_error', 'Failed to load image.');
        }
        $base64 = base64_encode($image_data);

        // Call OpenAI
        $body = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,$base64"]],
                ],
            ]],
            'max_tokens' => 50,
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            $filename_raw = $body['choices'][0]['message']['content'];
            return sanitize_title($filename_raw); // remove spaces, symbols, etc.
        }

        return new WP_Error('ai_error', 'Failed to generate filename.');
    }
}
