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
    [
        'key' => 'show_picpilot_in_column',
        'label' => __('📋 Show PicPilot Tools in Column', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Display PicPilot tools in a dedicated column instead of hover row actions. More comfortable for frequent use.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'show_hover_info',
        'label' => __('ℹ️ Show Metadata on Hover', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Display alt text, title, and filename on hover when using the PicPilot column. Requires "Show PicPilot Tools in Column" to be enabled.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_media_modal_tools',
        'label' => __('🔧 Enable AI Tools in Media Modal', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Show AI tools in media library modal/popup. ⚠️ Disable this if you experience conflicts with page builders or performance issues.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_auto_generate_both',
        'label' => __('🪄 Enable Auto-Generate Both Button', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('Shows an additional "Auto-Generate Both" button when an image is missing both alt text and title. This generates both attributes at once with AI. ⚠️ This feature consumes more API credits.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'enable_dangerous_filename_rename',
        'label' => __('⚠️ Enable Dangerous Filename Renaming', 'pic-pilot-studio'),
        'type' => 'checkbox',
        'description' => __('DANGEROUS: Allows renaming image filenames which may break existing references. The system will detect image usage and warn you, but proceed with extreme caution. Only enable if you understand the risks.', 'pic-pilot-studio'),
    ],
];

foreach ($settings as $setting) {
    Settings::render_setting_row($setting);
}
