<?php

use PicPilotStudio\Admin\Settings;

$settings = [
    [
        'key' => 'enable_filename_generation',
        'label' => __('🧠 Enable Smart Filename Suggestions on Duplicate', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Adds a popup during duplication allowing manual, AI, or automatic filename choice.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_title_generation_on_duplicate',
        'label' => __('🧠 Enable Smart Title Suggestions on Duplicate', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Allows choosing to auto-copy, manually enter, or generate a new title with AI during duplication.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_alt_generation_on_duplicate',
        'label' => __('🧠 Enable Smart Alt Text Suggestions on Duplicate', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Same as above, but for alt text.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'auto_generate_metadata_on_upload',
        'label' => __('🪄 Auto-Generate Metadata on Upload', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Automatically generate title and alt using AI when a new image is uploaded. This may consume API credits.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'allow_generate_if_alt_missing',
        'label' => __('⚠️ Suggest Alt Text Only When Missing', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Prevent alt generation unless the image has no alt text.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'allow_regenerate_alt',
        'label' => __('🔁 Allow Alt Text Regeneration', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Enables the "Generate Alt Text" button even if alt already exists.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'show_keywords_field',
        'label' => __('🧩 Show Keywords Field in Media Library', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Adds a field to pass keywords for more accurate AI results (used in grid view).', 'pic-pilot-studio'),
    ],
    [
        'key' => 'show_ai_button_in_modal',
        'label' => __('🧠 Enable AI Metadata in Media Modal (Beta)', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Adds AI buttons inside the Gutenberg/Elementor attachment modals.', 'pic-pilot-studio'),
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
