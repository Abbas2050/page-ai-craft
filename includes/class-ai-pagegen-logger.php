
<?php
/**
 * Logger class for AI PageGen plugin
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen Logger Class
 */
class AI_PageGen_Logger {
    
    /**
     * Log file path
     */
    private static $log_file = null;
    
    /**
     * Initialize logger
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/ai-pagegen-logs.txt';
        
        // Create log file if it doesn't exist
        if (!file_exists(self::$log_file)) {
            self::write_log('INFO', 'AI PageGen logging initialized');
        }
    }
    
    /**
     * Write log entry
     *
     * @param string $level Log level (INFO, ERROR, DEBUG, WARNING)
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function write_log($level, $message, $context = array()) {
        if (!self::$log_file) {
            self::init();
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? " [User: {$user_id}]" : " [User: Guest]";
        
        $log_entry = "[{$timestamp}]{$user_info} [{$level}] {$message}";
        
        if (!empty($context)) {
            $log_entry .= " Context: " . json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        // Write to file
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AI PageGen [{$level}]: {$message}");
        }
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = array()) {
        self::write_log('INFO', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = array()) {
        self::write_log('ERROR', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = array()) {
        self::write_log('DEBUG', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = array()) {
        self::write_log('WARNING', $message, $context);
    }
    
    /**
     * Get recent logs
     *
     * @param int $lines Number of recent lines to get
     * @return array Array of log lines
     */
    public static function get_recent_logs($lines = 100) {
        if (!file_exists(self::$log_file)) {
            return array();
        }
        
        $file_lines = file(self::$log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (!$file_lines) {
            return array();
        }
        
        return array_slice($file_lines, -$lines);
    }
    
    /**
     * Clear log file
     */
    public static function clear_logs() {
        if (file_exists(self::$log_file)) {
            file_put_contents(self::$log_file, '');
            self::write_log('INFO', 'Log file cleared');
            return true;
        }
        return false;
    }
    
    /**
     * Get log file size
     */
    public static function get_log_size() {
        if (file_exists(self::$log_file)) {
            return filesize(self::$log_file);
        }
        return 0;
    }
}
