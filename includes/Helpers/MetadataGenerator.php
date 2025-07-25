<?php

namespace PicPilotStudio\Helpers;

use PicPilotStudio\Admin\Settings;
use WP_Error;

class MetadataGenerator {
    public static function generate($attachment_id, $type = 'alt') {
        $api_key = Settings::get('openai_api_key');
        $prompt_key = $type === 'title' ? 'default_prompt_title' : 'default_prompt_alt';
        $prompt = Settings::get($prompt_key) ?: ($type === 'title' ? 'Generate a descriptive title for this image.' : 'Generate a concise alt text for this image.');
        $image_path = get_attached_file($attachment_id);

        if (!$api_key || !$image_path || !file_exists($image_path)) {
            return new WP_Error('missing_data', 'Missing API key or image path.');
        }

        $image_data = file_get_contents($image_path);
        if (!$image_data) {
            return new WP_Error('image_error', 'Failed to load image file.');
        }

        $base64 = base64_encode($image_data);

        $body = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,$base64"]],
                ]
            ]],
            'max_tokens' => 80,
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            Logger::log("[{$type}] OpenAI error: " . $response->get_error_message());
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $content = trim($data['choices'][0]['message']['content'] ?? '');

        Logger::log("[{$type}] Raw AI response: " . $content);

        if (!$content) {
            return new WP_Error('empty_result', 'AI returned no usable metadata.');
        }

        if (str_starts_with($content, '"') && str_ends_with($content, '"')) {
            $content = trim($content, '"');
        } elseif (str_starts_with($content, '“') && str_ends_with($content, '”')) {
            $content = trim($content, '“”');
        }

        Logger::log("[{$type}] Cleaned result: " . $content);
        return $content;
    }
}
