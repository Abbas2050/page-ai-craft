<?php
/**
 * Admin interface for AI PageGen plugin
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen Admin Class
 */
class AI_PageGen_Admin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ai_pagegen_generate', array($this, 'handle_ajax_generate'));
        add_action('wp_ajax_ai_pagegen_create_page', array($this, 'handle_ajax_create_page'));
        add_action('wp_ajax_ai_pagegen_clear_logs', array($this, 'handle_clear_logs'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI PageGen', 'ai-pagegen'),
            __('AI PageGen', 'ai-pagegen'),
            'manage_ai_pagegen',
            'ai-pagegen',
            array($this, 'admin_page'),
            'dashicons-robot',
            30
        );
        
        add_submenu_page(
            'ai-pagegen',
            __('Generate Content', 'ai-pagegen'),
            __('Generate Content', 'ai-pagegen'),
            'manage_ai_pagegen',
            'ai-pagegen',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'ai-pagegen',
            __('Settings', 'ai-pagegen'),
            __('Settings', 'ai-pagegen'),
            'manage_ai_pagegen',
            'ai-pagegen-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'ai-pagegen',
            __('Logs', 'ai-pagegen'),
            __('Logs', 'ai-pagegen'),
            'manage_ai_pagegen',
            'ai-pagegen-logs',
            array($this, 'logs_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'ai_pagegen_settings_group',
            'ai_pagegen_settings',
            array($this, 'sanitize_settings')
        );
        
        // General Settings Section
        add_settings_section(
            'ai_pagegen_general_section',
            __('General Settings', 'ai-pagegen'),
            array($this, 'general_section_callback'),
            'ai-pagegen-settings'
        );
        
        // OpenAI API Key
        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'ai-pagegen'),
            array($this, 'openai_api_key_callback'),
            'ai-pagegen-settings',
            'ai_pagegen_general_section'
        );
        
        // Default Post Type
        add_settings_field(
            'default_post_type',
            __('Default Post Type', 'ai-pagegen'),
            array($this, 'default_post_type_callback'),
            'ai-pagegen-settings',
            'ai_pagegen_general_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['openai_api_key'])) {
            $sanitized['openai_api_key'] = sanitize_text_field($input['openai_api_key']);
        }
        
        if (isset($input['default_post_type'])) {
            $sanitized['default_post_type'] = sanitize_text_field($input['default_post_type']);
        }
        
        if (isset($input['default_header_footer'])) {
            $sanitized['default_header_footer'] = sanitize_text_field($input['default_header_footer']);
        }
        
        if (isset($input['seo_optimization'])) {
            $sanitized['seo_optimization'] = (bool) $input['seo_optimization'];
        }
        
        if (isset($input['default_color_scheme'])) {
            $sanitized['default_color_scheme'] = sanitize_text_field($input['default_color_scheme']);
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ai-pagegen') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ai-pagegen-admin',
            AI_PAGEGEN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_PAGEGEN_VERSION
        );
        
        wp_enqueue_script(
            'ai-pagegen-admin',
            AI_PAGEGEN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_PAGEGEN_VERSION,
            true
        );
        
        wp_localize_script('ai-pagegen-admin', 'aiPageGen', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_pagegen_nonce'),
            'is_pro' => AI_PageGen_Licensing::is_pro_user(),
            'strings' => array(
                'generating' => __('Generating content...', 'ai-pagegen'),
                'error' => __('An error occurred. Please try again.', 'ai-pagegen'),
                'pro_required' => __('This feature is available in Pro version only.', 'ai-pagegen'),
                'page_created' => __('Page created successfully!', 'ai-pagegen'),
                'creating_page' => __('Creating page...', 'ai-pagegen')
            )
        ));
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $is_pro = AI_PageGen_Licensing::is_pro_user();
        ?>
        <div class="wrap ai-pagegen-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-pagegen-container">
                <div class="ai-pagegen-form-container">
                    <form id="ai-pagegen-form" method="post">
                        <?php wp_nonce_field('ai_pagegen_generate', 'ai_pagegen_nonce'); ?>
                        
                        <!-- Main Prompt -->
                        <div class="form-group">
                            <label for="ai_prompt"><?php _e('Content Prompt', 'ai-pagegen'); ?></label>
                            <textarea id="ai_prompt" name="ai_prompt" rows="4" placeholder="<?php _e('Describe what you want to generate...', 'ai-pagegen'); ?>" required></textarea>
                        </div>
                        
                        <!-- Post Type Selection -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label for="post_type"><?php _e('Post Type', 'ai-pagegen'); ?></label>
                            <select id="post_type" name="post_type" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                                <option value="post"><?php _e('Blog Post', 'ai-pagegen'); ?></option>
                                <option value="page"><?php _e('Page', 'ai-pagegen'); ?></option>
                            </select>
                            <?php if (!$is_pro) : ?>
                                <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Header/Footer Options -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label for="header_footer"><?php _e('Header/Footer Style', 'ai-pagegen'); ?></label>
                            <select id="header_footer" name="header_footer" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                                <option value="theme"><?php _e('Use Theme Default', 'ai-pagegen'); ?></option>
                                <option value="custom"><?php _e('Custom AI Generated', 'ai-pagegen'); ?></option>
                            </select>
                            <?php if (!$is_pro) : ?>
                                <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- SEO Optimization -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label>
                                <input type="checkbox" id="seo_optimization" name="seo_optimization" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                                <?php _e('Enable SEO Optimization', 'ai-pagegen'); ?>
                                <?php if (!$is_pro) : ?>
                                    <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        
                        <!-- SEO Keywords (shown when SEO is enabled) -->
                        <div id="seo_fields" class="form-group" style="display: none;">
                            <label for="seo_keywords"><?php _e('SEO Keywords', 'ai-pagegen'); ?></label>
                            <input type="text" id="seo_keywords" name="seo_keywords" placeholder="<?php _e('Enter keywords separated by commas', 'ai-pagegen'); ?>">
                        </div>
                        
                        <!-- Color Scheme -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label for="color_scheme"><?php _e('Color Scheme', 'ai-pagegen'); ?></label>
                            <input type="text" id="color_scheme" name="color_scheme" placeholder="<?php _e('e.g., #2271b1,#ffffff,#000000 or blue/white/dark', 'ai-pagegen'); ?>" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <?php if (!$is_pro) : ?>
                                <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Page Sections -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label for="page_sections"><?php _e('Page Sections', 'ai-pagegen'); ?></label>
                            <input type="text" id="page_sections" name="page_sections" placeholder="<?php _e('e.g., Hero, Services, About, Contact', 'ai-pagegen'); ?>" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                            <?php if (!$is_pro) : ?>
                                <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Elementor Compatibility (Pro Only) -->
                        <div class="form-group<?php echo !$is_pro ? ' pro-disabled' : ''; ?>">
                            <label>
                                <input type="checkbox" id="elementor_compatible" name="elementor_compatible" <?php echo !$is_pro ? 'disabled' : ''; ?>>
                                <?php _e('Make Elementor Compatible', 'ai-pagegen'); ?>
                                <?php if (!$is_pro) : ?>
                                    <span class="pro-tooltip" data-tooltip="<?php _e('Available in Pro version', 'ai-pagegen'); ?>">🔒</span>
                                <?php endif; ?>
                            </label>
                            <p class="description"><?php _e('Generate content compatible with Elementor page builder (Pro feature)', 'ai-pagegen'); ?></p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary button-large" id="generate-btn">
                                <span class="dashicons dashicons-robot"></span>
                                <?php _e('Generate Content', 'ai-pagegen'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="ai-pagegen-preview">
                    <h3><?php _e('Generated Content Preview', 'ai-pagegen'); ?></h3>
                    <div id="content-preview">
                        <p class="placeholder"><?php _e('Generated content will appear here...', 'ai-pagegen'); ?></p>
                    </div>
                    <div id="page-actions" style="display: none; margin-top: 20px;">
                        <button type="button" class="button button-primary" id="create-page-btn">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Create WordPress Page', 'ai-pagegen'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI PageGen Settings', 'ai-pagegen'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_pagegen_settings_group');
                do_settings_sections('ai-pagegen-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        $logs = AI_PageGen_Logger::get_recent_logs(200);
        $log_size = AI_PageGen_Logger::get_log_size();
        ?>
        <div class="wrap">
            <h1><?php _e('AI PageGen Logs', 'ai-pagegen'); ?></h1>
            
            <div class="log-header" style="margin-bottom: 20px;">
                <p><?php printf(__('Log file size: %s bytes', 'ai-pagegen'), number_format($log_size)); ?></p>
                <button type="button" class="button button-secondary" id="clear-logs-btn">
                    <?php _e('Clear Logs', 'ai-pagegen'); ?>
                </button>
                <button type="button" class="button" onclick="location.reload();">
                    <?php _e('Refresh', 'ai-pagegen'); ?>
                </button>
            </div>
            
            <div class="log-container" style="background: #f1f1f1; padding: 15px; border: 1px solid #ccc; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                <?php if (empty($logs)) : ?>
                    <p><?php _e('No logs available.', 'ai-pagegen'); ?></p>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <div style="margin-bottom: 5px; <?php echo strpos($log, '[ERROR]') !== false ? 'color: red;' : (strpos($log, '[WARNING]') !== false ? 'color: orange;' : ''); ?>">
                            <?php echo esc_html($log); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#clear-logs-btn').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to clear all logs?', 'ai-pagegen'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'ai_pagegen_clear_logs',
                        nonce: '<?php echo wp_create_nonce('ai_pagegen_clear_logs'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Failed to clear logs.', 'ai-pagegen'); ?>');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the general settings for AI PageGen.', 'ai-pagegen') . '</p>';
    }
    
    /**
     * OpenAI API Key callback
     */
    public function openai_api_key_callback() {
        $options = get_option('ai_pagegen_settings');
        $api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
        ?>
        <input type="password" id="openai_api_key" name="ai_pagegen_settings[openai_api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
        <p class="description"><?php _e('Enter your OpenAI API key. You can get one from OpenAI\'s website.', 'ai-pagegen'); ?></p>
        <?php
    }
    
    /**
     * Default post type callback
     */
    public function default_post_type_callback() {
        $options = get_option('ai_pagegen_settings');
        $post_type = isset($options['default_post_type']) ? $options['default_post_type'] : 'post';
        ?>
        <select id="default_post_type" name="ai_pagegen_settings[default_post_type]">
            <option value="post" <?php selected($post_type, 'post'); ?>><?php _e('Post', 'ai-pagegen'); ?></option>
            <option value="page" <?php selected($post_type, 'page'); ?>><?php _e('Page', 'ai-pagegen'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Handle AJAX content generation
     */
    public function handle_ajax_generate() {
        // Log the start of generation
        AI_PageGen_Logger::info('Content generation started', array(
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_nonce')) {
            AI_PageGen_Logger::error('Nonce verification failed');
            wp_send_json_error(__('Security check failed', 'ai-pagegen'));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_ai_pagegen')) {
            AI_PageGen_Logger::error('User permission denied', array('user_id' => get_current_user_id()));
            wp_send_json_error(__('You do not have permission to perform this action', 'ai-pagegen'));
            return;
        }
        
        $prompt = sanitize_textarea_field($_POST['ai_prompt']);
        $post_type = sanitize_text_field($_POST['post_type']);
        $header_footer = sanitize_text_field($_POST['header_footer']);
        $seo_optimization = isset($_POST['seo_optimization']) ? true : false;
        $seo_keywords = sanitize_text_field($_POST['seo_keywords']);
        $color_scheme = sanitize_text_field($_POST['color_scheme']);
        $page_sections = sanitize_text_field($_POST['page_sections']);
        $elementor_compatible = isset($_POST['elementor_compatible']) ? true : false;
        
        AI_PageGen_Logger::info('Generation parameters', array(
            'prompt' => substr($prompt, 0, 100) . '...',
            'post_type' => $post_type,
            'seo_optimization' => $seo_optimization,
            'elementor_compatible' => $elementor_compatible
        ));
        
        // Check if pro features are being used
        $is_pro = AI_PageGen_Licensing::is_pro_user();
        if (!$is_pro && ($post_type !== 'post' || $header_footer !== 'theme' || $seo_optimization || !empty($color_scheme) || !empty($page_sections) || $elementor_compatible)) {
            AI_PageGen_Logger::warning('Pro features attempted by free user');
            wp_send_json_error(__('Pro features require upgrading to Pro version', 'ai-pagegen'));
            return;
        }
        
        try {
            // Generate content using OpenAI
            $openai = new AI_PageGen_OpenAI();
            $content = $openai->generate_content($prompt, array(
                'post_type' => $post_type,
                'header_footer' => $header_footer,
                'seo_optimization' => $seo_optimization,
                'seo_keywords' => $seo_keywords,
                'color_scheme' => $color_scheme,
                'page_sections' => $page_sections,
                'elementor_compatible' => $elementor_compatible
            ));
            
            if ($content) {
                AI_PageGen_Logger::info('Content generated successfully', array(
                    'content_length' => strlen($content['content'])
                ));
                
                wp_send_json_success(array(
                    'content' => $content['content'],
                    'title' => $content['title'],
                    'seo_data' => isset($content['seo_data']) ? $content['seo_data'] : null,
                    'full_content' => $content
                ));
            } else {
                AI_PageGen_Logger::error('Content generation failed - empty response');
                wp_send_json_error(__('Failed to generate content', 'ai-pagegen'));
            }
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Content generation exception', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle AJAX page creation
     */
    public function handle_ajax_create_page() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_nonce')) {
            AI_PageGen_Logger::error('Page creation - Nonce verification failed');
            wp_send_json_error(__('Security check failed', 'ai-pagegen'));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_pages')) {
            AI_PageGen_Logger::error('Page creation - User permission denied');
            wp_send_json_error(__('You do not have permission to create pages', 'ai-pagegen'));
            return;
        }
        
        $content_data = json_decode(stripslashes($_POST['content_data']), true);
        $elementor_compatible = isset($_POST['elementor_compatible']) ? true : false;
        
        if (!$content_data) {
            AI_PageGen_Logger::error('Page creation - Invalid content data');
            wp_send_json_error(__('Invalid content data', 'ai-pagegen'));
            return;
        }
        
        try {
            // Create the post/page
            $post_creator = new AI_PageGen_Post_Creator();
            $post_id = $post_creator->create_post($content_data, array(
                'post_type' => 'page',
                'elementor_compatible' => $elementor_compatible
            ));
            
            if ($post_id) {
                AI_PageGen_Logger::info('Page created successfully', array(
                    'post_id' => $post_id,
                    'title' => $content_data['title']
                ));
                
                wp_send_json_success(array(
                    'post_id' => $post_id,
                    'edit_link' => get_edit_post_link($post_id),
                    'view_link' => get_permalink($post_id),
                    'message' => __('Page created successfully!', 'ai-pagegen')
                ));
            } else {
                AI_PageGen_Logger::error('Page creation failed');
                wp_send_json_error(__('Failed to create page', 'ai-pagegen'));
            }
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Page creation exception', array(
                'error' => $e->getMessage()
            ));
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle clear logs AJAX
     */
    public function handle_clear_logs() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_clear_logs')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        if (!current_user_can('manage_ai_pagegen')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        if (AI_PageGen_Logger::clear_logs()) {
            wp_send_json_success('Logs cleared successfully');
        } else {
            wp_send_json_error('Failed to clear logs');
        }
    }
}
