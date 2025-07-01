
<?php
/**
 * Admin interface for AI PageGen
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
     * Get single instance
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ai_pagegen_generate', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_ai_pagegen_create_page', array($this, 'ajax_create_page'));
        add_action('wp_ajax_ai_pagegen_test_api', array($this, 'ajax_test_api'));
        add_action('admin_init', array($this, 'register_settings'));
        
        AI_PageGen_Logger::info('Admin class initialized');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI PageGen', 'ai-pagegen'),
            __('AI PageGen', 'ai-pagegen'),
            'edit_posts',
            'ai-pagegen',
            array($this, 'render_main_page'),
            'dashicons-admin-page',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-pagegen',
            __('Settings', 'ai-pagegen'),
            __('Settings', 'ai-pagegen'),
            'manage_options',
            'ai-pagegen-settings',
            array($this, 'render_settings_page')
        );
        
        // Logs submenu
        add_submenu_page(
            'ai-pagegen',
            __('Logs', 'ai-pagegen'),
            __('Logs', 'ai-pagegen'),
            'manage_options',
            'ai-pagegen-logs',
            array($this, 'render_logs_page')
        );
        
        // License submenu
        add_submenu_page(
            'ai-pagegen',
            __('License', 'ai-pagegen'),
            __('License', 'ai-pagegen'),
            'manage_options',
            'ai-pagegen-license',
            array($this, 'render_license_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ai_pagegen_settings', 'ai_pagegen_settings', array($this, 'validate_settings'));
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Validate API key
        if (isset($input['openai_api_key'])) {
            $api_key = sanitize_text_field($input['openai_api_key']);
            if (empty($api_key)) {
                add_settings_error('ai_pagegen_settings', 'api_key_empty', __('OpenAI API key is required.', 'ai-pagegen'));
            } elseif (!preg_match('/^sk-[a-zA-Z0-9]{48}$/', $api_key)) {
                add_settings_error('ai_pagegen_settings', 'api_key_invalid', __('OpenAI API key format is invalid.', 'ai-pagegen'));
            } else {
                $validated['openai_api_key'] = $api_key;
                AI_PageGen_Logger::info('API key updated successfully');
            }
        }
        
        // Validate other settings
        $validated['default_post_type'] = isset($input['default_post_type']) ? sanitize_text_field($input['default_post_type']) : 'post';
        $validated['seo_optimization'] = isset($input['seo_optimization']) ? (bool)$input['seo_optimization'] : false;
        $validated['default_color_scheme'] = isset($input['default_color_scheme']) ? sanitize_text_field($input['default_color_scheme']) : '#2271b1,#ffffff,#000000';
        
        return $validated;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ai-pagegen') === false) {
            return;
        }
        
        wp_enqueue_script(
            'ai-pagegen-admin',
            AI_PAGEGEN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_PAGEGEN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ai-pagegen-admin',
            AI_PAGEGEN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_PAGEGEN_VERSION
        );
        
        // Localize script
        $settings = get_option('ai_pagegen_settings', array());
        wp_localize_script('ai-pagegen-admin', 'aiPageGen', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_pagegen_nonce'),
            'settings' => $settings,
            'is_pro' => AI_PageGen_Licensing::is_pro_user(),
            'strings' => array(
                'generating' => __('Generating...', 'ai-pagegen'),
                'error' => __('An error occurred. Please try again.', 'ai-pagegen'),
                'success' => __('Content generated successfully!', 'ai-pagegen'),
                'creating_page' => __('Creating Page...', 'ai-pagegen'),
                'page_created' => __('Page created successfully!', 'ai-pagegen')
            )
        ));
    }
    
    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
        AI_PageGen_Logger::info('AJAX generate content request started');
        
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_nonce')) {
                AI_PageGen_Logger::error('Nonce verification failed');
                wp_send_json_error(__('Security check failed.', 'ai-pagegen'));
            }
            
            // Check user permissions
            if (!current_user_can('edit_posts')) {
                AI_PageGen_Logger::error('Insufficient permissions');
                wp_send_json_error(__('Insufficient permissions.', 'ai-pagegen'));
            }
            
            // Get form data
            $prompt = sanitize_textarea_field($_POST['ai_prompt']);
            $post_type = sanitize_text_field($_POST['post_type']);
            $seo_optimization = isset($_POST['seo_optimization']);
            $seo_keywords = sanitize_text_field($_POST['seo_keywords'] ?? '');
            $page_sections = sanitize_text_field($_POST['page_sections'] ?? '');
            $color_scheme = sanitize_text_field($_POST['color_scheme'] ?? '');
            $elementor_compatible = isset($_POST['elementor_compatible']);
            
            AI_PageGen_Logger::info('Form data received', array(
                'prompt_length' => strlen($prompt),
                'post_type' => $post_type,
                'seo_optimization' => $seo_optimization,
                'elementor_compatible' => $elementor_compatible
            ));
            
            // Validate prompt
            if (empty($prompt) || strlen($prompt) < 10) {
                AI_PageGen_Logger::error('Invalid prompt provided');
                wp_send_json_error(__('Please provide a detailed prompt (at least 10 characters).', 'ai-pagegen'));
            }
            
            // Check API key
            $settings = get_option('ai_pagegen_settings', array());
            if (empty($settings['openai_api_key'])) {
                AI_PageGen_Logger::error('No API key configured');
                wp_send_json_error(__('OpenAI API key is not configured. Please set it in plugin settings.', 'ai-pagegen'));
            }
            
            // Prepare options
            $options = array(
                'post_type' => $post_type,
                'seo_optimization' => $seo_optimization,
                'seo_keywords' => $seo_keywords,
                'page_sections' => $page_sections,
                'color_scheme' => $color_scheme,
                'elementor_compatible' => $elementor_compatible
            );
            
            // Generate content
            $openai = new AI_PageGen_OpenAI();
            $generated_content = $openai->generate_content($prompt, $options);
            
            if (!$generated_content) {
                AI_PageGen_Logger::error('Content generation failed');
                wp_send_json_error(__('Failed to generate content. Please check the logs for more details.', 'ai-pagegen'));
            }
            
            AI_PageGen_Logger::info('Content generated successfully');
            
            // Prepare response
            $response_data = array(
                'content' => $generated_content['content'],
                'title' => $generated_content['title'],
                'excerpt' => $generated_content['excerpt'],
                'full_content' => $generated_content
            );
            
            // Add SEO data if available
            if (isset($generated_content['seo_title']) && isset($generated_content['meta_description'])) {
                $response_data['seo_data'] = array(
                    'title' => $generated_content['seo_title'],
                    'description' => $generated_content['meta_description']
                );
            }
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Exception in content generation', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for creating WordPress page
     */
    public function ajax_create_page() {
        AI_PageGen_Logger::info('AJAX create page request started');
        
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_nonce')) {
                AI_PageGen_Logger::error('Nonce verification failed for page creation');
                wp_send_json_error(__('Security check failed.', 'ai-pagegen'));
            }
            
            // Check user permissions
            if (!current_user_can('edit_pages')) {
                AI_PageGen_Logger::error('Insufficient permissions for page creation');
                wp_send_json_error(__('Insufficient permissions to create pages.', 'ai-pagegen'));
            }
            
            // Get content data
            $content_data = json_decode(stripslashes($_POST['content_data']), true);
            $elementor_compatible = isset($_POST['elementor_compatible']) && $_POST['elementor_compatible'] === 'true';
            
            if (!$content_data) {
                AI_PageGen_Logger::error('Invalid content data for page creation');
                wp_send_json_error(__('Invalid content data.', 'ai-pagegen'));
            }
            
            AI_PageGen_Logger::info('Creating page with content', array(
                'title' => $content_data['title'],
                'elementor_compatible' => $elementor_compatible
            ));
            
            // Create page
            $post_creator = new AI_PageGen_Post_Creator();
            $post_id = $post_creator->create_post($content_data, array(
                'post_type' => 'page',
                'elementor_compatible' => $elementor_compatible
            ));
            
            if (!$post_id) {
                AI_PageGen_Logger::error('Failed to create page');
                wp_send_json_error(__('Failed to create page.', 'ai-pagegen'));
            }
            
            AI_PageGen_Logger::info('Page created successfully', array('post_id' => $post_id));
            
            wp_send_json_success(array(
                'message' => __('Page created successfully!', 'ai-pagegen'),
                'post_id' => $post_id,
                'edit_link' => admin_url('post.php?post=' . $post_id . '&action=edit'),
                'view_link' => get_permalink($post_id)
            ));
            
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Exception in page creation', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_api() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_pagegen_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed.', 'ai-pagegen'));
        }
        
        try {
            $openai = new AI_PageGen_OpenAI();
            $result = $openai->test_connection();
            
            if ($result) {
                AI_PageGen_Logger::info('API connection test successful');
                wp_send_json_success(__('API connection successful!', 'ai-pagegen'));
            } else {
                AI_PageGen_Logger::error('API connection test failed');
                wp_send_json_error(__('API connection failed. Please check your API key.', 'ai-pagegen'));
            }
        } catch (Exception $e) {
            AI_PageGen_Logger::error('API test exception', array('error' => $e->getMessage()));
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Render main page
     */
    public function render_main_page() {
        $settings = get_option('ai_pagegen_settings', array());
        ?>
        <div class="ai-pagegen-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (empty($settings['openai_api_key'])): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Please configure your OpenAI API key in settings before generating content.', 'ai-pagegen'); ?> 
                    <a href="<?php echo admin_url('admin.php?page=ai-pagegen-settings'); ?>"><?php _e('Go to Settings', 'ai-pagegen'); ?></a></p>
                </div>
            <?php endif; ?>
            
            <form id="ai-pagegen-form" method="post">
                <?php wp_nonce_field('ai_pagegen_nonce', 'ai_pagegen_nonce'); ?>
                
                <div class="form-group">
                    <label for="ai_prompt"><?php _e('Content Prompt', 'ai-pagegen'); ?></label>
                    <textarea id="ai_prompt" name="ai_prompt" rows="6" required 
                        placeholder="<?php _e('Describe the content you want to generate...', 'ai-pagegen'); ?>"></textarea>
                    <p class="description"><?php _e('Be specific about what you want. Example: "Create a landing page for a fitness app with sections for features, testimonials, and pricing"', 'ai-pagegen'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="post_type"><?php _e('Content Type', 'ai-pagegen'); ?></label>
                    <select id="post_type" name="post_type">
                        <option value="post"><?php _e('Blog Post', 'ai-pagegen'); ?></option>
                        <option value="page"><?php _e('Page', 'ai-pagegen'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="page_sections"><?php _e('Page Sections', 'ai-pagegen'); ?></label>
                    <input type="text" id="page_sections" name="page_sections" 
                        placeholder="<?php _e('e.g., Header, Features, Testimonials, Footer', 'ai-pagegen'); ?>">
                    <p class="description"><?php _e('Specify the sections you want in your content (optional)', 'ai-pagegen'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="color_scheme"><?php _e('Color Scheme', 'ai-pagegen'); ?></label>
                    <input type="text" id="color_scheme" name="color_scheme" 
                        placeholder="<?php _e('e.g., #2271b1,#ffffff,#000000', 'ai-pagegen'); ?>">
                    <p class="description"><?php _e('Specify colors for design suggestions (optional)', 'ai-pagegen'); ?></p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="seo_optimization" name="seo_optimization" <?php echo !AI_PageGen_Licensing::is_pro_user() ? 'disabled' : ''; ?>>
                        <?php _e('SEO Optimization', 'ai-pagegen'); ?>
                        <?php if (!AI_PageGen_Licensing::is_pro_user()): ?>
                            <span class="pro-badge"><?php _e('PRO', 'ai-pagegen'); ?></span>
                        <?php endif; ?>
                    </label>
                </div>
                
                <div id="seo_fields" style="display: none;">
                    <div class="form-group">
                        <label for="seo_keywords"><?php _e('SEO Keywords', 'ai-pagegen'); ?></label>
                        <input type="text" id="seo_keywords" name="seo_keywords" 
                            placeholder="<?php _e('keyword1, keyword2, keyword3', 'ai-pagegen'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="elementor_compatible" name="elementor_compatible" <?php echo !AI_PageGen_Licensing::is_pro_user() ? 'disabled' : ''; ?>>
                        <?php _e('Elementor Compatible', 'ai-pagegen'); ?>
                        <?php if (!AI_PageGen_Licensing::is_pro_user()): ?>
                            <span class="pro-badge"><?php _e('PRO', 'ai-pagegen'); ?></span>
                        <?php endif; ?>
                    </label>
                    <p class="description"><?php _e('Generate content that works well with Elementor page builder', 'ai-pagegen'); ?></p>
                </div>
                
                <div class="form-group">
                    <button type="submit" id="generate-btn" class="button button-primary">
                        <?php _e('Generate Content', 'ai-pagegen'); ?>
                    </button>
                </div>
            </form>
            
            <div id="content-preview"></div>
            
            <div id="page-actions" style="display: none;">
                <button id="create-page-btn" class="button button-secondary">
                    <?php _e('Create WordPress Page', 'ai-pagegen'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            $settings = array(
                'openai_api_key' => sanitize_text_field($_POST['openai_api_key']),
                'default_post_type' => sanitize_text_field($_POST['default_post_type']),
                'seo_optimization' => isset($_POST['seo_optimization']),
                'default_color_scheme' => sanitize_text_field($_POST['default_color_scheme'])
            );
            
            update_option('ai_pagegen_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'ai-pagegen') . '</p></div>';
            
            AI_PageGen_Logger::info('Settings updated');
        }
        
        $settings = get_option('ai_pagegen_settings', array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_pagegen_settings', 'ai_pagegen_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'ai-pagegen'); ?></th>
                        <td>
                            <input type="password" name="openai_api_key" 
                                value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" 
                                class="regular-text" required>
                            <p class="description">
                                <?php _e('Get your API key from', 'ai-pagegen'); ?> 
                                <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                            </p>
                            <button type="button" id="test-api-btn" class="button"><?php _e('Test Connection', 'ai-pagegen'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Post Type', 'ai-pagegen'); ?></th>
                        <td>
                            <select name="default_post_type">
                                <option value="post" <?php selected($settings['default_post_type'] ?? 'post', 'post'); ?>><?php _e('Post', 'ai-pagegen'); ?></option>
                                <option value="page" <?php selected($settings['default_post_type'] ?? 'post', 'page'); ?>><?php _e('Page', 'ai-pagegen'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Color Scheme', 'ai-pagegen'); ?></th>
                        <td>
                            <input type="text" name="default_color_scheme" 
                                value="<?php echo esc_attr($settings['default_color_scheme'] ?? '#2271b1,#ffffff,#000000'); ?>" 
                                class="regular-text">
                            <p class="description"><?php _e('Comma-separated hex colors (e.g., #2271b1,#ffffff,#000000)', 'ai-pagegen'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api-btn').on('click', function() {
                var $btn = $(this);
                var originalText = $btn.text();
                
                $btn.prop('disabled', true).text('<?php _e('Testing...', 'ai-pagegen'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ai_pagegen_test_api',
                        nonce: '<?php echo wp_create_nonce('ai_pagegen_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('API connection successful!', 'ai-pagegen'); ?>');
                        } else {
                            alert('<?php _e('API connection failed: ', 'ai-pagegen'); ?>' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Connection test failed. Please try again.', 'ai-pagegen'); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        // Handle clear logs action
        if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_logs')) {
            AI_PageGen_Logger::clear_logs();
            echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully!', 'ai-pagegen') . '</p></div>';
        }
        
        $logs = AI_PageGen_Logger::get_recent_logs(200);
        $log_size = AI_PageGen_Logger::get_log_size();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="log-info">
                <p><strong><?php _e('Log file size:', 'ai-pagegen'); ?></strong> <?php echo size_format($log_size); ?></p>
                <p><strong><?php _e('Total entries:', 'ai-pagegen'); ?></strong> <?php echo count($logs); ?></p>
            </div>
            
            <div class="log-actions">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('clear_logs'); ?>
                    <input type="submit" name="clear_logs" value="<?php _e('Clear Logs', 'ai-pagegen'); ?>" 
                        class="button button-secondary" 
                        onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'ai-pagegen'); ?>');">
                </form>
                <button onclick="location.reload()" class="button"><?php _e('Refresh', 'ai-pagegen'); ?></button>
            </div>
            
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <p><?php _e('No logs found.', 'ai-pagegen'); ?></p>
                <?php else: ?>
                    <pre class="log-content"><?php
                        foreach (array_reverse($logs) as $log) {
                            echo esc_html($log) . "\n";
                        }
                    ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .log-container {
            margin-top: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .log-content {
            padding: 15px;
            max-height: 600px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            margin: 0;
        }
        .log-info {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .log-actions {
            margin-bottom: 10px;
        }
        .log-actions .button {
            margin-right: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Render license page
     */
    public function render_license_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="license-info">
                <h2><?php _e('License Status', 'ai-pagegen'); ?></h2>
                
                <?php if (AI_PageGen_Licensing::is_pro_user()): ?>
                    <div class="notice notice-success">
                        <p><strong><?php _e('Pro License Active', 'ai-pagegen'); ?></strong></p>
                        <p><?php _e('You have access to all pro features including SEO optimization and Elementor compatibility.', 'ai-pagegen'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-info">
                        <p><strong><?php _e('Free Version', 'ai-pagegen'); ?></strong></p>
                        <p><?php _e('Upgrade to Pro to unlock advanced features:', 'ai-pagegen'); ?></p>
                        <ul>
                            <li>✓ <?php _e('SEO Optimization', 'ai-pagegen'); ?></li>
                            <li>✓ <?php _e('Elementor Compatibility', 'ai-pagegen'); ?></li>
                            <li>✓ <?php _e('Advanced Styling Options', 'ai-pagegen'); ?></li>
                            <li>✓ <?php _e('Priority Support', 'ai-pagegen'); ?></li>
                        </ul>
                        <p><a href="#" class="button button-primary"><?php _e('Upgrade to Pro', 'ai-pagegen'); ?></a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
