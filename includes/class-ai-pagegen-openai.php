
<?php
/**
 * OpenAI API communication for AI PageGen
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen OpenAI Class
 */
class AI_PageGen_OpenAI {
    
    /**
     * OpenAI API base URL
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('ai_pagegen_settings', array());
        $this->api_key = isset($settings['openai_api_key']) ? $settings['openai_api_key'] : '';
        
        AI_PageGen_Logger::debug('OpenAI class initialized', array(
            'has_api_key' => !empty($this->api_key)
        ));
    }
    
    /**
     * Generate content using OpenAI
     *
     * @param string $prompt User prompt
     * @param array $options Generation options
     * @return array|false Generated content or false on failure
     */
    public function generate_content($prompt, $options = array()) {
        if (empty($this->api_key)) {
            AI_PageGen_Logger::error('OpenAI API key not configured');
            throw new Exception(__('OpenAI API key is not configured. Please set it in plugin settings.', 'ai-pagegen'));
        }
        
        AI_PageGen_Logger::info('Starting content generation', array(
            'prompt_length' => strlen($prompt),
            'options' => $options
        ));
        
        // Build the system prompt based on options
        $system_prompt = $this->build_system_prompt($options);
        
        // Prepare the request
        $request_data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.7
        );
        
        AI_PageGen_Logger::debug('OpenAI request prepared', array(
            'model' => $request_data['model'],
            'system_prompt_length' => strlen($system_prompt)
        ));
        
        // Make the API request
        $response = $this->make_api_request($request_data);
        
        if (!$response) {
            AI_PageGen_Logger::error('OpenAI API request failed');
            return false;
        }
        
