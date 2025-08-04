<?php

namespace PicPilotStudio\Admin;

defined('ABSPATH') || exit;

class AttachmentFields {

    public static function init() {
        // Add AI tools to attachment edit form
        add_filter('attachment_fields_to_edit', [__CLASS__, 'add_ai_tools_fields'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_attachment_scripts']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_attachment_scripts']); // For frontend editors
    }

    /**
     * Add AI tools fields to attachment edit form
     */
    public static function add_ai_tools_fields($form_fields, $post) {
        // Only show for images
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        // Get current values
        $current_alt = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
        $current_title = $post->post_title;

        // Create AI Tools section
        $ai_tools_html = self::render_ai_tools_section($post->ID, $current_title, $current_alt);

        // Insert AI tools after standard fields but before alt text
        // Find the position to insert (after image_url if it exists, otherwise after post_excerpt)
        $insert_position = 0;
        $field_keys = array_keys($form_fields);
        
        // Debug: Log available fields to understand the structure
        error_log('PicPilot: Available form fields: ' . implode(', ', $field_keys));
        
        // Look for common fields that appear before alt text
        $target_fields = ['image_url', 'post_excerpt', 'post_content', 'url'];
        foreach ($target_fields as $target_field) {
            $pos = array_search($target_field, $field_keys);
            if ($pos !== false) {
                $insert_position = $pos + 1;
                error_log("PicPilot: Found $target_field at position $pos, inserting AI tools at position $insert_position");
                break;
            }
        }
        
        // If no target fields found, insert before image_alt
        if ($insert_position === 0) {
            $alt_pos = array_search('image_alt', $field_keys);
            $insert_position = $alt_pos !== false ? $alt_pos : count($form_fields);
            error_log("PicPilot: No target fields found, inserting at position $insert_position (before alt or at end)");
        }
        
        // Split the array and insert our field
        $before = array_slice($form_fields, 0, $insert_position, true);
        $after = array_slice($form_fields, $insert_position, null, true);
        
        $form_fields = array_merge($before, [
            'pic_pilot_ai_tools' => [
                'label' => '',
                'input' => 'html',
                'html' => $ai_tools_html,
                'show_in_edit' => true,
                'show_in_modal' => true,
            ]
        ], $after);

        return $form_fields;
    }

    /**
     * Render the AI tools section HTML
     */
    private static function render_ai_tools_section($attachment_id, $current_title, $current_alt) {
        ob_start();
        
        // Load script inline for page builder compatibility
        static $script_loaded = false;
        if (!$script_loaded) {
            $script_loaded = true;
            $script_url = PIC_PILOT_STUDIO_URL . 'assets/js/attachment-fields-vanilla.js?v=' . get_plugin_data(PIC_PILOT_STUDIO_PATH . 'pic-pilot-studio.php')['Version'];
            $ajax_url = admin_url('admin-ajax.php');
            $nonce = wp_create_nonce('picpilot_studio_generate');
            ?>
            <script>
            window.picPilotAttachment = { ajax_url: '<?php echo esc_js($ajax_url); ?>', nonce: '<?php echo esc_js($nonce); ?>' };
            </script>
            <script src="<?php echo esc_url($script_url); ?>"></script>
            <?php
        }
        ?>
        <div class="pic-pilot-attachment-ai-tools" data-attachment-id="<?php echo esc_attr($attachment_id); ?>">
            <div class="pic-pilot-ai-launcher" style="text-align: center; padding: 10px;">
                <button type="button" 
                        class="button button-primary pic-pilot-launch-modal-btn" 
                        data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                        style="background: #2271b1; border-color: #2271b1; font-size: 14px; padding: 8px 16px;">
                    🤖 AI Tools
                </button>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                    Generate alt text, titles, and duplicate with AI
                </p>
            </div>
        </div>

        <style>
            .pic-pilot-attachment-ai-tools {
                margin: 15px 0;
                padding: 12px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 6px;
                text-align: center;
            }
            
            .pic-pilot-ai-launcher {
                padding: 0 !important;
            }
            
            .pic-pilot-launch-modal-btn {
                width: 100% !important;
                margin: 0 !important;
                padding: 10px 16px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
            }
            
            .pic-pilot-launch-modal-btn:hover {
                background: #1e5c8c !important;
                border-color: #1e5c8c !important;
            }
            
            /* Better positioning in media sidebar */
            .media-sidebar .pic-pilot-attachment-ai-tools {
                margin: 12px 0;
                background: #fff;
                border: 2px solid #2271b1;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            /* Ensure full visibility */
            .attachment-details .pic-pilot-attachment-ai-tools {
                clear: both;
                display: block !important;
                visibility: visible !important;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts for attachment edit screen
     */
    public static function enqueue_attachment_scripts($hook = '') {
        // Only enqueue in admin or frontend editor contexts where media modals are used
        if (!is_admin() && !isset($_GET['elementor-preview']) && !isset($_GET['fl_builder'])) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'pic-pilot-attachment-fields',
            PIC_PILOT_STUDIO_URL . 'assets/css/pic-pilot-studio.css',
            [],
            PIC_PILOT_STUDIO_VERSION
        );

        // Enqueue JavaScript (main updated file with jQuery for modal functionality)
        wp_enqueue_script(
            'pic-pilot-attachment-fields',
            PIC_PILOT_STUDIO_URL . 'assets/js/attachment-fields.js',
            ['jquery'],
            PIC_PILOT_STUDIO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('pic-pilot-attachment-fields', 'picPilotAttachment', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('picpilot_studio_generate'),
        ]);
    }
}