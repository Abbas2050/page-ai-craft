
<?php
/**
 * Plugin Name: AI PageGen - WordPress Content Generator
 * Plugin URI: https://codecanyon.net/item/ai-pagegen
 * Description: Professional AI-powered content generator for WordPress. Create stunning posts and pages using OpenAI GPT technology. Free and Pro versions available.
 * Version: 1.0.0
 * Author: AI PageGen Team
 * Author URI: https://ai-pagegen.com
 * Text Domain: ai-pagegen
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package AI_PageGen
 * @version 1.0.0
 * @author AI PageGen Team
 * @copyright 2024 AI PageGen
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_PAGEGEN_VERSION', '1.0.0');
define('AI_PAGEGEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_PAGEGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_PAGEGEN_PLUGIN_FILE', __FILE__);
define('AI_PAGEGEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI PageGen Plugin Class
 * 
 * @since 1.0.0
 */
final class AI_PageGen_Plugin {
    
    /**
     * Single instance of the plugin
     * 
     * @var AI_PageGen_Plugin
     * @since 1.0.0
     */
    private static $instance = null;
    
    /**
     * Get single instance
     * 
     * @return AI_PageGen_Plugin
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AI_PageGen_Plugin', 'uninstall'));
        
        // Add settings link on plugin page
        add_filter('plugin_action_links_' . AI_PAGEGEN_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Load plugin dependencies
     * 
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Check if files exist before including
        $required_files = array(
            'includes/class-ai-pagegen-admin.php',
            'includes/class-ai-pagegen-openai.php',
            'includes/class-ai-pagegen-post-creator.php',
            'includes/class-ai-pagegen-licensing.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = AI_PAGEGEN_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Load plugin text domain for translations
     * 
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-pagegen', 
            false, 
            dirname(AI_PAGEGEN_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     * 
     * @since 1.0.0
     */
    public function init() {
        // Initialize admin interface if in admin
        if (is_admin()) {
            AI_PageGen_Admin::get_instance();
        }
        
        // Initialize licensing system
        AI_PageGen_Licensing::get_instance();
    }
    
    /**
     * Plugin activation
     * 
     * @since 1.0.0
     */
    public function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(AI_PAGEGEN_PLUGIN_BASENAME);
            wp_die(
                __('AI PageGen requires PHP 7.4 or higher. Please upgrade your PHP version.', 'ai-pagegen'),
                __('Plugin Activation Error', 'ai-pagegen'),
                array('back_link' => true)
            );
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(AI_PAGEGEN_PLUGIN_BASENAME);
            wp_die(
                __('AI PageGen requires WordPress 5.0 or higher. Please upgrade your WordPress installation.', 'ai-pagegen'),
                __('Plugin Activation Error', 'ai-pagegen'),
                array('back_link' => true)
            );
        }
        
        // Create default options
        $default_options = array(
            'openai_api_key' => '',
            'default_post_type' => 'post',
            'default_header_footer' => 'theme',
            'seo_optimization' => false,
            'default_color_scheme' => '#2271b1,#ffffff,#000000',
            'plugin_version' => AI_PAGEGEN_VERSION
        );
        
        add_option('ai_pagegen_settings', $default_options);
        
        // Create custom capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_ai_pagegen');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     * 
     * @since 1.0.0
     */
    public function deactivate() {
        // Clean up temporary data
        delete_transient('ai_pagegen_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     * 
     * @since 1.0.0
     */
    public static function uninstall() {
        // Remove plugin options
        delete_option('ai_pagegen_settings');
        delete_option('ai_pagegen_license');
        
        // Remove capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_ai_pagegen');
        }
        
        // Clear any cached data
        wp_cache_flush();
    }
    
    /**
     * Add action links to plugin page
     * 
     * @param array $links Existing links
     * @return array Modified links
     * @since 1.0.0
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=ai-pagegen-settings') . '">' . __('Settings', 'ai-pagegen') . '</a>',
            '<a href="' . admin_url('admin.php?page=ai-pagegen') . '">' . __('Generate Content', 'ai-pagegen') . '</a>',
        );
        
        // Add pro upgrade link if not pro
        if (!AI_PageGen_Licensing::is_pro_user()) {
            $plugin_links[] = '<a href="#" style="color: #d54e21; font-weight: bold;">' . __('Upgrade to Pro', 'ai-pagegen') . '</a>';
        }
        
        return array_merge($plugin_links, $links);
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('AI_PageGen_Plugin', 'get_instance'));
