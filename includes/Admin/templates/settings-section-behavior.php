<?php

use PicPilotStudio\Admin\Settings;

$settings = [
    [
        'key' => 'enable_filename_generation',
        'label' => __('🧠 Enable Smart Filename Generation', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('When duplicating images, shows a modal with options to manually enter, auto-generate with AI, or keep the original filename. When disabled, duplicates use simple "-copy" suffix.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_title_generation_on_duplicate',
        'label' => __('🧠 Enable Smart Title Generation', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('When duplicating images, allows you to manually enter a new title, generate one with AI using keywords, or copy the original title. When disabled, duplicates get "(Copy)" suffix.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_alt_generation_on_duplicate',
        'label' => __('🧠 Enable Smart Alt Text Generation', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('When duplicating images, allows you to manually enter new alt text, generate descriptive alt text with AI using keywords, or copy the original alt text.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'auto_generate_metadata_on_upload',
        'label' => __('🪄 Auto-Generate Alt Text on Upload', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Automatically generate descriptive alt text using AI when a new image is uploaded. ⚠️ This feature consumes OpenAI API credits with each upload.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'auto_generate_title_on_upload',
        'label' => __('🪄 Auto-Generate Title on Upload', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Automatically generate SEO-friendly titles using AI when a new image is uploaded. ⚠️ This feature consumes OpenAI API credits with each upload.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'show_keywords_field',
        'label' => __('🧩 Show Keywords Field in Media Library', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Adds a field to pass keywords for more accurate AI results (used in grid view).', 'pic-pilot-studio'),
    ],
    [
        'key' => 'log_enabled',
        'label' => __('📜 Enable AI Logging', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Logs all metadata generation attempts, results, and errors for debugging.', 'pic-pilot-studio'),
    ],
];

foreach ($settings as $setting) {
    Settings::render_setting_row($setting);
}
