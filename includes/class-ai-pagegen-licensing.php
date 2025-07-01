
<?php
/**
 * Licensing system for AI PageGen
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen Licensing Class
 * 
 * NOTE: This is a placeholder implementation. 
 * For production, integrate with Freemius SDK or similar licensing system.
 * 
 * Freemius Integration Steps:
 * 1. Download Freemius SDK from https://github.com/Freemius/wordpress-sdk
 * 2. Replace this class with proper Freemius initialization
 * 3. Configure pricing plans and feature restrictions
 * 4. Implement proper license validation
 */
class AI_PageGen_Licensing {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * License status
     */
    private $license_status;
    
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
        $this->init_hooks();
        $this->load_license_status();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_license_menu'), 100);
        add_action('admin_init', array($this, 'handle_license_actions'));
    }
    
    /**
     * Load license status
     */
    private function load_license_status() {
        // TODO: Replace with actual license validation
        $this->license_status = get_option('ai_pagegen_license_status', 'free');
    }
    
    /**
     * Check if user has pro license
     *
     * @return bool True if pro user, false otherwise
     */
    public static function is_pro_user() {
        $instance = self::get_instance();
        
        // TODO: Replace with actual license validation logic
        // For now, check if a pro license key is saved
        $license_key = get_option('ai_pagegen_license_key', '');
        $license_status = get_option('ai_pagegen_license_status', 'free');
        
        // Temporary validation - replace with actual API validation
        return !empty($license_key) && $license_status === 'valid';
    }
    
    /**
     * Get license status
     *
     * @return string License status (free, valid, expired, invalid)
     */
    public function get_license_status() {
        return $this->license_status;
    }
    
    /**
     * Add license menu
     */
    public function add_license_menu() {
        add_submenu_page(
            'ai-pagegen',
            __('License', 'ai-pagegen'),
            __('License', 'ai-pagegen'),
            'manage_ai_pagegen',
            'ai-pagegen-license',
            array($this, 'license_page')
        );
    }
    
    /**
     * License page
     */
    public function license_page() {
        $license_key = get_option('ai_pagegen_license_key', '');
        $license_status = get_option('ai_pagegen_license_status', 'free');
        ?>
        <div class="wrap">
            <h1><?php _e('AI PageGen License', 'ai-pagegen'); ?></h1>
            
            <?php if ($license_status === 'free') : ?>
                <div class="notice notice-info">
                    <p><?php _e('You are using the free version of AI PageGen. Upgrade to Pro to unlock all features!', 'ai-pagegen'); ?></p>
                </div>
                
                <div class="ai-pagegen-upgrade-box">
                    <h2><?php _e('Upgrade to Pro', 'ai-pagegen'); ?></h2>
                    <p><?php _e('Unlock powerful features with AI PageGen Pro:', 'ai-pagegen'); ?></p>
                    <ul>
                        <li>✓ <?php _e('Post/Page type selection', 'ai-pagegen'); ?></li>
                        <li>✓ <?php _e('Custom header/footer generation', 'ai-pagegen'); ?></li>
                        <li>✓ <?php _e('Advanced SEO optimization', 'ai-pagegen'); ?></li>
                        <li>✓ <?php _e('Custom color schemes', 'ai-pagegen'); ?></li>
                        <li>✓ <?php _e('Page section structuring', 'ai-pagegen'); ?></li>
                        <li>✓ <?php _e('Priority support', 'ai-pagegen'); ?></li>
                    </ul>
                    <a href="#" class="button button-primary button-large"><?php _e('Upgrade to Pro', 'ai-pagegen'); ?></a>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_pagegen_license', 'ai_pagegen_license_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key"><?php _e('License Key', 'ai-pagegen'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="license_key" name="license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your license key to activate Pro features.', 'ai-pagegen'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('License Status', 'ai-pagegen'); ?></th>
                        <td>
                            <span class="license-status license-<?php echo esc_attr($license_status); ?>">
                                <?php
                                switch ($license_status) {
                                    case 'valid':
                                        _e('✓ Active', 'ai-pagegen');
                                        break;
                                    case 'expired':
                                        _e('✗ Expired', 'ai-pagegen');
                                        break;
                                    case 'invalid':
                                        _e('✗ Invalid', 'ai-pagegen');
                                        break;
                                    default:
                                        _e('Free Version', 'ai-pagegen');
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <?php
                if ($license_status === 'valid') {
                    submit_button(__('Deactivate License', 'ai-pagegen'), 'secondary', 'deactivate_license');
                } else {
                    submit_button(__('Activate License', 'ai-pagegen'), 'primary', 'activate_license');
                }
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle license actions
     */
    public function handle_license_actions() {
        if (!isset($_POST['ai_pagegen_license_nonce']) || !wp_verify_nonce($_POST['ai_pagegen_license_nonce'], 'ai_pagegen_license')) {
            return;
        }
        
        if (!current_user_can('manage_ai_pagegen')) {
            return;
        }
        
        if (isset($_POST['activate_license'])) {
            $license_key = sanitize_text_field($_POST['license_key']);
            $this->activate_license($license_key);
        } elseif (isset($_POST['deactivate_license'])) {
            $this->deactivate_license();
        }
    }
    
    /**
     * Activate license
     *
     * @param string $license_key License key
     */
    private function activate_license($license_key) {
        if (empty($license_key)) {
            add_settings_error('ai_pagegen_license', 'empty_key', __('Please enter a license key.', 'ai-pagegen'));
            return;
        }
        
        // TODO: Replace with actual license validation API call
        // This is a placeholder implementation
        $validation_result = $this->validate_license_key($license_key);
        
        if ($validation_result['valid']) {
            update_option('ai_pagegen_license_key', $license_key);
            update_option('ai_pagegen_license_status', 'valid');
            add_settings_error('ai_pagegen_license', 'license_activated', __('License activated successfully!', 'ai-pagegen'), 'success');
        } else {
            update_option('ai_pagegen_license_status', 'invalid');
            add_settings_error('ai_pagegen_license', 'invalid_license', $validation_result['message']);
        }
        
        $this->license_status = get_option('ai_pagegen_license_status');
    }
    
    /**
     * Deactivate license
     */
    private function deactivate_license() {
        // TODO: Call license deactivation API
        
        update_option('ai_pagegen_license_key', '');
        update_option('ai_pagegen_license_status', 'free');
        $this->license_status = 'free';
        
        add_settings_error('ai_pagegen_license', 'license_deactivated', __('License deactivated.', 'ai-pagegen'), 'success');
    }
    
    /**
     * Validate license key
     *
     * @param string $license_key License key
     * @return array Validation result
     */
    private function validate_license_key($license_key) {
        // TODO: Replace with actual API validation
        // This is a placeholder that accepts any key starting with "PRO-"
        
        if (strpos($license_key, 'PRO-') === 0 && strlen($license_key) >= 10) {
            return array(
                'valid' => true,
                'message' => __('License is valid', 'ai-pagegen')
            );
        } else {
            return array(
                'valid' => false,
                'message' => __('Invalid license key. Please check your key and try again.', 'ai-pagegen')
            );
        }
    }
    
    /**
     * Get features available for current license
     *
     * @return array Available features
     */
    public function get_available_features() {
        $features = array(
            'basic_generation' => true
        );
        
        if (self::is_pro_user()) {
            $features = array_merge($features, array(
                'post_type_selection' => true,
                'header_footer_options' => true,
                'seo_optimization' => true,
                'color_schemes' => true,
                'page_sections' => true,
                'priority_support' => true
            ));
        }
        
        return $features;
    }
}

/*
 * FREEMIUS INTEGRATION NOTES:
 * 
 * To properly integrate Freemius SDK, replace this entire class with:
 * 
 * 1. Include Freemius SDK:
 *    require_once dirname(__FILE__) . '/freemius/start.php';
 * 
 * 2. Initialize Freemius:
 *    $ai_pagegen_fs = fs_dynamic_init( array(
 *        'id'                  => 'YOUR_PLUGIN_ID',
 *        'slug'                => 'ai-pagegen',
 *        'type'                => 'plugin',
 *        'public_key'          => 'YOUR_PUBLIC_KEY',
 *        'is_premium'          => false,
 *        'has_premium_version' => true,
 *        'has_paid_plans'      => true,
 *        'menu'                => array(
 *            'slug'        => 'ai-pagegen',
 *            'parent'      => array(
 *                'slug' => 'ai-pagegen',
 *            ),
 *        ),
 *    ) );
 * 
 * 3. Replace is_pro_user() method:
 *    public static function is_pro_user() {
 *        global $ai_pagegen_fs;
 *        return $ai_pagegen_fs->can_use_premium_code();
 *    }
 * 
 * 4. Set up premium features and pricing plans in Freemius dashboard
 */
