
<?php
/**
 * Plugin Name: AI PageGen
 * Plugin URI: https://your-domain.com/ai-pagegen
 * Description: Generate WordPress posts and pages using AI with customizable options. Free and Pro versions available.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-domain.com
 * Text Domain: ai-pagegen
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

/**
 * Main AI PageGen Plugin Class
 */
class AI_PageGen_Plugin {
    
    /**
     * Single instance of the plugin
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once AI_PAGEGEN_PLUGIN_DIR . 'includes/class-ai-pagegen-admin.php';
        require_once AI_PAGEGEN_PLUGIN_DIR . 'includes/class-ai-pagegen-openai.php';
        require_once AI_PAGEGEN_PLUGIN_DIR . 'includes/class-ai-pagegen-post-creator.php';
        require_once AI_PAGEGEN_PLUGIN_DIR . 'includes/class-ai-pagegen-licensing.php';
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('ai-pagegen', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Initialize plugin
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
     */
    public function activate() {
        // Create default options
        $default_options = array(
            'openai_api_key' => '',
            'default_post_type' => 'post',
            'default_header_footer' => 'theme',
            'seo_optimization' => false,
            'default_color_scheme' => '#2271b1,#ffffff,#000000'
        );
        
        add_option('ai_pagegen_settings', $default_options);
        
        // Create custom capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_ai_pagegen');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
}

// Initialize the plugin
AI_PageGen_Plugin::get_instance();