        // Process the response
        return $this->process_response($response, $options);
    }
    
    /**
     * Build system prompt based on options
     *
     * @param array $options Generation options
     * @return string System prompt
     */
    private function build_system_prompt($options) {
        $prompt = "You are a professional content creator for WordPress. ";
        
        // Post type specific instructions
        if (isset($options['post_type']) && $options['post_type'] === 'page') {
            $prompt .= "Create a complete webpage with proper HTML structure. ";
        } else {
            $prompt .= "Create a blog post with engaging content. ";
        }
        
        // SEO optimization
        if (isset($options['seo_optimization']) && $options['seo_optimization']) {
            $prompt .= "Include SEO-optimized content with proper heading structure (H1, H2, H3), ";
            $prompt .= "meta description, and incorporate the keywords: " . ($options['seo_keywords'] ?? '') . ". ";
        }
        
        // Page sections
        if (!empty($options['page_sections'])) {
            $prompt .= "Structure the content with these sections: " . $options['page_sections'] . ". ";
        }
        
        // Elementor compatibility
        if (isset($options['elementor_compatible']) && $options['elementor_compatible']) {
            $prompt .= "Make the content compatible with Elementor page builder using proper HTML structure and CSS classes. ";
        }
        
        // Color scheme
        if (!empty($options['color_scheme'])) {
            $prompt .= "Consider this color scheme: " . $options['color_scheme'] . " when suggesting design elements. ";
        }
        
        $prompt .= "Return the response as JSON with the following structure: ";
        $prompt .= '{"title": "Page/Post Title", "content": "HTML content", "excerpt": "Brief excerpt"}';
        
        if (isset($options['seo_optimization']) && $options['seo_optimization']) {
            $prompt .= ', "seo_title": "SEO optimized title", "meta_description": "Meta description under 160 characters"';
        }
        
        $prompt .= '. Ensure all HTML is valid and properly formatted for WordPress.';
        
        return $prompt;
    }
    
    /**
     * Make API request to OpenAI
     *
     * @param array $data Request data
     * @return array|false API response or false on failure
     */
    private function make_api_request($data) {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        );
        
        $args = array(
            'headers' => $headers,
            'body' => json_encode($data),
            'method' => 'POST',
            'timeout' => 60,
        );
        
        AI_PageGen_Logger::debug('Making OpenAI API request');
        
        $response = wp_remote_request($this->api_url, $args);
        
        if (is_wp_error($response)) {
            AI_PageGen_Logger::error('OpenAI API request error', array(
                'error' => $response->get_error_message()
            ));
            throw new Exception($response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        AI_PageGen_Logger::debug('OpenAI API response received', array(
            'status_code' => $response_code,
            'response_length' => strlen($response_body)
        ));
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = 'OpenAI API Error (Code: ' . $response_code . ')';
            
            if (isset($error_data['error']['message'])) {
                $error_message .= ': ' . $error_data['error']['message'];
            }
            
            AI_PageGen_Logger::error('OpenAI API error response', array(
                'status_code' => $response_code,
                'error_data' => $error_data
            ));
            
            throw new Exception($error_message);
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (!$decoded_response) {
            AI_PageGen_Logger::error('Failed to decode OpenAI response');
            throw new Exception(__('Failed to decode API response', 'ai-pagegen'));
        }
        
        return $decoded_response;
    }
    
    /**
     * Process OpenAI response
     *
     * @param array $response API response
     * @param array $options Generation options
     * @return array Processed content
     */
    private function process_response($response, $options) {
        if (!isset($response['choices'][0]['message']['content'])) {
            AI_PageGen_Logger::error('Invalid OpenAI response structure', array(
                'response' => $response
            ));
            throw new Exception(__('Invalid API response structure', 'ai-pagegen'));
        }
        
        $content = $response['choices'][0]['message']['content'];
        
        AI_PageGen_Logger::debug('Processing OpenAI response', array(
            'content_length' => strlen($content)
        ));
        
        // Try to decode as JSON first
        $decoded_content = json_decode($content, true);
        
        if ($decoded_content) {
            AI_PageGen_Logger::info('Content generated successfully as JSON');
            return $decoded_content;
        }
        
        // Fallback: treat as plain content
        AI_PageGen_Logger::warning('OpenAI returned non-JSON content, creating fallback structure');
        
        // Extract title from content if possible
        $title = $this->extract_title_from_content($content);
        
        // Clean and format content
        $formatted_content = $this->format_content($content);
        
        $result = array(
            'title' => $title,
            'content' => $formatted_content,
            'excerpt' => $this->generate_excerpt($formatted_content)
        );
        
        // Add SEO data if requested
        if (isset($options['seo_optimization']) && $options['seo_optimization']) {
            $result['seo_title'] = $title;
            $result['meta_description'] = $this->generate_meta_description($formatted_content);
        }
        
        return $result;
    }
    
    /**
     * Extract title from content
     *
     * @param string $content Content
     * @return string Extracted title
     */
    private function extract_title_from_content($content) {
        // Look for h1 tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }
        
        // Look for first line that looks like a title
        $lines = explode("\n", strip_tags($content));
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && strlen($line) < 100) {
                return $line;
            }
        }
        
        return __('AI Generated Content', 'ai-pagegen');
    }
    
    /**
     * Format content for WordPress
     *
     * @param string $content Raw content
     * @return string Formatted content
     */
    private function format_content($content) {
        // Basic HTML cleanup
        $content = trim($content);
        
        // Ensure paragraphs are properly wrapped
        if (strpos($content, '<p>') === false && strpos($content, '<h') === false) {
            $content = wpautop($content);
        }
        
        return $content;
    }
    
    /**
     * Generate excerpt from content
     *
     * @param string $content Full content
     * @return string Excerpt
     */
    private function generate_excerpt($content) {
        $plain_text = wp_strip_all_tags($content);
        $excerpt = wp_trim_words($plain_text, 30);
        return $excerpt;
    }
    
    /**
     * Generate meta description
     *
     * @param string $content Full content
     * @return string Meta description
     */
    private function generate_meta_description($content) {
        $plain_text = wp_strip_all_tags($content);
        $description = wp_trim_words($plain_text, 25);
        
        // Ensure it's under 160 characters
        if (strlen($description) > 157) {
            $description = substr($description, 0, 157) . '...';
        }
        
        return $description;
    }
    
    /**
     * Test API connection
     *
     * @return bool True if connection successful
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return false;
        }
        
        try {
            $test_data = array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello, this is a test.'
                    )
                ),
                'max_tokens' => 10
            );
            
            $response = $this->make_api_request($test_data);
            return !empty($response);
            
        } catch (Exception $e) {
            AI_PageGen_Logger::error('API connection test failed', array(
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
}
