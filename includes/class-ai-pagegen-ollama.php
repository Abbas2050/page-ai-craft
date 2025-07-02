<?php
/**
 * Ollama API communication for AI PageGen
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen Ollama Class
 */
class AI_PageGen_Ollama {
    
    /**
     * Ollama API URL
     */
    private $api_url;
    
    /**
     * Model name
     */
    private $model;
    
    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('ai_pagegen_settings', array());
        $this->api_url = isset($settings['ollama_url']) ? rtrim($settings['ollama_url'], '/') : 'http://localhost:11434';
        $this->model = isset($settings['ollama_model']) ? $settings['ollama_model'] : 'llama2';
        
        AI_PageGen_Logger::debug('Ollama class initialized', array(
            'api_url' => $this->api_url,
            'model' => $this->model
        ));
    }
    
    /**
     * Generate content using Ollama
     *
     * @param string $prompt User prompt
     * @param array $options Generation options
     * @return array|false Generated content or false on failure
     */
    public function generate_content($prompt, $options = array()) {
        AI_PageGen_Logger::info('Starting Ollama content generation', array(
            'prompt_length' => strlen($prompt),
            'options' => $options,
            'model' => $this->model
        ));
        
        if (empty($this->api_url)) {
            AI_PageGen_Logger::error('Ollama URL not configured');
            throw new Exception(__('Ollama URL is not configured. Please set it in plugin settings.', 'ai-pagegen'));
        }
        
        // Build the system prompt based on options
        $system_prompt = $this->build_system_prompt($options);
        
        // Prepare the request
        $request_data = array(
            'model' => $this->model,
            'prompt' => $system_prompt . "\n\nUser Request: " . $prompt,
            'stream' => false,
            'options' => array(
                'temperature' => 0.7,
                'top_p' => 0.9,
                'stop' => array("\n\nUser:", "\n\nHuman:")
            )
        );
        
        AI_PageGen_Logger::debug('Ollama request prepared', array(
            'model' => $request_data['model'],
            'prompt_length' => strlen($request_data['prompt'])
        ));
        
        // Make the API request
        try {
            $response = $this->make_api_request($request_data);
            
            if (!$response) {
                AI_PageGen_Logger::error('Ollama API request failed - no response');
                throw new Exception(__('Failed to get response from Ollama API', 'ai-pagegen'));
            }
            
            // Process the response
            $result = $this->process_response($response, $options);
            
            AI_PageGen_Logger::info('Ollama content generation completed successfully');
            return $result;
            
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Ollama content generation failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            throw $e;
        }
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
        
        AI_PageGen_Logger::debug('Ollama system prompt built', array('prompt_length' => strlen($prompt)));
        
        return $prompt;
    }
    
    /**
     * Make API request to Ollama
     *
     * @param array $data Request data
     * @return array|false API response or false on failure
     */
    private function make_api_request($data) {
        $url = $this->api_url . '/api/generate';
        
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        $args = array(
            'headers' => $headers,
            'body' => json_encode($data),
            'method' => 'POST',
            'timeout' => 120, // Ollama can be slower than OpenAI
        );
        
        AI_PageGen_Logger::debug('Making Ollama API request', array(
            'url' => $url,
            'timeout' => $args['timeout']
        ));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            AI_PageGen_Logger::error('Ollama API request error (WP_Error)', array(
                'error_code' => $response->get_error_code(),
                'error_message' => $response->get_error_message()
            ));
            throw new Exception('Ollama API Request Error: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        AI_PageGen_Logger::debug('Ollama API response received', array(
            'status_code' => $response_code,
            'response_length' => strlen($response_body)
        ));
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = 'Ollama API Error (Code: ' . $response_code . ')';
            
            if (isset($error_data['error'])) {
                $error_message .= ': ' . $error_data['error'];
            }
            
            AI_PageGen_Logger::error('Ollama API error response', array(
                'status_code' => $response_code,
                'error_data' => $error_data,
                'response_body' => $response_body
            ));
            
            throw new Exception($error_message);
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (!$decoded_response) {
            AI_PageGen_Logger::error('Failed to decode Ollama response', array(
                'response_body' => $response_body
            ));
            throw new Exception(__('Failed to decode Ollama API response', 'ai-pagegen'));
        }
        
        return $decoded_response;
    }
    
    /**
     * Process Ollama response
     *
     * @param array $response API response
     * @param array $options Generation options
     * @return array Processed content
     */
    private function process_response($response, $options) {
        AI_PageGen_Logger::debug('Processing Ollama response', array(
            'response_structure' => array_keys($response)
        ));
        
        if (!isset($response['response'])) {
            AI_PageGen_Logger::error('Invalid Ollama response structure', array(
                'response' => $response
            ));
            throw new Exception(__('Invalid Ollama API response structure', 'ai-pagegen'));
        }
        
        $content = $response['response'];
        
        AI_PageGen_Logger::debug('Raw content received from Ollama', array(
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 100) . '...'
        ));
        
        // Try to decode as JSON first
        $decoded_content = json_decode($content, true);
        
        if ($decoded_content) {
            AI_PageGen_Logger::info('Ollama content generated successfully as JSON', array(
                'has_title' => isset($decoded_content['title']),
                'has_content' => isset($decoded_content['content']),
                'has_excerpt' => isset($decoded_content['excerpt'])
            ));
            return $decoded_content;
        }
        
        // Fallback: treat as plain content
        AI_PageGen_Logger::warning('Ollama returned non-JSON content, creating fallback structure');
        
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
        
        AI_PageGen_Logger::info('Ollama fallback content structure created', array(
            'title' => $title,
            'content_length' => strlen($formatted_content)
        ));
        
        return $result;
    }
    
    /**
     * Extract title from content
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
        
        return __('AI Generated Content (Ollama)', 'ai-pagegen');
    }
    
    /**
     * Format content for WordPress
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
     */
    private function generate_excerpt($content) {
        $plain_text = wp_strip_all_tags($content);
        $excerpt = wp_trim_words($plain_text, 30);
        return $excerpt;
    }
    
    /**
     * Generate meta description
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
     * Test Ollama connection
     *
     * @return bool True if connection successful
     */
    public function test_connection() {
        if (empty($this->api_url)) {
            AI_PageGen_Logger::error('Ollama test failed - no URL configured');
            return false;
        }
        
        try {
            $test_data = array(
                'model' => $this->model,
                'prompt' => 'Hello, this is a test. Please respond with "Test successful".',
                'stream' => false,
                'options' => array(
                    'temperature' => 0.1
                )
            );
            
            AI_PageGen_Logger::info('Testing Ollama connection');
            
            $response = $this->make_api_request($test_data);
            
            if ($response && isset($response['response'])) {
                AI_PageGen_Logger::info('Ollama connection test successful');
                return true;
            } else {
                AI_PageGen_Logger::error('Ollama connection test failed - invalid response');
                return false;
            }
            
        } catch (Exception $e) {
            AI_PageGen_Logger::error('Ollama connection test failed', array(
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
}