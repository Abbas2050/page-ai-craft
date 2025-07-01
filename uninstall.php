
<?php
/**
 * Uninstall AI PageGen Plugin
 * 
 * Fired when the plugin is uninstalled.
 * This file handles the complete removal of plugin data.
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Complete plugin cleanup
 * 
 * @since 1.0.0
 */
function ai_pagegen_complete_uninstall() {
    
    // Remove plugin options
    delete_option('ai_pagegen_settings');
    delete_option('ai_pagegen_license');
    delete_option('ai_pagegen_license_status');
    delete_option('ai_pagegen_version');
    
    // Remove user meta data
    delete_metadata('user', 0, 'ai_pagegen_intro_dismissed', '', true);
    delete_metadata('user', 0, 'ai_pagegen_preferences', '', true);
    
    // Remove transients
    delete_transient('ai_pagegen_cache');
    delete_transient('ai_pagegen_license_check');
    delete_transient('ai_pagegen_api_status');
    
    // Remove custom capabilities
    $roles = array('administrator', 'editor');
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_ai_pagegen');
        }
    }
    
    // Clean up any scheduled events
    wp_clear_scheduled_hook('ai_pagegen_cleanup');
    wp_clear_scheduled_hook('ai_pagegen_license_check');
    
    // Remove custom database tables (if any were created)
    global $wpdb;
    
    // Remove any custom tables (example)
    $table_name = $wpdb->prefix . 'ai_pagegen_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Clear all cached data
    wp_cache_flush();
    
    // For multisite, clean up site options
    if (is_multisite()) {
        delete_site_option('ai_pagegen_network_settings');
        delete_site_option('ai_pagegen_network_license');
    }
}

// Execute cleanup
ai_pagegen_complete_uninstall();
