<?php
// settings-section-ai.php (refactored with API key obscured + toggle)

use PicPilotStudio\Admin\Settings;

$settings = [
    [
        'key' => 'ai_provider',
        'label' => __('🤖 AI Provider', 'pic-pilot-studio'),
        'type' => 'select',
        'options' => [
            'openai' => __('OpenAI (GPT-4 Vision)', 'pic-pilot-studio'),
            'gemini' => __('Google Gemini Pro Vision', 'pic-pilot-studio'),
        ],
        'description' => __('Choose which AI service to use for generating metadata.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'openai_api_key',
        'label' => __('🔑 OpenAI API Key', 'pic-pilot-studio'),
        'type' => 'password-toggle',
        'description' => __('Required when using OpenAI provider. Get your API key from OpenAI dashboard.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'gemini_api_key',
        'label' => __('🔑 Gemini API Key', 'pic-pilot-studio'),
        'type' => 'password-toggle',
        'description' => __('Required when using Gemini provider. Get your API key from Google AI Studio.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'default_prompt_alt',
        'label' => __('✍️ Default Alt Text Prompt', 'pic-pilot-studio'),
        'type' => 'text',
        'description' => __('Prompt used to generate descriptive alt text via AI.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'default_prompt_title',
        'label' => __('🏷️ Default Title Prompt', 'pic-pilot-studio'),
        'type' => 'text',
        'description' => __('Prompt used to generate a smart title for the image via AI.', 'pic-pilot-studio'),
    ],
    [
        'key' => 'default_prompt_filename',
        'label' => __('🧾 Default Filename Prompt', 'pic-pilot-studio'),
        'type' => 'text',
        'description' => __('Prompt used to suggest a file name during duplication.', 'pic-pilot-studio'),
    ],
];

foreach ($settings as $setting) {
    Settings::render_setting_row($setting);
}

// Output JS to handle password-toggle visibility
add_action('admin_footer', function () {
    echo "<script>
    document.querySelectorAll('input[type=password][data-toggleable]').forEach(function(input) {
      const wrapper = document.createElement('div');
      wrapper.style.position = 'relative';
      wrapper.style.maxWidth = '480px';
      wrapper.style.display = 'inline-block';
      input.style.width = '100%';

      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      const toggle = document.createElement('span');
      toggle.textContent = '👁️';
      toggle.title = 'Show/hide API key';
      toggle.style.cssText = 'position:absolute; top:50%; right:8px; transform:translateY(-50%); cursor:pointer; font-size:14px;';
      wrapper.appendChild(toggle);

      toggle.addEventListener('click', function() {
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    });
  </script>";
});
