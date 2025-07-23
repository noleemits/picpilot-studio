<?php

namespace PicPilotStudio\Admin;

class Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_options_page(
            'Pic Pilot: Studio Settings',
            'Pic Pilot: Studio',
            'manage_options',
            'picpilot_studio_settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('picpilot_studio_group', 'picpilot_studio_settings');

        add_settings_section('ai_settings', 'AI Engine Settings', '__return_null', 'picpilot_studio_settings');
        add_settings_section('behavior_settings', 'Behavior Settings', '__return_null', 'picpilot_studio_settings');

        // Add fields
        add_settings_field('openai_api_key', 'OpenAI API Key', [__CLASS__, 'field_api_key'], 'picpilot_studio_settings', 'ai_settings');
        add_settings_field('default_prompt_alt', 'Default Alt Prompt', [__CLASS__, 'field_prompt_alt'], 'picpilot_studio_settings', 'ai_settings');
        add_settings_field('default_prompt_title', 'Default Title Prompt', [__CLASS__, 'field_prompt_title'], 'picpilot_studio_settings', 'ai_settings');
        add_settings_field('highlight_missing_alt', 'Highlight Missing Alt Tags', [__CLASS__, 'field_highlight_alt'], 'picpilot_studio_settings', 'behavior_settings');
        // Engine selection
        add_settings_field('default_engine', 'Default AI Engine', [__CLASS__, 'field_default_engine'], 'picpilot_studio_settings', 'ai_settings');

        // Filename prompt
        add_settings_field('default_prompt_filename', 'Filename Prompt', [__CLASS__, 'field_prompt_filename'], 'picpilot_studio_settings', 'ai_settings');

        // Engine toggle for filename
        add_settings_field('enable_filename_generation', 'Enable Filename Generation', [__CLASS__, 'field_enable_filename'], 'picpilot_studio_settings', 'behavior_settings');

        // Auto-apply
        add_settings_field('auto_apply_metadata', 'Auto-Apply Metadata', [__CLASS__, 'field_auto_apply'], 'picpilot_studio_settings', 'behavior_settings');

        // Allow regenerate alt
        add_settings_field('allow_regenerate_alt', 'Allow Regenerate ALT Text', [__CLASS__, 'field_regen_alt'], 'picpilot_studio_settings', 'behavior_settings');

        // Allow generate alt only
        add_settings_field('allow_generate_if_alt_missing', 'Allow "Generate ALT Only"', [__CLASS__, 'field_generate_if_missing'], 'picpilot_studio_settings', 'behavior_settings');

        // Show AI button in modal
        add_settings_field('show_ai_button_in_modal', 'Enable AI Button in Media Modals', [__CLASS__, 'field_modal_toggle'], 'picpilot_studio_settings', 'behavior_settings');

        //Enable logging
        add_settings_field(
            'log_enabled',
            __('Enable Logging', 'pic-pilot-studio'),
            [__CLASS__, 'field_log_enabled'],
            'picpilot_studio_settings',
            'behavior_settings'
        );
        //Keyword search field
        add_settings_field(
            'show_keywords_field',
            __('Show Keywords Input Field', 'pic-pilot-studio'),
            [__CLASS__, 'field_show_keywords_field'],
            'picpilot_studio_settings',
            'behavior_settings'
        );
    }

    public static function get_option($key, $default = '') {
        $options = get_option('picpilot_studio_settings', []);
        return isset($options[$key]) ? $options[$key] : $default;
    }

    public static function render_settings_page() {
?>
        <div class="wrap">
            <h1>Pic Pilot: Studio Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('picpilot_studio_group'); ?>

                <h2>AI Engine Settings</h2>
                <table class="form-table" role="presentation">
                    <?php do_settings_fields('picpilot_studio_settings', 'ai_settings'); ?>
                </table>

                <h2>Behavior & UI Settings</h2>
                <table class="form-table" role="presentation">
                    <?php do_settings_fields('picpilot_studio_settings', 'behavior_settings'); ?>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }

    public static function field_api_key() {
        $val = esc_attr(self::get_option('openai_api_key'));
    ?>
        <input type="password" id="openai_api_key" name="picpilot_studio_settings[openai_api_key]" value="<?php echo $val; ?>" style="width: 300px;" />
        <button type="button" class="button" onclick="toggleAPIKey()">👁</button>
        <script>
            function toggleAPIKey() {
                const input = document.getElementById('openai_api_key');
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        </script>
    <?php
    }

    public static function field_prompt_alt() {
        $val = esc_textarea(self::get_option('default_prompt_alt', 'Describe this image for alt text in one short sentence.'));
        echo '<textarea name="picpilot_studio_settings[default_prompt_alt]" rows="3" cols="60">' . $val . '</textarea>';
    }

    public static function field_prompt_title() {
        $val = esc_textarea(self::get_option('default_prompt_title', 'Suggest a short SEO-friendly title for this image.'));
        echo '<textarea name="picpilot_studio_settings[default_prompt_title]" rows="3" cols="60">' . $val . '</textarea>';
    }

    public static function field_highlight_alt() {
        $val = self::get_option('highlight_missing_alt') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[highlight_missing_alt]" value="1" ' . $val . '> Highlight images missing alt text in Media Library</label>';
    }

    //AI Section Fields

    public static function field_default_engine() {
        $val = self::get_option('default_engine', 'gpt-4');
    ?>
        <select name="picpilot_studio_settings[default_engine]">
            <option value="gpt-4" <?php selected($val, 'gpt-4'); ?>>GPT-4 Vision (OpenAI)</option>
            <option value="gemini" <?php selected($val, 'gemini'); ?>>Gemini (Google)</option>
            <option value="claude" <?php selected($val, 'claude'); ?>>Claude (Anthropic)</option>
        </select>
    <?php
    }

    public static function field_prompt_filename() {
        $val = esc_textarea(self::get_option('default_prompt_filename', 'Generate a short, SEO-friendly filename based on this image.'));
        echo '<textarea name="picpilot_studio_settings[default_prompt_filename]" rows="3" cols="60">' . $val . '</textarea>';
    }
    //Behavior Section Fields
    public static function field_enable_filename() {
        $val = self::get_option('enable_filename_generation') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[enable_filename_generation]" value="1" ' . $val . '> Enable filename suggestion and replacement</label>';
    }

    public static function field_auto_apply() {
        $val = self::get_option('auto_apply_metadata') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[auto_apply_metadata]" value="1" ' . $val . '> Automatically apply metadata without preview</label>';
    }

    public static function field_regen_alt() {
        $val = self::get_option('allow_regenerate_alt') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[allow_regenerate_alt]" value="1" ' . $val . '> Allow regenerating alt text even if already set</label>';
    }

    public static function field_generate_if_missing() {
        $val = self::get_option('allow_generate_if_alt_missing') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[allow_generate_if_alt_missing]" value="1" ' . $val . '> Enable "Generate ALT only" if no alt is set</label>';
    }
    public static function field_modal_toggle() {
        $val = self::get_option('show_ai_button_in_modal') ? 'checked' : '';
        echo '<label><input type="checkbox" name="picpilot_studio_settings[show_ai_button_in_modal]" value="1" ' . $val . '> Show AI metadata button inside Gutenberg/Elementor modals</label>';
    }
    public static function field_log_enabled() {
        $settings = get_option('picpilot_studio_settings', []);
    ?>
        <label>
            <input type="checkbox" name="picpilot_studio_settings[log_enabled]" value="1"
                <?= checked($settings['log_enabled'] ?? '', 1, false); ?>>
            <?= esc_html__('Log AI metadata generation requests and errors', 'pic-pilot-studio'); ?>
        </label>
    <?php
    }

    public static function field_show_keywords_field() {
        $settings = get_option('picpilot_studio_settings', []);
    ?>
        <label>
            <input type="checkbox" name="picpilot_studio_settings[show_keywords_field]" value="1"
                <?= checked($settings['show_keywords_field'] ?? '', 1, false); ?>>
            <?= esc_html__('Allow custom keywords/context for AI metadata generation', 'pic-pilot-studio'); ?>
        </label>
<?php
    }
}
