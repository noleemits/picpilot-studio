<?php

namespace PicPilotStudio\Admin;

defined('ABSPATH') || exit;

class MediaList {

    public static function init() {
        add_filter('media_row_actions', [__CLASS__, 'add_duplicate_action'], 10, 2);
        // Enqueue styles/scripts for list view
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_filter('media_row_actions', [__CLASS__, 'add_generate_button'], 10, 2);
        
        // Add alt text filtering
        add_action('restrict_manage_posts', [__CLASS__, 'add_alt_text_filter']);
        add_filter('pre_get_posts', [__CLASS__, 'filter_by_alt_text']);
        
        // Add attachment edit page functionality
        add_action('edit_form_after_title', [__CLASS__, 'add_generate_buttons_to_edit_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_edit_page_scripts']);
        
        // Add bulk actions
        add_filter('bulk_actions-upload', [__CLASS__, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [__CLASS__, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [__CLASS__, 'bulk_action_notices']);
    }


    public static function add_generate_button($actions, $post) {
        if ($post->post_type !== 'attachment' || !wp_attachment_is_image($post->ID)) return $actions;

        $settings = get_option('picpilot_studio_settings', []);
        $show_keywords = !empty($settings['show_keywords_field']);

        // Only output keyword input once
        $keywords_input = '';
        if ($show_keywords) {
            $keywords_input = '<input type="text" class="picpilot-keywords" placeholder="' . esc_attr__('Optional keywords/context', 'pic-pilot-studio') . '" data-id="' . esc_attr($post->ID) . '" style="margin-right:6px;max-width:160px;" />';
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
        $alt_button_text = !empty($existing_alt) ? 
            esc_html__('Regenerate Alt Text', 'pic-pilot-studio') : 
            esc_html__('Generate Alt Text', 'pic-pilot-studio');

        $actions['generate_meta'] = $keywords_input
            . sprintf(
                '<button type="button" class="picpilot-generate-meta" data-id="%d" data-type="alt">%s</button> ',
                esc_attr($post->ID),
                $alt_button_text
            )
            . sprintf(
                '<button type="button" class="picpilot-generate-meta" data-id="%d" data-type="title">%s</button>',
                esc_attr($post->ID),
                esc_html__('Generate Title', 'pic-pilot-studio')
            );

        return $actions;
    }



    public static function add_duplicate_action($actions, $post) {
        if ($post->post_type === 'attachment' && current_user_can('upload_files')) {
            $actions['duplicate_image_quick'] = '<a href="#" class="pic-pilot-duplicate-image" data-id="' . esc_attr($post->ID) . '">' . esc_html__('Duplicate', 'pic-pilot-studio') . '</a>';
        }

        return $actions;
    }



    public static function enqueue_scripts($hook) {
        if ('upload.php' !== $hook) {
            return;
        }

        // Enqueue CSS styles
        wp_enqueue_style(
            'pic-pilot-studio-styles',
            PIC_PILOT_STUDIO_URL . 'assets/css/pic-pilot-studio.css',
            [],
            '1.0.0'
        );

        $settings = get_option('picpilot_studio_settings', []);

        // Enqueue for duplicate logic (still uses jQuery)
        wp_enqueue_script(
            'pic-pilot-studio-duplicate',
            PIC_PILOT_STUDIO_URL . 'assets/js/duplicate-image.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('pic-pilot-studio-duplicate', 'PicPilotStudio', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('picpilot_studio_generate'),
            'enable_filename_generation' => $settings['enable_filename_generation'] ?? false,
            'enable_title_generation_on_duplicate' => $settings['enable_title_generation_on_duplicate'] ?? false,
            'enable_alt_generation_on_duplicate' => $settings['enable_alt_generation_on_duplicate'] ?? false,
        ]);


        // Enqueue media list script (vanilla JS)
        wp_enqueue_script(
            'pic-pilot-media-list',
            PIC_PILOT_STUDIO_URL . 'assets/js/media-list.js',
            [],
            '1.0.0',
            true
        );

        //Enqueue smart duplication modal
        wp_enqueue_script(
            'picpilot-smart-duplication',
            PIC_PILOT_STUDIO_URL . 'assets/js/smart-duplication-modal.js',
            [],
            null,
            true
        );


        // Enqueue bulk operations script
        wp_enqueue_script(
            'pic-pilot-bulk-operations',
            PIC_PILOT_STUDIO_URL . 'assets/js/bulk-operations.js',
            [],
            '1.0.0',
            true
        );

        // Enqueue image tags script
        wp_enqueue_script(
            'pic-pilot-image-tags',
            PIC_PILOT_STUDIO_URL . 'assets/js/image-tags.js',
            [],
            '1.0.0',
            true
        );

        // Attach config BEFORE script load for media-list, smart-duplication modal, and bulk operations
        $bulk_data = [];
        if (isset($_GET['picpilot_bulk_action']) && $_GET['picpilot_bulk_action'] === 'generate') {
            $bulk_data = [
                'key' => sanitize_text_field($_GET['picpilot_bulk_key'] ?? ''),
                'count' => intval($_GET['picpilot_bulk_count'] ?? 0)
            ];
        }

        wp_add_inline_script('pic-pilot-media-list', 'window.picPilotStudio = ' . json_encode([
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('picpilot_studio_generate'),
            'bulk' => $bulk_data,
        ]) . ';', 'before');
    }




    public static function add_row_actions($actions, $post) {
        if ($post->post_type !== 'attachment' || !wp_attachment_is_image($post)) return $actions;

        $actions['picpilot_generate'] = sprintf(
            '<a href="#" class="pic-pilot-generate-metadata" data-id="%d">%s</a>',
            esc_attr($post->ID),
            esc_html__('Generate Metadata', 'pic-pilot-studio')
        );

        return $actions;
    }

    /**
     * Add comprehensive image filter dropdown to media library
     */
    public static function add_alt_text_filter() {
        global $pagenow;
        
        if ($pagenow !== 'upload.php') {
            return;
        }

        // Get current filter values
        $current_alt_filter = isset($_GET['picpilot_alt_filter']) ? sanitize_text_field($_GET['picpilot_alt_filter']) : '';
        $current_tag_filter = isset($_GET['picpilot_tag_filter']) ? sanitize_text_field($_GET['picpilot_tag_filter']) : '';
        $current_filter = $current_alt_filter ?: ($current_tag_filter ? 'tag:' . $current_tag_filter : '');

        // Get available tags with counts
        $tags = get_terms([
            'taxonomy' => 'picpilot_image_tags',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        // Calculate accurate tag counts
        $tag_options = [];
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $attachments = get_objects_in_term($tag->term_id, 'picpilot_image_tags');
                $image_count = 0;
                
                if (is_array($attachments)) {
                    foreach ($attachments as $attachment_id) {
                        if (wp_attachment_is_image($attachment_id)) {
                            $image_count++;
                        }
                    }
                }
                
                if ($image_count > 0) {
                    $tag_options[] = [
                        'value' => 'tag:' . $tag->slug,
                        'label' => sprintf('Tagged: %s (%d)', $tag->name, $image_count),
                        'selected' => $current_filter === ('tag:' . $tag->slug)
                    ];
                }
            }
        }
        
        echo '<select name="picpilot_filter" id="picpilot-comprehensive-filter">';
        echo '<option value="">' . esc_html__('All Images', 'pic-pilot-studio') . '</option>';
        
        // Alt text options
        echo '<optgroup label="' . esc_attr__('By Alt Text', 'pic-pilot-studio') . '">';
        echo '<option value="with_alt"' . selected($current_filter, 'with_alt', false) . '>' . esc_html__('Images with Alt Text', 'pic-pilot-studio') . '</option>';
        echo '<option value="without_alt"' . selected($current_filter, 'without_alt', false) . '>' . esc_html__('Images without Alt Text', 'pic-pilot-studio') . '</option>';
        echo '</optgroup>';
        
        // Tag options
        if (!empty($tag_options)) {
            echo '<optgroup label="' . esc_attr__('By Tags', 'pic-pilot-studio') . '">';
            foreach ($tag_options as $tag_option) {
                echo sprintf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($tag_option['value']),
                    selected($tag_option['selected'], true, false),
                    esc_html($tag_option['label'])
                );
            }
            echo '</optgroup>';
        }
        
        echo '</select>';
        
        // Add JavaScript to handle the unified filter
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const filter = document.getElementById('picpilot-comprehensive-filter');
            if (filter) {
                filter.addEventListener('change', function() {
                    const form = this.closest('form') || document.getElementById('posts-filter');
                    if (form) {
                        // Clear existing filter inputs
                        const existingInputs = form.querySelectorAll('input[name="picpilot_alt_filter"], input[name="picpilot_tag_filter"]');
                        existingInputs.forEach(input => input.remove());
                        
                        // Add appropriate hidden input based on selection
                        const value = this.value;
                        if (value.startsWith('tag:')) {
                            const tagSlug = value.replace('tag:', '');
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'picpilot_tag_filter';
                            hiddenInput.value = tagSlug;
                            form.appendChild(hiddenInput);
                        } else if (value) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'picpilot_alt_filter';
                            hiddenInput.value = value;
                            form.appendChild(hiddenInput);
                        }
                        
                        form.submit();
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Filter attachments by alt text status
     */
    public static function filter_by_alt_text($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'upload.php' || !$query->is_main_query()) {
            return;
        }

        if (!isset($_GET['picpilot_alt_filter']) || empty($_GET['picpilot_alt_filter'])) {
            return;
        }

        $filter = sanitize_text_field($_GET['picpilot_alt_filter']);
        
        if ($filter === 'with_alt') {
            // Images with alt text
            $query->set('meta_query', [
                [
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '!='
                ]
            ]);
        } elseif ($filter === 'without_alt') {
            // Images without alt text
            $query->set('meta_query', [
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
            ]);
        }

        // Ensure we only get image attachments
        $query->set('post_type', 'attachment');
        $query->set('post_status', 'inherit');
        $query->set('post_mime_type', 'image');
    }

    /**
     * Add generate buttons to attachment edit page
     */
    public static function add_generate_buttons_to_edit_page($post) {
        if (!$post || $post->post_type !== 'attachment' || !wp_attachment_is_image($post->ID)) {
            return;
        }

        $existing_alt = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
        $alt_button_text = !empty($existing_alt) ? 
            esc_html__('Regenerate Alt Text', 'pic-pilot-studio') : 
            esc_html__('Generate Alt Text', 'pic-pilot-studio');

        ?>
        <div id="picpilot-edit-page-controls" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
            <h3 style="margin-top: 0; font-size: 14px; color: #333;">
                🤖 <?php esc_html_e('AI Metadata Generation', 'pic-pilot-studio'); ?>
            </h3>
            
            <div style="margin-bottom: 15px;">
                <label for="picpilot-edit-keywords" style="display: block; margin-bottom: 5px; font-weight: 600;">
                    <?php esc_html_e('Keywords (optional):', 'pic-pilot-studio'); ?>
                </label>
                <input type="text" id="picpilot-edit-keywords" class="widefat" 
                       placeholder="<?php esc_attr_e('Add context for better AI results (e.g., Business manager, construction site)', 'pic-pilot-studio'); ?>" />
                <p class="description">
                    <?php esc_html_e('Provide context about the person, profession, or setting to help AI generate more accurate descriptions.', 'pic-pilot-studio'); ?>
                </p>
            </div>

            <div class="picpilot-edit-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="picpilot-generate-title-edit" class="button button-secondary" data-id="<?php echo esc_attr($post->ID); ?>">
                    <span class="dashicons dashicons-format-chat" style="margin-right: 5px;"></span>
                    <?php esc_html_e('Generate Title', 'pic-pilot-studio'); ?>
                </button>
                
                <button type="button" id="picpilot-generate-alt-edit" class="button button-secondary" data-id="<?php echo esc_attr($post->ID); ?>">
                    <span class="dashicons dashicons-universal-access-alt" style="margin-right: 5px;"></span>
                    <?php echo $alt_button_text; ?>
                </button>
            </div>

            <div id="picpilot-edit-status" style="margin-top: 10px; padding: 8px; border-radius: 3px; display: none;">
                <!-- Status messages will appear here -->
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts for attachment edit page
     */
    public static function enqueue_edit_page_scripts($hook) {
        global $post;
        
        if ($hook !== 'post.php' || !$post || $post->post_type !== 'attachment' || !wp_attachment_is_image($post->ID)) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'pic-pilot-studio-edit-styles',
            PIC_PILOT_STUDIO_URL . 'assets/css/pic-pilot-studio.css',
            [],
            '1.0.0'
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'pic-pilot-studio-edit',
            PIC_PILOT_STUDIO_URL . 'assets/js/attachment-edit.js',
            [],
            '1.0.0',
            true
        );

        // Localize script
        wp_localize_script('pic-pilot-studio-edit', 'PicPilotStudioEdit', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('picpilot_studio_generate'),
            'attachment_id' => $post->ID,
        ]);
    }

    /**
     * Add bulk actions to media library
     */
    public static function add_bulk_actions($actions) {
        $actions['picpilot_bulk_generate'] = __('Generate AI Metadata', 'pic-pilot-studio');
        return $actions;
    }

    /**
     * Handle bulk actions - redirect to JavaScript modal instead of processing here
     */
    public static function handle_bulk_actions($redirect_url, $action, $post_ids) {
        if ($action !== 'picpilot_bulk_generate') {
            return $redirect_url;
        }

        // Filter to only include image attachments
        $image_ids = [];
        foreach ($post_ids as $id) {
            if (wp_attachment_is_image($id)) {
                $image_ids[] = $id;
            }
        }

        if (empty($image_ids)) {
            $redirect_url = add_query_arg('picpilot_bulk_error', 'no_images', $redirect_url);
            return $redirect_url;
        }

        // Store selected IDs in transient for JavaScript to access
        $transient_key = 'picpilot_bulk_' . get_current_user_id() . '_' . time();
        set_transient($transient_key, $image_ids, 300); // 5 minutes

        // Add query args to trigger JavaScript modal
        $redirect_url = add_query_arg([
            'picpilot_bulk_action' => 'generate',
            'picpilot_bulk_key' => $transient_key,
            'picpilot_bulk_count' => count($image_ids)
        ], $redirect_url);

        return $redirect_url;
    }

    /**
     * Display bulk action notices
     */
    public static function bulk_action_notices() {
        if (isset($_GET['picpilot_bulk_error']) && $_GET['picpilot_bulk_error'] === 'no_images') {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 esc_html__('No image attachments were selected for AI metadata generation.', 'pic-pilot-studio') . 
                 '</p></div>';
        }

        if (isset($_GET['picpilot_bulk_success'])) {
            $count = intval($_GET['picpilot_bulk_success']);
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 sprintf(esc_html__('Successfully generated AI metadata for %d images.', 'pic-pilot-studio'), $count) . 
                 '</p></div>';
        }
    }
}
